<?php

namespace App\Services\Import;

use App\Models\Loan;
use App\Models\Member;
use App\Models\Setting;
use App\Services\DailyDueService;
use App\Services\FuelConsumptionService;
use App\Services\LoanPaymentService;
use Illuminate\Support\Str;

/**
 * daily collection template.xlsx
 * headers: name, route, plate number, number of tickets, total amount,
 *          date, loans, benefit claim, alkansiya, diesel liters
 *          (a.k.a. "diesel consumtion"), remarks
 *
 * Each row -> one daily_dues row. The member pays a "total amount" that is a
 * multiple of the ₱50 per-ticket price; the system works out the number of
 * tickets (₱100 = 2, ₱200 = 4, …), assigns the ticket numbers itself, and
 * splits each ticket into savings / member's share / association fund from
 * Settings. "number of tickets" may be given instead of / as well as the
 * amount. If the diesel column is filled, also a fuel_consumptions row
 * (litres drive the per-litre rebate). "alkansiya" -> a voluntary Alkansiya
 * (SSS) contribution, kept separate from savings. "loans" -> a loan payment,
 * accepted only if the member already has an approved/active loan.
 * "benefit claim" is noted in remarks only.
 */
class DailyCollectionImporter extends BaseImporter
{
    /** @var array<string, bool> member_id|date keys already handled this file */
    private array $seen = [];

    /** @var array<string, Member>|null  lowercased plate + full name => Member */
    private ?array $memberIndex = null;

    public function __construct(
        SpreadsheetReader $reader,
        private DailyDueService $dailyDues,
        private FuelConsumptionService $fuel,
        private LoanPaymentService $loanPayments,
        private ?int $collectorId = null,
    ) {
        parent::__construct($reader);
    }

    public function forUser(int $userId): static
    {
        $this->collectorId = $userId;

        return $this;
    }

    protected function template(): string
    {
        return 'daily_collection';
    }

    protected function requiredHeaders(): array
    {
        return ['total amount', 'date'];
    }

    protected function handleRow(array $row, int $line): void
    {
        $plate = $this->value($row, 'plate number');
        $name = $this->value($row, 'name');
        $member = $this->resolveMember($plate, $name);

        if (! $member) {
            throw new RowSkipped("No member matches plate \"{$plate}\" / name \"{$name}\".");
        }

        $date = $this->date($row, 'date');
        if (! $date) {
            throw new RowSkipped('Missing or unparseable date.');
        }

        $key = $member->id.'|'.$date;
        if (isset($this->seen[$key])) {
            throw new RowSkipped("Member already has a due for {$date} earlier in this file.");
        }
        $this->seen[$key] = true;

        $route = $this->normaliseRoute($this->value($row, 'route')) ?? $member->route;

        [$quantity, $amountPaid] = $this->resolveTicketsAndAmount($row);

        // A loan payment is only accepted when the member already has an
        // approved/active loan (applied + approved manually via the Loans
        // screen). Check first so the whole row is skipped atomically.
        $loanPayment = $this->number($row, 'loans');
        $loan = null;
        if ($loanPayment !== null && $loanPayment > 0) {
            $loan = Loan::where('member_id', $member->id)->active()->latest('loan_date')->first();
            if (! $loan) {
                throw new RowSkipped(
                    "Loan payment ₱{$loanPayment} skipped — {$member->full_name} has no approved loan. "
                    .'Apply and approve the loan on the Loans screen first, then re-import this row.'
                );
            }
        }

        $remarkBits = array_filter([
            $this->value($row, 'remarks'),
            $loanPayment > 0 ? "loan payment: {$loanPayment}" : null,
            ($b = $this->number($row, 'benefit claim')) ? "benefit claim: {$b}" : null,
        ]);

        $due = $this->dailyDues->recordWithGeneratedTickets(
            member: $member,
            date: $date,
            route: $route,
            quantity: $quantity,
            amountPaid: $amountPaid,
            opts: [
                'remarks' => $remarkBits ? implode(' · ', $remarkBits) : null,
                'collected_by' => $this->collectorId,
                'alkansiya' => $this->number($row, 'alkansiya'),
            ],
        );

        if ($loan) {
            $this->loanPayments->pay($loan, (float) $loanPayment, [
                'payment_date' => $date,
                'payment_method' => 'cash',
                'received_by' => $this->collectorId,
            ]);
        }

        $diesel = $this->number($row, 'diesel liters')
            ?? $this->number($row, 'diesel consumtion')
            ?? $this->number($row, 'diesel consumption');
        if ($diesel && $diesel > 0) {
            $this->fuel->record($member, $date, $diesel, null, 'Imported');
        }
    }

    /**
     * Work out how many tickets the row is for and the amount paid.
     *
     * The member pays a "total amount" that must be a whole multiple of the
     * ₱50 per-ticket price — the ticket count is amount ÷ 50. A
     * "number of tickets" column may be given instead, or as well (it must
     * then agree with the amount). Minimum one ticket / ₱50.
     *
     * @return array{0:int, 1:float}  [quantity, amountPaid]
     */
    private function resolveTicketsAndAmount(array $row): array
    {
        $price = (float) Setting::get('dues_price_per_ticket', 50.0);
        $priceLabel = rtrim(rtrim(number_format($price, 2), '0'), '.');

        $amount = $this->number($row, 'total amount');
        $qty = $this->number($row, 'number of tickets') ?? $this->number($row, 'tickets');

        if ($amount !== null && $amount > 0) {
            if ($amount < $price || abs(fmod($amount, $price)) > 0.001) {
                throw new RowSkipped("Total amount ₱{$amount} must be a whole multiple of ₱{$priceLabel} (₱{$priceLabel} per ticket).");
            }

            $computed = (int) round($amount / $price);

            if ($qty !== null && (int) $qty !== $computed) {
                throw new RowSkipped("Total amount (₱{$amount} = {$computed} ticket(s)) doesn't match the number of tickets ({$qty}).");
            }

            return [$computed, $amount];
        }

        if ($qty !== null && $qty >= 1) {
            return [(int) $qty, (int) $qty * $price];
        }

        throw new RowSkipped("Provide a total amount (min ₱{$priceLabel}) or a number of tickets.");
    }

    private function resolveMember(string $plate, string $name): ?Member
    {
        $index = $this->memberIndex();

        if ($plate !== '' && isset($index['plate:'.Str::lower(trim($plate))])) {
            return $index['plate:'.Str::lower(trim($plate))];
        }

        if ($name !== '') {
            $key = 'name:'.Str::lower(preg_replace('/\s+/', ' ', trim($name)));

            return $index[$key] ?? null;
        }

        return null;
    }

    /**
     * Build a lookup once. Small table, and it keeps member resolution
     * database-agnostic (no CONCAT vs || portability issues).
     *
     * @return array<string, Member>
     */
    private function memberIndex(): array
    {
        if ($this->memberIndex !== null) {
            return $this->memberIndex;
        }

        $this->memberIndex = [];
        foreach (Member::all() as $member) {
            if ($member->plate_number) {
                $this->memberIndex['plate:'.Str::lower(trim($member->plate_number))] = $member;
            }
            $full = preg_replace('/\s+/', ' ', trim("{$member->firstname} {$member->lastname}"));
            $fullWithMiddle = preg_replace('/\s+/', ' ', trim("{$member->firstname} {$member->middlename} {$member->lastname}"));
            $this->memberIndex['name:'.Str::lower($full)] = $member;
            $this->memberIndex['name:'.Str::lower($fullWithMiddle)] = $member;
        }

        return $this->memberIndex;
    }

    private function normaliseRoute(string $value): ?string
    {
        return match (Str::lower(trim($value))) {
            'carmen' => 'Carmen',
            'cogon' => 'Cogon',
            default => null,
        };
    }
}

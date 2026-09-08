<?php

namespace App\Services;

use App\Exceptions\DailyDueException;
use App\Models\AlkansiyaContribution;
use App\Models\DailyDue;
use App\Models\Member;
use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Single write path for recording a daily due (+ its tickets + the
 * savings-share ledger deposit), shared by DailyDuesController and the
 * Excel importer.
 *
 * The savings/rebate/association split is filled by the DailyDue model's
 * `creating` hook from Settings; this service only sets amount_paid,
 * quantity and the ticket links, then records the savings deposit through
 * SavingsLedgerService (the one balance writer).
 */
class DailyDueService
{
    public function __construct(
        private SavingsLedgerService $ledger,
        private AlkansiyaService $alkansiya,
    ) {}

    private function pricePerTicket(): float
    {
        return (float) Setting::get('dues_price_per_ticket', 50.00);
    }

    /**
     * HTTP path: auto-assign the N lowest-numbered available tickets for
     * the route (falling back to route-agnostic tickets).
     *
     * @param  array{member_id:int, collection_date:string, route:string, ticket_quantity:int, remarks?:?string, collected_by:int, alkansiya?:?float}  $data
     * @return array{due: DailyDue, ticket_numbers: string[]}
     */
    public function recordWithAutoTickets(array $data): array
    {
        $this->assertNoExistingDue($data['member_id'], $data['collection_date']);
        $quantity = (int) $data['ticket_quantity'];

        return DB::transaction(function () use ($data, $quantity) {
            $tickets = Ticket::where('status', 'available')
                ->where(fn ($q) => $q->where('route', $data['route'])->orWhereNull('route'))
                ->orderByRaw('route = ? DESC', [$data['route']])
                ->orderBy('ticket_number')
                ->lockForUpdate()
                ->limit($quantity)
                ->get();

            if ($tickets->count() < $quantity) {
                throw DailyDueException::noTicketsAvailable();
            }

            $due = $this->persist(
                $data['member_id'],
                $data['collection_date'],
                $data['route'],
                $quantity,
                $this->pricePerTicket() * $quantity,
                $data['remarks'] ?? null,
                $data['collected_by'],
                $tickets,
                $data['alkansiya'] ?? null,
            );

            return ['due' => $due, 'ticket_numbers' => $tickets->pluck('ticket_number')->all()];
        });
    }

    /**
     * Import path: claim specific ticket numbers from the sheet. Missing
     * Ticket rows are created; a number that already exists and is used
     * is rejected.
     *
     * @param  string[]  $ticketNumbers
     * @param  array{remarks?:?string, collected_by:int, alkansiya?:?float}  $opts
     */
    public function recordWithTicketNumbers(
        Member $member,
        string $date,
        string $route,
        array $ticketNumbers,
        ?float $amountPaid,
        array $opts,
    ): DailyDue {
        $this->assertNoExistingDue($member->id, $date);

        $ticketNumbers = array_values(array_filter(array_map('trim', $ticketNumbers), fn ($n) => $n !== ''));
        $quantity = max(1, count($ticketNumbers));

        return DB::transaction(function () use ($member, $date, $route, $ticketNumbers, $amountPaid, $opts, $quantity) {
            $tickets = collect($ticketNumbers)->map(function (string $number) use ($route) {
                $ticket = Ticket::where('ticket_number', $number)->lockForUpdate()->first();

                if ($ticket && $ticket->status === 'used') {
                    throw DailyDueException::ticketAlreadyUsed($number);
                }

                return $ticket ?? new Ticket([
                    'ticket_number' => $number,
                    'route' => in_array($route, ['Carmen', 'Cogon'], true) ? $route : null,
                    'status' => 'available',
                ]);
            });

            foreach ($tickets as $ticket) {
                if (! $ticket->exists) {
                    $ticket->save();
                }
            }

            return $this->persist(
                $member->id,
                $date,
                $route,
                $quantity,
                $amountPaid ?? ($this->pricePerTicket() * $quantity),
                $opts['remarks'] ?? null,
                $opts['collected_by'],
                $tickets,
                $opts['alkansiya'] ?? null,
            );
        });
    }

    /** Ticket numbers the system assigns are zero-padded to at least this width. */
    private const TICKET_NUMBER_WIDTH = 4;

    /**
     * Import path with system-assigned ticket numbers: the sheet gives an
     * amount / ticket count but no explicit numbers, so claim $quantity
     * fresh numbers running sequentially. On a fresh system numbering
     * starts at the `ticket_start_number` setting (default 1001); once
     * tickets exist it continues from one past the highest number on file.
     * Numbers are padded to at least 4 digits and widen past 9999.
     *
     * @param  array{remarks?:?string, collected_by:int, alkansiya?:?float}  $opts
     */
    public function recordWithGeneratedTickets(
        Member $member,
        string $date,
        string $route,
        int $quantity,
        ?float $amountPaid,
        array $opts,
    ): DailyDue {
        $this->assertNoExistingDue($member->id, $date);
        $quantity = max(1, $quantity);

        return DB::transaction(function () use ($member, $date, $route, $quantity, $amountPaid, $opts) {
            $base = (int) Setting::get('ticket_start_number', 1001);
            $start = max($base, (int) DB::table('tickets')->max(DB::raw('ticket_number + 0')) + 1);

            $tickets = collect(range($start, $start + $quantity - 1))->map(function (int $number) use ($route) {
                $ticket = new Ticket([
                    'ticket_number' => str_pad((string) $number, self::TICKET_NUMBER_WIDTH, '0', STR_PAD_LEFT),
                    'route' => in_array($route, ['Carmen', 'Cogon'], true) ? $route : null,
                    'status' => 'available',
                ]);
                $ticket->save();

                return $ticket;
            });

            return $this->persist(
                $member->id,
                $date,
                $route,
                $quantity,
                $amountPaid ?? ($this->pricePerTicket() * $quantity),
                $opts['remarks'] ?? null,
                $opts['collected_by'],
                $tickets,
                $opts['alkansiya'] ?? null,
            );
        });
    }

    /**
     * Reverse a due: release its tickets, back out the savings deposit,
     * and undo any Alkansiya contribution recorded with it.
     */
    public function reverse(DailyDue $due): void
    {
        DB::transaction(function () use ($due) {
            AlkansiyaContribution::where('daily_due_id', $due->id)
                ->get()
                ->each(fn ($contribution) => $this->alkansiya->reverse($contribution));

            Ticket::where('daily_due_id', $due->id)->update([
                'status' => 'available',
                'daily_due_id' => null,
            ]);

            $this->ledger->record(
                member: $due->member,
                date: today()->toDateString(),
                sourceType: 'adjustment',
                txnType: 'withdrawal',
                amount: $due->savings_share,
                remarks: "Reversal - deleted daily due #{$due->id} ({$due->route}, {$due->collection_date->toDateString()})",
            );

            $due->delete();
        });
    }

    private function assertNoExistingDue(int $memberId, string $date): void
    {
        $exists = DailyDue::where('member_id', $memberId)
            ->whereDate('collection_date', $date)
            ->exists();

        if ($exists) {
            throw DailyDueException::duplicate();
        }
    }

    /** @param  Collection<int, Ticket>  $tickets */
    private function persist(
        int $memberId,
        string $date,
        string $route,
        int $quantity,
        float $amountPaid,
        ?string $remarks,
        int $collectedBy,
        Collection $tickets,
        ?float $alkansiya = null,
    ): DailyDue {
        $due = DailyDue::create([
            'member_id' => $memberId,
            'collection_date' => $date,
            'route' => $route,
            'remarks' => $remarks,
            'amount_paid' => $amountPaid,
            'ticket_quantity' => $quantity,
            'collected_by' => $collectedBy,
        ]);

        foreach ($tickets as $ticket) {
            $ticket->update([
                'status' => 'used',
                'daily_due_id' => $due->id,
                'used_on' => $date,
            ]);
        }

        if ($tickets->isNotEmpty()) {
            $due->update(['ticket_number' => $tickets->first()->ticket_number]);
        }

        $ticketLabel = $tickets->isNotEmpty() ? " (Ticket {$tickets->first()->ticket_number})" : '';

        $this->ledger->record(
            member: $due->member,
            date: $due->collection_date,
            sourceType: 'daily_dues',
            txnType: 'deposit',
            amount: $due->savings_share,
            sourceable: $due,
            remarks: "Daily due - {$due->route}{$ticketLabel}",
        );

        if ($alkansiya !== null && $alkansiya > 0) {
            $this->alkansiya->record(
                $due->member,
                $date,
                $alkansiya,
                [
                    'daily_due_id' => $due->id,
                    'collected_by' => $collectedBy,
                    'remarks' => 'Alkansiya (SSS) - voluntary',
                ],
            );
        }

        return $due->refresh();
    }
}

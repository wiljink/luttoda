<?php

namespace App\Services\Import;

use App\Models\IncomeExpense;

/**
 * rental template.xlsx
 * headers: business name, date, route, rental amount, rental type, remarks
 *   (older drafts also had separate "parking fee" / "dispatcher rental"
 *    money columns — still supported if present.)
 *
 * Each row -> one income_expenses row (type = income) for the rental
 * amount, plus one more for each optional extra money column that is set.
 * Category = slug of the rental type, falling back to the business name.
 */
class RentalImporter extends BaseImporter
{
    /** Optional extra money columns (header => description label). */
    private const EXTRA_COLUMNS = [
        'parking fee' => 'Parking Fee',
        'dispatcher rental' => 'Dispatcher Rental',
    ];

    public function __construct(SpreadsheetReader $reader, private ?int $userId = null)
    {
        parent::__construct($reader);
    }

    public function forUser(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    protected function template(): string
    {
        return 'rental';
    }

    protected function requiredHeaders(): array
    {
        return ['business name', 'date', 'rental amount'];
    }

    protected function handleRow(array $row, int $line): void
    {
        $date = $this->date($row, 'date');
        if (! $date) {
            throw new RowSkipped('Missing or unparseable date.');
        }

        $business = $this->value($row, 'business name');
        if ($business === '') {
            throw new RowSkipped('Missing business name.');
        }

        $rentalType = $this->value($row, 'rental type');
        $route = $this->value($row, 'route');
        $remarks = trim(implode(' · ', array_filter([
            $route !== '' ? "route: {$route}" : null,
            $this->value($row, 'remarks') ?: null,
        ]))) ?: null;

        $category = $this->slug($rentalType !== '' ? $rentalType : $business);
        $created = 0;

        $rental = $this->number($row, 'rental amount');
        if ($rental !== null && $rental > 0) {
            $this->createIncome($date, $category, $business, $rentalType ?: 'Rental', $rental, $remarks);
            $created++;
        }

        foreach (self::EXTRA_COLUMNS as $header => $label) {
            $amount = $this->number($row, $header);
            if ($amount !== null && $amount > 0) {
                $this->createIncome($date, $this->slug($label), $business, $label, $amount, $remarks);
                $created++;
            }
        }

        if ($created === 0) {
            throw new RowSkipped('No rental amount on this row.');
        }
    }

    private function createIncome(string $date, string $category, string $business, string $label, float $amount, ?string $remarks): void
    {
        IncomeExpense::create([
            'transaction_date' => $date,
            'type' => 'income',
            'category' => $category,
            'description' => trim("{$business} — {$label}"),
            'reference_no' => null,
            'remarks' => $remarks,
            'amount' => $amount,
            'recorded_by' => $this->userId,
        ]);
    }
}

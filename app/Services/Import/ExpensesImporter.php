<?php

namespace App\Services\Import;

use App\Models\IncomeExpense;

/**
 * expenses template.xlsx
 * headers: name, date, particulars, amount, control number, remarks
 *
 * Each row -> one income_expenses row (type = expense).
 */
class ExpensesImporter extends BaseImporter
{
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
        return 'expenses';
    }

    protected function requiredHeaders(): array
    {
        return ['date', 'particulars', 'amount'];
    }

    protected function handleRow(array $row, int $line): void
    {
        $date = $this->date($row, 'date');
        if (! $date) {
            throw new RowSkipped('Missing or unparseable date.');
        }

        $amount = $this->number($row, 'amount');
        if ($amount === null || $amount <= 0) {
            throw new RowSkipped('Missing or invalid amount.');
        }

        $particulars = $this->value($row, 'particulars');
        if ($particulars === '') {
            throw new RowSkipped('Missing particulars.');
        }

        $name = $this->value($row, 'name');

        IncomeExpense::create([
            'transaction_date' => $date,
            'type' => 'expense',
            'category' => 'other_expense',
            'description' => $name !== '' ? "{$particulars} — {$name}" : $particulars,
            'reference_no' => $this->value($row, 'control number') ?: null,
            'remarks' => $this->value($row, 'remarks') ?: null,
            'amount' => $amount,
            'recorded_by' => $this->userId,
        ]);
    }
}

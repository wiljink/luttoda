<?php

namespace App\Services\Import;

class ImportResult
{
    public int $imported = 0;

    public int $skipped = 0;

    /** @var array<int, array{row: int, message: string}> */
    public array $errors = [];

    public function importedRow(): void
    {
        $this->imported++;
    }

    public function skippedRow(int $row, string $message): void
    {
        $this->skipped++;
        $this->errors[] = ['row' => $row, 'message' => $message];
    }

    public function toArray(): array
    {
        return [
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}

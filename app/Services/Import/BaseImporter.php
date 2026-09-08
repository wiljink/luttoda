<?php

namespace App\Services\Import;

use App\Models\ImportLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

abstract class BaseImporter
{
    protected ImportResult $result;

    public function __construct(protected SpreadsheetReader $reader)
    {
        $this->result = new ImportResult;
    }

    /** daily_collection | expenses | rental */
    abstract protected function template(): string;

    /** Normalised header names that must be present. */
    abstract protected function requiredHeaders(): array;

    /**
     * Import one data row. Throw any exception to skip the row with that
     * message; return normally to count it as imported.
     *
     * @param  array<string, string>  $row
     */
    abstract protected function handleRow(array $row, int $line): void;

    /**
     * @throws RuntimeException when the file is unreadable or missing headers
     */
    public function run(string $path, bool $preview, ?int $userId, string $originalName): ImportLog
    {
        $missing = array_diff($this->requiredHeaders(), $this->reader->headers($path));
        if ($missing) {
            throw new RuntimeException('Missing required column(s): '.implode(', ', $missing));
        }

        DB::beginTransaction();

        try {
            foreach ($this->reader->rows($path) as $entry) {
                try {
                    // Per-row savepoint: a skipped row leaves nothing behind.
                    DB::transaction(fn () => $this->handleRow($entry['data'], $entry['line']));
                    $this->result->importedRow();
                } catch (RowSkipped $e) {
                    $this->result->skippedRow($entry['line'], $e->getMessage());
                } catch (Throwable $e) {
                    $this->result->skippedRow($entry['line'], $e->getMessage());
                }
            }
        } catch (Throwable $e) {
            // A fatal reader error (e.g. row cap) — abort everything.
            DB::rollBack();
            throw $e;
        }

        if ($preview) {
            DB::rollBack();
        } else {
            DB::commit();
        }

        return ImportLog::create([
            'template' => $this->template(),
            'filename' => $originalName,
            'preview' => $preview,
            'imported' => $this->result->imported,
            'skipped' => $this->result->skipped,
            'errors' => $this->result->errors ?: null,
            'user_id' => $userId,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Shared row helpers */
    /* ------------------------------------------------------------------ */

    protected function value(array $row, string $key, string $default = ''): string
    {
        return trim((string) ($row[$key] ?? $default));
    }

    protected function number(array $row, string $key): ?float
    {
        $raw = preg_replace('/[^0-9.\-]/', '', $this->value($row, $key));

        return $raw === '' ? null : (float) $raw;
    }

    protected function date(array $row, string $key): ?string
    {
        $raw = $this->value($row, $key);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    protected function slug(string $value): string
    {
        return Str::slug($value, '_') ?: 'other';
    }
}

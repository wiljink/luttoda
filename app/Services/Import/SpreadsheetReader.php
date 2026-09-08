<?php

namespace App\Services\Import;

use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;

/**
 * Thin wrapper over OpenSpout: reads the first sheet of an .xlsx file and
 * yields each data row as an associative array keyed by the normalised
 * header name (lower-cased, trimmed, internal whitespace collapsed).
 */
class SpreadsheetReader
{
    public const MAX_ROWS = 2000;

    /**
     * @return \Generator<int, array{line: int, data: array<string, string>}>
     */
    public function rows(string $path): \Generator
    {
        $reader = new Reader;
        $reader->open($path);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $headers = null;
                $dataRows = 0;

                foreach ($sheet->getRowIterator() as $lineNo => $row) {
                    $cells = array_map(
                        fn ($v) => is_string($v) ? trim($v) : (string) ($v ?? ''),
                        $row->toArray(),
                    );

                    if ($headers === null) {
                        $headers = array_map([$this, 'normalise'], $cells);

                        continue;
                    }

                    if ($this->isBlank($cells)) {
                        continue;
                    }

                    if (++$dataRows > self::MAX_ROWS) {
                        throw new RuntimeException(
                            'File has more than '.self::MAX_ROWS.' data rows. Split it and import in batches.',
                        );
                    }

                    $assoc = [];
                    foreach ($headers as $i => $key) {
                        if ($key === '') {
                            continue;
                        }
                        $assoc[$key] = $cells[$i] ?? '';
                    }

                    yield ['line' => $lineNo, 'data' => $assoc];
                }

                break; // first sheet only
            }
        } finally {
            $reader->close();
        }
    }

    /** Header names present in the first row (normalised). */
    public function headers(string $path): array
    {
        $reader = new Reader;
        $reader->open($path);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    return array_values(array_filter(array_map(
                        fn ($v) => $this->normalise(is_string($v) ? $v : (string) ($v ?? '')),
                        $row->toArray(),
                    )));
                }
            }
        } finally {
            $reader->close();
        }

        return [];
    }

    private function normalise(string $value): string
    {
        return preg_replace('/\s+/', ' ', strtolower(trim($value)));
    }

    private function isBlank(array $cells): bool
    {
        foreach ($cells as $c) {
            if (trim((string) $c) !== '') {
                return false;
            }
        }

        return true;
    }
}

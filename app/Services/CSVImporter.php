<?php

namespace App\Services;

use Log;

/**
 * We are using this for database seeder and other CSV import options
 * Auth @x3n-on
 * On 29 Sept 2025
 */

class CSVImporter
{
    public static function read(string $file): array
    {
        if (!file_exists($file) || !is_readable($file)) {
            throw new \Exception("CSV file not found or not readable: $file");
        }

        $header = null;
        $data = [];

        if (($handle = fopen($file, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (!$header) {
                    $header = $row;
                } elseif (count($header) === count($row)) {
                    $data[] = array_combine($header, $row);
                } else {
                    Log::warning('Skipping invalid CSV row', [
                        'row'      => $row,
                        'expected' => count($header),
                        'got'      => count($row),
                    ]);
                }
            }
            fclose($handle);
        }

        return $data;
    }
}

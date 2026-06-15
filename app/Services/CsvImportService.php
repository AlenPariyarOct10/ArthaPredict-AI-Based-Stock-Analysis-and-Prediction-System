<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CsvImportService
{
    /**
     * Extract date from the filename (format YYYY_MM_DD.csv)
     *
     * @param string $filename
     * @return string|null YYYY-MM-DD format or null if invalid
     */
    public function extractDateFromFilename(string $filename): ?string
    {
        if (preg_match('/^(\d{4})_(\d{2})_(\d{2})\.csv$/', $filename, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            $day = (int)$matches[3];

            // Validate if it's a valid calendar date
            if (checkdate($month, $day, $year)) {
                // Pad month and day
                $m = str_pad($month, 2, '0', STR_PAD_LEFT);
                $d = str_pad($day, 2, '0', STR_PAD_LEFT);
                return "{$year}-{$m}-{$d}";
            }
        }
        return null;
    }

    /**
     * Import stock price data from CSV
     *
     * @param string $filePath
     * @param string $filename
     * @return array
     * @throws \Exception
     */
    public function import(string $filePath, string $filename): array
    {
        $date = $this->extractDateFromFilename($filename);
        if (!$date) {
            throw new \InvalidArgumentException("Filename '{$filename}' does not match the YYYY_MM_DD.csv format or contains an invalid calendar date.");
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException("The file '{$filename}' is not readable or does not exist.");
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException("Failed to open the uploaded file.");
        }

        // Read header
        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        if (!$headers) {
            fclose($handle);
            throw new \RuntimeException("The CSV file is empty or has an invalid header row.");
        }

        // Map column names to index numbers (case-insensitive, trimmed)
        $headerMap = [];
        foreach ($headers as $index => $header) {
            $headerMap[strtolower(trim($header))] = $index;
        }

        $symbolIndex = $headerMap['symbol'] ?? null;
        $openIndex = $headerMap['open'] ?? null;
        $highIndex = $headerMap['high'] ?? null;
        $lowIndex = $headerMap['low'] ?? null;
        $closeIndex = $headerMap['close'] ?? null;
        $volumeIndex = $headerMap['vol'] ?? $headerMap['volume'] ?? null;

        if ($symbolIndex === null || $closeIndex === null) {
            fclose($handle);
            throw new \InvalidArgumentException("CSV file must contain at least 'Symbol' and 'Close' columns.");
        }

        // Pre-load all stock symbols to prevent N+1 queries during creation
        $stocksMap = Stock::pluck('id', 'symbol')->mapWithKeys(function ($id, $sym) {
            return [strtoupper(trim($sym)) => $id];
        })->toArray();

        // Pre-load all existing price stock_ids for this date to avoid duplicate insertion errors
        $existingPrices = StockPrice::where('date', $date)
            ->pluck('stock_id')
            ->toArray();
        $existingPricesMap = array_flip($existingPrices);

        $totalRows = 0;
        $importedRows = 0;
        $skippedRows = 0;
        $errorRows = 0;
        $errorsLog = [];

        $batch = [];
        $chunkSize = 500;
        
        // Track symbols processed within this file to prevent duplicates in the same file
        $processedStockIdsInThisImport = [];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                // Skip empty rows
                if (empty($row) || (count($row) === 1 && $row[0] === null)) {
                    continue;
                }

                $totalRows++;
                $rowNum = $totalRows + 1; // 1-based, accounts for header

                // Retrieve symbol
                $symbol = isset($row[$symbolIndex]) ? strtoupper(trim($row[$symbolIndex])) : '';
                if (empty($symbol)) {
                    $errorRows++;
                    $errorsLog[] = "Row {$rowNum}: Symbol is empty.";
                    continue;
                }

                // Retrieve and validate close price
                $closeStr = isset($row[$closeIndex]) ? trim($row[$closeIndex]) : '';
                $closeStrClean = str_replace(',', '', $closeStr);
                if ($closeStrClean === '' || !is_numeric($closeStrClean)) {
                    $errorRows++;
                    $errorsLog[] = "Row {$rowNum} ({$symbol}): Close price '{$closeStr}' is empty or invalid.";
                    continue;
                }
                $close = (float)$closeStrClean;

                // Parse optional columns
                $open = null;
                if ($openIndex !== null && isset($row[$openIndex])) {
                    $val = str_replace(',', '', trim($row[$openIndex]));
                    if ($val !== '' && is_numeric($val)) {
                        $open = (float)$val;
                    }
                }

                $high = null;
                if ($highIndex !== null && isset($row[$highIndex])) {
                    $val = str_replace(',', '', trim($row[$highIndex]));
                    if ($val !== '' && is_numeric($val)) {
                        $high = (float)$val;
                    }
                }

                $low = null;
                if ($lowIndex !== null && isset($row[$lowIndex])) {
                    $val = str_replace(',', '', trim($row[$lowIndex]));
                    if ($val !== '' && is_numeric($val)) {
                        $low = (float)$val;
                    }
                }

                $volume = null;
                if ($volumeIndex !== null && isset($row[$volumeIndex])) {
                    $val = str_replace(',', '', trim($row[$volumeIndex]));
                    if ($val !== '' && is_numeric($val)) {
                        $volume = (int)round((float)$val);
                    }
                }

                // Retrieve or create stock entry
                if (!isset($stocksMap[$symbol])) {
                    $newStock = Stock::create([
                        'symbol' => $symbol,
                        'name' => $symbol, // default name to symbol
                    ]);
                    $stocksMap[$symbol] = $newStock->id;
                }
                $stockId = $stocksMap[$symbol];

                // Skip if duplicate (already in DB for this date, or duplicate within this CSV file)
                if (isset($existingPricesMap[$stockId]) || isset($processedStockIdsInThisImport[$stockId])) {
                    $skippedRows++;
                    continue;
                }
                
                $processedStockIdsInThisImport[$stockId] = true;

                $batch[] = [
                    'stock_id' => $stockId,
                    'date' => $date,
                    'open' => $open,
                    'high' => $high,
                    'low' => $low,
                    'close' => $close,
                    'volume' => $volume,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= $chunkSize) {
                    StockPrice::insert($batch);
                    $importedRows += count($batch);
                    $batch = [];
                }
            }

            // Insert remaining batch
            if (count($batch) > 0) {
                StockPrice::insert($batch);
                $importedRows += count($batch);
            }

            $this->refreshUsableDatapointCounts(
                array_keys($processedStockIdsInThisImport)
            );
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("CSV dataset import failed: " . $e->getMessage(), [
                'exception' => $e,
                'filename' => $filename,
            ]);
            throw $e;
        } finally {
            fclose($handle);
        }

        return [
            'total_rows' => $totalRows,
            'imported_rows' => $importedRows,
            'skipped_rows' => $skippedRows,
            'error_rows' => $errorRows,
            'errors_log' => $errorsLog,
            'trading_date' => $date,
        ];
    }

    private function refreshUsableDatapointCounts(array $stockIds): void
    {
        if (empty($stockIds)) {
            return;
        }

        $updates = StockPrice::whereIn('stock_id', $stockIds)
            ->select(
                'stock_id',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('MAX(close) as maximum_close'),
                DB::raw(
                    'SUM(CASE WHEN close >= 10 THEN 1 ELSE 0 END) '
                    . 'as ordinary_price_count'
                )
            )
            ->groupBy('stock_id')
            ->get();

        foreach ($updates as $row) {
            Stock::whereKey($row->stock_id)->update([
                'usable_datapoints_count' => $row->maximum_close >= 50
                    ? $row->ordinary_price_count
                    : $row->total_count,
            ]);
        }
    }
}

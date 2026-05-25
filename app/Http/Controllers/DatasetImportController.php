<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportDatasetRequest;
use App\Models\DatasetImport;
use App\Services\CsvImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DatasetImportController extends Controller
{
    protected CsvImportService $importService;

    public function __construct(CsvImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Display the import page with history table.
     */
    public function index()
    {
        $imports = DatasetImport::with('user')
            ->latest()
            ->paginate(10);

        return view('admin.dataset-import', compact('imports'));
    }

    /**
     * Process the uploaded CSV dataset.
     */
    public function import(ImportDatasetRequest $request)
    {
        $file = $request->file('csv_file');
        $originalName = $file->getClientOriginalName();

        // Step 1: Validate filename date
        $tradingDate = $this->importService->extractDateFromFilename($originalName);
        if (!$tradingDate) {
            return back()->withErrors([
                'csv_file' => 'The CSV filename must match the YYYY_MM_DD.csv format (e.g. 2026_05_24.csv) and be a valid calendar date.'
            ])->withInput();
        }

        try {
            // Step 2: Perform the import
            $result = $this->importService->import($file->getRealPath(), $originalName);

            // Step 3: Record import stats in history
            $status = 'completed';
            if ($result['error_rows'] > 0) {
                $status = $result['imported_rows'] > 0 ? 'partial' : 'failed';
            }

            DatasetImport::create([
                'user_id' => auth()->id(),
                'filename' => $originalName,
                'trading_date' => $result['trading_date'],
                'total_rows' => $result['total_rows'],
                'imported_rows' => $result['imported_rows'],
                'skipped_rows' => $result['skipped_rows'],
                'error_rows' => $result['error_rows'],
                'errors_log' => $result['errors_log'],
                'status' => $status,
            ]);

            $summary = "Total: {$result['total_rows']}, Imported: {$result['imported_rows']}, Skipped: {$result['skipped_rows']}, Errors: {$result['error_rows']}.";

            if ($status === 'partial') {
                return back()->with('partial_success', "Import completed with some row validation failures. {$summary}");
            } elseif ($status === 'failed') {
                return back()->with('error', "Import failed. No rows could be successfully parsed. {$summary}");
            }

            return back()->with('success', "Dataset imported successfully for date {$result['trading_date']}! {$summary}");

        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['csv_file' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            // Log import failure
            DatasetImport::create([
                'user_id' => auth()->id(),
                'filename' => $originalName,
                'trading_date' => $tradingDate ?: now()->toDateString(),
                'total_rows' => 0,
                'imported_rows' => 0,
                'skipped_rows' => 0,
                'error_rows' => 0,
                'errors_log' => [$e->getMessage()],
                'status' => 'failed',
            ]);

            return back()->withErrors(['csv_file' => 'Import failed: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Download the sample CSV file.
     */
    public function downloadSample()
    {
        $path = storage_path('app/samples/sample_dataset.csv');
        if (!file_exists($path)) {
            abort(404, 'Sample file not found.');
        }

        return response()->download($path, 'YYYY_MM_DD_sample.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}

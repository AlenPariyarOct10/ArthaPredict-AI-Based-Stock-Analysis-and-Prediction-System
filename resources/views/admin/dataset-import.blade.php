@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Dataset Import</h1>
            <p class="text-sm text-muted-foreground mt-1">Upload bulk CSV files to import historical stock market trading data.</p>
        </div>
        <div>
            <a href="{{ route('admin.dataset-import.sample') }}" 
               class="inline-flex items-center justify-center px-4 py-2 border border-border bg-card text-foreground hover:bg-muted-background text-sm font-medium rounded-lg transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Download Sample CSV
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="bg-blue-100 dark:bg-blue-900/30 border border-green-400 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('partial_success'))
        <div class="bg-amber-100 dark:bg-amber-900/30 border border-amber-400 dark:border-amber-800 text-amber-700 dark:text-amber-400 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline font-medium">{{ session('partial_success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline font-medium">{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg relative" role="alert">
            <ul class="list-disc pl-5 text-sm font-medium">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Upload Card -->
    <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border p-6 max-w-2xl">
        <h2 class="text-xl font-bold mb-2">Upload Stock Market CSV</h2>
        <p class="text-sm text-muted-foreground mb-6">
            The CSV file must follow the naming convention <strong>YYYY_MM_DD.csv</strong> (e.g., <code>2026_05_24.csv</code>). 
            The system will automatically extract this date and use it as the trading/import date. 
            New stock symbols will be automatically registered in the database. Duplicate records for the same symbol on this date will be skipped.
        </p>

        <form id="import-form" action="{{ route('admin.dataset-import.import') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <div class="space-y-2">
                <label for="csv_file" class="block text-sm font-medium text-foreground">Select CSV File</label>
                
                <div class="flex items-center justify-center w-full">
                    <label for="csv_file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-border rounded-lg cursor-pointer bg-muted-background/30 hover:bg-muted-background transition">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-8 h-8 mb-3 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <p class="mb-2 text-sm text-muted-foreground font-semibold" id="file-label-text">Click to upload or drag & drop</p>
                            <p class="text-xs text-muted-foreground/75">CSV or TXT files only (Max. 10MB)</p>
                        </div>
                        <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt" class="hidden" required />
                    </label>
                </div>
            </div>

            <!-- Upload Button -->
            <button type="submit" id="submit-btn" class="inline-flex items-center justify-center text-white gradient-accent hover:opacity-90 font-medium rounded-lg text-sm px-5 py-2.5 transition shadow-sm w-full sm:w-auto">
                <!-- Spinner (hidden by default) -->
                <svg id="loading-spinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span id="btn-text">Import Dataset</span>
            </button>
        </form>
    </div>

    <!-- Import History -->
    <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border overflow-hidden">
        <div class="px-6 py-4 border-b border-border">
            <h2 class="text-xl font-bold">Import History</h2>
            <p class="text-sm text-muted-foreground mt-0.5">Logs of recent dataset bulk imports.</p>
        </div>

        @if($imports->isEmpty())
            <div class="p-8 text-center text-muted-foreground">
                No import history records found.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-muted-foreground uppercase bg-muted-background/50 border-b border-border font-semibold">
                        <tr>
                            <th scope="col" class="px-6 py-3">Filename</th>
                            <th scope="col" class="px-6 py-3">Trading Date</th>
                            <th scope="col" class="px-6 py-3 text-center">Status</th>
                            <th scope="col" class="px-6 py-3 text-right">Total Rows</th>
                            <th scope="col" class="px-6 py-3 text-right text-blue-600 dark:text-blue-400">Imported</th>
                            <th scope="col" class="px-6 py-3 text-right text-amber-600 dark:text-amber-400">Skipped</th>
                            <th scope="col" class="px-6 py-3 text-right text-red-600 dark:text-red-400">Errors</th>
                            <th scope="col" class="px-6 py-3">Imported By</th>
                            <th scope="col" class="px-6 py-3">Date Imported</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($imports as $import)
                            <tr class="hover:bg-muted-background/30 transition">
                                <td class="px-6 py-4 font-medium text-foreground">
                                    {{ $import->filename }}
                                    @if(!empty($import->errors_log))
                                        <div class="mt-2">
                                            <details class="text-xs text-muted-foreground cursor-pointer focus:outline-none">
                                                <summary class="hover:underline font-semibold text-red-500/80">View Error Details ({{ count($import->errors_log) }})</summary>
                                                <div class="mt-1 p-2 rounded bg-muted-background/50 border border-border max-h-40 overflow-y-auto font-mono text-xxs leading-relaxed">
                                                    @foreach($import->errors_log as $err)
                                                        <p class="text-red-600 dark:text-red-400 mb-0.5">• {{ $err }}</p>
                                                    @endforeach
                                                </div>
                                            </details>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $import->trading_date->format('Y-m-d') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($import->status === 'completed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-green-800 dark:bg-blue-900/30 dark:text-green-400">
                                            Completed
                                        </span>
                                    @elseif($import->status === 'partial')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                            Partial
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                            Failed
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">{{ number_format($import->total_rows) }}</td>
                                <td class="px-6 py-4 text-right whitespace-nowrap font-medium text-blue-600 dark:text-blue-400">{{ number_format($import->imported_rows) }}</td>
                                <td class="px-6 py-4 text-right whitespace-nowrap text-amber-600 dark:text-amber-400">{{ number_format($import->skipped_rows) }}</td>
                                <td class="px-6 py-4 text-right whitespace-nowrap text-red-600 dark:text-red-400 font-semibold">{{ number_format($import->error_rows) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $import->user->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-muted-foreground">{{ $import->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-border">
                {{ $imports->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    // Show selected filename
    const fileInput = document.getElementById('csv_file');
    const fileLabelText = document.getElementById('file-label-text');
    
    fileInput.addEventListener('change', function() {
        if (fileInput.files.length > 0) {
            fileLabelText.textContent = "Selected: " + fileInput.files[0].name;
            fileLabelText.classList.remove('text-muted-foreground');
            fileLabelText.classList.add('text-primary', 'font-semibold');
        } else {
            fileLabelText.textContent = "Click to upload or drag & drop";
            fileLabelText.classList.add('text-muted-foreground');
            fileLabelText.classList.remove('text-primary', 'font-semibold');
        }
    });

    // Handle submit state
    document.getElementById('import-form').addEventListener('submit', function() {
        const btn = document.getElementById('submit-btn');
        const spinner = document.getElementById('loading-spinner');
        const btnText = document.getElementById('btn-text');
        
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');
        spinner.classList.remove('hidden');
        btnText.textContent = 'Importing Data...';
    });
</script>
@endsection

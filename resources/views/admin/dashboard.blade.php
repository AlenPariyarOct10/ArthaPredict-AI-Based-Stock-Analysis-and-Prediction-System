@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border p-6 flex flex-col justify-between">
            <h3 class="text-lg font-medium text-muted-foreground">Total Users</h3>
            <p class="text-3xl font-bold mt-2">{{ $users_count }}</p>
        </div>
        <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border p-6 flex flex-col justify-between">
            <h3 class="text-lg font-medium text-muted-foreground">Monitored Stocks</h3>
            <p class="text-3xl font-bold mt-2">{{ $stocks_count }}</p>
        </div>
        <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border p-6 flex flex-col justify-between">
            <h3 class="text-lg font-medium text-muted-foreground">Pending Feedback</h3>
            <p class="text-3xl font-bold mt-2">{{ $pending_feedback_count }}</p>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="bg-blue-100 dark:bg-blue-900/30 border border-green-400 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl relative" role="alert">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Queue worker helper banner (Hidden by default, shown via JS if jobs stay in 'queued' state) -->
    <div id="queue-warning" class="hidden bg-amber-50 dark:bg-amber-950/20 border border-amber-300 dark:border-amber-900 text-amber-800 dark:text-amber-400 p-4 rounded-xl items-start space-x-3 shadow-sm transition-all duration-300" role="alert">
        <svg class="h-5 w-5 text-amber-500 dark:text-amber-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <h4 class="font-bold text-sm">Action Required: Queue Worker is Not Running</h4>
            <p class="text-xs mt-1">
                Your model training requests are currently in the <strong>Queued</strong> state. Because ArthaPredict processes these calculations in the background, you must ensure that your Laravel queue worker is running. Please run the following command in your terminal:
            </p>
            <code class="block text-xs bg-amber-100 dark:bg-amber-950/60 text-amber-900 dark:text-amber-300 font-mono mt-2 p-2 rounded border border-amber-200 dark:border-amber-900/40 select-all">php artisan queue:work</code>
        </div>
    </div>

    <!-- Main Grid Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Side: Training Trigger Form -->
        <div class="lg:col-span-1 bg-card shadow-sm rounded-xl border border-border p-6 flex flex-col justify-between">
            <div>
                <h2 class="text-xl font-bold mb-3">Train ML Model</h2>
                <p class="text-sm text-muted-foreground mb-6">
                    Trigger a force re-train of predictive models (Moving Average, XGBoost, and LSTM) for a specific stock. The training runs in the background and will refresh the prediction tables upon completion.
                </p>

                <form action="{{ route('admin.training.start') }}" method="POST" class="space-y-4" id="training-form">
                    @csrf
                    <div>
                        <label for="stock-trigger" class="block text-sm font-medium text-foreground mb-2">Select Stock Symbol</label>

                        {{-- Custom searchable combobox (replaces TomSelect). Uses your own design tokens. --}}
                        <div id="stock-combobox" class="relative" data-open="false">
                            <!-- Real value submitted with the form -->
                            <input type="hidden" id="stock_id" name="stock_id" value="" required>

                            <!-- Trigger / opener -->
                            <button type="button" id="stock-trigger" aria-haspopup="listbox" aria-expanded="false"
                                    class="w-full flex items-center justify-between bg-background border border-border text-foreground text-sm rounded-lg px-3 py-2.5 text-left focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition">
                                <span id="stock-trigger-label" class="text-muted-foreground truncate">Choose a stock to train...</span>
                                <i class="fa-solid fa-chevron-down text-xs text-muted-foreground ml-2 shrink-0 transition-transform"></i>
                            </button>

                            <!-- Dropdown panel -->
                            <div id="stock-panel"
                                 class="hidden absolute z-50 mt-1 w-full bg-card text-card-foreground border border-border rounded-lg shadow-lg overflow-hidden">
                                <div class="p-2 border-b border-border">
                                    <input type="text" id="stock-search" autocomplete="off" placeholder="Search symbol or name..."
                                           class="w-full bg-background border border-border text-foreground text-sm rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <ul id="stock-options" class="max-h-60 overflow-y-auto py-1" role="listbox">
                                    @foreach($stocks as $stock)
                                        <li role="option"
                                            data-value="{{ $stock->id }}"
                                            data-label="{{ $stock->symbol }} - {{ $stock->name }}{{ $stock->predictions()->exists() ? ' (exists)' : '' }}"
                                            data-search="{{ \Illuminate\Support\Str::lower($stock->symbol.' '.$stock->name) }}"
                                            class="stock-option px-3 py-2 cursor-pointer hover:bg-muted transition flex flex-col">
                                            <span class="text-sm font-medium text-foreground">{{ $stock->symbol }}</span>
                                            <span class="text-xs text-muted-foreground">{{ $stock->name }}{{ $stock->predictions()->exists() ? ' (exists)' : '' }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <div id="stock-no-results" class="hidden px-3 py-3 text-sm text-muted-foreground text-center">No stocks found.</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <button type="submit" id="train-submit-btn"
                                class="flex-1 text-white gradient-accent hover:opacity-90 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition shadow-sm">
                            Initiate Training
                        </button>

                        <button type="button" id="force-train-btn"
                                class="px-3 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm transition"
                                title="Force retrain (overwrites existing)">
                            <i class="fa-solid fa-bolt text-lg p-0.5"></i>
                        </button>
                    </div>
                </form>
                <!-- Universal Model Training Form -->
                <div class="mt-8">
                    <h2 class="text-xl font-bold mb-3">Train Universal Model</h2>
                    <p class="text-sm text-muted-foreground mb-6">
                        Train a universal model (LSTM, XGBoost, or Random Forest) across all stocks. This may take several minutes.
                    </p>
                    <form action="{{ route('admin.training.universal') }}" method="POST" class="space-y-4" id="universal-training-form">
                        @csrf
                        <div>
                            <label for="model-type" class="block text-sm font-medium text-foreground mb-2">Select Model Type</label>
                            <select id="model-type" name="model_type" class="w-full bg-background border border-border text-foreground text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="lstm">LSTM</option>
                                <option value="xgboost">XGBoost</option>
                                <option value="random_forest">Random Forest</option>
                            </select>
                        </div>
                        <button type="submit" class="flex-1 text-white gradient-accent hover:opacity-90 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition shadow-sm">
                            Train Universal Model
                        </button>
                    </form>
                </div>

            </div>

            <div class="mt-8 border-t border-border/50 pt-4 text-xs text-muted-foreground flex items-center justify-between">
                <span>Status Polling: <span class="text-blue-500 font-bold">Active</span></span>
                <span class="px-2 py-0.5 bg-muted rounded">database queue</span>
            </div>
        </div>

        <!-- Right Side: Job History and Realtime Polling -->
        <div class="lg:col-span-2 bg-card shadow-sm rounded-xl border border-border p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold">Recent Training History</h2>
                <button onclick="window.location.reload()" class="p-1 hover:bg-muted rounded-lg text-muted-foreground transition" title="Refresh Dashboard">
                    <i class="fa-solid fa-arrows-rotate text-lg p-0.5"></i>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-border text-xs text-muted-foreground uppercase font-semibold">
                            <th class="px-4 py-3">Stock</th>
                            <th class="px-4 py-3">Last Update</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Message / Error</th>
                        </tr>
                    </thead>
                    <tbody id="jobs-table-body">
                        @forelse($recentJobs as $job)
                            <tr class="border-b border-border/50 hover:bg-muted/30 transition">
                                <td class="px-4 py-3 text-sm font-medium text-foreground">
                                    <div>{{ $job->symbol ?? ($job->stock->symbol ?? 'N/A') }}</div>
                                    <div class="text-xs text-muted-foreground font-normal">{{ $job->name ?? ($job->stock->name ?? '') }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">
                                    <div>{{ $job->updated_at->diffForHumans() }}</div>
                                    <div class="text-[10px] text-muted-foreground/60">{{ $job->created_at->format('Y-m-d H:i:s') }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($job->status === 'completed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-green-800 dark:bg-blue-900/30 dark:text-green-400">
                                            <svg class="mr-1 h-3 w-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Completed
                                        </span>
                                    @elseif($job->status === 'failed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400" title="{{ $job->error_message }}">
                                            <svg class="mr-1 h-3 w-3 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                            Failed
                                        </span>
                                    @elseif($job->status === 'processing')
                                        <div class="flex flex-col space-y-1.5 min-w-[150px]">
                                            <div class="flex items-center justify-between text-xs font-semibold">
                                                <span class="text-blue-600 dark:text-blue-400 flex items-center">
                                                    <svg class="animate-spin mr-1 h-3.5 w-3.5 text-blue-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    {{ $job->current_stage ?? 'Processing' }}
                                                </span>
                                                <span class="text-muted-foreground text-[10px]">{{ $job->total_rows > 0 ? round(($job->processed_rows / $job->total_rows) * 100) : 0 }}%</span>
                                            </div>
                                            <div class="w-full bg-muted dark:bg-muted/50 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-blue-600 dark:bg-blue-500 h-1.5 rounded-full transition-all duration-300" style="width: {{ $job->total_rows > 0 ? min(100, ($job->processed_rows / $job->total_rows) * 100) : 0 }}%"></div>
                                            </div>
                                            <div class="flex justify-between text-[10px] text-muted-foreground font-mono">
                                                <span>P: {{ number_format($job->processed_rows) }}</span>
                                                <span>R: {{ number_format(max(0, $job->total_rows - $job->processed_rows)) }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400">
                                            <span class="relative flex h-1.5 w-1.5 mr-1.5">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gray-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-gray-500"></span>
                                            </span>
                                            Queued
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($job->error_message)
                                        <div class="text-xs text-red-500 dark:text-red-400 max-w-xs truncate" title="{{ $job->error_message }}">
                                            {{ $job->error_message }}
                                        </div>
                                    @else
                                        <span class="text-xs text-muted-foreground">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr id="no-jobs-row">
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    No training jobs have been executed yet. Choose a stock to trigger the pipeline.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

@push('scripts')
    <script>
        // Lightweight custom searchable dropdown API (replaces TomSelect)
        let comboboxApi = null;

        document.addEventListener('DOMContentLoaded', function () {
            // ---------------------------------------------------------------
            // Custom searchable stock dropdown (no third-party library)
            // ---------------------------------------------------------------
            (function initStockCombobox() {
                const root      = document.getElementById('stock-combobox');
                if (!root) return;

                const hidden    = document.getElementById('stock_id');
                const trigger   = document.getElementById('stock-trigger');
                const chevron   = trigger.querySelector('i');
                const label     = document.getElementById('stock-trigger-label');
                const panel     = document.getElementById('stock-panel');
                const search    = document.getElementById('stock-search');
                const options   = Array.from(document.querySelectorAll('.stock-option'));
                const noResults = document.getElementById('stock-no-results');

                function open() {
                    panel.classList.remove('hidden');
                    root.dataset.open = 'true';
                    trigger.setAttribute('aria-expanded', 'true');
                    chevron.classList.add('rotate-180');
                    search.value = '';
                    filter('');
                    setTimeout(() => search.focus(), 0);
                }

                function close() {
                    panel.classList.add('hidden');
                    root.dataset.open = 'false';
                    trigger.setAttribute('aria-expanded', 'false');
                    chevron.classList.remove('rotate-180');
                }

                function filter(term) {
                    term = term.toLowerCase().trim();
                    let visible = 0;
                    options.forEach(opt => {
                        const match = opt.dataset.search.includes(term);
                        opt.classList.toggle('hidden', !match);
                        if (match) visible++;
                    });
                    noResults.classList.toggle('hidden', visible > 0);
                }

                function select(opt) {
                    hidden.value = opt.dataset.value;
                    label.textContent = opt.dataset.label;
                    label.classList.remove('text-muted-foreground');
                    label.classList.add('text-foreground');
                    close();
                }

                trigger.addEventListener('click', () => {
                    root.dataset.open === 'true' ? close() : open();
                });

                search.addEventListener('input', e => filter(e.target.value));
                search.addEventListener('keydown', e => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const firstVisible = options.find(o => !o.classList.contains('hidden'));
                        if (firstVisible) select(firstVisible);
                    }
                });

                options.forEach(opt => opt.addEventListener('click', () => select(opt)));

                // Close on outside click / Escape
                document.addEventListener('click', e => { if (!root.contains(e.target)) close(); });
                document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });

                // Public API used by the form handlers below
                comboboxApi = {
                    clear() {
                        hidden.value = '';
                        label.textContent = 'Choose a stock to train...';
                        label.classList.add('text-muted-foreground');
                        label.classList.remove('text-foreground');
                    },
                    getValue() { return hidden.value; }
                };
            })();

            // ---------------------------------------------------------------
            // Job history polling + rendering (unchanged logic)
            // ---------------------------------------------------------------
            const tableBody = document.getElementById('jobs-table-body');
            const noJobsRow = document.getElementById('no-jobs-row');
            const queueWarning = document.getElementById('queue-warning');
            let pollingInterval = null;
            let queuedTime = 0;

            function checkActiveJobs(jobs) {
                return jobs.some(job => job.status === 'queued' || job.status === 'processing');
            }

            function renderJobs(jobs) {
                if (!jobs || jobs.length === 0) {
                    if (noJobsRow) noJobsRow.classList.remove('hidden');
                    tableBody.innerHTML = '';
                    return;
                }

                if (noJobsRow) noJobsRow.classList.add('hidden');

                let html = '';
                let hasQueued = false;

                jobs.forEach(job => {
                    let statusBadge = '';
                    let rowClass = 'border-b border-border/50 hover:bg-muted/30 transition';

                    if (job.status === 'completed') {
                        statusBadge = `
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-green-800 dark:bg-blue-900/30 dark:text-green-400">
                                <svg class="mr-1 h-3 w-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Completed
                            </span>
                        `;
                    } else if (job.status === 'failed') {
                        statusBadge = `
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400" title="${escapeHtml(job.error_message)}">
                                <svg class="mr-1 h-3 w-3 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                Failed
                            </span>
                        `;
                    } else if (job.status === 'processing') {
                        statusBadge = `
                            <div class="flex flex-col space-y-1.5 min-w-[150px]">
                                <div class="flex items-center justify-between text-xs font-semibold">
                                    <span class="text-blue-600 dark:text-blue-400 flex items-center">
                                        <svg class="animate-spin mr-1 h-3.5 w-3.5 text-blue-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        ${escapeHtml(job.current_stage || 'Processing')}
                                    </span>
                                    <span class="text-muted-foreground text-[10px]">${job.progress_pct}%</span>
                                </div>
                                <div class="w-full bg-muted dark:bg-muted/50 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-blue-600 dark:bg-blue-500 h-1.5 rounded-full transition-all duration-300" style="width: ${job.progress_pct}%"></div>
                                </div>
                                <div class="flex justify-between text-[10px] text-muted-foreground font-mono">
                                    <span>P: ${job.processed_rows.toLocaleString()}</span>
                                    <span>R: ${job.remaining_rows.toLocaleString()}</span>
                                </div>
                            </div>
                        `;
                    } else {
                        hasQueued = true;
                        statusBadge = `
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400">
                                <span class="relative flex h-1.5 w-1.5 mr-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gray-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-gray-500"></span>
                                </span>
                                Queued
                            </span>
                        `;
                    }

                    let errorCell = job.error_message
                        ? `<div class="text-xs text-red-500 dark:text-red-400 max-w-xs truncate font-mono" title="${escapeHtml(job.error_message)}">${escapeHtml(job.error_message)}</div>`
                        : `<span class="text-xs text-muted-foreground">-</span>`;

                    html += `
                        <tr class="${rowClass}">
                            <td class="px-4 py-3 text-sm font-medium text-foreground">
                                <div>${escapeHtml(job.symbol)}</div>
                                <div class="text-xs text-muted-foreground font-normal">${escapeHtml(job.name)}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-muted-foreground">
                                <div>${escapeHtml(job.updated_at)}</div>
                                <div class="text-[10px] text-muted-foreground/60">${escapeHtml(job.created_at_formatted)}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                ${statusBadge}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                ${errorCell}
                            </td>
                        </tr>
                    `;
                });

                tableBody.innerHTML = html;

                // Manage Queue Warning display logic
                if (hasQueued) {
                    queuedTime += 2;
                    if (queuedTime >= 6 && queueWarning) {
                        queueWarning.classList.remove('hidden');
                        queueWarning.classList.add('flex');
                    }
                } else {
                    queuedTime = 0;
                    if (queueWarning) {
                        queueWarning.classList.add('hidden');
                        queueWarning.classList.remove('flex');
                    }
                }
            }

            function escapeHtml(string) {
                if (!string) return '';
                return String(string)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function fetchStatus() {
                fetch('{{ route('admin.training.status') }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.jobs) {
                            renderJobs(data.jobs);

                            const active = checkActiveJobs(data.jobs);
                            if (!active && pollingInterval) {
                                clearInterval(pollingInterval);
                                pollingInterval = null;
                                console.log('Polling stopped: No active jobs.');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching job status:', error);
                    });
            }

            // Initialize polling if there are active jobs on load
            const initialJobs = [
                @foreach($recentJobs as $job)
                {
                    status: '{{ $job->status }}'
                },
                @endforeach
            ];

            if (checkActiveJobs(initialJobs)) {
                console.log('Active background jobs found. Initializing status polling...');
                pollingInterval = setInterval(fetchStatus, 2000);
            }

            document.getElementById('training-form')?.addEventListener('submit', async (e) => {
                e.preventDefault();

                const form = e.target;
                const formData = new FormData(form);
                const stockId = formData.get('stock_id');
                const submitBtn = document.getElementById('train-submit-btn');
                const forceBtn = document.getElementById('force-train-btn');

                if (!stockId) {
                    alert('Please select a stock');
                    return;
                }

                // Disable buttons
                submitBtn.disabled = true;
                forceBtn.disabled = true;
                submitBtn.innerHTML = '<div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div> Starting...';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);

                        // Clear the selection
                        if (comboboxApi) comboboxApi.clear();

                        location.reload();
                    } else {
                        alert(data.message || 'Failed to start training');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Network error occurred');
                } finally {
                    submitBtn.disabled = false;
                    forceBtn.disabled = false;
                    submitBtn.innerHTML = 'Initiate Training';
                }
            });

            document.getElementById('force-train-btn')?.addEventListener('click', async () => {
                if (!confirm('Force retrain will cancel any existing training and start fresh. Continue?')) {
                    return;
                }

                const stockId = comboboxApi ? comboboxApi.getValue() : '';

                if (!stockId) {
                    alert('Please select a stock first');
                    return;
                }

                const formData = new FormData();
                formData.append('stock_id', stockId);
                formData.append('force_retrain', '1');

                const submitBtn = document.getElementById('train-submit-btn');
                    // location.reload();


                submitBtn.disabled = true;
                forceBtn.disabled = true;
                forceBtn.innerHTML = '<div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>';

                try {
                    const response = await fetch('{{ route("admin.training.start") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);

                        // Clear the selection
                        if (comboboxApi) comboboxApi.clear();

                        location.reload();
                    } else {
                        alert(data.message || 'Failed to start training');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Network error occurred');
                } finally {
                    submitBtn.disabled = false;
                    forceBtn.disabled = false;
                    forceBtn.innerHTML = '<i class="fa-solid fa-bolt text-lg p-0.5"></i>';
                }
            });
        });
    </script>
@endpush
@endsection

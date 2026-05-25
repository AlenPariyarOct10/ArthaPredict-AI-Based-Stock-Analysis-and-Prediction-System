@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border p-6 flex flex-col justify-between">
            <h3 class="text-lg font-medium text-muted-foreground">Total Users</h3>
            <p class="text-3xl font-bold mt-2">{{ $usersCount }}</p>
        </div>
        <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border p-6 flex flex-col justify-between">
            <h3 class="text-lg font-medium text-muted-foreground">Monitored Stocks</h3>
            <p class="text-3xl font-bold mt-2">{{ $stocksCount }}</p>
        </div>
        <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border p-6 flex flex-col justify-between">
            <h3 class="text-lg font-medium text-muted-foreground">Pending Feedback</h3>
            <p class="text-3xl font-bold mt-2">{{ $pendingFeedbackCount }}</p>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl relative" role="alert">
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

                <form action="{{ route('admin.train') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="stock_id" class="block text-sm font-medium text-foreground mb-2">Select Stock Symbol</label>
                        <select id="stock_id" name="stock_id" required
                                class="w-full bg-background border border-border text-foreground text-sm rounded-lg focus:ring-primary focus:border-primary block p-2.5 dark:border-border dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary dark:focus:border-primary">
                            <option value="" disabled selected>Choose a stock to train...</option>
                            @foreach($stocks as $stock)
                                <option value="{{ $stock->id }}">{{ $stock->symbol }} - {{ $stock->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <button type="submit" 
                            class="w-full text-white gradient-accent hover:opacity-90 focus:ring-4 focus:outline-none focus:ring-emerald-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:focus:ring-emerald-800 transition shadow-sm">
                        Initiate Training
                    </button>
                </form>
            </div>
            
            <div class="mt-8 border-t border-border/50 pt-4 text-xs text-muted-foreground flex items-center justify-between">
                <span>Status Polling: <span class="text-emerald-500 font-bold">Active</span></span>
                <span class="px-2 py-0.5 bg-muted rounded">database queue</span>
            </div>
        </div>

        <!-- Right Side: Job History and Realtime Polling -->
        <div class="lg:col-span-2 bg-card shadow-sm rounded-xl border border-border p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold">Recent Training History</h2>
                <button onclick="window.location.reload()" class="p-1 hover:bg-muted rounded-lg text-muted-foreground transition" title="Refresh Dashboard">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89M9 11l3-3 3 3m-3-3v12"/>
                    </svg>
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
                                    <div>{{ $job->stock->symbol }}</div>
                                    <div class="text-xs text-muted-foreground font-normal">{{ $job->stock->name }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">
                                    <div>{{ $job->updated_at->diffForHumans() }}</div>
                                    <div class="text-[10px] text-muted-foreground/60">{{ $job->created_at->format('Y-m-d H:i:s') }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($job->status === 'completed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
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
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                            <svg class="animate-spin mr-1 h-3 w-3 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Processing
                                        </span>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
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
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
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
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                        <svg class="animate-spin mr-1 h-3 w-3 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing
                    </span>
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
        fetch('{{ route('admin.train.status') }}')
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
});
</script>
@endsection

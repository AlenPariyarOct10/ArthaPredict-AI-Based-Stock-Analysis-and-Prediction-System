@extends('layouts.app')

@section('content')
    @if(session('success'))
        <div
            class="mb-6 rounded-lg border border-green-200 dark:border-green-800 bg-blue-50 dark:bg-blue-900/20 px-4 py-3 text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div
            class="mb-6 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <!-- Header Section with Optimized Layout -->
    <div class="mb-6">
        <!-- Title & Exchange Badge -->
        <div class="mb-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:gap-3 gap-2">
                <h3 class="text-2xl sm:text-3xl font-bold text-gray-700 dark:text-gray-200">
                    {{ $stock->name }} ({{ $stock->symbol }})
                </h3>
                <span class="inline-block w-fit text-sm px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium">
                    {{ $stock->exchange }}
                </span>
            </div>
            <!-- <p class="mt-2 text-gray-500 dark:text-gray-400 text-sm sm:text-base">{{ $stock->sector }}</p> -->
        </div>

        <!-- Action Buttons Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2 sm:gap-2">
            <!-- Export Data Button -->
            <a href="{{ route('stocks.export', $stock->symbol) }}"
               class="px-3 sm:px-4 py-2 bg-purple-50 dark:bg-purple-900 border border-purple-200 dark:border-purple-700 text-purple-700 dark:text-purple-100 shadow-sm rounded-lg hover:bg-purple-100 dark:hover:bg-purple-800 transition flex items-center justify-center sm:justify-start gap-2 text-sm sm:text-base font-medium">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <span class="truncate">Export Data</span>
            </a>

            <a href="{{ route('stocks.analysis_report', $stock->symbol) }}"
               class="px-3 sm:px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 text-emerald-700 dark:text-emerald-200 shadow-sm rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900 transition flex items-center justify-center sm:justify-start gap-2 text-sm sm:text-base font-medium">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2zM12 3v5h5"></path>
                </svg>
                <span class="truncate">Analysis PDF</span>
            </a>

            <!-- Calc Trend Button -->
            <form action="{{ route('stocks.run_ma', $stock->symbol) }}" method="POST" class="contents">
                @csrf
                <button type="submit"
                        class="px-3 sm:px-4 py-2 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-100 shadow-sm rounded-lg hover:bg-blue-100 dark:hover:bg-blue-800 transition flex items-center justify-center sm:justify-start gap-2 text-sm sm:text-base font-medium">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                    <span class="truncate">Calc Trend</span>
                </button>
            </form>

            <!-- Predict Button -->
            <form action="{{ route('stocks.predict', $stock->symbol) }}" method="POST" onsubmit="showPredictLoading(event)" class="contents">
                @csrf
                <button type="submit" id="predict-btn"
                        class="px-3 sm:px-4 py-2 bg-blue-50 dark:bg-blue-900/40 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 shadow-sm rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900 transition flex items-center justify-center sm:justify-start gap-2 text-sm sm:text-base font-medium">
                    <svg id="predict-btn-icon" class="w-5 h-5 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    <div>
                        <span id="predict-btn-text" class="truncate">Predict</span>
                    </div>
                </button>
            </form>

            <!-- Watchlist Button -->
            <form action="{{ route('watchlist.toggle') }}" method="POST" class="contents">
                @csrf
                <input type="hidden" name="stock_id" value="{{ $stock->id }}">
                <button type="submit"
                        class="px-3 sm:px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 shadow-sm rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition flex items-center justify-center sm:justify-start gap-2 text-sm sm:text-base font-medium">
                    <svg class="w-5 h-5 flex-shrink-0 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                        </path>
                    </svg>
                    <span class="truncate">{{ $isInWatchlist ? 'Remove' : 'Watchlist' }}</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Key Metrics Snippet -->
    <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
        @php
            $latest = $historicalData->last();
        @endphp
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-sm text-gray-500 dark:text-gray-400">Current Price</div>
            <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Rs. {{ $latest ? number_format($latest->close, 2) : 'N/A' }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-sm text-gray-500 dark:text-gray-400">Day High</div>
            <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Rs. {{ $latest ? number_format($latest->high, 2) : 'N/A' }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-sm text-gray-500 dark:text-gray-400">Day Low</div>
            <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Rs. {{ $latest ? number_format($latest->low, 2) : 'N/A' }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-sm text-gray-500 dark:text-gray-400">Volume</div>
            <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ $latest ? number_format($latest->volume / 1000000, 2) : 'N/A' }}M
            </div>
        </div>
    </div>

    <div class="mt-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-3 mb-4">
                <h4 class="text-xl font-semibold text-gray-800 dark:text-white">Price History & Predictions</h4>
                <div class="flex flex-wrap gap-2">
                    <div class="flex gap-2 mr-2">
                        <button type="button" data-chart-scope="universal" aria-pressed="true"
                                class="chart-scope-btn px-3 py-1 text-sm rounded-md transition font-medium bg-blue-600 text-white">
                            General
                        </button>
                        <button type="button" data-chart-scope="individual" aria-pressed="true"
                                class="chart-scope-btn px-3 py-1 text-sm rounded-md transition font-medium bg-purple-600 text-white">
                            Individual
                        </button>
                    </div>
                    <button type="button" data-range="1M"
                            class="chart-range-btn px-3 py-1 text-sm bg-blue-50 text-blue-600 dark:bg-blue-900 hover:bg-blue-100 rounded-md transition font-medium">1M</button>
                    <button type="button" data-range="3M"
                            class="chart-range-btn px-3 py-1 text-sm bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 rounded-md transition">3M</button>
                    <button type="button" data-range="1Y"
                            class="chart-range-btn px-3 py-1 text-sm bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 rounded-md transition">1Y</button>
                </div>
            </div>
            <div id="prediction-chart-grid" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div data-chart-panel="universal" class="rounded-xl border border-blue-100 dark:border-blue-900/50 p-4">
                    <div class="mb-3">
                        <h5 class="font-bold text-gray-800 dark:text-white">General Model Predictions</h5>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Universal models trained across all eligible stocks.</p>
                    </div>
                    <div id="general-stock-chart" class="w-full h-96"></div>
                </div>
                <div data-chart-panel="individual" class="rounded-xl border border-purple-100 dark:border-purple-900/50 p-4">
                    <div class="mb-3">
                        <h5 class="font-bold text-gray-800 dark:text-white">Individual Model Predictions</h5>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Stock-specific models trained only with {{ $stock->symbol }} history.</p>
                    </div>
                    <div id="individual-stock-chart" class="w-full h-96"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-xl font-semibold text-gray-800 dark:text-white">Trend Snapshot (SMA/EMA)</h4>
                @if($trendImage)
                    <a href="{{ $trendImage }}" target="_blank"
                       class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">Open Image</a>
                @endif
            </div>

            @if($trendImage)
                <img src="{{ $trendImage }}" alt="{{ $stock->symbol }} SMA EMA trend"
                     class="w-full rounded-lg border border-gray-200 dark:border-gray-700">
            @else
                <div
                    class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center text-gray-500 dark:text-gray-400">
                    Click `Calc Trend (SMA/EMA)` to generate the latest trend chart for this stock.
                </div>
            @endif
        </div>
    </div>

    <!-- AI Forecast Predictions -->
    <div class="mt-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
            <h4 class="text-xl font-semibold text-gray-800 dark:text-white">AI Forecast Predictions</h4>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-ai-scope-toggle="universal" aria-pressed="true"
                        class="ai-scope-toggle px-3 py-1.5 text-xs rounded-md font-medium bg-blue-600 text-white">
                    General: Expanded
                </button>
                <button type="button" data-ai-scope-toggle="individual" aria-pressed="true"
                        class="ai-scope-toggle px-3 py-1.5 text-xs rounded-md font-medium bg-purple-600 text-white">
                    Individual: Expanded
                </button>
                @if($latest)
                    <span class="text-sm text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-3.5 py-1 rounded-full font-medium">
                        Latest Price Date: {{ \Carbon\Carbon::parse($latest->date)->format('M d, Y') }}
                    </span>
                @endif
            </div>
        </div>

        @if($predictions->count())
            @php
                $groupedByDate = $predictions->groupBy(function($p) {
                    return \Carbon\Carbon::parse($p->target_date)->format('Y-m-d');
                });
                $latestDate = $latest ? \Carbon\Carbon::parse($latest->date)->startOfDay() : now()->startOfDay();
            @endphp

            @if($modelComparisons->isNotEmpty())
                <div class="mb-6 grid grid-cols-1 xl:grid-cols-2 gap-5">
                    @foreach(['universal' => 'General Model', 'individual' => 'Individual Model'] as $scope => $scopeLabel)
                        @php
                            $comparison = $modelComparisons->get($scope, collect());
                            $recommended = $recommendedModels->get($scope);
                            $bestOverall = $bestOverallModels->get($scope);
                        @endphp
                        <details open data-performance-scope="{{ $scope }}"
                                 class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                            <summary class="px-5 py-4 cursor-pointer select-none flex items-center justify-between border-b border-gray-100 dark:border-gray-700">
                                <div>
                                    <h5 class="font-bold text-gray-800 dark:text-white">{{ $scopeLabel }} Performance</h5>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $scope === 'universal' ? 'Trained across all eligible stocks.' : 'Trained only with '.$stock->symbol.' history.' }}
                                    </p>
                                </div>
                                <span class="text-xs font-medium text-gray-500">Show / hide</span>
                            </summary>
                            @if($comparison->isNotEmpty())
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs">
                                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Model</th>
                                                <th class="px-3 py-2 text-right">RMSE</th>
                                                <th class="px-3 py-2 text-right">MAE</th>
                                                <th class="px-3 py-2 text-right">MAPE</th>
                                                <th class="px-3 py-2 text-right">R²</th>
                                                <th class="px-3 py-2 text-right">Direction</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @foreach($comparison->sortBy('mape') as $model)
                                                <tr>
                                                    <td class="px-3 py-2 font-semibold">
                                                        {{ ucwords(str_replace('_', ' ', $model['model_type'])) }}
                                                        @if($model['benchmark'])
                                                            <span class="text-[9px] rounded bg-amber-100 text-amber-700 px-1 py-0.5">Benchmark</span>
                                                        @endif
                                                        @if($recommended && $recommended['model_type'] === $model['model_type'])
                                                            <span class="text-[9px] rounded bg-blue-100 text-blue-700 px-1 py-0.5">Recommended</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-right">{{ number_format($model['rmse'], 2) }}</td>
                                                    <td class="px-3 py-2 text-right">{{ number_format($model['mae'], 2) }}</td>
                                                    <td class="px-3 py-2 text-right">{{ number_format($model['mape'], 2) }}%</td>
                                                    <td class="px-3 py-2 text-right">{{ number_format($model['r2'], 3) }}</td>
                                                    <td class="px-3 py-2 text-right">{{ number_format($model['directional_accuracy'], 2) }}%</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($recommended)
                                    <div class="px-5 py-3 bg-blue-50 dark:bg-blue-900/20 text-xs text-gray-700 dark:text-gray-300">
                                        Recommended learned model: <strong>{{ ucwords(str_replace('_', ' ', $recommended['model_type'])) }}</strong>.
                                        @if($bestOverall && $bestOverall['benchmark'])
                                            Moving Average remains the lowest-error benchmark.
                                        @endif
                                    </div>
                                @endif
                            @else
                                <div class="p-5 text-sm text-gray-500 dark:text-gray-400">
                                    No {{ strtolower($scopeLabel) }} metrics are available. Train this scope to enable comparison.
                                </div>
                            @endif
                        </details>
                    @endforeach
                </div>
                <p class="mb-5 text-xs text-gray-500 dark:text-gray-400">
                    Metrics use chronological held-out test data. General and individual results are evaluated independently.
                </p>
            @endif

            <div class="space-y-6">
                @foreach($groupedByDate as $date => $datePredictions)
                    @php
                        $parsedDate = \Carbon\Carbon::parse($date);
                        $daysFromLatest = (int) $latestDate->diffInDays($parsedDate, false);
                        if ($daysFromLatest <= 1) {
                            $label = 'Short-term (1 Day)';
                            $labelColor = 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
                        } elseif ($daysFromLatest <= 7) {
                            $label = 'Mid-term (1 Week)';
                            $labelColor = 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
                        } else {
                            $label = 'Long-term (1 Month)';
                            $labelColor = 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
                        }
                    @endphp

                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                        <!-- Date Header -->
                        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-800/80">
                            <div class="flex items-center space-x-3">
                                <div class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-200">
                                    <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ $parsedDate->format('l, M d, Y') }}
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $labelColor }}">{{ $label }}</span>
                            </div>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $datePredictions->count() }} model{{ $datePredictions->count() > 1 ? 's' : '' }}</span>
                        </div>

                        <div class="p-4 space-y-4">
                            @foreach(['universal' => 'General Model Predictions', 'individual' => 'Individual Model Predictions'] as $scope => $scopeLabel)
                                @php
                                    $scopePredictions = $datePredictions->where('model_scope', $scope);
                                @endphp
                                <details open data-prediction-scope="{{ $scope }}"
                                         class="rounded-lg border {{ $scope === 'universal' ? 'border-blue-200 dark:border-blue-800' : 'border-purple-200 dark:border-purple-800' }} overflow-hidden">
                                    <summary class="cursor-pointer select-none px-4 py-3 flex items-center justify-between {{ $scope === 'universal' ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-purple-50 dark:bg-purple-900/20' }}">
                                        <span class="font-bold text-sm text-gray-700 dark:text-gray-200">{{ $scopeLabel }}</span>
                                        <span class="text-xs text-gray-500">{{ $scopePredictions->count() }} algorithm{{ $scopePredictions->count() === 1 ? '' : 's' }} · Show / hide</span>
                                    </summary>
                                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 p-4">
                                        @foreach(['lstm', 'xgboost', 'random_forest', 'moving_average'] as $modelType)
                                            @php
                                                $prediction = $scopePredictions->firstWhere('model_type', $modelType);
                                                $currentPrice = $latest ? $latest->close : null;
                                                $change = $prediction && $currentPrice
                                                    ? (($prediction->predicted_price - $currentPrice) / $currentPrice) * 100
                                                    : null;
                                            @endphp
                                            <div class="rounded-lg border {{ $scope === 'universal' ? 'border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-900/10' : 'border-purple-200 dark:border-purple-800 bg-purple-50/50 dark:bg-purple-900/10' }} p-4">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                                        {{ str_replace('_', ' ', $modelType) }}
                                                        @if($modelType === 'moving_average')
                                                            <span class="normal-case text-[9px] text-amber-700">Benchmark</span>
                                                        @endif
                                                    </span>
                                                    @if($change !== null)
                                                        <span class="text-xs font-semibold {{ $change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                            {{ $change >= 0 ? '+' : '' }}{{ number_format($change, 2) }}%
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($prediction)
                                                    <div class="mt-2 text-2xl font-extrabold {{ $scope === 'universal' ? 'text-blue-600 dark:text-blue-400' : 'text-purple-600 dark:text-purple-400' }}">
                                                        Rs. {{ number_format($prediction->predicted_price, 2) }}
                                                    </div>
                                                    <div class="mt-3 text-xs space-y-1">
                                                        @foreach(collect($prediction->additional_metrics)->only(['rmse', 'mae', 'mape', 'r2', 'directional_accuracy']) as $key => $value)
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-500">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                                                <span class="font-medium">
                                                                    {{ in_array($key, ['mape', 'directional_accuracy']) ? number_format((float) $value, 2).'%' : number_format((float) $value, $key === 'r2' ? 3 : 2) }}
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                                        Not available. Train the {{ strtolower($scopeLabel) }} model.
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800">
                <svg class="mx-auto h-10 w-10 text-gray-400 dark:text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <p class="font-medium">No predictions available for this week</p>
                <p class="text-sm mt-1">Predictions will appear here when their target dates fall within the current week. Ask an admin to train the forecasting models.</p>
            </div>
            @endforelse
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const initialHistoryData = @json($historicalData);
            const predictionData = @json($predictions);
            const chartContainers = {
                universal: document.querySelector("#general-stock-chart"),
                individual: document.querySelector("#individual-stock-chart")
            };
            const chartGrid = document.querySelector("#prediction-chart-grid");
            const rangeButtons = document.querySelectorAll('.chart-range-btn');
            const chartScopeButtons = document.querySelectorAll('.chart-scope-btn');
            const aiScopeButtons = document.querySelectorAll('.ai-scope-toggle');
            const chartApiUrl = @json(route('stocks.api.chart', $stock->symbol));

            function buildActualSeries(historyData) {
                return historyData
                    .filter(item => item.close !== null)
                    .map(item => ({
                        x: new Date(item.date).getTime(),
                        y: Number(parseFloat(item.close).toFixed(2))
                    }));
            }

            const modelPresentation = {
                lstm: { name: 'LSTM', color: '#8b5cf6' },
                xgboost: { name: 'XGBoost', color: '#f97316' },
                random_forest: { name: 'Random Forest', color: '#10b981' },
                moving_average: { name: 'Moving Average Benchmark', color: '#eab308' }
            };
            function buildPredictionSeries(actualPrices, modelScope) {
                if (!actualPrices.length) {
                    return [];
                }

                const lastActual = actualPrices[actualPrices.length - 1];
                return Object.entries(modelPresentation).map(([modelType, presentation]) => {
                    const points = predictionData
                        .filter(p => p.model_type === modelType && p.model_scope === modelScope)
                        .sort((a, b) => new Date(a.target_date) - new Date(b.target_date))
                        .map(p => ({
                            x: new Date(p.target_date).getTime(),
                            y: Number(parseFloat(p.predicted_price).toFixed(2))
                        }));

                    return {
                        name: presentation.name,
                        type: 'line',
                        data: points.length ? [lastActual, ...points] : []
                    };
                });
            }

            let actualPrices = buildActualSeries(initialHistoryData);

            if (!actualPrices.length) {
                Object.values(chartContainers).forEach(chartContainer => {
                    chartContainer.innerHTML = `
                        <div class="h-full flex items-center justify-center text-center text-gray-500 dark:text-gray-400">
                            <div>
                                <div class="text-lg font-medium">No price history available</div>
                                <div class="mt-1 text-sm">Stock price records are required to render this chart.</div>
                            </div>
                        </div>
                    `;
                });
                return;
            }

            function chartOptions(modelScope) {
                return {
                    series: [
                        { name: 'Actual Price', type: 'area', data: actualPrices },
                        ...buildPredictionSeries(actualPrices, modelScope)
                    ],
                    chart: {
                        height: 400,
                        type: 'line',
                        background: 'transparent',
                        toolbar: { show: true },
                        fontFamily: 'Inter, sans-serif'
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 2,
                        dashArray: [0, 5, 5, 5, 5]
                    },
                    fill: {
                        type: ['gradient', 'solid', 'solid', 'solid', 'solid'],
                        opacity: [0.3, 1, 1, 1, 1],
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.4,
                            opacityTo: 0.05,
                            stops: [0, 100]
                        }
                    },
                    colors: [
                        '#3b82f6',
                        ...Object.values(modelPresentation).map(model => model.color)
                    ],
                    xaxis: { type: 'datetime' },
                    theme: {
                        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    },
                    legend: { position: 'top' },
                    noData: {
                        text: modelScope === 'individual'
                            ? 'Train individual models to display predictions.'
                            : 'No general model predictions available.'
                    }
                };
            }

            const charts = {
                universal: new ApexCharts(
                    chartContainers.universal,
                    chartOptions('universal')
                ),
                individual: new ApexCharts(
                    chartContainers.individual,
                    chartOptions('individual')
                )
            };
            Object.values(charts).forEach(chart => chart.render());

            chartScopeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const scope = button.dataset.chartScope;
                    const panel = document.querySelector(`[data-chart-panel="${scope}"]`);
                    const nextVisible = panel.classList.contains('hidden');
                    panel.classList.toggle('hidden', !nextVisible);
                    button.setAttribute('aria-pressed', String(nextVisible));
                    button.classList.toggle('opacity-40', !nextVisible);
                    button.textContent = scope === 'universal'
                        ? `General${nextVisible ? '' : ' (Hidden)'}`
                        : `Individual${nextVisible ? '' : ' (Hidden)'}`;

                    const visiblePanels = document.querySelectorAll('[data-chart-panel]:not(.hidden)').length;
                    chartGrid.classList.toggle('xl:grid-cols-2', visiblePanels > 1);
                    chartGrid.classList.toggle('xl:grid-cols-1', visiblePanels === 1);
                    window.setTimeout(() => {
                        window.dispatchEvent(new Event('resize'));
                    }, 50);
                });
            });

            aiScopeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const scope = button.dataset.aiScopeToggle;
                    const sections = document.querySelectorAll(
                        `[data-performance-scope="${scope}"], [data-prediction-scope="${scope}"]`
                    );
                    const shouldOpen = !Array.from(sections).every(section => section.open);
                    sections.forEach(section => {
                        section.open = shouldOpen;
                    });
                    button.setAttribute('aria-pressed', String(shouldOpen));
                    button.classList.toggle('opacity-50', !shouldOpen);
                    button.textContent = `${scope === 'universal' ? 'General' : 'Individual'}: ${shouldOpen ? 'Expanded' : 'Collapsed'}`;
                });
            });

            async function updateChartRange(range) {
                rangeButtons.forEach(button => {
                    const active = button.dataset.range === range;
                    button.className = active
                        ? 'chart-range-btn px-3 py-1 text-sm bg-blue-50 text-blue-600 dark:bg-blue-900 hover:bg-blue-100 rounded-md transition font-medium'
                        : 'chart-range-btn px-3 py-1 text-sm bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 rounded-md transition';
                });

                try {
                    const response = await fetch(`${chartApiUrl}?range=${encodeURIComponent(range)}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const historyData = await response.json();
                    const nextActualPrices = buildActualSeries(historyData);

                    Object.entries(charts).forEach(([modelScope, chart]) => {
                        chart.updateSeries([
                            { name: 'Actual Price', type: 'area', data: nextActualPrices },
                            ...buildPredictionSeries(nextActualPrices, modelScope)
                        ]);
                    });
                } catch (error) {
                    console.error('Failed to update chart range', error);
                }
            }

            rangeButtons.forEach(button => {
                button.addEventListener('click', () => updateChartRange(button.dataset.range));
            });

            // Update Chart Theme automatically on Dark mode toggle
            const htmlObserver = new MutationObserver(mutations => {
                const isDark = document.documentElement.classList.contains('dark');
                Object.values(charts).forEach(chart => {
                    chart.updateOptions({ theme: { mode: isDark ? 'dark' : 'light' } });
                });
            });
            htmlObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        });

        function showPredictLoading(event) {
            event.preventDefault();

            const form = event.target;
            const btn = document.getElementById('predict-btn');
            const text = document.getElementById('predict-btn-text');
            const icon = document.getElementById('predict-btn-icon');

            if (btn) btn.disabled = true;
            if (text) text.innerText = 'Predicting...';
            if (icon) {
                icon.classList.add('animate-spin');
                // Replace inner HTML with standard spinner path
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>';
            }

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to calculate predictions.'));
                        resetPredictButton();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred during prediction.');
                    resetPredictButton();
                });
        }

        function resetPredictButton() {
            const btn = document.getElementById('predict-btn');
            const text = document.getElementById('predict-btn-text');
            const icon = document.getElementById('predict-btn-icon');

            if (btn) btn.disabled = false;
            if (text) text.innerText = 'Predict';
            if (icon) {
                icon.classList.remove('animate-spin');
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>';
            }
        }
    </script>
@endpush

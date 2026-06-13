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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2">
            <!-- Export Data Button -->
            <a href="{{ route('stocks.export', $stock->symbol) }}"
               class="px-3 sm:px-4 py-2 bg-purple-50 dark:bg-purple-900 border border-purple-200 dark:border-purple-700 text-purple-700 dark:text-purple-100 shadow-sm rounded-lg hover:bg-purple-100 dark:hover:bg-purple-800 transition flex items-center justify-center sm:justify-start gap-2 text-sm sm:text-base font-medium">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <span class="truncate">Export Data</span>
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
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-xl font-semibold text-gray-800 dark:text-white">Price History & Predictions</h4>
                <div class="flex space-x-2">
                    <button type="button" data-range="1M"
                            class="chart-range-btn px-3 py-1 text-sm bg-blue-50 text-blue-600 dark:bg-blue-900 hover:bg-blue-100 rounded-md transition font-medium">1M</button>
                    <button type="button" data-range="3M"
                            class="chart-range-btn px-3 py-1 text-sm bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 rounded-md transition">3M</button>
                    <button type="button" data-range="1Y"
                            class="chart-range-btn px-3 py-1 text-sm bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 rounded-md transition">1Y</button>
                </div>
            </div>
            <div id="stock-chart" class="w-full h-96"></div>
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
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-xl font-semibold text-gray-800 dark:text-white">AI Forecast Predictions</h4>
            @if($latest)
                <span class="text-sm text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-3.5 py-1 rounded-full font-medium">
                    Latest Price Date: {{ \Carbon\Carbon::parse($latest->date)->format('M d, Y') }}
                </span>
            @endif
        </div>

        @if($predictions->count())
            @php
                $groupedByDate = $predictions->groupBy(function($p) {
                    return \Carbon\Carbon::parse($p->target_date)->format('Y-m-d');
                });
                $latestDate = $latest ? \Carbon\Carbon::parse($latest->date)->startOfDay() : now()->startOfDay();
            @endphp

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

                        <!-- Model Predictions for this date -->
                        <div class="grid grid-cols-1 md:grid-cols-{{ min($datePredictions->count(), 3) }} divide-y md:divide-y-0 md:divide-x divide-gray-100 dark:divide-gray-700">
                            @foreach($datePredictions as $prediction)
                                <div class="p-5 relative group">
                                    <div class="absolute inset-0 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 opacity-0 group-hover:opacity-100 transition duration-300 z-0"></div>
                                    <div class="relative z-10">
                                        <div class="flex items-center justify-between mb-3">
                                            <h5 class="text-sm font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wide">{{ $prediction->model_type }}</h5>
                                            @php
                                                $latest = $historicalData->last();
                                                $currentPrice = $latest ? $latest->close : null;
                                                $change = $currentPrice ? (($prediction->predicted_price - $currentPrice) / $currentPrice) * 100 : null;
                                            @endphp
                                            @if($change !== null)
                                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $change >= 0 ? 'bg-blue-100 text-green-700 dark:bg-blue-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                                    {{ $change >= 0 ? '+' : '' }}{{ number_format($change, 2) }}%
                                                </span>
                                            @endif
                                        </div>

                                        <div class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">
                                            Rs. {{ number_format($prediction->predicted_price, 2) }}
                                        </div>

                                        @if($prediction->additional_metrics)
                                            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs space-y-1">
                                                @if(is_iterable($prediction->additional_metrics))
                                                    @foreach(collect($prediction->additional_metrics)->only(['confidence_score', 'mape', 'directional_accuracy']) as $key => $value)
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-500 dark:text-gray-400">{{ ucwords(str_replace('_', ' ', (string) $key)) }}</span>
                                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ is_scalar($value) ? $value : json_encode($value) }}</span>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
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
            const chartContainer = document.querySelector("#stock-chart");
            const rangeButtons = document.querySelectorAll('.chart-range-btn');
            const chartApiUrl = @json(route('stocks.api.chart', $stock->symbol));

            function buildActualSeries(historyData) {
                return historyData
                    .filter(item => item.close !== null)
                    .map(item => ({
                        x: new Date(item.date).getTime(),
                        y: Number(parseFloat(item.close).toFixed(2))
                    }));
            }

            function buildPredictionSeries(actualPrices) {
                if (!actualPrices.length || !predictionData.length) {
                    return [];
                }

                const lastActual = actualPrices[actualPrices.length - 1];
                const predPoints = [lastActual];

                predictionData.forEach(p => {
                    predPoints.push({
                        x: new Date(p.target_date).getTime(),
                        y: Number(parseFloat(p.predicted_price).toFixed(2))
                    });
                });

                return predPoints;
            }

            let actualPrices = buildActualSeries(initialHistoryData);
            let predPoints = buildPredictionSeries(actualPrices);

            if (!actualPrices.length) {
                chartContainer.innerHTML = `
                        <div class="h-full flex items-center justify-center text-center text-gray-500 dark:text-gray-400">
                            <div>
                                <div class="text-lg font-medium">No price history available</div>
                                <div class="mt-1 text-sm">Stock price records are required to render this chart.</div>
                            </div>
                        </div>
                    `;
                return;
            }

            var options = {
                series: [
                    {
                        name: 'Actual Price',
                        type: 'area',
                        data: actualPrices
                    },
                    {
                        name: 'Predicted Price',
                        type: 'line',
                        data: predPoints
                    }
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
                    width: [2, 3],
                    dashArray: [0, 5]
                },
                fill: {
                    type: ['gradient', 'solid'],
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                        stops: [0, 100]
                    }
                },
                colors: ['#3b82f6', '#10b981'],
                xaxis: {
                    type: 'datetime'
                },
                theme: {
                    mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                },
                legend: {
                    position: 'top'
                }
            };

            var chart = new ApexCharts(chartContainer, options);
            chart.render();

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

                    chart.updateSeries([
                        { name: 'Actual Price', type: 'area', data: nextActualPrices },
                        { name: 'Predicted Price', type: 'line', data: buildPredictionSeries(nextActualPrices) }
                    ]);
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
                chart.updateOptions({ theme: { mode: isDark ? 'dark' : 'light' } });
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
            if (text) text.innerText = 'Predict (LSTM)';
            if (icon) {
                icon.classList.remove('animate-spin');
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>';
            }
        }
    </script>
@endpush

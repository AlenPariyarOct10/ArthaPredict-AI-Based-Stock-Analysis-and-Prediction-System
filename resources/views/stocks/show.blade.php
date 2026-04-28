@extends('layouts.app')

@section('content')
@if(session('success'))
    <div class="mb-6 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-green-700 dark:text-green-300">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-6 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-red-700 dark:text-red-300">
        {{ session('error') }}
    </div>
@endif

<div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6">
    <div>
        <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-bold flex items-center">
            {{ $stock->name }} ({{ $stock->symbol }})
            <span class="ml-4 text-sm px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">{{ $stock->exchange }}</span>
        </h3>
        <p class="mt-1 text-gray-500 dark:text-gray-400">{{ $stock->sector }}</p>
    </div>
    <div class="mt-4 md:mt-0 flex space-x-2">
        <form action="{{ route('stocks.run_ma', $stock->symbol) }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-100 shadow-sm rounded-lg hover:bg-blue-100 dark:hover:bg-blue-800 transition flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                Calc Trend (SMA/EMA)
            </button>
        </form>

        <form action="{{ route('watchlist.toggle') }}" method="POST">
            @csrf
            <input type="hidden" name="stock_id" value="{{ $stock->id }}">
            <button type="submit" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 shadow-sm rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                {{ $isInWatchlist ? 'Remove from Watchlist' : 'Add to Watchlist' }}
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
        <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">${{ $latest ? number_format($latest->close, 2) : 'N/A' }}</div>
    </div>
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="text-sm text-gray-500 dark:text-gray-400">Day High</div>
        <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">${{ $latest ? number_format($latest->high, 2) : 'N/A' }}</div>
    </div>
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="text-sm text-gray-500 dark:text-gray-400">Day Low</div>
        <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">${{ $latest ? number_format($latest->low, 2) : 'N/A' }}</div>
    </div>
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="text-sm text-gray-500 dark:text-gray-400">Volume</div>
        <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $latest ? number_format($latest->volume / 1000000, 2) : 'N/A' }}M</div>
    </div>
</div>

<div class="mt-8">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="flex justify-between items-center mb-4">
            <h4 class="text-xl font-semibold text-gray-800 dark:text-white">Price History & Predictions</h4>
            <div class="flex space-x-2">
                <button type="button" data-range="1M" class="chart-range-btn px-3 py-1 text-sm bg-blue-50 text-blue-600 dark:bg-blue-900 hover:bg-blue-100 rounded-md transition font-medium">1M</button>
                <button type="button" data-range="3M" class="chart-range-btn px-3 py-1 text-sm bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 rounded-md transition">3M</button>
                <button type="button" data-range="1Y" class="chart-range-btn px-3 py-1 text-sm bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 rounded-md transition">1Y</button>
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
                <a href="{{ $trendImage }}" target="_blank" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">Open Image</a>
            @endif
        </div>

        @if($trendImage)
            <img src="{{ $trendImage }}" alt="{{ $stock->symbol }} SMA EMA trend" class="w-full rounded-lg border border-gray-200 dark:border-gray-700">
        @else
            <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center text-gray-500 dark:text-gray-400">
                Click `Calc Trend (SMA/EMA)` to generate the latest trend chart for this stock.
            </div>
        @endif
    </div>
</div>

<!-- Predictions Data Cards -->
<div class="mt-8">
    <h4 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">AI Forecast Models</h4>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($predictions as $prediction)
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 opacity-0 group-hover:opacity-100 transition duration-300 z-0"></div>
            <div class="relative z-10">
                <h5 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $prediction->model_type }}</h5>
                <p class="text-sm text-gray-500 dark:text-gray-400">Target Date: {{ $prediction->target_date }}</p>
                
                <div class="mt-4 flex items-end">
                    <div class="text-3xl font-extrabold text-blue-600 dark:text-blue-400">
                        ${{ number_format($prediction->predicted_price, 2) }}
                    </div>
                </div>
                
                @if($prediction->additional_metrics)
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 text-sm">
                    @foreach($prediction->additional_metrics as $key => $value)
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="md:col-span-3 rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800">
            No predictions are available for this stock yet. Ask an admin to train the forecasting models first.
        </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const initialHistoryData = @json($historicalData);
        const predictionData = @json($predictions);
        const chartContainer = document.querySelector("#stock-chart");
        const rangeButtons = document.querySelectorAll('.chart-range-btn');
        const chartApiUrl = @json(route('api.stocks.chart', $stock->symbol));
        
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
</script>
@endpush

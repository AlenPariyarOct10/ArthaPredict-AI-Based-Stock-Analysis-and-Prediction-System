@extends('layouts.app')

@section('content')
<div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6">
    <div>
        <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-bold flex items-center">
            {{ $stock->name }} ({{ $stock->symbol }})
            <span class="ml-4 text-sm px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">{{ $stock->exchange }}</span>
        </h3>
        <p class="mt-1 text-gray-500 dark:text-gray-400">{{ $stock->sector }}</p>
    </div>
    <div class="mt-4 md:mt-0">
        <form action="{{ route('watchlist.toggle') }}" method="POST">
            @csrf
            <input type="hidden" name="stock_id" value="{{ $stock->id }}">
            <button type="submit" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 shadow-sm rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                Toggle Watchlist
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
                <button class="px-3 py-1 text-sm bg-blue-50 text-blue-600 dark:bg-blue-900 hover:bg-blue-100 rounded-md transition font-medium">1M</button>
                <button class="px-3 py-1 text-sm bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 rounded-md transition">3M</button>
                <button class="px-3 py-1 text-sm bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 rounded-md transition">1Y</button>
            </div>
        </div>
        <div id="stock-chart" class="w-full h-96"></div>
    </div>
</div>

<!-- Predictions Data Cards -->
<div class="mt-8">
    <h4 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">AI Forecast Models</h4>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($predictions as $prediction)
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
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const historyData = @json($historicalData);
        const predictionData = @json($predictions);
        
        let actualPrices = historyData.map(item => {
            return {
                x: new Date(item.date).getTime(),
                y: parseFloat(item.close).toFixed(2)
            };
        });
        
        // Add prediction point connecting from last actual price
        let lastActual = actualPrices[actualPrices.length - 1];
        let predPoints = [lastActual];
        
        predictionData.forEach(p => {
            predPoints.push({
                x: new Date(p.target_date).getTime(),
                y: parseFloat(p.predicted_price).toFixed(2)
            });
        });

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

        var chart = new ApexCharts(document.querySelector("#stock-chart"), options);
        chart.render();
        
        // Update Chart Theme automatically on Dark mode toggle
        const htmlObserver = new MutationObserver(mutations => {
             const isDark = document.documentElement.classList.contains('dark');
             chart.updateOptions({ theme: { mode: isDark ? 'dark' : 'light' } });
        });
        htmlObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    });
</script>
@endpush

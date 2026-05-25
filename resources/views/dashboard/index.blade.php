@extends('layouts.app')

@section('content')
    <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Dashboard Overview</h3>
    <p class="mt-1 text-gray-500 dark:text-gray-400">Welcome to ArthaPredict Insights.</p>

    <div class="mt-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($stocks as $stock)
                @php
                    $latest = $stock->prices->last();
                    $previous = $stock->prices->count() > 1 ? $stock->prices[$stock->prices->count() - 2] : null;
                    $change = 0;
                    $percent = 0;
                    if ($latest && $previous) {
                        $change = $latest->close - $previous->close;
                        $percent = ($change / $previous->close) * 100;
                    }
                    $isPositive = $change >= 0;
                @endphp
                <a href="{{ route('stocks.show', $stock->symbol) }}"
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col transform hover:-translate-y-1 transition duration-300 group">
                    <div class="flex items-center justify-between">
                        <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-200 group-hover:text-blue-500 transition">
                            {{ $stock->symbol }}</h4>
                        <span
                            class="px-2 py-1 text-xs rounded-full {{ $isPositive ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                            {{ $isPositive ? '+' : '' }}{{ number_format($percent, 2) }}%
                        </span>
                    </div>
                    <div class="mt-4 text-3xl font-bold text-gray-800 dark:text-white">
                        Rs. {{ $latest ? number_format($latest->close, 2) : 'N/A' }}
                    </div>
                    <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Vol: {{ $latest ? number_format($latest->volume / 1000000, 2) . 'M' : 'N/A' }}
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Market Trends Chart -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 lg:col-span-2">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h4 class="text-xl font-semibold text-gray-800 dark:text-white">Market Trend Overview</h4>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $chartStock?->symbol ? "Showing {$chartStock->symbol}" : 'No stock data available' }}
                </span>
            </div>
            <div id="market-chart" class="w-full h-80"></div>
        </div>

        <!-- AI Predictions Card -->
        <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-xl shadow-lg p-6 text-white">
            <h4 class="text-xl font-semibold mb-4">AI Top Pick</h4>
            @php
                $topPickSymbol = $aiTopPick?->stock?->symbol;
                $topPickTarget = $aiTopPick?->target_date ? \Carbon\Carbon::parse($aiTopPick->target_date)->format('M d, Y') : null;
            @endphp
            <div class="mt-4">
                <span class="text-sm font-medium uppercase tracking-wider text-indigo-200">Symbol</span>
                <div class="text-4xl font-bold mt-1">{{ $topPickSymbol ?? 'N/A' }}</div>
            </div>
            <div class="mt-6">
                <span class="text-sm font-medium text-indigo-200">
                    {{ $aiTopPick ? "Predicted Price ({$aiTopPick->model_type})" : 'Predicted Trend' }}
                </span>
                <div class="flex items-end mt-1">
                    <span class="text-3xl font-bold text-green-300">
                        {{ $aiTopPick ? 'Rs.' . number_format((float) $aiTopPick->predicted_price, 2) : 'Pending' }}
                    </span>
                    @if ($topPickTarget)
                        <span class="ml-2 mb-1 text-sm text-indigo-100 border-b border-indigo-300">{{ $topPickTarget }}</span>
                    @endif
                </div>
            </div>
            <div class="mt-8">
                <a href="{{ $topPickSymbol ? route('stocks.show', $topPickSymbol) : route('stocks.index') }}"
                    class="inline-block bg-white text-indigo-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition shadow-sm">
                    View Full Analysis
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Prepare data from PHP variable
            const rawData = @json($marketTrend);
            const chartContainer = document.querySelector("#market-chart");

            let seriesData = rawData
                .filter(item => item.open !== null && item.high !== null && item.low !== null && item.close !== null)
                .map(item => ({
                    x: new Date(item.date).getTime(),
                    y: [Number(item.open), Number(item.high), Number(item.low), Number(item.close)]
                }));

            if (!seriesData.length) {
                chartContainer.innerHTML = `
                    <div class="h-full flex items-center justify-center text-center text-gray-500 dark:text-gray-400">
                        <div>
                            <div class="text-lg font-medium">No market trend data available</div>
                            <div class="mt-1 text-sm">Add stock price records with OHLC values to render the chart.</div>
                        </div>
                    </div>
                `;
                return;
            }

            // Add apex chart configuration
            var options = {
                series: [{
                    name: 'candle',
                    data: seriesData
                }],
                chart: {
                    type: 'candlestick',
                    height: 350,
                    toolbar: { show: false },
                    background: 'transparent',
                    fontFamily: 'Inter, sans-serif'
                },
                theme: {
                    mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                },
                xaxis: {
                    type: 'datetime'
                },
                yaxis: {
                    tooltip: { enabled: true }
                },
                grid: {
                    borderColor: '#4b5563', // gray-600
                    strokeDashArray: 3,
                }
            };

            var chart = new ApexCharts(chartContainer, options);
            chart.render();

            // Listen for dark mode toggle to update chart theme
            window.addEventListener('storage', () => {
                const isDark = localStorage.getItem('darkMode') === 'true';
                chart.updateOptions({
                    theme: { mode: isDark ? 'dark' : 'light' }
                });
            });

            // Also listen for a custom event we can dispatch from the toggle button
            document.addEventListener('alpine:initialized', () => {
                Alpine.effect(() => {
                    const isDark = Alpine.store('darkMode') /* if user sets store or watches body class */
                    // A quick hack is using a mutation observer on the HTML tag class list
                    const htmlObserver = new MutationObserver(mutations => {
                        const isDark = document.documentElement.classList.contains('dark');
                        chart.updateOptions({
                            theme: { mode: isDark ? 'dark' : 'light' }
                        });
                    });
                    htmlObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                });
            });
        });
    </script>
@endpush
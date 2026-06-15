@extends('layouts.app')

@section('content')
    <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Dashboard Overview</h3>
    <p class="mt-1 text-gray-500 dark:text-gray-400">
        Data-driven overview using stocks with the strongest usable price history.
    </p>

    <div class="mt-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($stocks as $stock)
                @php
                    $latest = $stock->prices->first();
                    $previous = $stock->prices->count() > 1 ? $stock->prices->last() : null;
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
                            class="px-2 py-1 text-xs rounded-full {{ $isPositive ? 'bg-blue-100 text-green-800 dark:bg-blue-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                            {{ $isPositive ? '+' : '' }}{{ number_format($percent, 2) }}%
                        </span>
                    </div>
                    <div class="mt-4 text-3xl font-bold text-gray-800 dark:text-white">
                        Rs. {{ $latest ? number_format($latest->close, 2) : 'N/A' }}
                    </div>
                    <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Vol: {{ $latest ? number_format($latest->volume / 1000000, 2) . 'M' : 'N/A' }}
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                        {{ number_format($stock->usable_datapoints_count) }} usable datapoints
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
                    {{ $chartStock?->symbol
                        ? "Showing {$chartStock->symbol}: " . number_format($chartStock->usable_datapoints_count) . ' usable datapoints'
                        : 'No stock data available' }}
                </span>
            </div>
            <div id="market-chart" class="w-full h-80"></div>
        </div>

@auth
        <!-- AI Predictions Card -->
        <div class="bg-primary dark:bg-primary-light shadow-lg p-6 text-white">
            <h4 class="text-xl font-semibold mb-4">AI Top Pick</h4>
            @php
                $topPickSymbol = $aiTopPick['stock']->symbol ?? null;
                $topPickTarget = !empty($aiTopPick['target_date'])
                    ? \Carbon\Carbon::parse($aiTopPick['target_date'])->format('M d, Y')
                    : null;
            @endphp
            <div class="mt-4">
                <span class="text-sm font-medium uppercase tracking-wider text-white/80">Symbol</span>
                <div class="text-4xl font-bold mt-1">{{ $topPickSymbol ?? 'N/A' }}</div>
            </div>
            <div class="mt-6">
                <span class="text-sm font-medium text-white/80">
                    {{ $aiTopPick ? '30-Day Consensus Forecast' : 'Predicted Trend' }}
                </span>
                <div class="flex items-end mt-1">
                    <span class="text-3xl font-bold text-white">
                        {{ $aiTopPick ? 'Rs.' . number_format($aiTopPick['predicted_price'], 2) : 'Pending' }}
                    </span>
                    @if ($topPickTarget)
                        <span class="ml-2 mb-1 text-sm text-white/90 border-b border-white/50">{{ $topPickTarget }}</span>
                    @endif
                </div>
            </div>
            @if($aiTopPick)
                <div class="mt-6 space-y-2 text-sm text-white/90">
                    <div class="flex justify-between gap-4">
                        <span>Projected return</span>
                        <strong>{{ $aiTopPick['projected_return'] >= 0 ? '+' : '' }}{{ number_format($aiTopPick['projected_return'], 2) }}%</strong>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span>Positive agreement</span>
                        <strong>{{ $aiTopPick['agreement_count'] }}/{{ $aiTopPick['algorithm_count'] }} algorithms</strong>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span>Forecast spread</span>
                        <strong>{{ number_format($aiTopPick['forecast_spread'], 2) }}%</strong>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span>Data strength</span>
                        <strong>{{ number_format($aiTopPick['datapoints']) }} points</strong>
                    </div>
                    <p class="pt-3 border-t border-white/20 text-xs leading-relaxed text-white/80">
                        Selected only from stocks tied for the most usable history, then ranked by four-model
                        agreement, lower forecast disagreement and projected return.
                    </p>
                </div>
            @endif
            <div class="mt-8">
                <a href="{{ $topPickSymbol ? route('stocks.show', $topPickSymbol) : route('stocks.index') }}"
                    class="inline-block bg-white text-primary px-4 py-2 font-medium hover:bg-gray-100 transition shadow-sm">
                    View Full Analysis
                </a>
            </div>
        </div>
    @else
        <div class="bg-gray-100 dark:bg-gray-800 rounded-xl p-6 text-center">
            <p class="text-gray-700 dark:text-gray-200 mb-4">Sign in to see AI predictions.</p>
            <a href="{{ route('login') }}" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition">
                Login
            </a>
        </div>
    @endauth
    </div>

    <section class="mt-10">
        <div class="mb-5">
            <h4 class="text-2xl font-semibold text-gray-800 dark:text-white">Overall System Comparative Analysis</h4>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Coverage, model performance and prediction parameters calculated from the current system data.
            </p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-5">
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Eligible Stocks</div>
                <div class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">
                    {{ number_format($systemSummary['eligible_stocks']) }}
                    <span class="text-sm font-normal text-gray-400">/ {{ number_format($systemSummary['active_stocks']) }}</span>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-5">
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Usable Datapoints</div>
                <div class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">
                    {{ number_format($systemSummary['usable_datapoints']) }}
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-5">
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Universal Coverage</div>
                <div class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">
                    {{ number_format($systemSummary['universal_coverage']) }} stocks
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-5">
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Stored Predictions</div>
                <div class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">
                    {{ number_format($systemSummary['predictions']) }}
                </div>
            </div>
        </div>

        <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h5 class="font-semibold text-gray-800 dark:text-white">Graphical Model Performance Comparison</h5>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Metrics come from time-based held-out test data. Moving Average is the benchmark.
                    </p>
                </div>
                <div class="inline-flex rounded-lg bg-gray-100 dark:bg-gray-700 p-1 self-start">
                    <button type="button" data-performance-scope="universal"
                        class="performance-scope-btn px-4 py-2 rounded-md text-sm font-medium bg-blue-600 text-white">
                        Universal
                    </button>
                    <button type="button" data-performance-scope="individual"
                        class="performance-scope-btn px-4 py-2 rounded-md text-sm font-medium text-gray-600 dark:text-gray-300">
                        Individual Average
                    </button>
                </div>
            </div>

            @foreach(['universal' => 'Universal', 'individual' => 'Individual Average'] as $scope => $scopeLabel)
                @php $scopeAnalysis = $performanceAnalysis[$scope]; @endphp
                <div data-performance-panel="{{ $scope }}" class="{{ $scope === 'individual' ? 'hidden' : '' }} p-6">
                    <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <h6 class="font-semibold text-gray-700 dark:text-gray-200">{{ $scopeLabel }} Model Performance</h6>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Based on {{ number_format($scopeAnalysis['interpretation']['model_count']) }}
                                active {{ $scope === 'universal' ? 'system-wide models' : 'stock-specific models' }}.
                            </p>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Error metrics: lower is better. Fit and direction: higher is better.
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        @foreach([
                            'rmse' => 'RMSE (lower is better)',
                            'mae' => 'MAE (lower is better)',
                            'mape' => 'MAPE % (lower is better)',
                            'r2' => 'R2 (higher is better)',
                            'directional_accuracy' => 'Directional Accuracy % (higher is better)',
                        ] as $metric => $title)
                            <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3">
                                <div data-metric-chart="{{ $scope }}:{{ $metric }}"
                                    data-chart-title="{{ $title }}" class="h-64"></div>
                            </div>
                        @endforeach

                        <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50/70 dark:bg-blue-900/20 p-5">
                            <h6 class="font-semibold text-blue-900 dark:text-blue-200">Interpretation</h6>
                            <div class="mt-4 space-y-3 text-sm text-blue-900/80 dark:text-blue-100/80 leading-relaxed">
                                <p>
                                    Evaluation uses chronological train, validation and held-out test periods,
                                    preventing future values from entering model training.
                                </p>
                                @if($scopeAnalysis['interpretation']['best_error'])
                                    <p>
                                        <strong>Best overall error:</strong>
                                        {{ ucwords(str_replace('_', ' ', $scopeAnalysis['interpretation']['best_error']['algorithm'])) }}
                                        with {{ number_format($scopeAnalysis['interpretation']['best_error']['mape'], 2) }}% MAPE.
                                    </p>
                                @endif
                                @if($scopeAnalysis['interpretation']['best_learned_error'])
                                    <p>
                                        <strong>Best learned-model error:</strong>
                                        {{ ucwords(str_replace('_', ' ', $scopeAnalysis['interpretation']['best_learned_error']['algorithm'])) }}
                                        with {{ number_format($scopeAnalysis['interpretation']['best_learned_error']['mape'], 2) }}% MAPE.
                                    </p>
                                @endif
                                @if($scopeAnalysis['interpretation']['best_fit'])
                                    <p>
                                        <strong>Best fit:</strong>
                                        {{ ucwords(str_replace('_', ' ', $scopeAnalysis['interpretation']['best_fit']['algorithm'])) }}
                                        with R&sup2; {{ number_format($scopeAnalysis['interpretation']['best_fit']['r2'], 3) }}.
                                    </p>
                                @endif
                                @if($scopeAnalysis['interpretation']['best_direction'])
                                    <p>
                                        <strong>Best learned directional accuracy:</strong>
                                        {{ ucwords(str_replace('_', ' ', $scopeAnalysis['interpretation']['best_direction']['algorithm'])) }}
                                        at {{ number_format($scopeAnalysis['interpretation']['best_direction']['directional_accuracy'], 2) }}%.
                                    </p>
                                @endif
                                <p class="pt-3 border-t border-blue-200 dark:border-blue-800 text-xs">
                                    Moving Average is a benchmark. LSTM, XGBoost and Random Forest are the
                                    scratch-built learned models used for academic comparison.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                    <h5 class="font-semibold text-gray-800 dark:text-white">Algorithm Performance</h5>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Individual rows are averages across active stock-specific models. Lower RMSE, MAE and MAPE
                        are better; higher R² and directional accuracy are better.
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-5 py-3 text-left">Scope</th>
                                <th class="px-5 py-3 text-left">Algorithm</th>
                                <th class="px-5 py-3 text-right">Models</th>
                                <th class="px-5 py-3 text-right">RMSE</th>
                                <th class="px-5 py-3 text-right">MAE</th>
                                <th class="px-5 py-3 text-right">MAPE</th>
                                <th class="px-5 py-3 text-right">R²</th>
                                <th class="px-5 py-3 text-right">Direction</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($modelComparison as $model)
                                <tr class="text-gray-700 dark:text-gray-300">
                                    <td class="px-5 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $model['scope'] === 'universal' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' }}">
                                            {{ ucfirst($model['scope']) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 font-medium">
                                        {{ ucwords(str_replace('_', ' ', $model['algorithm'])) }}
                                        @if($model['benchmark'])
                                            <span class="ml-1 text-xs text-amber-600 dark:text-amber-400">Benchmark</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right">{{ $model['model_count'] }}</td>
                                    <td class="px-5 py-4 text-right">{{ $model['rmse'] !== null ? number_format($model['rmse'], 3) : 'N/A' }}</td>
                                    <td class="px-5 py-4 text-right">{{ $model['mae'] !== null ? number_format($model['mae'], 3) : 'N/A' }}</td>
                                    <td class="px-5 py-4 text-right">{{ $model['mape'] !== null ? number_format($model['mape'], 2) . '%' : 'N/A' }}</td>
                                    <td class="px-5 py-4 text-right">{{ $model['r2'] !== null ? number_format($model['r2'], 3) : 'N/A' }}</td>
                                    <td class="px-5 py-4 text-right">{{ $model['directional_accuracy'] !== null ? number_format($model['directional_accuracy'], 2) . '%' : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-10 text-center text-gray-500">No trained model metrics available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6">
                <h5 class="font-semibold text-gray-800 dark:text-white">Prediction Methodology</h5>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase text-gray-400">Input Features</dt>
                        <dd class="mt-1 text-gray-700 dark:text-gray-300">{{ $predictionParameters['input'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Lookback window</dt>
                        <dd class="font-medium text-gray-800 dark:text-white">{{ $predictionParameters['sequence_length'] }} days</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Minimum history</dt>
                        <dd class="font-medium text-gray-800 dark:text-white">{{ $predictionParameters['minimum_datapoints'] }} points</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Time split</dt>
                        <dd class="font-medium text-right text-gray-800 dark:text-white">
                            {{ number_format($predictionParameters['train_ratio'] * 100) }}% /
                            {{ number_format($predictionParameters['validation_ratio'] * 100) }}% /
                            {{ number_format($predictionParameters['test_ratio'] * 100) }}%
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Forecast horizons</dt>
                        <dd class="mt-1 font-medium text-gray-800 dark:text-white">{{ $predictionParameters['horizons'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Leakage protection</dt>
                        <dd class="mt-1 text-gray-700 dark:text-gray-300">{{ $predictionParameters['normalization'] }}</dd>
                    </div>
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                        Latest market data: {{ $systemSummary['latest_trading_date'] ?? 'N/A' }}<br>
                        Individual model coverage: {{ number_format($systemSummary['individual_coverage']) }} stocks
                    </div>
                </dl>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const rawData = @json($marketTrend);
            const performanceData = @json(collect($performanceAnalysis)->map(
                fn ($analysis) => $analysis['charts']
            ));
            const chartContainer = document.querySelector("#market-chart");
            const renderedCharts = [];

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
            } else {
                const marketChart = new ApexCharts(chartContainer, {
                    series: [{
                        name: 'Price',
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
                    xaxis: { type: 'datetime' },
                    yaxis: { tooltip: { enabled: true } },
                    grid: {
                        borderColor: '#4b5563',
                        strokeDashArray: 3,
                    }
                });
                marketChart.render();
                renderedCharts.push(marketChart);
            }

            document.querySelectorAll('[data-metric-chart]').forEach(container => {
                const [scope, metric] = container.dataset.metricChart.split(':');
                const scopeData = performanceData[scope];
                const values = scopeData?.[metric] ?? [];

                if (!scopeData || !values.length) {
                    container.innerHTML = '<div class="h-full flex items-center justify-center text-sm text-gray-400">No metric data</div>';
                    return;
                }

                const metricChart = new ApexCharts(container, {
                    series: [{
                        name: container.dataset.chartTitle,
                        data: values.map(value => value === null ? 0 : Number(value))
                    }],
                    chart: {
                        type: 'bar',
                        height: 250,
                        toolbar: { show: false },
                        background: 'transparent',
                        fontFamily: 'Inter, sans-serif',
                        animations: { speed: 350 }
                    },
                    theme: {
                        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    },
                    title: {
                        text: container.dataset.chartTitle,
                        align: 'left',
                        style: { fontSize: '13px', fontWeight: 600 }
                    },
                    colors: scopeData.colors,
                    plotOptions: {
                        bar: {
                            distributed: true,
                            borderRadius: 3,
                            columnWidth: '58%',
                            dataLabels: { position: 'top' }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: value => Number(value).toFixed(metric === 'r2' ? 3 : 2),
                        offsetY: -18,
                        style: {
                            fontSize: '10px',
                            colors: [document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151']
                        }
                    },
                    xaxis: {
                        categories: scopeData.labels,
                        labels: {
                            rotate: -20,
                            trim: false,
                            style: { fontSize: '10px' }
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: value => Number(value).toFixed(metric === 'r2' ? 2 : 1)
                        }
                    },
                    legend: { show: false },
                    grid: {
                        borderColor: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                        strokeDashArray: 3
                    },
                    tooltip: {
                        y: {
                            formatter: value => {
                                const suffix = ['mape', 'directional_accuracy'].includes(metric) ? '%' : '';
                                return `${Number(value).toFixed(metric === 'r2' ? 3 : 2)}${suffix}`;
                            }
                        }
                    }
                });
                metricChart.render();
                renderedCharts.push(metricChart);
            });

            const scopeButtons = document.querySelectorAll('.performance-scope-btn');
            scopeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const selectedScope = button.dataset.performanceScope;

                    document.querySelectorAll('[data-performance-panel]').forEach(panel => {
                        panel.classList.toggle(
                            'hidden',
                            panel.dataset.performancePanel !== selectedScope
                        );
                    });

                    scopeButtons.forEach(scopeButton => {
                        const active = scopeButton.dataset.performanceScope === selectedScope;
                        scopeButton.classList.toggle('bg-blue-600', active);
                        scopeButton.classList.toggle('text-white', active);
                        scopeButton.classList.toggle('text-gray-600', !active);
                        scopeButton.classList.toggle('dark:text-gray-300', !active);
                    });

                    window.dispatchEvent(new Event('resize'));
                });
            });

            const updateChartTheme = () => {
                const isDark = document.documentElement.classList.contains('dark');
                renderedCharts.forEach(chart => {
                    chart.updateOptions({
                        theme: { mode: isDark ? 'dark' : 'light' },
                        grid: { borderColor: isDark ? '#374151' : '#e5e7eb' },
                        dataLabels: {
                            style: { colors: [isDark ? '#e5e7eb' : '#374151'] }
                        }
                    }, false, false);
                });
            };

            const htmlObserver = new MutationObserver(updateChartTheme);
            htmlObserver.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });
        });
    </script>
@endpush

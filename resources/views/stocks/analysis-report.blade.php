<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $stock->symbol }} Analysis Report</title>
    <style>
        @page { margin: 32px 38px 42px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #1e293b;
            font-size: 10px;
            line-height: 1.45;
        }
        h1, h2, h3, p { margin-top: 0; }
        h1 { font-size: 25px; margin-bottom: 5px; color: #0f172a; }
        h2 { font-size: 17px; color: #0f172a; margin-bottom: 12px; }
        h3 { font-size: 12px; color: #1d4ed8; margin-bottom: 8px; }
        .muted { color: #64748b; }
        .small { font-size: 8px; }
        .header {
            padding: 20px 24px;
            background: #0f3b77;
            color: #fff;
            border-radius: 8px;
            margin-bottom: 18px;
        }
        .header h1, .header .muted { color: #fff; }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 8px;
            font-weight: bold;
        }
        .benchmark { background: #fef3c7; color: #92400e; }
        .scope-individual { background: #ede9fe; color: #6d28d9; }
        .grid-4 { width: 100%; border-collapse: separate; border-spacing: 8px; margin: 0 -8px 14px; }
        .grid-4 td { width: 25%; vertical-align: top; }
        .metric-card {
            border: 1px solid #dbe3ee;
            border-radius: 7px;
            padding: 10px;
            background: #f8fafc;
            min-height: 58px;
        }
        .metric-label { color: #64748b; font-size: 8px; text-transform: uppercase; }
        .metric-value { color: #0f172a; font-size: 16px; font-weight: bold; margin-top: 4px; }
        .section {
            border: 1px solid #dbe3ee;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        .chart {
            width: 100%;
            border: 1px solid #dbe3ee;
            background: #f8fafc;
            padding: 10px;
        }
        .history-chart {
            width: 100%;
            height: 145px;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .history-chart td {
            height: 125px;
            padding: 0 1px;
            vertical-align: bottom;
            border-bottom: 1px solid #94a3b8;
        }
        .history-bar {
            width: 100%;
            background: #2563eb;
            border-radius: 2px 2px 0 0;
        }
        .chart-axis {
            width: 100%;
            margin-top: 4px;
            color: #64748b;
            font-size: 7px;
        }
        .chart-axis td:last-child { text-align: right; }
        .bar-chart { width: 100%; border-collapse: collapse; }
        .bar-chart td { padding: 4px 3px; vertical-align: middle; }
        .bar-label { width: 31%; font-size: 8px; color: #334155; }
        .bar-track {
            position: relative;
            width: 54%;
            height: 13px;
            background: #e2e8f0;
            border-radius: 3px;
        }
        .bar-fill { height: 13px; border-radius: 3px; }
        .bar-value { width: 15%; text-align: right; font-weight: bold; font-size: 8px; }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 7px;
            font-size: 8px;
        }
        table.data th {
            background: #eaf1fb;
            color: #334155;
            text-align: left;
            padding: 6px 5px;
            border: 1px solid #cbd5e1;
        }
        table.data td {
            padding: 5px;
            border: 1px solid #dbe3ee;
            vertical-align: top;
        }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .text-right { text-align: right; }
        .positive { color: #15803d; font-weight: bold; }
        .negative { color: #b91c1c; font-weight: bold; }
        .page-break { page-break-before: always; }
        .two-column { width: 100%; border-collapse: separate; border-spacing: 10px 0; margin-left: -10px; }
        .two-column > tbody > tr > td { width: 50%; vertical-align: top; }
        .interpretation {
            padding: 12px;
            border-left: 4px solid #2563eb;
            background: #eff6ff;
            margin-bottom: 10px;
        }
        .warning {
            padding: 10px;
            border-left: 4px solid #eab308;
            background: #fffbeb;
            color: #713f12;
        }
        .footer {
            position: fixed;
            bottom: -28px;
            left: 0;
            right: 0;
            color: #64748b;
            font-size: 7px;
            border-top: 1px solid #dbe3ee;
            padding-top: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="footer">
        ArthaPredict academic analysis report | {{ $stock->symbol }} | Generated {{ $generatedAt->format('Y-m-d H:i') }}
    </div>

    <div class="header">
        <div class="small" style="text-transform: uppercase; letter-spacing: 1px;">ArthaPredict</div>
        <h1>{{ $stock->name }} ({{ $stock->symbol }})</h1>
        <div class="muted">
            Stock Analysis and Forecast Comparison Report
            @if($stock->exchange) | {{ $stock->exchange }} @endif
            @if($stock->sector) | {{ $stock->sector }} @endif
        </div>
    </div>

    @if($latest)
        <table class="grid-4">
            <tr>
                <td><div class="metric-card"><div class="metric-label">Current Price</div><div class="metric-value">Rs. {{ number_format($latest->close, 2) }}</div></div></td>
                <td><div class="metric-card"><div class="metric-label">Usable Datapoints</div><div class="metric-value">{{ number_format($historyStats['count']) }}</div></div></td>
                <td><div class="metric-card"><div class="metric-label">Historical Range</div><div class="metric-value">Rs. {{ number_format($historyStats['minimum'], 0) }} - {{ number_format($historyStats['maximum'], 0) }}</div></div></td>
                <td><div class="metric-card"><div class="metric-label">Recent 30-Point Change</div><div class="metric-value {{ ($historyStats['month_change'] ?? 0) >= 0 ? 'positive' : 'negative' }}">{{ ($historyStats['month_change'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($historyStats['month_change'] ?? 0, 2) }}%</div></div></td>
            </tr>
        </table>
    @endif

    <div class="section">
        <h2>1. Historical Price Analysis</h2>
        <p class="muted">
            The graph uses cleaned, usable closing-price records from {{ $historyStats['first_date'] ?? 'N/A' }}
            to {{ $historyStats['last_date'] ?? 'N/A' }}. Placeholder low-price rows are excluded using the
            same data-cleaning rule applied during model training.
        </p>
        <div class="chart">
            @if($priceChartData)
                <table class="history-chart">
                    <tr>
                        @foreach($priceChartData as $point)
                            <td title="{{ $point['date'] }}: {{ number_format($point['value'], 2) }}">
                                <div class="history-bar" style="height: {{ number_format($point['height'], 2, '.', '') }}px;"></div>
                            </td>
                        @endforeach
                    </tr>
                </table>
                <table class="chart-axis">
                    <tr>
                        <td>{{ $priceChartData[0]['date'] }}</td>
                        <td>{{ $priceChartData[count($priceChartData) - 1]['date'] }}</td>
                    </tr>
                </table>
                <div class="small muted" style="text-align:center; margin-top:5px;">
                    Last {{ count($priceChartData) }} usable closing-price records
                </div>
            @else
                <div class="muted" style="text-align:center;">No historical graph data available.</div>
            @endif
        </div>
        @if($latest)
            <table class="data">
                <tr>
                    <th>Latest Date</th><th>Open</th><th>High</th><th>Low</th><th>Close</th><th>Volume</th><th>Average Close</th>
                </tr>
                <tr>
                    <td>{{ $latest->date }}</td>
                    <td>Rs. {{ number_format($latest->open, 2) }}</td>
                    <td>Rs. {{ number_format($latest->high, 2) }}</td>
                    <td>Rs. {{ number_format($latest->low, 2) }}</td>
                    <td><strong>Rs. {{ number_format($latest->close, 2) }}</strong></td>
                    <td>{{ number_format($latest->volume) }}</td>
                    <td>Rs. {{ number_format($historyStats['average'], 2) }}</td>
                </tr>
            </table>
        @endif
    </div>

    <div class="page-break"></div>
    <div class="section">
        <h2>2. Forecast Comparison</h2>
        <p class="muted">
            General models are trained across all eligible stocks. Individual models use only {{ $stock->symbol }}
            history. The red reference line represents the latest actual closing price.
        </p>
        <div class="chart">
            <h3 style="text-align:center;">30-Day General vs Individual Forecast</h3>
            @if($forecastChartData)
                <table class="bar-chart">
                    @foreach($forecastChartData as $bar)
                        <tr>
                            <td class="bar-label">{{ $bar['label'] }}</td>
                            <td>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: {{ number_format($bar['width'], 2, '.', '') }}%; background: {{ $bar['color'] }};"></div>
                                </div>
                            </td>
                            <td class="bar-value">Rs. {{ number_format($bar['value'], 2) }}</td>
                        </tr>
                    @endforeach
                </table>
                @if($latest)
                    <div class="small muted" style="text-align:center; margin-top:6px;">
                        Current reference price: Rs. {{ number_format($latest->close, 2) }}
                    </div>
                @endif
            @else
                <div class="muted" style="text-align:center;">No 30-day forecast data available.</div>
            @endif
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th>Scope</th>
                    <th>Algorithm</th>
                    <th>Horizon</th>
                    <th>Target Date</th>
                    <th class="text-right">Predicted Price</th>
                    <th class="text-right">Expected Change</th>
                </tr>
            </thead>
            <tbody>
                @forelse($predictions as $prediction)
                    @php
                        $change = $latest && $latest->close
                            ? (($prediction->predicted_price - $latest->close) / $latest->close) * 100
                            : null;
                    @endphp
                    <tr>
                        <td><span class="badge {{ $prediction->model_scope === 'individual' ? 'scope-individual' : '' }}">{{ ucfirst($prediction->model_scope) }}</span></td>
                        <td>
                            {{ ucwords(str_replace('_', ' ', $prediction->model_type)) }}
                            @if(($prediction->additional_metrics['benchmark'] ?? false))
                                <span class="badge benchmark">Benchmark</span>
                            @endif
                        </td>
                        <td>{{ $prediction->additional_metrics['horizon'] ?? 'N/A' }}</td>
                        <td>{{ $prediction->target_date }}</td>
                        <td class="text-right">Rs. {{ number_format($prediction->predicted_price, 2) }}</td>
                        <td class="text-right {{ ($change ?? 0) >= 0 ? 'positive' : 'negative' }}">
                            {{ $change !== null ? (($change >= 0 ? '+' : '') . number_format($change, 2) . '%') : 'N/A' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No future predictions are currently stored for this stock.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>
    <h2>3. Model Performance Comparison</h2>
    <table class="two-column">
        <tr>
            <td>
                <div class="section">
                    <h3>MAPE % (Lower Is Better)</h3>
                    <table class="bar-chart">
                        @foreach($mapeChartData as $bar)
                            <tr>
                                <td class="bar-label">{{ $bar['label'] }}</td>
                                <td><div class="bar-track"><div class="bar-fill" style="width: {{ number_format($bar['width'], 2, '.', '') }}%; background: {{ $bar['color'] }};"></div></div></td>
                                <td class="bar-value">{{ number_format($bar['value'], 2) }}%</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </td>
            <td>
                <div class="section">
                    <h3>Directional Accuracy % (Higher Is Better)</h3>
                    <table class="bar-chart">
                        @foreach($directionChartData as $bar)
                            <tr>
                                <td class="bar-label">{{ $bar['label'] }}</td>
                                <td><div class="bar-track"><div class="bar-fill" style="width: {{ number_format($bar['width'], 2, '.', '') }}%; background: {{ $bar['color'] }};"></div></div></td>
                                <td class="bar-value">{{ number_format($bar['value'], 2) }}%</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </td>
        </tr>
    </table>

    @foreach(['universal' => 'General / Universal Models', 'individual' => 'Individual Stock Models'] as $scope => $scopeLabel)
        <div class="section">
            <h3>{{ $scopeLabel }}</h3>
            <table class="data">
                <thead>
                    <tr>
                        <th>Algorithm</th>
                        <th class="text-right">RMSE</th>
                        <th class="text-right">MAE</th>
                        <th class="text-right">MAPE</th>
                        <th class="text-right">R2</th>
                        <th class="text-right">Direction</th>
                        <th>Training Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($models->get($scope, collect()) as $model)
                        <tr>
                            <td>
                                {{ ucwords(str_replace('_', ' ', $model->model_type)) }}
                                @if($model->model_type === 'moving_average')
                                    <span class="badge benchmark">Benchmark</span>
                                @endif
                            </td>
                            <td class="text-right">{{ $model->rmse !== null ? number_format($model->rmse, 3) : 'N/A' }}</td>
                            <td class="text-right">{{ $model->mae !== null ? number_format($model->mae, 3) : 'N/A' }}</td>
                            <td class="text-right">{{ $model->mape !== null ? number_format($model->mape, 2) . '%' : 'N/A' }}</td>
                            <td class="text-right">{{ $model->r2 !== null ? number_format($model->r2, 3) : 'N/A' }}</td>
                            <td class="text-right">{{ $model->directional_accuracy !== null ? number_format($model->directional_accuracy, 2) . '%' : 'N/A' }}</td>
                            <td>{{ $model->training_date?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No active {{ strtolower($scopeLabel) }} are available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="page-break"></div>
    <h2>4. Interpretation and Recommendation</h2>
    @foreach(['universal' => 'General Model', 'individual' => 'Individual Model'] as $scope => $scopeLabel)
        @php $result = $comparison[$scope]; @endphp
        <div class="interpretation">
            <h3>{{ $scopeLabel }}</h3>
            @if($result['best_learned'])
                <p>
                    The recommended learned algorithm is
                    <strong>{{ ucwords(str_replace('_', ' ', $result['best_learned']->model_type)) }}</strong>
                    because it has the lowest MAPE among the scratch-built learned models
                    ({{ number_format($result['best_learned']->mape, 2) }}%).
                </p>
            @else
                <p>No learned-model metrics are available for this scope.</p>
            @endif

            @if($result['best_overall'])
                <p>
                    The lowest overall error is produced by
                    <strong>{{ ucwords(str_replace('_', ' ', $result['best_overall']->model_type)) }}</strong>
                    with {{ number_format($result['best_overall']->mape, 2) }}% MAPE.
                    @if($result['best_overall']->model_type === 'moving_average')
                        This is the benchmark result and should be compared with, not treated as a learned model.
                    @endif
                </p>
            @endif

            @if($result['best_direction'])
                <p>
                    The strongest learned directional accuracy is
                    <strong>{{ ucwords(str_replace('_', ' ', $result['best_direction']->model_type)) }}</strong>
                    at {{ number_format($result['best_direction']->directional_accuracy, 2) }}%.
                </p>
            @endif
        </div>
    @endforeach

    <div class="warning">
        <strong>Academic interpretation:</strong> Error metrics evaluate price closeness, while directional
        accuracy evaluates up/down movement. A model can perform well on one and poorly on the other.
        Forecasts are experimental outputs based on historical data and are not financial advice.
    </div>

    <div class="section" style="margin-top: 14px;">
        <h2>5. Recent Historical Records</h2>
        <table class="data">
            <thead>
                <tr><th>Date</th><th class="text-right">Open</th><th class="text-right">High</th><th class="text-right">Low</th><th class="text-right">Close</th><th class="text-right">Volume</th></tr>
            </thead>
            <tbody>
                @foreach($recentHistory as $price)
                    <tr>
                        <td>{{ $price->date }}</td>
                        <td class="text-right">{{ number_format($price->open, 2) }}</td>
                        <td class="text-right">{{ number_format($price->high, 2) }}</td>
                        <td class="text-right">{{ number_format($price->low, 2) }}</td>
                        <td class="text-right"><strong>{{ number_format($price->close, 2) }}</strong></td>
                        <td class="text-right">{{ number_format($price->volume) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>

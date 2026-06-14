<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $stock->symbol }} Analysis Report</title>
    <style>
        @page { margin: 25px 30px 35px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #1e293b;
            font-size: 10px;
            line-height: 1.5;
        }
        h1, h2, h3, p { margin-top: 0; margin-bottom: 0; }
        h1 { font-size: 22px; margin-bottom: 4px; color: #0f172a; font-weight: 600; }
        h2 { font-size: 16px; color: #1e293b; margin-bottom: 10px; font-weight: 600; }
        h3 { font-size: 11px; color: #2563eb; margin-bottom: 6px; font-weight: 600; }
        .muted { color: #64748b; }
        .small { font-size: 8px; }
        .header {
            padding: 18px 22px;
            background: linear-gradient(135deg, #1e40af 0%, #0f3b77 100%);
            color: #fff;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .header h1, .header .muted { color: #fff; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 8px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 7px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .benchmark { background: #fef3c7; color: #92400e; }
        .scope-individual { background: #ede9fe; color: #6d28d9; }
        .grid-4 { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 0 -6px 12px; }
        .grid-4 td { width: 25%; vertical-align: top; padding: 0 6px; }
        .metric-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px;
            background: #f8fafc;
            min-height: 50px;
        }
        .metric-label { color: #64748b; font-size: 7px; text-transform: uppercase; font-weight: 500; }
        .metric-value { color: #0f172a; font-size: 14px; font-weight: 600; margin-top: 2px; }
        .section {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .chart {
            width: 100%;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 8px;
            border-radius: 4px;
        }
        .history-chart {
            width: 100%;
            height: 140px;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .history-chart td {
            height: 115px;
            padding: 0 1px;
            vertical-align: bottom;
            border-bottom: 1px solid #94a3b8;
        }
        .history-bar {
            width: 100%;
            background: #2563eb;
            border-radius: 1px 1px 0 0;
        }
        .chart-axis {
            width: 100%;
            margin-top: 3px;
            color: #64748b;
            font-size: 6px;
        }
        .chart-axis td:last-child { text-align: right; }
        .bar-chart { width: 100%; border-collapse: collapse; }
        .bar-chart td { padding: 3px 2px; vertical-align: middle; }
        .bar-label { width: 31%; font-size: 7px; color: #334155; }
        .bar-track {
            position: relative;
            width: 54%;
            height: 11px;
            background: #e2e8f0;
            border-radius: 2px;
        }
        .bar-fill { height: 11px; border-radius: 2px; }
        .bar-value { width: 15%; text-align: right; font-weight: 600; font-size: 7px; }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 7px;
        }
        table.data th {
            background: #f1f5f9;
            color: #475569;
            text-align: left;
            padding: 5px 4px;
            border: 1px solid #cbd5e1;
            font-weight: 600;
        }
        table.data td {
            padding: 4px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .text-right { text-align: right; }
        .positive { color: #15803d; font-weight: 600; }
        .negative { color: #b91c1c; font-weight: 600; }
        .page-break { page-break-before: always; page-break-after: avoid; }
        .two-column { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-left: -8px; }
        .two-column > tbody > tr > td { width: 50%; vertical-align: top; padding: 0 8px; }
        .interpretation {
            padding: 10px;
            border-left: 3px solid #2563eb;
            background: #eff6ff;
            margin-bottom: 8px;
            border-radius: 0 4px 4px 0;
        }
        .warning {
            padding: 10px;
            border-left: 3px solid #eab308;
            background: #fffbeb;
            color: #713f12;
            border-radius: 0 4px 4px 0;
        }
        @page :footer {
            content: element(footer);
        }
        .footer-content {
            font-size: 7px;
            color: #64748b;
            text-align: center;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="small" style="text-transform: uppercase; letter-spacing: 1px; opacity: 0.9;">ArthaPredict Academic Analysis</div>
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
            <h3 style="text-align:center; margin-bottom: 8px;">30-Day General vs Individual Forecast</h3>
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
                    <tr><td colspan="6" style="text-align:center;">No future predictions are currently stored for this stock.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>
    <div class="section">
        <h2>3. Model Performance Comparison</h2>
        <table class="two-column">
            <tr>
                <td>
                    <div class="section" style="margin-bottom: 0;">
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
                    <div class="section" style="margin-bottom: 0;">
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
            <div class="section" style="margin-top: 12px;">
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
                            <tr><td colspan="7" style="text-align:center;">No active {{ strtolower($scopeLabel) }} are available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    <div class="page-break"></div>
    <div class="section">
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

    <div style="position: running(footer);">
        <div class="footer-content">
            ArthaPredict academic analysis report | {{ $stock->symbol }} | Generated {{ $generatedAt->format('Y-m-d H:i') }}
        </div>
    </div>
</body>
</html>

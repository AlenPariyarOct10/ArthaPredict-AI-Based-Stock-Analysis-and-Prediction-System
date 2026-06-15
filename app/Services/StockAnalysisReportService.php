<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockPrediction;
use App\Models\TrainedModel;
use Illuminate\Support\Collection;

class StockAnalysisReportService
{
    private const ALGORITHMS = [
        'lstm',
        'xgboost',
        'random_forest',
        'moving_average',
    ];

    private const COLORS = [
        'lstm' => '#7c3aed',
        'xgboost' => '#f97316',
        'random_forest' => '#10b981',
        'moving_average' => '#eab308',
    ];

    public function build(Stock $stock): array
    {
        $history = $stock->usablePrices()->orderBy('date')->get();
        $latest = $history->last();
        $currentPrice = (float) ($latest?->close ?? 0);

        $predictions = StockPrediction::where('stock_id', $stock->id)
            ->when($latest, fn ($query) => $query->where('target_date', '>', $latest->date))
            ->orderByDesc('id')
            ->get()
            ->unique(fn ($prediction) =>
                "{$prediction->model_scope}:{$prediction->model_type}:{$prediction->target_date}"
            )
            ->sortBy(fn ($prediction) =>
                "{$prediction->target_date}:{$prediction->model_scope}:{$prediction->model_type}"
            )
            ->values();

        $models = TrainedModel::where('is_active', true)
            ->where(function ($query) use ($stock) {
                $query->where(function ($universal) {
                    $universal->whereNull('stock_id')
                        ->where('model_scope', 'universal');
                })->orWhere(function ($individual) use ($stock) {
                    $individual->where('stock_id', $stock->id)
                        ->where('model_scope', 'individual');
                });
            })
            ->get()
            ->groupBy('model_scope')
            ->map(fn ($scopeModels) =>
                $scopeModels->sortBy(
                    fn ($model) => array_search($model->model_type, self::ALGORITHMS, true)
                )->values()
            );

        foreach (['universal', 'individual'] as $scope) {
            $scopeModels = $models->get($scope, collect())->keyBy('model_type');
            $fallbackPredictions = $predictions
                ->where('model_scope', $scope)
                ->unique('model_type');

            foreach ($fallbackPredictions as $prediction) {
                if ($scopeModels->has($prediction->model_type)) {
                    continue;
                }

                $metrics = $prediction->additional_metrics ?? [];
                $scopeModels->put($prediction->model_type, (object) [
                    'model_type' => $prediction->model_type,
                    'model_scope' => $scope,
                    'mse' => $metrics['mse'] ?? null,
                    'rmse' => $metrics['rmse'] ?? null,
                    'mae' => $metrics['mae'] ?? null,
                    'mape' => $metrics['mape'] ?? null,
                    'r2' => $metrics['r2'] ?? null,
                    'directional_accuracy' => $metrics['directional_accuracy'] ?? null,
                    'training_date' => $prediction->updated_at,
                ]);
            }

            $models->put(
                $scope,
                $scopeModels->sortBy(
                    fn ($model) => array_search(
                        $model->model_type,
                        self::ALGORITHMS,
                        true
                    )
                )->values()
            );
        }

        $comparison = $this->comparison($models);

        return [
            'stock' => $stock,
            'generatedAt' => now(),
            'history' => $history,
            'recentHistory' => $history->take(-20)->reverse()->values(),
            'latest' => $latest,
            'historyStats' => $this->historyStats($history),
            'predictions' => $predictions,
            'predictionGroups' => $predictions->groupBy('model_scope'),
            'models' => $models,
            'comparison' => $comparison,
            'priceChartData' => $this->priceChartData($history->take(-60)),
            'forecastChartData' => $this->forecastChartData(
                $predictions,
                $currentPrice
            ),
            'mapeChartData' => $this->metricChartData($models, 'mape'),
            'directionChartData' => $this->metricChartData(
                $models,
                'directional_accuracy'
            ),
        ];
    }

    private function historyStats(Collection $history): array
    {
        if ($history->isEmpty()) {
            return [];
        }

        $latest = $history->last();
        $monthStart = $history->slice(max(0, $history->count() - 31))->first();
        $monthChange = $monthStart && (float) $monthStart->close !== 0.0
            ? (((float) $latest->close - (float) $monthStart->close)
                / (float) $monthStart->close) * 100
            : null;

        return [
            'count' => $history->count(),
            'first_date' => $history->first()->date,
            'last_date' => $latest->date,
            'minimum' => (float) $history->min('close'),
            'maximum' => (float) $history->max('close'),
            'average' => (float) $history->avg('close'),
            'month_change' => $monthChange,
        ];
    }

    private function comparison(Collection $models): array
    {
        return collect(['universal', 'individual'])
            ->mapWithKeys(function ($scope) use ($models) {
                $scopeModels = $models->get($scope, collect());
                $learned = $scopeModels
                    ->where('model_type', '!=', 'moving_average')
                    ->filter(fn ($model) => $model->mape !== null);

                return [$scope => [
                    'best_overall' => $scopeModels
                        ->filter(fn ($model) => $model->mape !== null)
                        ->sortBy('mape')
                        ->first(),
                    'best_learned' => $learned->sortBy('mape')->first(),
                    'best_direction' => $learned
                        ->filter(fn ($model) => $model->directional_accuracy !== null)
                        ->sortByDesc('directional_accuracy')
                        ->first(),
                ]];
            })
            ->all();
    }

    private function priceChartData(Collection $history): array
    {
        if ($history->isEmpty()) {
            return [];
        }

        $minimum = (float) $history->min('close');
        $maximum = (float) $history->max('close');
        $range = max(1, $maximum - $minimum);

        return $history->values()->map(fn ($price) => [
            'date' => (string) $price->date,
            'value' => (float) $price->close,
            'height' => 18 + ((((float) $price->close - $minimum) / $range) * 82),
        ])->all();
    }

    private function forecastChartData(
        Collection $predictions,
        float $currentPrice
    ): array {
        $items = $predictions
            ->filter(fn ($prediction) =>
                ($prediction->additional_metrics['horizon'] ?? null) === '1 Month'
            )
            ->map(fn ($prediction) => [
                'label' => ($prediction->model_scope === 'universal' ? 'General ' : 'Individual ')
                    . ucwords(str_replace('_', ' ', $prediction->model_type)),
                'value' => (float) $prediction->predicted_price,
                'color' => $prediction->model_scope === 'universal'
                    ? self::COLORS[$prediction->model_type]
                    : $this->lighten(self::COLORS[$prediction->model_type]),
            ])
            ->values();

        return $this->normalizeBars($items, $currentPrice);
    }

    private function metricChartData(
        Collection $models,
        string $metric
    ): array {
        $items = collect(['universal', 'individual'])->flatMap(function ($scope) use (
            $models,
            $metric
        ) {
            return $models->get($scope, collect())
                ->filter(fn ($model) => $model->{$metric} !== null)
                ->map(fn ($model) => [
                    'label' => ($scope === 'universal' ? 'General ' : 'Individual ')
                        . ucwords(str_replace('_', ' ', $model->model_type)),
                    'value' => (float) $model->{$metric},
                    'color' => $scope === 'universal'
                        ? self::COLORS[$model->model_type]
                        : $this->lighten(self::COLORS[$model->model_type]),
                ]);
        })->values();

        return $this->normalizeBars($items);
    }

    private function normalizeBars(
        Collection $items,
        ?float $reference = null
    ): array {
        if ($items->isEmpty()) {
            return [];
        }

        $maximum = max(1, (float) $items->max('value'), (float) ($reference ?? 0));

        return $items->map(fn ($item) => [
            ...$item,
            'width' => max(2, ($item['value'] / $maximum) * 100),
            'reference_width' => $reference !== null
                ? ($reference / $maximum) * 100
                : null,
            'reference' => $reference,
        ])->all();
    }

    private function priceChart(Collection $history): string
    {
        if ($history->count() < 2) {
            return $this->emptyChart('Insufficient historical data');
        }

        $values = $history->pluck('close')->map(fn ($value) => (float) $value);
        $min = $values->min();
        $max = $values->max();
        $range = max(1, $max - $min);
        $width = 700;
        $height = 220;
        $left = 50;
        $top = 20;
        $plotWidth = 620;
        $plotHeight = 150;
        $points = $values->values()->map(function ($value, $index) use (
            $values,
            $min,
            $range,
            $left,
            $top,
            $plotWidth,
            $plotHeight
        ) {
            $x = $left + ($index / max(1, $values->count() - 1)) * $plotWidth;
            $y = $top + $plotHeight - (($value - $min) / $range) * $plotHeight;
            return round($x, 2) . ',' . round($y, 2);
        })->implode(' ');

        $firstDate = $history->first()->date;
        $lastDate = $history->last()->date;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="700" height="220" viewBox="0 0 700 220">
  <rect width="700" height="220" fill="#f8fafc"/>
  <line x1="50" y1="20" x2="50" y2="170" stroke="#cbd5e1"/>
  <line x1="50" y1="170" x2="670" y2="170" stroke="#cbd5e1"/>
  <polyline points="{$points}" fill="none" stroke="#2563eb" stroke-width="3"/>
  <text x="8" y="27" font-size="10" fill="#475569">Rs. {$this->number($max, 2)}</text>
  <text x="8" y="171" font-size="10" fill="#475569">Rs. {$this->number($min, 2)}</text>
  <text x="50" y="192" font-size="10" fill="#64748b">{$firstDate}</text>
  <text x="590" y="192" font-size="10" fill="#64748b">{$lastDate}</text>
  <text x="350" y="212" text-anchor="middle" font-size="11" fill="#334155">Usable closing-price history</text>
</svg>
SVG;
    }

    private function forecastChart(Collection $predictions, float $currentPrice): string
    {
        $monthPredictions = $predictions->filter(fn ($prediction) =>
            ($prediction->additional_metrics['horizon'] ?? null) === '1 Month'
        );
        if ($monthPredictions->isEmpty()) {
            return $this->emptyChart('No 30-day forecasts available');
        }

        $items = $monthPredictions->map(fn ($prediction) => [
            'label' => ($prediction->model_scope === 'universal' ? 'G ' : 'I ')
                . $this->shortAlgorithm($prediction->model_type),
            'value' => (float) $prediction->predicted_price,
            'color' => $prediction->model_scope === 'universal'
                ? self::COLORS[$prediction->model_type]
                : $this->lighten(self::COLORS[$prediction->model_type]),
        ])->values();

        return $this->barChart(
            $items,
            '30-Day General (G) vs Individual (I) Forecast',
            $currentPrice
        );
    }

    private function metricChart(
        Collection $models,
        string $metric,
        string $title
    ): string {
        $items = collect(['universal', 'individual'])->flatMap(function ($scope) use (
            $models,
            $metric
        ) {
            return $models->get($scope, collect())
                ->filter(fn ($model) => $model->{$metric} !== null)
                ->map(fn ($model) => [
                    'label' => ($scope === 'universal' ? 'G ' : 'I ')
                        . $this->shortAlgorithm($model->model_type),
                    'value' => (float) $model->{$metric},
                    'color' => $scope === 'universal'
                        ? self::COLORS[$model->model_type]
                        : $this->lighten(self::COLORS[$model->model_type]),
                ]);
        })->values();

        return $items->isEmpty()
            ? $this->emptyChart("No {$title} data")
            : $this->barChart($items, $title);
    }

    private function barChart(
        Collection $items,
        string $title,
        ?float $reference = null
    ): string {
        $width = 700;
        $height = 250;
        $left = 45;
        $top = 35;
        $plotWidth = 620;
        $plotHeight = 150;
        $maximum = max(1, (float) $items->max('value'), (float) ($reference ?? 0));
        $slot = $plotWidth / max(1, $items->count());
        $barWidth = min(50, $slot * 0.62);
        $bars = '';

        foreach ($items as $index => $item) {
            $barHeight = ($item['value'] / $maximum) * $plotHeight;
            $x = $left + ($index * $slot) + (($slot - $barWidth) / 2);
            $y = $top + $plotHeight - $barHeight;
            $value = $this->number($item['value'], 2);
            $label = htmlspecialchars($item['label'], ENT_XML1);
            $bars .= '<rect x="' . round($x, 2) . '" y="' . round($y, 2)
                . '" width="' . round($barWidth, 2) . '" height="'
                . round($barHeight, 2) . '" rx="3" fill="' . $item['color'] . '"/>';
            $bars .= '<text x="' . round($x + ($barWidth / 2), 2) . '" y="'
                . round(max(25, $y - 5), 2)
                . '" text-anchor="middle" font-size="9" fill="#334155">'
                . $value . '</text>';
            $bars .= '<text x="' . round($x + ($barWidth / 2), 2)
                . '" y="205" text-anchor="middle" font-size="8" fill="#475569">'
                . $label . '</text>';
        }

        $referenceLine = '';
        if ($reference !== null && $reference > 0) {
            $referenceY = $top + $plotHeight - ($reference / $maximum) * $plotHeight;
            $referenceLine = '<line x1="' . $left . '" y1="' . round($referenceY, 2)
                . '" x2="' . ($left + $plotWidth) . '" y2="' . round($referenceY, 2)
                . '" stroke="#dc2626" stroke-width="2" stroke-dasharray="6 4"/>'
                . '<text x="' . ($left + $plotWidth - 3) . '" y="'
                . round($referenceY - 4, 2)
                . '" text-anchor="end" font-size="9" fill="#dc2626">Current Rs. '
                . $this->number($reference, 2) . '</text>';
        }

        $safeTitle = htmlspecialchars($title, ENT_XML1);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="700" height="250" viewBox="0 0 700 250">
  <rect width="700" height="250" fill="#f8fafc"/>
  <text x="350" y="20" text-anchor="middle" font-size="13" font-weight="bold" fill="#0f172a">{$safeTitle}</text>
  <line x1="45" y1="185" x2="665" y2="185" stroke="#cbd5e1"/>
  {$referenceLine}
  {$bars}
</svg>
SVG;
    }

    private function emptyChart(string $message): string
    {
        $safe = htmlspecialchars($message, ENT_XML1);
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="700" height="180" viewBox="0 0 700 180">
  <rect width="700" height="180" fill="#f8fafc"/>
  <text x="350" y="92" text-anchor="middle" font-size="14" fill="#64748b">{$safe}</text>
</svg>
SVG;
    }

    private function asImage(string $svg): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function shortAlgorithm(string $algorithm): string
    {
        return match ($algorithm) {
            'random_forest' => 'RF',
            'moving_average' => 'MA',
            default => strtoupper($algorithm),
        };
    }

    private function lighten(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $channels = str_split($hex, 2);
        $light = array_map(
            fn ($channel) => min(255, (int) round(hexdec($channel) + (255 - hexdec($channel)) * 0.38)),
            $channels
        );

        return '#' . implode('', array_map(
            fn ($channel) => str_pad(dechex($channel), 2, '0', STR_PAD_LEFT),
            $light
        ));
    }

    private function number(float $value, int $decimals): string
    {
        return number_format($value, $decimals, '.', ',');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockPrediction;
use App\Models\StockPrice;
use App\Models\TrainedModel;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    private const MIN_ELIGIBLE_DATAPOINTS = 30;

    public function index()
    {
        $stocks = Stock::where('is_active', true)
            ->where('usable_datapoints_count', '>=', self::MIN_ELIGIBLE_DATAPOINTS)
            ->with(['prices' => function ($query) {
                $query->orderByDesc('date')->limit(2);
            }])
            ->orderByDesc('usable_datapoints_count')
            ->orderBy('symbol')
            ->limit(6)
            ->get();

        $chartStock = $stocks->first();
        if ($chartStock) {
            $chartStock->load(['prices' => function ($query) {
                $query->orderByDesc('date')->limit(60);
            }]);
        }

        $marketTrend = $chartStock
            ? $chartStock->prices->sortBy('date')->values()
            : collect();

        $aiTopPick = $this->determineAiTopPick();
        $modelComparison = $this->buildModelComparison();
        $performanceAnalysis = $this->buildPerformanceAnalysis($modelComparison);
        $systemSummary = $this->buildSystemSummary();
        $predictionParameters = $this->predictionParameters();

        return view('dashboard.index', compact(
            'stocks',
            'marketTrend',
            'aiTopPick',
            'chartStock',
            'modelComparison',
            'performanceAnalysis',
            'systemSummary',
            'predictionParameters'
        ));
    }

    private function determineAiTopPick(): ?array
    {
        $maximumDatapoints = Stock::where('is_active', true)
            ->where('usable_datapoints_count', '>=', self::MIN_ELIGIBLE_DATAPOINTS)
            ->max('usable_datapoints_count');

        if (!$maximumDatapoints) {
            return null;
        }

        $candidates = Stock::where('is_active', true)
            ->where('usable_datapoints_count', $maximumDatapoints)
            ->with('latestPrice')
            ->orderBy('symbol')
            ->get();

        $predictions = StockPrediction::whereIn('stock_id', $candidates->pluck('id'))
            ->where('model_scope', 'universal')
            ->orderByDesc('id')
            ->get()
            ->filter(fn ($prediction) =>
                ($prediction->additional_metrics['horizon'] ?? null) === '1 Month'
            )
            ->groupBy('stock_id');

        return $candidates
            ->map(function ($stock) use ($predictions) {
                $latestPrice = (float) ($stock->latestPrice?->close ?? 0);
                if ($latestPrice <= 0) {
                    return null;
                }

                $latestByAlgorithm = $predictions
                    ->get($stock->id, collect())
                    ->unique('model_type')
                    ->keyBy('model_type');

                $requiredAlgorithms = [
                    'lstm',
                    'xgboost',
                    'random_forest',
                    'moving_average',
                ];
                if (collect($requiredAlgorithms)->diff($latestByAlgorithm->keys())->isNotEmpty()) {
                    return null;
                }

                $forecastPrices = collect($requiredAlgorithms)
                    ->map(fn ($algorithm) =>
                        (float) $latestByAlgorithm[$algorithm]->predicted_price
                    );
                $returns = $forecastPrices
                    ->map(fn ($price) => (($price - $latestPrice) / $latestPrice) * 100);
                $consensusCount = $returns->filter(fn ($return) => $return > 0)->count();
                $averageForecast = $forecastPrices->average();
                $averageReturn = $returns->average();
                $spread = $averageForecast > 0
                    ? (($forecastPrices->max() - $forecastPrices->min()) / $averageForecast) * 100
                    : 0;
                $targetDate = $latestByAlgorithm->max('target_date');

                return [
                    'stock' => $stock,
                    'latest_price' => $latestPrice,
                    'predicted_price' => $averageForecast,
                    'projected_return' => $averageReturn,
                    'agreement_count' => $consensusCount,
                    'algorithm_count' => count($requiredAlgorithms),
                    'forecast_spread' => $spread,
                    'target_date' => $targetDate,
                    'datapoints' => $stock->usable_datapoints_count,
                ];
            })
            ->filter()
            ->sort(function ($left, $right) {
                return [
                    -$left['agreement_count'],
                    $left['forecast_spread'],
                    -$left['projected_return'],
                    $left['stock']->symbol,
                ] <=> [
                    -$right['agreement_count'],
                    $right['forecast_spread'],
                    -$right['projected_return'],
                    $right['stock']->symbol,
                ];
            })
            ->first();
    }

    private function buildModelComparison(): Collection
    {
        $models = TrainedModel::where('is_active', true)
            ->where(function ($query) {
                $query->where(function ($universal) {
                    $universal->where('model_scope', 'universal')
                        ->whereNull('stock_id');
                })->orWhere('model_scope', 'individual');
            })
            ->get();

        return $models
            ->groupBy(fn ($model) => "{$model->model_scope}:{$model->model_type}")
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'scope' => $first->model_scope,
                    'algorithm' => $first->model_type,
                    'model_count' => $group->count(),
                    'rmse' => $this->averageMetric($group, 'rmse'),
                    'mae' => $this->averageMetric($group, 'mae'),
                    'mape' => $this->averageMetric($group, 'mape'),
                    'r2' => $this->averageMetric($group, 'r2'),
                    'directional_accuracy' => $this->averageMetric(
                        $group,
                        'directional_accuracy'
                    ),
                    'benchmark' => $first->model_type === 'moving_average',
                ];
            })
            ->sortBy([
                ['scope', 'desc'],
                ['mape', 'asc'],
            ])
            ->values();
    }

    private function averageMetric(Collection $models, string $metric): ?float
    {
        $values = $models->pluck($metric)
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value);

        return $values->isEmpty() ? null : $values->average();
    }

    private function buildPerformanceAnalysis(Collection $comparison): array
    {
        return collect(['universal', 'individual'])
            ->mapWithKeys(function ($scope) use ($comparison) {
                $algorithmOrder = [
                    'lstm' => 0,
                    'xgboost' => 1,
                    'random_forest' => 2,
                    'moving_average' => 3,
                ];
                $rows = $comparison->where('scope', $scope)
                    ->sortBy(fn ($row) => $algorithmOrder[$row['algorithm']] ?? 99)
                    ->values();
                $learnedModels = $rows->where('benchmark', false)->values();

                return [$scope => [
                    'charts' => [
                        'labels' => $rows->pluck('algorithm')
                            ->map(fn ($algorithm) =>
                                ucwords(str_replace('_', ' ', $algorithm))
                            )
                            ->values(),
                        'colors' => $rows->map(fn ($row) =>
                            match ($row['algorithm']) {
                                'lstm' => '#7c3aed',
                                'xgboost' => '#f97316',
                                'random_forest' => '#10b981',
                                'moving_average' => '#eab308',
                                default => '#3b82f6',
                            }
                        )->values(),
                        'rmse' => $rows->pluck('rmse')->values(),
                        'mae' => $rows->pluck('mae')->values(),
                        'mape' => $rows->pluck('mape')->values(),
                        'r2' => $rows->pluck('r2')->values(),
                        'directional_accuracy' => $rows
                            ->pluck('directional_accuracy')
                            ->values(),
                    ],
                    'interpretation' => [
                        'best_error' => $this->bestMetricRow($rows, 'mape'),
                        'best_learned_error' => $this->bestMetricRow(
                            $learnedModels,
                            'mape'
                        ),
                        'best_fit' => $this->bestMetricRow($rows, 'r2', true),
                        'best_direction' => $this->bestMetricRow(
                            $learnedModels,
                            'directional_accuracy',
                            true
                        ),
                        'model_count' => (int) $rows->sum('model_count'),
                    ],
                ]];
            })
            ->all();
    }

    private function bestMetricRow(
        Collection $rows,
        string $metric,
        bool $higherIsBetter = false
    ): ?array {
        $eligible = $rows->filter(fn ($row) => $row[$metric] !== null);
        if ($eligible->isEmpty()) {
            return null;
        }

        return $higherIsBetter
            ? $eligible->sortByDesc($metric)->first()
            : $eligible->sortBy($metric)->first();
    }

    private function buildSystemSummary(): array
    {
        return [
            'active_stocks' => Stock::where('is_active', true)->count(),
            'eligible_stocks' => Stock::where('is_active', true)
                ->where('usable_datapoints_count', '>=', self::MIN_ELIGIBLE_DATAPOINTS)
                ->count(),
            'usable_datapoints' => (int) Stock::where('is_active', true)
                ->sum('usable_datapoints_count'),
            'predictions' => StockPrediction::count(),
            'latest_trading_date' => StockPrice::max('date'),
            'universal_coverage' => StockPrediction::where('model_scope', 'universal')
                ->distinct('stock_id')
                ->count('stock_id'),
            'individual_coverage' => StockPrediction::where('model_scope', 'individual')
                ->distinct('stock_id')
                ->count('stock_id'),
        ];
    }

    private function predictionParameters(): array
    {
        $config = TrainedModel::whereNull('stock_id')
            ->where('model_scope', 'universal')
            ->where('is_active', true)
            ->value('config_json') ?? [];

        return [
            'input' => 'Adjusted usable closing-price history and stable one-hot stock identity',
            'sequence_length' => (int) ($config['sequence_length'] ?? 20),
            'minimum_datapoints' => (int) (
                $config['min_stock_length'] ?? self::MIN_ELIGIBLE_DATAPOINTS
            ),
            'train_ratio' => (float) ($config['train_ratio'] ?? 0.80),
            'validation_ratio' => (float) ($config['validation_ratio'] ?? 0.10),
            'test_ratio' => max(
                0,
                1 - (float) ($config['train_ratio'] ?? 0.80)
                    - (float) ($config['validation_ratio'] ?? 0.10)
            ),
            'horizons' => '1 day, 7 days and 30 days',
            'normalization' => 'Min-max scaling fitted only on each training period',
        ];
    }
}

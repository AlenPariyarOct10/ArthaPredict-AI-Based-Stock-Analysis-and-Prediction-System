<?php

use App\Models\Stock;
use App\Models\StockPrice;
use App\Models\StockPrediction;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArthaNoteController;
use App\Http\Controllers\DatasetImportController;
use App\Http\Controllers\AnalysisController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::get('/', function () {
    // Don't cache Eloquent models - they can cause __PHP_Incomplete_Class errors
    // Instead, fetch fresh data or cache only primitives
    $stockCount = Stock::where('is_active', true)->count();
    $latestTradingDate = StockPrice::max('date');
    $predictionCount = StockPrediction::count();
    $latestPriceCount = $latestTradingDate
        ? StockPrice::whereDate('date', $latestTradingDate)->count()
        : 0;

    $featuredStocks = Stock::where('is_active', true)
        ->with([
            'prices' => function ($query) {
                $query->latest('date')->limit(1);
            },
            'predictions' => function ($query) {
                $query->orderBy('target_date')->limit(1);
            },
        ])
        ->take(3)
        ->get();

    $landingData = [
        'stockCount' => $stockCount,
        'latestPriceCount' => $latestPriceCount,
        'predictionCount' => $predictionCount,
        'latestTradingDate' => $latestTradingDate,
    ];

    return view('welcome', [
        'landingStats' => $landingData,
        'featuredStocks' => $featuredStocks,
    ]);
})->name('home');

// Authenticated User Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Management
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // Stock Management
    Route::prefix('stocks')->name('stocks.')->group(function () {
        Route::get('/', [StockController::class, 'index'])->name('index');
        Route::get('/{symbol}/analysis-report', [StockController::class, 'analysisReport'])
            ->name('analysis_report')
            ->middleware('throttle:10,1');
        Route::get('/{symbol}', [StockController::class, 'show'])->name('show');
        Route::get('/{symbol}/export', [StockController::class, 'exportData'])->name('export');

        // Predictions and Analysis
        Route::post('/{symbol}/moving-average', [StockController::class, 'runMovingAverage'])
            ->name('run_ma')
            ->middleware('throttle:10,1');

        Route::post('/{symbol}/predict', [StockController::class, 'runPrediction'])
            ->name('predict')
            ->middleware('throttle:5,1');

        // API endpoints for AJAX calls
        Route::get('/api/{symbol}/chart', [StockController::class, 'getChartData'])
            ->name('api.chart');

        Route::get('/api/{symbol}/predictions', [StockController::class, 'getPredictions'])
            ->name('api.predictions');

        Route::get('/api/{symbol}/metrics', [StockController::class, 'getModelMetrics'])
            ->name('api.metrics');
    });

    // Watchlist
    Route::prefix('watchlist')->name('watchlist.')->group(function () {
        Route::get('/', [WatchlistController::class, 'index'])->name('index');
        Route::post('/toggle', [WatchlistController::class, 'toggle'])->name('toggle');
        Route::get('/export', [WatchlistController::class, 'export'])->name('export');
    });

    // Feedback System
    Route::prefix('feedback')->name('feedback.')->group(function () {
        Route::get('/', [FeedbackController::class, 'index'])->name('index');
        Route::post('/', [FeedbackController::class, 'store'])->name('store');
        Route::get('/my-feedback', [FeedbackController::class, 'userFeedback'])->name('user');
    });

    // Market Analysis
    Route::get('/analysis', [AnalysisController::class, 'index'])
        ->name('analysis.index')
        ->middleware('throttle:30,1');

    Route::get('/analysis/sector/{sector}', [AnalysisController::class, 'sectorAnalysis'])
        ->name('analysis.sector');

    Route::get('/analysis/compare', [AnalysisController::class, 'compareStocks'])
        ->name('analysis.compare');

    // ArthaNotes (Community Notes)
    Route::prefix('arthanotes')->name('arthanotes.')->group(function () {
        Route::get('/', [ArthaNoteController::class, 'index'])->name('index');
        // Show a single note
        Route::get('/{note}', [ArthaNoteController::class, 'show'])->name('show');
        // Edit note (author only)
        Route::get('/{note}/edit', [ArthaNoteController::class, 'edit'])->name('edit');
        Route::patch('/{note}', [ArthaNoteController::class, 'update'])->name('update');
        // Store new note
        Route::post('/', [ArthaNoteController::class, 'store'])->name('store');
        Route::delete('/{note}', [ArthaNoteController::class, 'destroy'])->name('destroy');
        Route::post('/{note}/like', [ArthaNoteController::class, 'toggleLike'])->name('like.toggle');
        Route::post('/{note}/comments', [ArthaNoteController::class, 'storeComment'])->name('comments.store');
        Route::patch('/comments/{comment}', [ArthaNoteController::class, 'updateComment'])->name('comments.update');
        Route::delete('/comments/{comment}', [ArthaNoteController::class, 'destroyComment'])->name('comments.destroy');
        Route::get('/{note}/comments', [ArthaNoteController::class, 'loadComments'])->name('comments.load');

        Route::get('/export/csv', [ArthaNoteController::class, 'exportCsv'])->name('export.csv');
    });

});

// Admin Authentication Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [App\Http\Controllers\Auth\AdminLoginController::class, 'create'])
            ->name('login');
        Route::post('/login', [App\Http\Controllers\Auth\AdminLoginController::class, 'store'])
            ->name('login.store')
            ->middleware('throttle:5,1');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [App\Http\Controllers\Auth\AdminLoginController::class, 'destroy'])
            ->name('logout');
    });
});

// Admin Protected Routes
Route::middleware(['auth', 'admin', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    // Feedback Management
    Route::prefix('feedbacks')->name('feedbacks.')->group(function () {
        Route::get('/', [FeedbackController::class, 'adminIndex'])->name('index');
        Route::get('/{feedback}', [FeedbackController::class, 'adminShow'])->name('show');
        Route::patch('/{feedback}', [FeedbackController::class, 'adminUpdate'])->name('update');
        Route::delete('/{feedback}', [FeedbackController::class, 'adminDestroy'])->name('destroy');
        Route::post('/bulk-resolve', [FeedbackController::class, 'bulkResolve'])->name('bulk-resolve');
    });

    // Model Training Management
    Route::prefix('training')->name('training.')->group(function () {
        Route::post('/start', [AdminController::class, 'trainModel'])->name('start');
        Route::get('/status', [AdminController::class, 'getTrainingStatus'])->name('status');
        Route::post('/cancel/{job}', [AdminController::class, 'cancelTraining'])->name('cancel');
        Route::post('/retry/{job}', [AdminController::class, 'retryFailedTraining'])->name('retry');
        Route::post('/bulk', [AdminController::class, 'bulkTrain'])->name('bulk');
        Route::get('/history', [AdminController::class, 'trainingHistory'])->name('history');
        Route::get('/metrics', [AdminController::class, 'modelMetrics'])->name('metrics');
        Route::post('/universal', [AdminController::class, 'trainUniversalModel'])->name('universal');
        Route::post('/universal/predict', [AdminController::class, 'generateUniversalPredictions'])
            ->name('universal.predict');
    });

    // Dataset Management
    Route::prefix('dataset')->name('dataset-import.')->group(function () {
        Route::get('/', [DatasetImportController::class, 'index'])->name('index');
        Route::post('/import', [DatasetImportController::class, 'import'])->name('import');
        Route::get('/sample', [DatasetImportController::class, 'downloadSample'])->name('sample');
    });

    // Stock Management (Admin)
    Route::prefix('stocks')->name('stocks.')->group(function () {
        Route::get('/', [AdminController::class, 'manageStocks'])->name('manage');
        Route::post('/{stock}/toggle-active', [AdminController::class, 'toggleStockActive'])->name('toggle-active');
        Route::post('/{stock}/update-metadata', [AdminController::class, 'updateStockMetadata'])->name('update-metadata');
        Route::delete('/{stock}', [AdminController::class, 'deleteStock'])->name('delete');
    });

    // System Management
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/cache/clear', [AdminController::class, 'clearCache'])->name('cache.clear');
        Route::get('/queue/stats', [AdminController::class, 'queueStats'])->name('queue.stats');
        Route::get('/logs', [AdminController::class, 'viewLogs'])->name('logs');
        Route::get('/phpinfo', [AdminController::class, 'phpInfo'])->name('phpinfo')
            ->middleware('password.confirm');
    });

    // Logo Management
    Route::prefix('logo')->name('logo.')->group(function () {
        Route::get('/', [AdminController::class, 'showLogoSettings'])->name('settings');
        Route::post('/update', [AdminController::class, 'updateLogo'])->name('update');
        Route::post('/reset', [AdminController::class, 'resetLogo'])->name('reset');
    });
});

// API Routes for AJAX calls (Rate limited)
Route::prefix('api')->name('api.')->middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/stocks/search', [StockController::class, 'search'])->name('stocks.search');
    Route::get('/stocks/popular', [StockController::class, 'popular'])->name('stocks.popular');
    Route::get('/predictions/latest', [StockController::class, 'latestPredictions'])->name('predictions.latest');
});

require __DIR__.'/auth.php';

// Health check endpoint (public, no auth)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'environment' => app()->environment(),
        'laravel_version' => app()->version(),
    ]);
})->name('health');

// Maintenance mode fallback
Route::fallback(function () {
    if (app()->isDownForMaintenance()) {
        return response()->view('errors.maintenance', [], 503);
    }
    abort(404);
});

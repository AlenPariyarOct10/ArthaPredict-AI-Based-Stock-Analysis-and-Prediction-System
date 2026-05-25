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

Route::get('/', function () {
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

    return view('welcome', [
        'landingStats' => [
            'stockCount' => $stockCount,
            'latestPriceCount' => $latestPriceCount,
            'predictionCount' => $predictionCount,
            'latestTradingDate' => $latestTradingDate,
        ],
        'featuredStocks' => $featuredStocks,
    ]);
});

// Replace Breeze's default dashboard route with our ArthaPredict Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Stocks
    Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::get('/stocks/{symbol}', [StockController::class, 'show'])->name('stocks.show');
    Route::post('/stocks/{symbol}/moving-average', [StockController::class, 'runMovingAverage'])->name('stocks.run_ma');
    Route::get('/api/stocks/{symbol}/chart', [StockController::class, 'getChartData'])->name('api.stocks.chart');
    
    // Watchlist
    Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');
    Route::post('/watchlist/toggle', [WatchlistController::class, 'toggle'])->name('watchlist.toggle');
    
    // Feedback
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

    // Analysis
    Route::get('/analysis', [App\Http\Controllers\AnalysisController::class, 'index'])->name('analysis.index');

    // ArthaNotes
    Route::get('/arthanotes', [ArthaNoteController::class, 'index'])->name('arthanotes.index');
    Route::get('/arthanotes/{note}', [ArthaNoteController::class, 'show'])->name('arthanotes.show');
    Route::post('/arthanotes', [ArthaNoteController::class, 'store'])->name('arthanotes.store');
    Route::delete('/arthanotes/{note}', [ArthaNoteController::class, 'destroy'])->name('arthanotes.destroy');
    Route::post('/arthanotes/{note}/like', [ArthaNoteController::class, 'toggleLike'])->name('arthanotes.like.toggle');
    Route::post('/arthanotes/{note}/comments', [ArthaNoteController::class, 'storeComment'])->name('arthanotes.comments.store');
    Route::patch('/arthanotes/comments/{comment}', [ArthaNoteController::class, 'updateComment'])->name('arthanotes.comments.update');
    Route::delete('/arthanotes/comments/{comment}', [ArthaNoteController::class, 'destroyComment'])->name('arthanotes.comments.destroy');
});

// Admin Auth Routes
Route::middleware('guest')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\AdminLoginController::class, 'create'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\AdminLoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Auth\AdminLoginController::class, 'destroy'])->name('logout');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/feedbacks', [FeedbackController::class, 'adminIndex'])->name('feedbacks.index');
    Route::get('/feedbacks/{feedback}', [FeedbackController::class, 'adminShow'])->name('feedbacks.show');
    Route::patch('/feedbacks/{feedback}', [FeedbackController::class, 'adminUpdate'])->name('feedbacks.update');
    Route::post('/train', [AdminController::class, 'trainModel'])->name('train');
    Route::get('/train/status', [AdminController::class, 'getTrainingStatus'])->name('train.status');
    Route::get('/dataset-import', [DatasetImportController::class, 'index'])->name('dataset-import.index');
    Route::post('/dataset-import', [DatasetImportController::class, 'import'])->name('dataset-import.import');
    Route::get('/dataset-import/sample', [DatasetImportController::class, 'downloadSample'])->name('dataset-import.sample');
});

require __DIR__.'/auth.php';


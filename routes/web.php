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
    Route::post('/train', [AdminController::class, 'trainModel'])->name('train');
});

require __DIR__.'/auth.php';


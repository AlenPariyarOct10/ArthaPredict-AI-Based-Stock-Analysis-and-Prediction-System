<?php

namespace Database\Seeders;

use App\Models\Stock;
use App\Models\StockPrediction;
use App\Models\StockPrice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@arthapredict.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        // Regular user
        User::create([
            'name' => 'Regular User',
            'email' => 'user@arthapredict.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $this->call(ArthaNoteSeeder::class);

        // Sample Stocks
        $stocks = [
            ['symbol' => 'AAPL', 'name' => 'Apple Inc.', 'sector' => 'Technology', 'exchange' => 'NASDAQ'],
            ['symbol' => 'GOOGL', 'name' => 'Alphabet Inc.', 'sector' => 'Technology', 'exchange' => 'NASDAQ'],
            ['symbol' => 'MSFT', 'name' => 'Microsoft Corp.', 'sector' => 'Technology', 'exchange' => 'NASDAQ'],
            ['symbol' => 'TSLA', 'name' => 'Tesla Inc.', 'sector' => 'Automotive', 'exchange' => 'NASDAQ'],
        ];

        foreach ($stocks as $stockData) {
            $stock = Stock::create($stockData);

            // Generate last 30 days of dummy prices
            $startDate = Carbon::today()->subDays(30);
            $basePrice = rand(100, 300);

            for ($i = 0; $i < 30; $i++) {
                $date = $startDate->copy()->addDays($i);

                // Skip weekends
                if ($date->isWeekend()) {
                    continue;
                }

                $change = (rand(-50, 50) / 100) * $basePrice * 0.05; // 5% max daily change
                $close = $basePrice + $change;
                $open = $close - (rand(-20, 20) / 100) * 2;
                $high = max($open, $close) + (rand(1, 20) / 100) * 2;
                $low = min($open, $close) - (rand(1, 20) / 100) * 2;

                StockPrice::create([
                    'stock_id' => $stock->id,
                    'date' => $date->format('Y-m-d'),
                    'open' => $open,
                    'high' => $high,
                    'low' => $low,
                    'close' => $close,
                    'volume' => rand(1000000, 5000000),
                ]);

                $basePrice = $close;
            }

            // Generate placeholder predictions
            $models = ['Moving Average', 'XGBoost', 'LSTM'];
            $targetDate = Carbon::today()->addDays(1);

            foreach ($models as $model) {
                $predictedPrice = $basePrice + (rand(-100, 100) / 100) * $basePrice * 0.05;
                StockPrediction::create([
                    'stock_id' => $stock->id,
                    'model_type' => $model,
                    'target_date' => $targetDate->format('Y-m-d'),
                    'predicted_price' => $predictedPrice,
                    'additional_metrics' => [
                        'confidence_interval' => rand(70, 95).'%',
                    ],
                ]);
            }
        }
    }
}

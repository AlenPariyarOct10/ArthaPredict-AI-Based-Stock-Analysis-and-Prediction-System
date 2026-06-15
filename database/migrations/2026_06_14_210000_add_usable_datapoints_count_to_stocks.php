<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->unsignedInteger('usable_datapoints_count')
                ->default(0)
                ->after('is_active')
                ->index();
        });

        DB::table('stock_prices')
            ->select(
                'stock_id',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('MAX(close) as maximum_close'),
                DB::raw(
                    'SUM(CASE WHEN close >= 10 THEN 1 ELSE 0 END) '
                    . 'as ordinary_price_count'
                )
            )
            ->groupBy('stock_id')
            ->orderBy('stock_id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('stocks')
                        ->where('id', $row->stock_id)
                        ->update([
                        'usable_datapoints_count' => $row->maximum_close >= 50
                            ? $row->ordinary_price_count
                            : $row->total_count,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn('usable_datapoints_count');
        });
    }
};

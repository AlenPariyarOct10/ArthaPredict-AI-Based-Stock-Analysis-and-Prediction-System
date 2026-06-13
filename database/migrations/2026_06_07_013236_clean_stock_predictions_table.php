<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Stock predictions table already has id, stock_id, model_type, target_date, predicted_price, additional_metrics.
        // We'll keep additional_metrics for prediction-specific confidence scores if needed in future.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE trained_models MODIFY COLUMN model_type VARCHAR(50) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE trained_models
            MODIFY COLUMN model_type ENUM(
                'lstm',
                'xgboost',
                'moving_average'
            ) NOT NULL
        ");
    }
};

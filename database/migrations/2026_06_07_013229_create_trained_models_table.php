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
        Schema::create('trained_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->string('stock_symbol');
            $table->enum('model_type', ['lstm', 'xgboost', 'moving_average']);
            $table->string('model_path')->nullable();
            $table->string('latest_path')->nullable();
            $table->string('fingerprint')->nullable();
            $table->timestamp('training_date')->nullable();
            $table->decimal('mse', 15, 8)->nullable();
            $table->decimal('mae', 15, 8)->nullable();
            $table->decimal('rmse', 15, 8)->nullable();
            $table->decimal('mape', 15, 8)->nullable();
            $table->decimal('directional_accuracy', 5, 2)->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->decimal('training_loss', 15, 8)->nullable();
            $table->json('config_json')->nullable();
            $table->integer('data_length')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trained_models');
    }
};

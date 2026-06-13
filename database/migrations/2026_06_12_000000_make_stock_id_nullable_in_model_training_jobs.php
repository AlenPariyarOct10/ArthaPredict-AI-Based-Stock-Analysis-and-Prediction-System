<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_training_jobs', function (Blueprint $table) {
            // Make stock_id nullable to support universal model training
            $table->unsignedBigInteger('stock_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('model_training_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_id')->nullable(false)->change();
        });
    }
};

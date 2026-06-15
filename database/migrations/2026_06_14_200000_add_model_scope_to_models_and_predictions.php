<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trained_models', function (Blueprint $table) {
            $table->string('model_scope', 20)
                ->default('universal')
                ->after('model_type');
            $table->index(
                ['stock_id', 'model_type', 'model_scope', 'is_active'],
                'trained_models_scope_lookup'
            );
        });

        Schema::table('stock_predictions', function (Blueprint $table) {
            $table->string('model_scope', 20)
                ->default('universal')
                ->after('model_type');
            $table->index(
                ['stock_id', 'model_type', 'model_scope', 'target_date'],
                'stock_predictions_scope_lookup'
            );
        });

        DB::table('trained_models')->update(['model_scope' => 'universal']);
        DB::table('stock_predictions')->update(['model_scope' => 'universal']);
    }

    public function down(): void
    {
        Schema::table('stock_predictions', function (Blueprint $table) {
            $table->dropIndex('stock_predictions_scope_lookup');
            $table->dropColumn('model_scope');
        });

        Schema::table('trained_models', function (Blueprint $table) {
            $table->dropIndex('trained_models_scope_lookup');
            $table->dropColumn('model_scope');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trained_models', function (Blueprint $table) {
            $table->decimal('r2', 15, 8)->nullable()->after('mape');
        });
    }

    public function down(): void
    {
        Schema::table('trained_models', function (Blueprint $table) {
            $table->dropColumn('r2');
        });
    }
};

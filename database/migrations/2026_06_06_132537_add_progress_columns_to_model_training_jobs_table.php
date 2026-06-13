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
        Schema::table('model_training_jobs', function (Blueprint $table) {
            $table->integer('total_rows')->default(0)->after('status');
            $table->integer('processed_rows')->default(0)->after('total_rows');
            $table->string('current_stage')->nullable()->after('processed_rows');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_training_jobs', function (Blueprint $table) {
            $table->dropColumn(['total_rows', 'processed_rows', 'current_stage']);
        });
    }
};

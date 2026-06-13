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
            $table->json('meta')->nullable()->after('error_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_training_jobs', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};

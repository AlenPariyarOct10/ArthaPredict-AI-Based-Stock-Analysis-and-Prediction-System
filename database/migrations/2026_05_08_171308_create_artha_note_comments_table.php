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
        Schema::create('artha_note_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artha_note_id')->constrained('artha_notes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('artha_note_comments')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['artha_note_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artha_note_comments');
    }
};

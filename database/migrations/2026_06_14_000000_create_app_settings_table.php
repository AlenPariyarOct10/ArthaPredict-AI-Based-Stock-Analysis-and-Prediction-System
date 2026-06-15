<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, image, color, etc.
            $table->string('group')->default('general');
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('app_settings')->insert([
            [
                'key' => 'app_logo',
                'value' => 'assets/images/Logo.png',
                'type' => 'image',
                'group' => 'general',
                'label' => 'Application Logo',
                'description' => 'The main logo displayed in the sidebar and landing page.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'app_name',
                'value' => 'ArthaPredict',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Application Name',
                'description' => 'The main name of the application.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};

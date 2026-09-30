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
        Schema::create('writing_exercises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_mode_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('prompt');
            $table->text('instructions')->nullable();
            $table->string('language')->default('en-US');
            $table->string('level')->default('beginner');
            $table->unsignedSmallInteger('min_words')->default(20);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['learning_mode_id', 'is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('writing_exercises');
    }
};

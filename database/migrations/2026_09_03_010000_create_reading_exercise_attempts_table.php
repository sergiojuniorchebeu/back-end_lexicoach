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
        Schema::create('reading_exercise_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reading_exercise_id')->constrained()->cascadeOnDelete();
            $table->text('transcript');
            $table->unsignedTinyInteger('score');
            $table->string('status');
            $table->boolean('is_correct');
            $table->json('words');
            $table->json('feedback');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'reading_exercise_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_exercise_attempts');
    }
};

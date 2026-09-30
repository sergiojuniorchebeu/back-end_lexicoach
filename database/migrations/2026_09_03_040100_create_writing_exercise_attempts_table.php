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
        Schema::create('writing_exercise_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('writing_exercise_id')->constrained()->cascadeOnDelete();
            $table->text('answer');
            $table->unsignedTinyInteger('score');
            $table->string('status');
            $table->text('corrected_text');
            $table->json('mistakes');
            $table->json('suggestions');
            $table->json('feedback');
            $table->json('raw_ai_response')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'writing_exercise_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('writing_exercise_attempts');
    }
};

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
        Schema::create('smart_abstract_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('smart_abstract_exercise_id')->constrained()->cascadeOnDelete();
            $table->text('summary');
            $table->unsignedTinyInteger('score');
            $table->string('status');
            $table->text('improved_summary');
            $table->json('missing_ideas');
            $table->json('strengths');
            $table->json('feedback');
            $table->json('raw_ai_response')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'smart_abstract_exercise_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smart_abstract_attempts');
    }
};

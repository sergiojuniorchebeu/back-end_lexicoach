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
        Schema::table('ai_conversation_sessions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('assessment_score')->nullable()->after('ended_at');
            $table->string('assessment_status')->nullable()->after('assessment_score');
            $table->json('assessment_feedback')->nullable()->after('assessment_status');
            $table->timestamp('assessed_at')->nullable()->after('assessment_feedback');
        });

        Schema::create('ai_conversation_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_conversation_session_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('role');
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['ai_conversation_session_id', 'created_at']);
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_conversation_messages');

        Schema::table('ai_conversation_sessions', function (Blueprint $table): void {
            $table->dropColumn([
                'assessment_score',
                'assessment_status',
                'assessment_feedback',
                'assessed_at',
            ]);
        });
    }
};

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
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedSmallInteger('ai_conversation_session_limit_seconds')
                ->default(180)
                ->after('role');
            $table->unsignedTinyInteger('ai_conversation_daily_session_limit')
                ->default(3)
                ->after('ai_conversation_session_limit_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'ai_conversation_session_limit_seconds',
                'ai_conversation_daily_session_limit',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('preferred_language', 12)->default('en')->after('role');
            $table->string('learning_level', 30)->default('beginner')->after('preferred_language');
            $table->unsignedTinyInteger('dyslexia_font_size')->default(16)->after('learning_level');
            $table->boolean('dyslexia_slow_speech')->default(true)->after('dyslexia_font_size');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'preferred_language',
                'learning_level',
                'dyslexia_font_size',
                'dyslexia_slow_speech',
            ]);
        });
    }
};

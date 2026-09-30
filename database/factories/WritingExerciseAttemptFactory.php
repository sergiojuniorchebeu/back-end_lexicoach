<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WritingExercise;
use App\Models\WritingExerciseAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WritingExerciseAttempt>
 */
class WritingExerciseAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'writing_exercise_id' => WritingExercise::factory(),
            'answer' => fake()->paragraph(),
            'score' => fake()->numberBetween(50, 100),
            'status' => 'good',
            'corrected_text' => fake()->paragraph(),
            'mistakes' => [],
            'suggestions' => ['Add more detail.'],
            'feedback' => [
                'title' => 'Good work!',
                'message' => 'Your answer is understandable.',
            ],
            'raw_ai_response' => [
                'provider' => 'fake',
            ],
        ];
    }
}

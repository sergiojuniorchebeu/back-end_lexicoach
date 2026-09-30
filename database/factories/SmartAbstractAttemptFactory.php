<?php

namespace Database\Factories;

use App\Models\SmartAbstractAttempt;
use App\Models\SmartAbstractExercise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmartAbstractAttempt>
 */
class SmartAbstractAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'smart_abstract_exercise_id' => SmartAbstractExercise::factory(),
            'summary' => fake()->paragraph(),
            'score' => fake()->numberBetween(50, 100),
            'status' => 'good',
            'improved_summary' => fake()->paragraph(),
            'missing_ideas' => [],
            'strengths' => ['The summary is readable.'],
            'feedback' => [
                'title' => 'Good summary!',
                'message' => 'The main idea is present.',
            ],
            'raw_ai_response' => [
                'provider' => 'fake',
            ],
        ];
    }
}

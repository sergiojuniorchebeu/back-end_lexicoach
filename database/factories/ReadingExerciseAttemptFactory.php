<?php

namespace Database\Factories;

use App\Models\ReadingExercise;
use App\Models\ReadingExerciseAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingExerciseAttempt>
 */
class ReadingExerciseAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reading_exercise_id' => ReadingExercise::factory(),
            'transcript' => 'The children visited the beautiful museum yesterday.',
            'score' => fake()->numberBetween(50, 100),
            'status' => 'good',
            'is_correct' => false,
            'words' => [
                [
                    'expected' => 'The',
                    'actual' => 'The',
                    'status' => 'correct',
                ],
            ],
            'feedback' => [
                'title' => 'Good job!',
                'message' => 'You read most of the sentence correctly.',
            ],
        ];
    }
}

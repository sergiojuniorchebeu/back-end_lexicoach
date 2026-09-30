<?php

namespace Database\Factories;

use App\Models\LearningMode;
use App\Models\ReadingExercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingExercise>
 */
class ReadingExerciseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'learning_mode_id' => LearningMode::query()->firstOrCreate(
                ['slug' => LearningMode::SLUG_READING],
                [
                    'name' => 'Reading Practice',
                    'description' => 'Read a sentence aloud, compare your transcript and improve fluency.',
                    'sort_order' => 1,
                ],
            )->id,
            'title' => fake()->sentence(3),
            'text' => fake()->sentence(8),
            'language' => 'en-US',
            'level' => 'beginner',
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}

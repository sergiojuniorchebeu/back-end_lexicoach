<?php

namespace Database\Factories;

use App\Models\LearningMode;
use App\Models\WritingExercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WritingExercise>
 */
class WritingExerciseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'learning_mode_id' => LearningMode::query()->firstOrCreate(
                ['slug' => LearningMode::SLUG_WRITING],
                [
                    'name' => 'Writing Assistant',
                    'description' => 'Write a text and receive spelling, grammar and clarity feedback.',
                    'sort_order' => 2,
                ],
            )->id,
            'title' => fake()->sentence(3),
            'prompt' => fake()->sentence(10),
            'instructions' => 'Write a short answer with clear sentences.',
            'language' => 'en-US',
            'level' => 'beginner',
            'min_words' => 20,
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}

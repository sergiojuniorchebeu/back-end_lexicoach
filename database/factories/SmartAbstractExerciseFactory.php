<?php

namespace Database\Factories;

use App\Models\LearningMode;
use App\Models\SmartAbstractExercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmartAbstractExercise>
 */
class SmartAbstractExerciseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'learning_mode_id' => LearningMode::query()->firstOrCreate(
                ['slug' => LearningMode::SLUG_SMART_ABSTRACT],
                [
                    'name' => 'Smart Abstract',
                    'description' => 'Summarize long text into simpler ideas.',
                    'sort_order' => 5,
                ],
            )->id,
            'title' => fake()->sentence(3),
            'source_text' => fake()->paragraph(5),
            'instructions' => 'Read the text and write a short summary.',
            'language' => 'en-US',
            'level' => 'beginner',
            'min_words' => 20,
            'max_words' => 80,
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}

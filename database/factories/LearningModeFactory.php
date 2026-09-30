<?php

namespace Database\Factories;

use App\Models\LearningMode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningMode>
 */
class LearningModeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }

    public function reading(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Reading Practice',
            'slug' => LearningMode::SLUG_READING,
            'description' => 'Read a sentence aloud, compare your transcript and improve fluency.',
            'sort_order' => 1,
        ]);
    }
}

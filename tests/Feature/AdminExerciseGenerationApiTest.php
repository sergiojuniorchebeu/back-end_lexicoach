<?php

namespace Tests\Feature;

use App\Models\ReadingExercise;
use App\Models\SmartAbstractExercise;
use App\Models\User;
use App\Models\WritingExercise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminExerciseGenerationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('ai.provider', 'fake');
    }

    public function test_admin_can_generate_reading_exercises(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/exercises/generate', [
            'type' => 'reading',
            'level' => 'beginner',
            'language' => 'en-US',
            'count' => 3,
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.exercises');

        $this->assertDatabaseCount('reading_exercises', 3);
        $this->assertSame('en-US', ReadingExercise::query()->first()->language);
    }

    public function test_admin_can_generate_writing_exercises(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/exercises/generate', [
            'type' => 'writing',
            'level' => 'intermediate',
            'language' => 'fr-FR',
            'count' => 2,
        ])
            ->assertCreated()
            ->assertJsonCount(2, 'data.exercises');

        $this->assertDatabaseCount('writing_exercises', 2);
    }

    public function test_admin_can_generate_smart_abstract_exercises(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/exercises/generate', [
            'type' => 'smart-abstract',
            'level' => 'advanced',
            'language' => 'en-US',
            'count' => 1,
        ])
            ->assertCreated()
            ->assertJsonCount(1, 'data.exercises');

        $this->assertDatabaseCount('smart_abstract_exercises', 1);
    }

    public function test_generation_request_is_validated(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/exercises/generate', [
            'type' => 'unknown-type',
            'level' => 'beginner',
            'language' => 'en-US',
            'count' => 20,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'count']);
    }

    public function test_generation_requires_admin_role(): void
    {
        $learner = User::factory()->create();

        Sanctum::actingAs($learner);

        $this->postJson('/api/admin/exercises/generate', [
            'type' => 'reading',
            'level' => 'beginner',
            'language' => 'en-US',
            'count' => 1,
        ])->assertForbidden();
    }
}

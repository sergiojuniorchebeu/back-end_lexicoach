<?php

namespace Database\Seeders;

use App\Models\LearningMode;
use App\Models\ReadingExercise;
use App\Models\SmartAbstractExercise;
use App\Models\User;
use App\Models\WritingExercise;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::query()->firstOrCreate([
            'email' => 'learner@example.com',
        ], [
            'name' => 'Marie Learner',
            'password' => 'password123',
            'role' => User::ROLE_LEARNER,
        ]);

        User::query()->firstOrCreate([
            'email' => 'tutor@example.com',
        ], [
            'name' => 'Paul Tutor',
            'password' => 'password123',
            'role' => User::ROLE_TUTOR,
        ]);

        User::query()->firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin LexiCoach',
            'password' => 'password123',
            'role' => User::ROLE_ADMIN,
        ]);

        $readingMode = LearningMode::query()->updateOrCreate([
            'slug' => LearningMode::SLUG_READING,
        ], [
            'name' => 'Reading Practice',
            'description' => 'Read a sentence aloud, compare your transcript and improve fluency.',
            'sort_order' => 1,
        ]);

        $writingMode = LearningMode::query()->updateOrCreate([
            'slug' => LearningMode::SLUG_WRITING,
        ], [
            'name' => 'Writing Assistant',
            'description' => 'Write a text and receive spelling, grammar and clarity feedback.',
            'sort_order' => 2,
        ]);

        LearningMode::query()->updateOrCreate([
            'slug' => LearningMode::SLUG_DICTATION,
        ], [
            'name' => 'Vocal Dictation',
            'description' => 'Listen to a sentence, repeat it and check the transcript.',
            'sort_order' => 3,
        ]);

        LearningMode::query()->updateOrCreate([
            'slug' => LearningMode::SLUG_WORD_SPLITTING,
        ], [
            'name' => 'Word Splitting',
            'description' => 'Split difficult words into smaller readable parts.',
            'sort_order' => 4,
        ]);

        $smartAbstractMode = LearningMode::query()->updateOrCreate([
            'slug' => LearningMode::SLUG_SMART_ABSTRACT,
        ], [
            'name' => 'Smart Abstract',
            'description' => 'Summarize long text into simpler ideas.',
            'sort_order' => 5,
        ]);

        ReadingExercise::query()->updateOrCreate([
            'title' => 'Museum visit',
        ], [
            'learning_mode_id' => $readingMode->id,
            'text' => 'The children visited the beautiful museum yesterday.',
            'language' => 'en-US',
            'level' => 'beginner',
            'sort_order' => 1,
        ]);

        ReadingExercise::query()->updateOrCreate([
            'title' => 'Garden story',
        ], [
            'learning_mode_id' => $readingMode->id,
            'text' => 'The little boy is playing in the garden.',
            'language' => 'en-US',
            'level' => 'beginner',
            'sort_order' => 2,
        ]);

        WritingExercise::query()->updateOrCreate([
            'title' => 'My school day',
        ], [
            'learning_mode_id' => $writingMode->id,
            'prompt' => 'Write five sentences about your school day.',
            'instructions' => 'Use simple sentences. Try to explain what you did, what you liked and one thing you learned.',
            'language' => 'en-US',
            'level' => 'beginner',
            'min_words' => 25,
            'sort_order' => 1,
        ]);

        WritingExercise::query()->updateOrCreate([
            'title' => 'A helpful friend',
        ], [
            'learning_mode_id' => $writingMode->id,
            'prompt' => 'Describe a friend who helped you.',
            'instructions' => 'Write who helped you, what happened and how you felt.',
            'language' => 'en-US',
            'level' => 'beginner',
            'min_words' => 20,
            'sort_order' => 2,
        ]);

        SmartAbstractExercise::query()->updateOrCreate([
            'title' => 'Library paragraph',
        ], [
            'learning_mode_id' => $smartAbstractMode->id,
            'source_text' => 'Every Wednesday, the class visits the library. The teacher helps each learner choose one book. After reading quietly, the learners share one new word they discovered.',
            'instructions' => 'Write a short summary with the main idea and one important detail.',
            'language' => 'en-US',
            'level' => 'beginner',
            'min_words' => 20,
            'max_words' => 60,
            'sort_order' => 1,
        ]);

        SmartAbstractExercise::query()->updateOrCreate([
            'title' => 'Garden paragraph',
        ], [
            'learning_mode_id' => $smartAbstractMode->id,
            'source_text' => 'Lina planted tomatoes in a small garden behind her home. She watered them every morning before school. After a few weeks, the plants grew tall and produced red tomatoes for her family.',
            'instructions' => 'Summarize what Lina did and what happened at the end.',
            'language' => 'en-US',
            'level' => 'beginner',
            'min_words' => 20,
            'max_words' => 60,
            'sort_order' => 2,
        ]);
    }
}

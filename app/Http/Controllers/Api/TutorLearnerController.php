<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearnerAssociationCode;
use App\Models\ReadingExerciseAttempt;
use App\Models\SmartAbstractAttempt;
use App\Models\User;
use App\Models\WritingExerciseAttempt;
use App\Services\LearningModeProgressSummary;
use App\Services\ReadingProgressSummary;
use App\Services\SmartAbstractProgressSummary;
use App\Services\WritingProgressSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TutorLearnerController extends Controller
{
    public function __construct(
        private readonly ReadingProgressSummary $readingProgressSummary,
        private readonly WritingProgressSummary $writingProgressSummary,
        private readonly SmartAbstractProgressSummary $smartAbstractProgressSummary,
        private readonly LearningModeProgressSummary $learningModeProgressSummary,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tutor = $this->userFrom($request);

        $learners = $tutor->learners()
            ->where('role', User::ROLE_LEARNER)
            ->orderBy('name')
            ->get()
            ->map(fn (User $learner): array => $this->formatLearner($learner));

        return response()->json([
            'success' => true,
            'message' => 'Tutor learners retrieved.',
            'data' => [
                'learners' => $learners,
            ],
        ]);
    }

    public function link(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $tutor = $this->userFrom($request);
        $associationCode = LearnerAssociationCode::query()
            ->with('learner')
            ->where('code', $validated['code'])
            ->first();

        if (! $associationCode instanceof LearnerAssociationCode || ! $associationCode->isUsable()) {
            throw ValidationException::withMessages([
                'code' => ['This association code is invalid or expired.'],
            ]);
        }

        $learner = $associationCode->learner;

        if ($learner->role !== User::ROLE_LEARNER) {
            throw ValidationException::withMessages([
                'code' => ['This code does not belong to a learner.'],
            ]);
        }

        $tutor->learners()->syncWithoutDetaching([$learner->id]);
        $associationCode->update([
            'used_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Learner linked to tutor.',
            'data' => [
                'learner' => $this->formatLearner($learner),
            ],
        ], 201);
    }

    public function progress(Request $request, User $learner): JsonResponse
    {
        $tutor = $this->userFrom($request);
        $this->ensureTutorCanAccessLearner($tutor, $learner);

        return response()->json([
            'success' => true,
            'message' => 'Learner progress retrieved.',
            'data' => [
                'learner' => $this->formatLearner($learner),
                'progress' => $this->learningModeProgressSummary->forUser($learner),
            ],
        ]);
    }

    public function readingAttempts(Request $request, User $learner): JsonResponse
    {
        $tutor = $this->userFrom($request);
        $this->ensureTutorCanAccessLearner($tutor, $learner);

        $attempts = ReadingExerciseAttempt::query()
            ->with('readingExercise')
            ->whereBelongsTo($learner, 'user')
            ->latest()
            ->get()
            ->map(fn (ReadingExerciseAttempt $attempt): array => $this->readingProgressSummary->formatAttempt($attempt));

        return response()->json([
            'success' => true,
            'message' => 'Learner reading attempts retrieved.',
            'data' => [
                'learner' => $this->formatLearner($learner),
                'attempts' => $attempts,
            ],
        ]);
    }

    public function writingAttempts(Request $request, User $learner): JsonResponse
    {
        $tutor = $this->userFrom($request);
        $this->ensureTutorCanAccessLearner($tutor, $learner);

        $attempts = WritingExerciseAttempt::query()
            ->with('writingExercise')
            ->whereBelongsTo($learner, 'user')
            ->latest()
            ->get()
            ->map(fn (WritingExerciseAttempt $attempt): array => $this->writingProgressSummary->formatAttempt($attempt));

        return response()->json([
            'success' => true,
            'message' => 'Learner writing attempts retrieved.',
            'data' => [
                'learner' => $this->formatLearner($learner),
                'attempts' => $attempts,
            ],
        ]);
    }

    public function smartAbstractAttempts(Request $request, User $learner): JsonResponse
    {
        $tutor = $this->userFrom($request);
        $this->ensureTutorCanAccessLearner($tutor, $learner);

        $attempts = SmartAbstractAttempt::query()
            ->with('smartAbstractExercise')
            ->whereBelongsTo($learner, 'user')
            ->latest()
            ->get()
            ->map(fn (SmartAbstractAttempt $attempt): array => $this->smartAbstractProgressSummary->formatAttempt($attempt));

        return response()->json([
            'success' => true,
            'message' => 'Learner smart abstract attempts retrieved.',
            'data' => [
                'learner' => $this->formatLearner($learner),
                'attempts' => $attempts,
            ],
        ]);
    }

    public function unlink(Request $request, User $learner): JsonResponse
    {
        $tutor = $this->userFrom($request);
        $this->ensureTutorCanAccessLearner($tutor, $learner);

        $tutor->learners()->detach($learner->id);

        return response()->json([
            'success' => true,
            'message' => 'Learner detached from tutor.',
            'data' => null,
        ]);
    }

    private function userFrom(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function ensureTutorCanAccessLearner(User $tutor, User $learner): void
    {
        abort_unless($learner->role === User::ROLE_LEARNER, 404);

        $isLinked = $tutor->learners()
            ->whereKey($learner->id)
            ->exists();

        abort_unless($isLinked, 403, 'This learner is not linked to this tutor.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatLearner(User $learner): array
    {
        return [
            'id' => $learner->id,
            'full_name' => $learner->name,
            'email' => $learner->email,
            'role' => $learner->role,
        ];
    }
}

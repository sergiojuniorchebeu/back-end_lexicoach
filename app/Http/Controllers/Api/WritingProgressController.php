<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WritingExerciseAttempt;
use App\Services\WritingProgressSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WritingProgressController extends Controller
{
    public function __construct(
        private readonly WritingProgressSummary $writingProgressSummary,
    ) {}

    public function attempts(Request $request): JsonResponse
    {
        $user = $this->userFrom($request);

        $attempts = WritingExerciseAttempt::query()
            ->with('writingExercise')
            ->whereBelongsTo($user)
            ->latest()
            ->get()
            ->map(fn (WritingExerciseAttempt $attempt): array => $this->writingProgressSummary->formatAttempt($attempt));

        return response()->json([
            'success' => true,
            'message' => 'Writing attempts retrieved.',
            'data' => [
                'attempts' => $attempts,
            ],
        ]);
    }

    public function progress(Request $request): JsonResponse
    {
        $user = $this->userFrom($request);

        return response()->json([
            'success' => true,
            'message' => 'Writing progress retrieved.',
            'data' => [
                'progress' => $this->writingProgressSummary->forUser($user),
            ],
        ]);
    }

    private function userFrom(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}

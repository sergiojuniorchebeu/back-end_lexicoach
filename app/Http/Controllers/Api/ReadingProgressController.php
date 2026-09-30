<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReadingExerciseAttempt;
use App\Models\User;
use App\Services\ReadingProgressSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingProgressController extends Controller
{
    public function __construct(
        private readonly ReadingProgressSummary $readingProgressSummary,
    ) {}

    public function attempts(Request $request): JsonResponse
    {
        $user = $this->userFrom($request);

        $attempts = ReadingExerciseAttempt::query()
            ->with('readingExercise')
            ->whereBelongsTo($user)
            ->latest()
            ->get()
            ->map(fn (ReadingExerciseAttempt $attempt): array => $this->readingProgressSummary->formatAttempt($attempt));

        return response()->json([
            'success' => true,
            'message' => 'Reading attempts retrieved.',
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
            'message' => 'Reading progress retrieved.',
            'data' => [
                'progress' => $this->readingProgressSummary->forUser($user),
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmartAbstractAttempt;
use App\Models\User;
use App\Services\SmartAbstractProgressSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmartAbstractProgressController extends Controller
{
    public function __construct(
        private readonly SmartAbstractProgressSummary $smartAbstractProgressSummary,
    ) {}

    public function attempts(Request $request): JsonResponse
    {
        $user = $this->userFrom($request);

        $attempts = SmartAbstractAttempt::query()
            ->with('smartAbstractExercise')
            ->whereBelongsTo($user)
            ->latest()
            ->get()
            ->map(fn (SmartAbstractAttempt $attempt): array => $this->smartAbstractProgressSummary->formatAttempt($attempt));

        return response()->json([
            'success' => true,
            'message' => 'Smart abstract attempts retrieved.',
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
            'message' => 'Smart abstract progress retrieved.',
            'data' => [
                'progress' => $this->smartAbstractProgressSummary->forUser($user),
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

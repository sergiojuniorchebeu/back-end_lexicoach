<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LearningModeProgressSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalProgressController extends Controller
{
    public function __construct(
        private readonly LearningModeProgressSummary $learningModeProgressSummary,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return response()->json([
            'success' => true,
            'message' => 'Global progress retrieved.',
            'data' => [
                'progress' => $this->learningModeProgressSummary->forUser($user),
            ],
        ]);
    }
}

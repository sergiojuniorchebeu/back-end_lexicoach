<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TutorDashboardSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TutorDashboardController extends Controller
{
    public function __construct(
        private readonly TutorDashboardSummary $tutorDashboardSummary,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $tutor = $request->user();

        abort_unless($tutor instanceof User, 401);

        return response()->json([
            'success' => true,
            'message' => 'Tutor dashboard retrieved.',
            'data' => [
                'dashboard' => $this->tutorDashboardSummary->forTutor($tutor),
            ],
        ]);
    }
}

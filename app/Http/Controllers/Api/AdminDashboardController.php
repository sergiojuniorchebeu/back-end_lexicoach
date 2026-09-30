<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardSummary;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardSummary $adminDashboardSummary,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Admin dashboard retrieved.',
            'data' => [
                'dashboard' => $this->adminDashboardSummary->get(),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['nullable', 'string', Rule::in(User::roles())],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $users = User::query()
            ->when($validated['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->withCount(['readingExerciseAttempts', 'learners', 'tutors'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (User $user): array => $this->formatUser($user));

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved.',
            'data' => [
                'users' => $users,
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount(['readingExerciseAttempts', 'learners', 'tutors']);

        return response()->json([
            'success' => true,
            'message' => 'User retrieved.',
            'data' => [
                'user' => $this->formatUser($user),
            ],
        ]);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(User::roles())],
        ]);

        $admin = $request->user();

        if ($admin instanceof User && $admin->is($user) && $validated['role'] !== User::ROLE_ADMIN) {
            throw ValidationException::withMessages([
                'role' => ['Un admin ne peut pas retirer son propre role admin.'],
            ]);
        }

        $user->update([
            'role' => $validated['role'],
        ]);

        $user->loadCount(['readingExerciseAttempts', 'learners', 'tutors']);

        return response()->json([
            'success' => true,
            'message' => 'User role updated.',
            'data' => [
                'user' => $this->formatUser($user),
            ],
        ]);
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(User::statuses())],
        ]);

        $admin = $request->user();

        if ($admin instanceof User && $admin->is($user) && $validated['status'] === User::STATUS_SUSPENDED) {
            throw ValidationException::withMessages([
                'status' => ['Un admin ne peut pas suspendre son propre compte.'],
            ]);
        }

        $user->update([
            'status' => $validated['status'],
        ]);

        if ($validated['status'] === User::STATUS_SUSPENDED) {
            $user->tokens()->delete();
        }

        $user->loadCount(['readingExerciseAttempts', 'learners', 'tutors']);

        return response()->json([
            'success' => true,
            'message' => 'User status updated.',
            'data' => [
                'user' => $this->formatUser($user),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'reading_attempts_count' => (int) ($user->getAttribute('reading_exercise_attempts_count') ?? 0),
            'learners_count' => (int) ($user->getAttribute('learners_count') ?? 0),
            'tutors_count' => (int) ($user->getAttribute('tutors_count') ?? 0),
            'created_at' => $user->created_at,
        ];
    }
}

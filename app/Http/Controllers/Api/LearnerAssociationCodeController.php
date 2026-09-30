<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearnerAssociationCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearnerAssociationCodeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $this->userFrom($request);
        $associationCode = $this->activeCodeFor($user);

        return response()->json([
            'success' => true,
            'message' => $associationCode instanceof LearnerAssociationCode
                ? 'Active association code retrieved.'
                : 'No active association code.',
            'data' => [
                'association_code' => $associationCode instanceof LearnerAssociationCode
                    ? $this->formatAssociationCode($associationCode)
                    : null,
                'linked_tutors' => $this->linkedTutorsFor($user),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->userFrom($request);
        $associationCode = $this->activeCodeFor($user);

        if (! $associationCode instanceof LearnerAssociationCode) {
            $associationCode = $this->createCodeFor($user);
        }

        return response()->json([
            'success' => true,
            'message' => 'Association code generated.',
            'data' => [
                'association_code' => $this->formatAssociationCode($associationCode),
                'linked_tutors' => $this->linkedTutorsFor($user),
            ],
        ], 201);
    }

    public function regenerate(Request $request): JsonResponse
    {
        $user = $this->userFrom($request);
        $this->cancelActiveCodeFor($user);

        $associationCode = $this->createCodeFor($user);

        return response()->json([
            'success' => true,
            'message' => 'Association code regenerated.',
            'data' => [
                'association_code' => $this->formatAssociationCode($associationCode),
                'linked_tutors' => $this->linkedTutorsFor($user),
            ],
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $this->userFrom($request);
        $cancelledCode = $this->cancelActiveCodeFor($user);

        return response()->json([
            'success' => true,
            'message' => $cancelledCode instanceof LearnerAssociationCode
                ? 'Association code cancelled.'
                : 'No active association code to cancel.',
            'data' => [
                'association_code' => $cancelledCode instanceof LearnerAssociationCode
                    ? $this->formatAssociationCode($cancelledCode)
                    : null,
                'linked_tutors' => $this->linkedTutorsFor($user),
            ],
        ]);
    }

    private function userFrom(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function activeCodeFor(User $user): ?LearnerAssociationCode
    {
        return LearnerAssociationCode::query()
            ->whereBelongsTo($user, 'learner')
            ->whereNull('used_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    private function createCodeFor(User $user): LearnerAssociationCode
    {
        return LearnerAssociationCode::query()->create([
            'learner_id' => $user->id,
            'code' => $this->generateUniqueCode(),
            'expires_at' => now()->addDay(),
        ]);
    }

    private function cancelActiveCodeFor(User $user): ?LearnerAssociationCode
    {
        $associationCode = $this->activeCodeFor($user);

        if (! $associationCode instanceof LearnerAssociationCode) {
            return null;
        }

        $associationCode->update([
            'cancelled_at' => now(),
        ]);

        return $associationCode;
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'LC-'.random_int(100000, 999999);
        } while (LearnerAssociationCode::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAssociationCode(LearnerAssociationCode $associationCode): array
    {
        return [
            'id' => $associationCode->id,
            'code' => $associationCode->code,
            'expires_at' => $associationCode->expires_at,
            'used_at' => $associationCode->used_at,
            'cancelled_at' => $associationCode->cancelled_at,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function linkedTutorsFor(User $user): array
    {
        return $user->tutors()
            ->where('role', User::ROLE_TUTOR)
            ->orderBy('name')
            ->get()
            ->map(fn (User $tutor): array => [
                'id' => $tutor->id,
                'full_name' => $tutor->name,
                'email' => $tutor->email,
                'role' => $tutor->role,
            ])
            ->all();
    }
}

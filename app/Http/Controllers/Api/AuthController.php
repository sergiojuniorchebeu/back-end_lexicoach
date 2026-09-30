<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearnerAssociationCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', Rule::in([User::ROLE_LEARNER, User::ROLE_TUTOR])],
            'association_code' => ['required_if:role,'.User::ROLE_TUTOR, 'nullable', 'string', 'max:20'],
        ]);

        $role = $validated['role'] ?? User::ROLE_LEARNER;
        $associationCode = $role === User::ROLE_TUTOR
            ? $this->usableAssociationCodeFrom($validated['association_code'] ?? null)
            : null;

        $user = DB::transaction(function () use ($validated, $role, $associationCode): User {
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $role,
            ]);

            if ($associationCode instanceof LearnerAssociationCode) {
                $user->learners()->syncWithoutDetaching([$associationCode->learner_id]);
                $associationCode->update([
                    'used_at' => now(),
                ]);
            }

            $user->refresh();

            return $user;
        });

        $user->load('learners');

        $token = $user->createToken($validated['device_name'] ?? 'api-client')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data' => [
                'user' => $this->formatUser($user),
                'linked_learner' => $role === User::ROLE_TUTOR
                    ? $user->learners->first()?->only(['id', 'name', 'email', 'role'])
                    : null,
                'token' => $this->formatToken($token),
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The credentials are incorrect.'],
            ])->status(401);
        }

        $token = $user->createToken($validated['device_name'] ?? 'api-client')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => $this->formatUser($user),
                'token' => $this->formatToken($token),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user.',
            'data' => [
                'user' => $this->formatUser($request->user()),
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'preferred_language' => ['required', 'string', 'max:12'],
            'learning_level' => ['required', 'string', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'dyslexia_font_size' => ['required', 'integer', 'min:14', 'max:26'],
            'dyslexia_slow_speech' => ['required', 'boolean'],
        ]);

        $user->update([
            'name' => $validated['full_name'],
            'email' => $validated['email'],
            'preferred_language' => $validated['preferred_language'],
            'learning_level' => $validated['learning_level'],
            'dyslexia_font_size' => $validated['dyslexia_font_size'],
            'dyslexia_slow_speech' => $validated['dyslexia_slow_speech'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated.',
            'data' => [
                'user' => $this->formatUser($user->fresh()),
            ],
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated.',
            'data' => null,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
            'data' => null,
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
            'preferences' => [
                'preferred_language' => $user->preferred_language,
                'learning_level' => $user->learning_level,
                'dyslexia_font_size' => $user->dyslexia_font_size,
                'dyslexia_slow_speech' => $user->dyslexia_slow_speech,
            ],
            'conversation_limits' => [
                'session_limit_seconds' => $user->ai_conversation_session_limit_seconds,
                'session_limit_minutes' => (int) ceil($user->ai_conversation_session_limit_seconds / 60),
                'daily_session_limit' => $user->ai_conversation_daily_session_limit,
            ],
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
        ];
    }

    private function usableAssociationCodeFrom(?string $code): LearnerAssociationCode
    {
        $associationCode = LearnerAssociationCode::query()
            ->with('learner')
            ->where('code', $code)
            ->first();

        if (! $associationCode instanceof LearnerAssociationCode || ! $associationCode->isUsable()) {
            throw ValidationException::withMessages([
                'association_code' => ['This association code is invalid or expired.'],
            ]);
        }

        if ($associationCode->learner->role !== User::ROLE_LEARNER) {
            throw ValidationException::withMessages([
                'association_code' => ['This code does not belong to a learner.'],
            ]);
        }

        return $associationCode;
    }

    /**
     * @return array<string, string|null>
     */
    private function formatToken(string $plainTextToken): array
    {
        return [
            'type' => 'Bearer',
            'access_token' => $plainTextToken,
            'expires_at' => null,
        ];
    }
}

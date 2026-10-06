<?php

use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AiConversationSessionController;
use App\Http\Controllers\Api\ApiDocumentationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GlobalProgressController;
use App\Http\Controllers\Api\LearnerAssociationCodeController;
use App\Http\Controllers\Api\LearningModeController;
use App\Http\Controllers\Api\MercyPayWebhookController;
use App\Http\Controllers\Api\ReadingExerciseController;
use App\Http\Controllers\Api\ReadingProgressController;
use App\Http\Controllers\Api\RealtimeSessionController;
use App\Http\Controllers\Api\SmartAbstractExerciseController;
use App\Http\Controllers\Api\SmartAbstractProgressController;
use App\Http\Controllers\Api\TutorDashboardController;
use App\Http\Controllers\Api\TutorLearnerController;
use App\Http\Controllers\Api\WordSplittingController;
use App\Http\Controllers\Api\WritingExerciseController;
use App\Http\Controllers\Api\WritingProgressController;
use Illuminate\Support\Facades\Route;

Route::get('/docs', ApiDocumentationController::class);

// Public : Mercy Pay appelle cette URL directement, aucune session utilisateur.
// La verification se fait via la signature HMAC (X-MercyPay-Signature), pas
// via auth:sanctum.
Route::post('/webhooks/mercy-pay', [MercyPayWebhookController::class, 'handle']);

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/profile', [AuthController::class, 'updateProfile']);
        Route::patch('/password', [AuthController::class, 'updatePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(['auth:sanctum', 'role:learner'])->group(function (): void {
    Route::get('/learning-modes', [LearningModeController::class, 'index']);
    Route::get('/learning-modes/{slug}', [LearningModeController::class, 'show']);
    Route::get('/learning-modes/{slug}/exercises', [LearningModeController::class, 'exercises']);
    Route::get('/reading-exercises', [ReadingExerciseController::class, 'index']);
    Route::get('/reading-exercises/{readingExercise}', [ReadingExerciseController::class, 'show']);
    Route::post('/reading-exercises/{readingExercise}/evaluate', [ReadingExerciseController::class, 'evaluate']);
    Route::get('/me/reading-attempts', [ReadingProgressController::class, 'attempts']);
    Route::get('/me/reading-progress', [ReadingProgressController::class, 'progress']);
    Route::get('/writing-exercises', [WritingExerciseController::class, 'index']);
    Route::get('/writing-exercises/{writingExercise}', [WritingExerciseController::class, 'show']);
    Route::post('/writing-exercises/{writingExercise}/evaluate', [WritingExerciseController::class, 'evaluate']);
    Route::get('/me/writing-attempts', [WritingProgressController::class, 'attempts']);
    Route::get('/me/writing-progress', [WritingProgressController::class, 'progress']);
    Route::get('/smart-abstract-exercises', [SmartAbstractExerciseController::class, 'index']);
    Route::get('/smart-abstract-exercises/{smartAbstractExercise}', [SmartAbstractExerciseController::class, 'show']);
    Route::post('/smart-abstract-exercises/{smartAbstractExercise}/evaluate', [SmartAbstractExerciseController::class, 'evaluate']);
    Route::post('/smart-abstract-exercises/{smartAbstractExercise}/checkout', [SmartAbstractExerciseController::class, 'checkout']);
    Route::get('/smart-abstract-payments/{smartAbstractPayment}', [SmartAbstractExerciseController::class, 'paymentStatus']);
    Route::get('/me/smart-abstract-attempts', [SmartAbstractProgressController::class, 'attempts']);
    Route::get('/me/smart-abstract-progress', [SmartAbstractProgressController::class, 'progress']);
    Route::get('/me/progress', GlobalProgressController::class);
    Route::post('/word-splitting/split', [WordSplittingController::class, 'split']);
    Route::post('/realtime/sessions', [RealtimeSessionController::class, 'start']);
    Route::post('/realtime/sessions/{session}/end', [RealtimeSessionController::class, 'end']);
    Route::post('/ai-conversations', [AiConversationSessionController::class, 'store']);
    Route::get('/ai-conversations/{aiConversationSession}/realtime-authorize', [AiConversationSessionController::class, 'realtimeAuthorize']);
    Route::get('/ai-conversations/{aiConversationSession}/messages', [AiConversationSessionController::class, 'messages']);
    Route::post('/ai-conversations/{aiConversationSession}/messages', [AiConversationSessionController::class, 'storeMessage']);
    Route::post('/ai-conversations/{aiConversationSession}/turn', [AiConversationSessionController::class, 'turn']);
    Route::post('/ai-conversations/{aiConversationSession}/assess', [AiConversationSessionController::class, 'assess']);
    Route::patch('/ai-conversations/{aiConversationSession}/end', [AiConversationSessionController::class, 'end']);
    Route::patch('/ai-conversations/{aiConversationSession}/expire', [AiConversationSessionController::class, 'expire']);
    Route::get('/me/association-code', [LearnerAssociationCodeController::class, 'show']);
    Route::post('/me/association-code', [LearnerAssociationCodeController::class, 'store']);
    Route::post('/me/association-code/regenerate', [LearnerAssociationCodeController::class, 'regenerate']);
    Route::delete('/me/association-code', [LearnerAssociationCodeController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:tutor'])->group(function (): void {
    Route::get('/tutor/dashboard', TutorDashboardController::class);
    Route::get('/tutor/learners', [TutorLearnerController::class, 'index']);
    Route::post('/tutor/learners/link', [TutorLearnerController::class, 'link']);
    Route::get('/tutor/learners/{learner}/progress', [TutorLearnerController::class, 'progress']);
    Route::get('/tutor/learners/{learner}/reading-attempts', [TutorLearnerController::class, 'readingAttempts']);
    Route::get('/tutor/learners/{learner}/writing-attempts', [TutorLearnerController::class, 'writingAttempts']);
    Route::get('/tutor/learners/{learner}/smart-abstract-attempts', [TutorLearnerController::class, 'smartAbstractAttempts']);
    Route::delete('/tutor/learners/{learner}', [TutorLearnerController::class, 'unlink']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::get('/admin/dashboard', AdminDashboardController::class);
    Route::get('/admin/users', [AdminUserController::class, 'index']);
    Route::get('/admin/users/{user}', [AdminUserController::class, 'show']);
    Route::patch('/admin/users/{user}/role', [AdminUserController::class, 'updateRole']);
    Route::patch('/admin/users/{user}/conversation-limits', [AdminUserController::class, 'updateConversationLimits']);
});

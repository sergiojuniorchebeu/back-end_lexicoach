<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvaluateSmartAbstractExerciseRequest;
use App\Models\SmartAbstractAttempt;
use App\Models\SmartAbstractExercise;
use App\Models\SmartAbstractPayment;
use App\Models\User;
use App\Services\Ai\AiFeedbackService;
use App\Services\Payments\MercyPayClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SmartAbstractExerciseController extends Controller
{
    public function __construct(
        private readonly AiFeedbackService $aiFeedbackService,
        private readonly MercyPayClient $mercyPayClient,
    ) {}

    public function checkout(SmartAbstractExercise $smartAbstractExercise, Request $request): JsonResponse
    {
        abort_unless($smartAbstractExercise->is_active, 404);

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $amount = (int) config('mercypay.smart_abstract.amount');
        $currency = (string) config('mercypay.smart_abstract.currency');

        $payment = SmartAbstractPayment::query()->create([
            'user_id' => $user->id,
            'smart_abstract_exercise_id' => $smartAbstractExercise->id,
            'checkout_reference' => (string) Str::uuid(),
            'amount' => $amount,
            'currency' => $currency,
            'status' => SmartAbstractPayment::STATUS_PENDING,
        ]);

        $session = $this->mercyPayClient->createCheckoutSession([
            'amount' => $amount,
            'currency' => $currency,
            'description' => "Lecture audio du document - {$smartAbstractExercise->title}",
            'success_url' => route('payments.smart-abstract.success'),
            'cancel_url' => route('payments.smart-abstract.cancel'),
            'metadata' => [
                'smart_abstract_payment_id' => $payment->id,
                'user_id' => $user->id,
                'smart_abstract_exercise_id' => $smartAbstractExercise->id,
            ],
        ], idempotencyKey: 'smart-abstract-'.$payment->id);

        $payment->update(['checkout_reference' => $session['reference']]);

        return response()->json([
            'success' => true,
            'message' => 'Checkout session created.',
            'data' => [
                'payment' => $this->formatPayment($payment),
                'checkout_url' => $session['checkout_url'],
            ],
        ], 201);
    }

    /**
     * Consumes one payment credit to authorize document read-aloud in the app.
     * No AI call happens here; only playback authorization is paid.
     */
    public function listen(SmartAbstractExercise $smartAbstractExercise, Request $request): JsonResponse
    {
        abort_unless($smartAbstractExercise->is_active, 404);

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $payment = $this->consumeAvailablePayment($user, $smartAbstractExercise);

        if (! $payment instanceof SmartAbstractPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment required.',
                'errors' => [
                    'payment' => ['A payment of '.config('mercypay.smart_abstract.amount').' '.config('mercypay.smart_abstract.currency').' is required to listen to this document.'],
                ],
            ], 402);
        }

        return response()->json([
            'success' => true,
            'message' => 'Playback authorized.',
            'data' => [
                'payment' => $this->formatPayment($payment),
            ],
        ]);
    }

    public function paymentStatus(SmartAbstractPayment $smartAbstractPayment, Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless($smartAbstractPayment->user_id === $user->id, 403);

        return response()->json([
            'success' => true,
            'message' => 'Payment status retrieved.',
            'data' => [
                'payment' => $this->formatPayment($smartAbstractPayment),
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        $exercises = SmartAbstractExercise::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (SmartAbstractExercise $exercise): array => $this->formatExercise($exercise));

        return response()->json([
            'success' => true,
            'message' => 'Smart abstract exercises retrieved.',
            'data' => [
                'exercises' => $exercises,
            ],
        ]);
    }

    public function show(SmartAbstractExercise $smartAbstractExercise): JsonResponse
    {
        abort_unless($smartAbstractExercise->is_active, 404);

        return response()->json([
            'success' => true,
            'message' => 'Smart abstract exercise retrieved.',
            'data' => [
                'exercise' => $this->formatExercise($smartAbstractExercise),
            ],
        ]);
    }

    public function evaluate(
        SmartAbstractExercise $smartAbstractExercise,
        EvaluateSmartAbstractExerciseRequest $request,
    ): JsonResponse {
        abort_unless($smartAbstractExercise->is_active, 404);

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        // Smart Abstract summaries are free. Only document read-aloud
        // playback is paid.
        try {
            $result = $this->aiFeedbackService->evaluateSmartAbstract(
                exercise: $smartAbstractExercise,
                documentText: $request->validated('document_text'),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'AI service unavailable.',
                'errors' => [
                    'ai' => [$exception->getMessage()],
                ],
            ], 503);
        }

        $attempt = SmartAbstractAttempt::query()->create([
            'user_id' => $user->id,
            'smart_abstract_exercise_id' => $smartAbstractExercise->id,
            'summary' => $result['summary'],
            'score' => $result['score'],
            'status' => $result['status'],
            'improved_summary' => $result['improved_summary'],
            'missing_ideas' => $result['missing_ideas'],
            'strengths' => $result['strengths'],
            'feedback' => $result['feedback'],
            'raw_ai_response' => [
                'document_text' => $result['document_text'],
                'ai' => $result['raw_ai_response'],
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Smart abstract evaluated.',
            'data' => [
                'exercise' => $this->formatExercise($smartAbstractExercise),
                'result' => $result,
                'attempt' => $this->formatAttemptSummary($attempt),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatExercise(SmartAbstractExercise $exercise): array
    {
        return [
            'id' => $exercise->id,
            'title' => $exercise->title,
            'source_text' => $exercise->source_text,
            'instructions' => $exercise->instructions,
            'language' => $exercise->language,
            'level' => $exercise->level,
            'min_words' => $exercise->min_words,
            'max_words' => $exercise->max_words,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAttemptSummary(SmartAbstractAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'score' => $attempt->score,
            'status' => $attempt->status,
            'created_at' => $attempt->created_at,
        ];
    }

    /**
     * Atomically reserves the oldest available (paid, unconsumed) credit for
     * this user/exercise pair, if any, and marks it consumed.
     */
    private function consumeAvailablePayment(User $user, SmartAbstractExercise $exercise): ?SmartAbstractPayment
    {
        return DB::transaction(function () use ($user, $exercise): ?SmartAbstractPayment {
            $payment = SmartAbstractPayment::query()
                ->where('user_id', $user->id)
                ->where('smart_abstract_exercise_id', $exercise->id)
                ->where('status', SmartAbstractPayment::STATUS_COMPLETED)
                ->whereNull('consumed_at')
                ->oldest()
                ->lockForUpdate()
                ->first();

            $payment?->update(['consumed_at' => now()]);

            return $payment;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPayment(SmartAbstractPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'reference' => $payment->checkout_reference,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'consumed_at' => $payment->consumed_at,
        ];
    }
}

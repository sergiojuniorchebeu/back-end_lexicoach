<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WordSyllableSplitter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WordSplittingController extends Controller
{
    public function __construct(
        private readonly WordSyllableSplitter $wordSyllableSplitter,
    ) {}

    public function split(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'min:2', 'max:500'],
            'language' => ['nullable', 'string', 'max:12'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Words split into syllables.',
            'data' => [
                'result' => $this->wordSyllableSplitter->splitText(
                    text: $validated['text'],
                    language: $validated['language'] ?? 'en-US',
                ),
            ],
        ]);
    }
}

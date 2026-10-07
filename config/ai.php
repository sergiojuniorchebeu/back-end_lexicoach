<?php

return [
    'provider' => env('AI_PROVIDER', 'fake'),

    'conversation_debug_logs' => (bool) env('AI_CONVERSATION_DEBUG_LOGS', true),

    'gemini' => [
        // Jusqu'a 4 cles : si l'une est a quota, GeminiKeyPool bascule sur
        // la suivante automatiquement (voir app/Services/Ai/GeminiKeyPool.php).
        // Gardees en entrees separees (plutot qu'un tableau precalcule) pour
        // que Config::set('ai.gemini.api_key', ...) reste utilisable en test.
        'api_key' => env('GEMINI_API_KEY'),
        'api_key_2' => env('GEMINI_API_KEY_2'),
        'api_key_3' => env('GEMINI_API_KEY_3'),
        'api_key_4' => env('GEMINI_API_KEY_4'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        'live_model' => env('GEMINI_LIVE_MODEL', 'gemini-3.8-live'),
        'live_response_modality' => env('GEMINI_LIVE_RESPONSE_MODALITY', 'AUDIO'),
        'live_proxy_url' => env('GEMINI_LIVE_PROXY_URL', 'ws://localhost:8787/ai-conversations/live'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 30),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'realtime_model' => env('OPENAI_REALTIME_MODEL', 'gpt-realtime-mini'),
        'realtime_voice' => env('OPENAI_REALTIME_VOICE', 'alloy'),
        'realtime_url' => env('OPENAI_REALTIME_URL', 'https://api.openai.com/v1'),
        'realtime_max_output_tokens' => (int) env('OPENAI_REALTIME_MAX_OUTPUT_TOKENS', 400),
        'timeout' => (int) env('OPENAI_TIMEOUT', 30),
    ],
];

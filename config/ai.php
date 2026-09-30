<?php

return [
    'provider' => env('AI_PROVIDER', 'fake'),

    'conversation_debug_logs' => (bool) env('AI_CONVERSATION_DEBUG_LOGS', true),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
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

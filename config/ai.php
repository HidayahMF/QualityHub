<?php

return [
    'smart_search' => [
        'enabled' => (bool) env('AI_SMART_SEARCH_ENABLED', false),
        'url' => env('AI_SMART_SEARCH_URL', 'http://127.0.0.1:8765'),
        'connect_timeout' => (float) env('AI_SMART_SEARCH_CONNECT_TIMEOUT', 1),
        'timeout' => (float) env('AI_SMART_SEARCH_TIMEOUT', 5),
        'top_k' => (int) env('AI_SMART_SEARCH_TOP_K', 20),
        'candidate_limit' => (int) env('AI_SMART_SEARCH_CANDIDATE_LIMIT', 250),
    ],
];

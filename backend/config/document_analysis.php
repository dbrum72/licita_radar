<?php

return [
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_ANALYSIS_MODEL', 'gpt-5.4-mini'),
    'reasoning_effort' => env('OPENAI_ANALYSIS_REASONING_EFFORT', 'medium'),
    'timeout' => (int) env('OPENAI_ANALYSIS_TIMEOUT', 180),
    'max_output_tokens' => (int) env('OPENAI_ANALYSIS_MAX_OUTPUT_TOKENS', 16000),
    'max_documents' => (int) env('OPENAI_ANALYSIS_MAX_DOCUMENTS', 40),
    'max_total_bytes' => 50 * 1024 * 1024,
    'max_visual_bytes' => 40 * 1024 * 1024,
    'max_text_chars' => (int) env('OPENAI_ANALYSIS_MAX_TEXT_CHARS', 500000),
    'max_calls' => (int) env('OPENAI_ANALYSIS_MAX_CALLS', 8),
    // Estimates only. Keep the rates/model aligned when changing providers or pricing.
    'pricing_model' => 'gpt-5.4-mini',
    'input_usd_per_million' => 0.75,
    'cached_input_usd_per_million' => 0.075,
    'output_usd_per_million' => 4.50,
];

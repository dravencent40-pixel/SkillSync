<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SkillSync AI Agent
    |--------------------------------------------------------------------------
    | GROQ_API_KEY diambil dari .env. Jika kosong, semua agent otomatis
    | jatuh ke mode heuristik lokal (rule-based) agar tetap bisa didemokan.
    */

    'groq_api_key' => env('GROQ_API_KEY', ''),

    'model' => env('AI_MODEL', 'llama-3.3-70b-versatile'),

];

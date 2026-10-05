<?php

// Instellingen van SteynPT (zie .env.example).
return [
    // E-mailadressen die automatisch beheerder worden (komma-gescheiden in ADMIN_EMAILS).
    'admin_emails' => array_values(array_filter(array_map(
        fn ($email) => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', ''))
    ))),

    // AI-concepten voor trainings- en voedingsschema's (Claude van Anthropic).
    'anthropic_api_key' => env('ANTHROPIC_API_KEY'),
    // Optioneel een vast model; leeg = automatisch het nieuwste Opus-model dat de API-sleutel kan gebruiken.
    'ai_model' => env('AI_MODEL'),
    // Testmodus: voorbeeldconcepten zonder API-sleutel (alleen voor lokaal testen).
    'ai_mock' => (bool) env('AI_MOCK', false),
];

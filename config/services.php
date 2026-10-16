<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'osrm' => [
        'url' => env('OSRM_URL'),
        // Distância máxima (m) entre o ponto clicado e a via mais próxima. Acima disso o OSRM
        // responde NoSegment — é o que barra pontos fora do Brasil (o grafo só tem dados do Brasil).
        'snap_radius_meters' => (int) env('OSRM_SNAP_RADIUS_METERS', 200),
    ],

    'percursos' => [
        // Comprimento máximo de um percurso, em km.
        'max_distance_km' => (float) env('PERCURSO_MAX_DISTANCE_KM', 50),
        // Máximo de paradas por percurso (o osrm-routed aceita até 500 por padrão).
        'max_stops' => (int) env('PERCURSO_MAX_STOPS', 100),
        // Máximo de pontos na geometria salva (proteção contra payloads gigantes).
        'max_route_points' => (int) env('PERCURSO_MAX_ROUTE_POINTS', 20000),
    ],
];

<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'AgroDiagnóstico'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env_bool('APP_DEBUG', false),
    'url' => rtrim(env('APP_URL', 'http://127.0.0.1:8080'), '/'),
    'timezone' => env('APP_TIMEZONE', 'America/Mexico_City'),
    'locale' => env('APP_LOCALE', 'es_MX'),
    'key' => env('APP_KEY', ''),
    'auto_verify_users' => env_bool('AUTO_VERIFY_USERS', false),

    'db' => [
        'url' => env('DATABASE_URL', ''),
        'unpooled_url' => env('DATABASE_URL_UNPOOLED', ''),
        'driver' => env('DB_DRIVER', 'auto'), // auto | http | pdo
    ],

    'session' => [
        'name' => env('SESSION_NAME', 'agro_session'),
        'lifetime' => (int) env('SESSION_LIFETIME', '7200'),
        'secure' => env_bool('SESSION_SECURE', str_starts_with((string) env('APP_URL', ''), 'https://')),
    ],

    'uploads' => [
        'path' => BASE_PATH . '/public/uploads/cases',
        'url' => '/uploads/cases',
        'max_bytes' => (int) env('UPLOAD_MAX_BYTES', (string) (5 * 1024 * 1024)),
        'max_files' => (int) env('UPLOAD_MAX_FILES', '6'),
    ],

    'cedula' => [
        'enabled' => env_bool('CEDULA_VALIDATION_ENABLED', false),
        'url' => env('CEDULA_API_URL', 'https://apimarket.mx/api/sep/grupo/validar-cedula'),
        'token' => env('CEDULA_API_TOKEN', ''),
        'timeout' => (int) env('CEDULA_API_TIMEOUT', '15'),
    ],

    'ai' => [
        'url' => env('AI_SERVICE_URL', ''),
        'token' => env('AI_SERVICE_TOKEN', ''),
    ],

    'mail' => [
        'enabled' => env_bool('MAIL_ENABLED', false),
        'from' => env('MAIL_FROM', 'no-reply@agrodiagnostico.mx'),
        'from_name' => env('MAIL_FROM_NAME', 'AgroDiagnóstico'),
    ],

    'pagination' => [
        'cases' => 9,
        'admin' => 20,
        'resources' => 12,
    ],

    'roles' => [
        'agronomo' => 'Ingeniero agrónomo',
        'editor' => 'Editor técnico',
        'admin' => 'Administrador',
    ],

    'moderators' => ['editor', 'admin'],
];

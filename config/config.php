<?php

$localConfig = is_file(__DIR__ . '/local.php') ? require __DIR__ . '/local.php' : [];

return [
    'app_name' => 'DocuMind',
    'base_url' => '/DesarrolloE2',
    'db' => [
        'host' => getenv('DOCUMIND_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DOCUMIND_DB_PORT') ?: '3306',
        'name' => getenv('DOCUMIND_DB_NAME') ?: 'documind',
        'user' => getenv('DOCUMIND_DB_USER') ?: 'root',
        'password' => getenv('DOCUMIND_DB_PASSWORD') ?: '',
    ],
    'ai' => array_merge([
        'enabled' => filter_var(getenv('DOCUMIND_AI_ENABLED') ?: false, FILTER_VALIDATE_BOOLEAN),
        'required' => true,
        'endpoint' => getenv('DOCUMIND_AI_ENDPOINT') ?: 'https://api.openai.com/v1/chat/completions',
        'api_key' => getenv('DOCUMIND_AI_KEY') ?: '',
        'model' => getenv('DOCUMIND_AI_MODEL') ?: 'gpt-4o-mini',
    ], $localConfig['ai'] ?? []),
    'upload_dir' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'documents',
    'max_upload_bytes' => 10 * 1024 * 1024,
];
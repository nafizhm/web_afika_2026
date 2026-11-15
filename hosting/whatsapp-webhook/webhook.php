<?php

// Standalone alternative for a separate PHP hosting account. No Laravel required.
declare(strict_types=1);
require __DIR__.'/Receiver.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405); header('Allow: POST');
    echo json_encode(['ok' => false, 'error' => 'POST required.']); exit;
}
try {
    // Keep this config one directory ABOVE the public web root.
    $configPath = getenv('AFIKA_WEBHOOK_CONFIG') ?: dirname(__DIR__).'/whatsapp-config.php';
    if (!is_file($configPath)) {
        http_response_code(503);
        echo json_encode(['ok' => false, 'error' => 'Webhook configuration missing.']); exit;
    }
    $config = require $configPath;
    $raw = file_get_contents('php://input', false, null, 0, 2 * 1024 * 1024 + 1);
    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    // Validate authentication before establishing a database connection.
    $validation = \Afika\WhatsApp\Receiver::validate($raw, $authorization, (string) ($config['secret'] ?? ''), (string) ($config['account'] ?? ''));
    if (isset($validation['response'])) {
        [$status, $body] = $validation['response'];
    } else {
        $pdo = new PDO($config['dsn'], $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        [$status, $body] = \Afika\WhatsApp\Receiver::handle($pdo, $raw, $authorization, $config['secret'], $config['account']);
    }
    http_response_code($status); echo json_encode($body, JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    // Do not expose database credentials or message contents in an HTTP error.
    error_log('Afika WhatsApp webhook failed: '.get_class($exception));
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Storage unavailable. Retry later.']);
}


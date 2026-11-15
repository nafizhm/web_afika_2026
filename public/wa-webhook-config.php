<?php
/** Konfigurasi webhook Afika. Edit file ini; pertahankan saat update webhook. */
declare(strict_types=1);

// Konfigurasi tidak menyediakan endpoint HTTP.
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    http_response_code(404);
    exit;
}

// ================= KONFIGURASI: EDIT DI SINI =================
$dbHost = '127.0.0.1';
$dbPort = 3306;
$dbName = 'web_afika';
$dbUser = 'root';
$dbPassword = '';
$accountNumber = '6282221992911';

// Opsional untuk testing. Jika diisi, gateway harus mengirim header:
// incomingCallbackHeaders: { "Authorization": "Bearer TOKEN_YANG_SAMA" }
// Jangan biarkan kosong untuk pemakaian publik setelah testing selesai.
$webhookToken = '';

// Token khusus worker VPS, minimal 32 karakter. Samakan dengan AFIKA_WORKER_TOKEN di VPS.
$workerToken = '';

// Balasan AI: isi di sini pada hosting mandiri, atau lewat .env Laravel di atas public/.
$aiConfig = [
    'enabled' => true,
    'worker_mode' => 'remote', // remote = worker VPS; cron = worker PHP lama
    'api_key' => '',
    'model' => 'glm-4-6v-flash:free',
    'base_url' => 'https://kenari.id/v1',
    'gateway_url' => 'http://43.133.133.142:3000',
    'gateway_token' => '',
    'client_id' => 'cobadong',
];
// Pada project Laravel, baca .env langsung agar key tidak tertimpa environment proses tes.
// Supports public_html separate from the Laravel Git checkout.
$laravelRoot = dirname(__DIR__);
$repositoryRoot = dirname(__DIR__).'/repositories/web_afika_2026';
if (is_file($repositoryRoot.'/.env') && is_file($repositoryRoot.'/vendor/autoload.php')) {
    $laravelRoot = $repositoryRoot;
}
$privateEnv = $laravelRoot.'/.env';
$autoload = $laravelRoot.'/vendor/autoload.php';
if (is_file($privateEnv) && is_file($autoload)) {
    require_once $autoload;
    $envValues = \Dotenv\Dotenv::parse(file_get_contents($privateEnv));
    // Shared Laravel hosting: the .env connection is authoritative.
    foreach (['DB_HOST'=>'dbHost','DB_PORT'=>'dbPort','DB_DATABASE'=>'dbName','DB_USERNAME'=>'dbUser','DB_PASSWORD'=>'dbPassword',
        'WHATSAPP_NUMBER'=>'accountNumber','WHATSAPP_WEBHOOK_SECRET'=>'webhookToken'] as $envName=>$variable) {
        if (array_key_exists($envName,$envValues)) $$variable=$envValues[$envName];
    }
    foreach (['api_key'=>'KENARI_API_KEY', 'model'=>'KENARI_MODEL', 'base_url'=>'KENARI_BASE_URL',
        'gateway_url'=>'WA_GATEWAY_URL', 'gateway_token'=>'WA_GATEWAY_TOKEN', 'client_id'=>'WA_GATEWAY_CLIENT_ID'] as $field=>$envName) {
        if (isset($envValues[$envName])) $aiConfig[$field] = $envValues[$envName];
    }
    if (isset($envValues['WA_AI_ENABLED'])) $aiConfig['enabled'] = filter_var($envValues['WA_AI_ENABLED'], FILTER_VALIDATE_BOOLEAN);
    if (isset($envValues['WA_AI_WORKER_MODE'])) $aiConfig['worker_mode'] = $envValues['WA_AI_WORKER_MODE'];
    if (isset($envValues['WA_AI_WORKER_TOKEN'])) $workerToken = $envValues['WA_AI_WORKER_TOKEN'];
}




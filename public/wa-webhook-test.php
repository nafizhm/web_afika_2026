<?php
/**
 * Webhook Afika untuk wa_gateway. PHP 8.2+, PDO MySQL, cURL, mbstring.
 * Upload bersama wa-webhook-config.php ke folder yang sama.
 * Saat update berikutnya, cukup ganti file webhook ini.
 */
declare(strict_types=1);

try {
    $configFile = __DIR__.'/wa-webhook-config.php';
    if (!is_file($configFile)) throw new RuntimeException('Missing configuration file.');
    require $configFile;
} catch (Throwable $error) {
    error_log('Afika webhook configuration failed: '.get_class($error));
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Konfigurasi webhook tidak tersedia atau tidak valid. Periksa wa-webhook-config.php.\n");
        exit(1);
    }
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['ok'=>false,'error'=>'Konfigurasi webhook tidak tersedia atau tidak valid.']);
    exit;
}

function afikaReply(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function afikaAiSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_ai_replies (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        dedup_key CHAR(64) NOT NULL UNIQUE,
        recipient VARCHAR(30) NOT NULL,
        incoming_text TEXT NOT NULL,
        reply_text TEXT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        attempts INT NOT NULL DEFAULT 0,
        gateway_job_id VARCHAR(191) NULL,
        last_error VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX ai_status_index (status, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function afikaAiEnqueue(PDO $pdo, array $config, array $payload, string $dedupKey, string $chat, ?string $phone, string $type, ?string $body, mixed $timestamp): bool
{
    if (!$config['enabled'] || ($payload['clientId'] ?? '') !== $config['client_id']) return false;
    if (!afikaAiRuntimeEnabled($pdo)) return false;
    if (!str_ends_with($chat, '@s.whatsapp.net') && !str_ends_with($chat, '@lid')) return false;
    // The current gateway only sends to phone numbers. Never treat a LID as a phone number.
    if (!$phone || !in_array($type, ['conversation', 'extendedTextMessage'], true) || trim($body ?? '') === '') return false;
    // Avoid replying to old messages replayed by Baileys on reconnect.
    if ($timestamp === null || abs(time() - (int) $timestamp) > 300) return false;
    afikaAiSchema($pdo);
    $statement = $pdo->prepare("INSERT INTO whatsapp_ai_replies (dedup_key, recipient, incoming_text, created_at, updated_at)
        SELECT ?, ?, ?, ?, ? WHERE (SELECT enabled FROM whatsapp_ai_control WHERE id=1)=1
        ON DUPLICATE KEY UPDATE dedup_key = VALUES(dedup_key)");
    $now = gmdate('Y-m-d H:i:s');
    $statement->execute([$dedupKey, $phone, mb_substr($body, 0, 8000), $now, $now]);
    return true;
}

function afikaAiHttp(string $url, array $payload, array $headers, int $timeout): array
{
    if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension is required.');
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers),
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
        CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $failed = $body === false;
    curl_close($curl);
    if ($failed) throw new RuntimeException('Upstream connection failed or timed out.');
    return [$status, json_decode($body, true)];
}

function afikaAiProcess(PDO $pdo, array $config, array $job, ?callable $http = null): string
{
    $http ??= 'afikaAiHttp';
    $attempts = (int) $job['attempts'] + 1;
    $update = function (string $status, ?string $reply, ?string $gatewayId, ?string $error) use ($pdo, $job, $attempts): string {
        $statement = $pdo->prepare('UPDATE whatsapp_ai_replies SET status=?, attempts=?, reply_text=?, gateway_job_id=?, last_error=?, updated_at=? WHERE id=?');
        $statement->execute([$status, $attempts, $reply, $gatewayId, $error, gmdate('Y-m-d H:i:s'), $job['id']]);
        return $status;
    };
    $answer = $job['reply_text'];
    $update('processing', $answer, null, null);
    if (!$answer) {
        try {
            [$status, $result] = $http(rtrim($config['base_url'], '/').'/chat/completions', [
                'model' => $config['model'],
                'messages' => [
                    ['role' => 'system', 'content' => 'Nama kamu Fika, asisten AI Afika Residence. Saat pertama berkenalan, disapa untuk berkenalan, atau ditanya nama, perkenalkan diri sebagai Fika. Jangan mengulang perkenalan pada setiap jawaban dan jangan mengaku manusia. Balas dalam bahasa Indonesia secara ramah dan singkat. Saat ini data properti belum tersedia: jangan mengarang harga, lokasi, promo, atau ketersediaan rumah. Jika ditanya data tersebut, arahkan untuk konfirmasi ke admin.'],
                    ['role' => 'user', 'content' => $job['incoming_text']],
                ],
                'max_tokens' => 1024, 'stream' => false,
            ], ['Authorization: Bearer '.$config['api_key']], 60);
            $answer = is_array($result) ? ($result['choices'][0]['message']['content'] ?? null) : null;
            if ($status !== 200 || !is_string($answer) || trim($answer) === '') {
                return $update($attempts < 3 ? 'pending' : 'failed', null, null, 'Kenari HTTP '.$status.' or empty reply.');
            }
            $answer = mb_substr(trim($answer), 0, 3500);
            $update('processing', $answer, null, null);
        } catch (Throwable) {
            return $update($attempts < 3 ? 'pending' : 'failed', null, null, 'Kenari connection failed.');
        }
    }
    try {
        $headers = $config['gateway_token'] === '' ? [] : ['Authorization: Bearer '.$config['gateway_token']];
        [$status, $result] = $http(rtrim($config['gateway_url'], '/').'/messages/text', [
            'clientId' => $config['client_id'], 'to' => $job['recipient'], 'text' => $answer,
        ], $headers, 15);
        $gatewayId = is_array($result) ? ($result['data']['id'] ?? null) : null;
        if ($status === 202 && ($result['ok'] ?? false) === true && is_string($gatewayId) && $gatewayId !== '') {
            // Queued is not proof of WhatsApp delivery. Check gateway job status separately.
            return $update('queued', $answer, $gatewayId, null);
        }
        return $update($status >= 400 && $status < 500 ? 'failed' : 'unknown', $answer, null, 'Gateway HTTP '.$status.'; no confirmed job ID.');
    } catch (Throwable) {
        // The gateway may have accepted the request before the connection broke.
        // Do not resend automatically: this gateway has no idempotency key support.
        return $update('unknown', $answer, null, 'Gateway result unknown; check gateway before resending.');
    }
}

function afikaAiWorker(PDO $pdo, array $config): void
{
    if (($config['worker_mode'] ?? 'cron') === 'remote') {
        echo "Mode worker VPS aktif; cron PHP tidak memproses balasan.\n";
        return;
    }
    if (!$config['enabled'] || trim($config['api_key']) === '' || trim($config['model']) === '' || trim($config['gateway_url']) === '') {
        throw new RuntimeException('Isi konfigurasi Kenari dan gateway sebelum menjalankan worker.');
    }
    afikaAiSchema($pdo);
    $locked = (int) $pdo->query("SELECT GET_LOCK('afika_whatsapp_ai_worker', 0)")->fetchColumn();
    if ($locked !== 1) { echo "Worker lain masih berjalan.\n"; return; }
    try {
        // Crash mid-send has an ambiguous outcome. Hold it instead of risking a double reply.
        $pdo->exec("UPDATE whatsapp_ai_replies SET status='unknown', last_error='Worker interrupted; verify gateway before resending.' WHERE status='processing'");
        $jobs = $pdo->query("SELECT * FROM whatsapp_ai_replies WHERE status='pending' AND attempts < 3 ORDER BY id LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($jobs as $job) {
            $status = afikaAiProcess($pdo, $config, $job);
            echo 'AI job '.$job['id'].': '.$status.PHP_EOL;
        }
        if (!$jobs) echo "Tidak ada pesan menunggu balasan.\n";
    } finally { $pdo->query("SELECT RELEASE_LOCK('afika_whatsapp_ai_worker')"); }
}


function afikaRemoteAuthorized(string $authorization, string $token): bool
{
    return strlen($token) >= 32 && hash_equals('Bearer '.$token, $authorization);
}

function afikaAiRuntimeEnabled(PDO $pdo): bool
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS whatsapp_ai_control (
        id TINYINT UNSIGNED PRIMARY KEY,
        enabled TINYINT(1) NOT NULL DEFAULT 1,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $pdo->exec('INSERT IGNORE INTO whatsapp_ai_control (id,enabled,updated_at) VALUES (1,1,UTC_TIMESTAMP())');
    return (bool)$pdo->query('SELECT enabled FROM whatsapp_ai_control WHERE id=1')->fetchColumn();
}

function afikaFaqSchema(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS faqs (
        id BIGINT UNSIGNED PRIMARY KEY,
        question VARCHAR(500) NOT NULL,
        answer TEXT NOT NULL,
        sort_order INT UNSIGNED NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function afikaFaqSync(PDO $pdo, mixed $faqs): array
{
    if (!is_array($faqs) || !array_is_list($faqs) || count($faqs)>100) return [422,['ok'=>false,'error'=>'Maximum 100 FAQ entries.']];
    $ids=[];
    foreach ($faqs as $faq) {
        if (!is_array($faq) || !is_int($faq['id']??null) || $faq['id']<1 || isset($ids[$faq['id']])
            || !is_string($faq['question']??null) || trim($faq['question'])==='' || mb_strlen($faq['question'])>500
            || !is_string($faq['answer']??null) || trim($faq['answer'])==='' || mb_strlen($faq['answer'])>20000
            || !is_int($faq['sort_order']??null) || $faq['sort_order']<0 || $faq['sort_order']>999999
            || !is_bool($faq['is_active']??null)) return [422,['ok'=>false,'error'=>'Invalid FAQ entry.']];
        $ids[$faq['id']]=true;
    }
    afikaFaqSchema($pdo);
    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM faqs');
        $insert=$pdo->prepare('INSERT INTO faqs (id,question,answer,sort_order,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?)');
        $now=gmdate('Y-m-d H:i:s');
        foreach($faqs as $faq) $insert->execute([$faq['id'],$faq['question'],$faq['answer'],$faq['sort_order'],(int)$faq['is_active'],$now,$now]);
        $pdo->commit();
    } catch(Throwable $error) { $pdo->rollBack(); throw $error; }
    return [200,['ok'=>true,'count'=>count($faqs)]];
}

function afikaFaqContext(PDO $pdo, string $question): array
{
    afikaFaqSchema($pdo);
    $rows=$pdo->query('SELECT * FROM faqs WHERE is_active=1 ORDER BY sort_order,id LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
    $words=preg_split('/[^\p{L}\p{N}]+/u',mb_strtolower($question),-1,PREG_SPLIT_NO_EMPTY);
    $words=array_filter(array_unique($words),fn($word)=>mb_strlen($word)>2 && !in_array($word,['apa','yang','dan','atau','saya','aku','kamu','anda','untuk','dengan','bagaimana','apakah','bisa','mau','ingin'],true));
    $ranked=[];
    foreach($rows as $row) {
        if (($row['faq_type'] ?? 'general') !== 'general' || (isset($row['is_ai']) && !(bool)$row['is_ai'])) continue;
        // Draft placeholders must not be quoted as actual business facts.
        if (preg_match('/\[[^\]]+\]/u',$row['question'].' '.$row['answer'])) continue;
        $text=mb_strtolower($row['question'].' '.$row['answer']);
        $score=0;foreach($words as $word) if(mb_strpos($text,$word)!==false) $score++;
        $ranked[]=['score'=>$score,'faq'=>['question'=>$row['question'],'answer'=>$row['answer']]];
    }
    usort($ranked,fn($a,$b)=>$b['score']<=>$a['score']);
    $context=[];$length=0;
    foreach($ranked as $row) {
        $size=mb_strlen($row['faq']['question'])+mb_strlen($row['faq']['answer']);
        if ($length+$size>40000) continue;
        $context[]=$row['faq'];$length+=$size;
        if(count($context)>=20) break;
    }
    return $context;
}

function afikaAssistantProfile(PDO $pdo): array
{
    $profile=['name'=>'Fika','handoff'=>'Admin kami akan mengambil alih obrolan ini pada jam kerja. Kakak tidak perlu menghubungi nomor lain.'];
    $seen=[];
    foreach ($pdo->query('SELECT * FROM faqs WHERE is_active=1 ORDER BY sort_order,id LIMIT 100')->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (isset($row['is_ai']) && !(bool)$row['is_ai']) continue;
        $type=$row['faq_type']??'general';
        if (isset($seen[$type]) || trim($row['answer'])==='') continue;
        if ($type==='identity') $profile['name']=mb_substr(trim($row['answer']),0,60);
        if ($type==='handoff') $profile['handoff']=mb_substr(trim($row['answer']),0,2000);
        $seen[$type]=true;
    }
    return $profile;
}

function afikaRemoteQueue(PDO $pdo, array $input): array
{
    $action = $input['action'] ?? '';
    if (!in_array($action, ['claim', 'prepare_send', 'complete', 'ai_error', 'sync_faqs','get_ai_status','set_ai_enabled'], true)) return [422, ['ok'=>false,'error'=>'Invalid action.']];
    afikaAiSchema($pdo);
    $pdo->exec('CREATE TABLE IF NOT EXISTS whatsapp_ai_remote (
        job_id BIGINT UNSIGNED PRIMARY KEY,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    if ((int) $pdo->query("SELECT GET_LOCK('afika_whatsapp_ai_worker', 0)")->fetchColumn() !== 1) {
        return [409, ['ok'=>false,'error'=>'Worker busy.']];
    }
    try {
        if ($action === 'sync_faqs') return afikaFaqSync($pdo,$input['faqs']??null);
        $aiEnabled=afikaAiRuntimeEnabled($pdo);
        if ($action==='get_ai_status') return [200,['ok'=>true,'ai_enabled'=>$aiEnabled]];
        if ($action==='set_ai_enabled') {
            if (!is_bool($input['enabled']??null)) return [422,['ok'=>false,'error'=>'Enabled must be boolean.']];
            $enabled=$input['enabled'];
            $pdo->beginTransaction();
            try {
                $set=$pdo->prepare('UPDATE whatsapp_ai_control SET enabled=?,updated_at=? WHERE id=1');
                $set->execute([(int)$enabled,gmdate('Y-m-d H:i:s')]);
                if(!$enabled || !$aiEnabled) $pdo->exec("UPDATE whatsapp_ai_replies SET status='cancelled',last_error='AI disabled by administrator.' WHERE status IN ('pending','processing')");
                $pdo->commit();
            } catch(Throwable $error) { $pdo->rollBack();throw $error; }
            return [200,['ok'=>true,'ai_enabled'=>$enabled]];
        }
        $now = gmdate('Y-m-d H:i:s');
        if ($action === 'claim') {
            if(!$aiEnabled) return [200,['ok'=>true,'job'=>null,'ai_enabled'=>false]];
            $recover = $pdo->prepare("UPDATE whatsapp_ai_replies j JOIN whatsapp_ai_remote r ON r.job_id=j.id
                SET j.status=CASE WHEN j.status='sending' THEN 'unknown' WHEN j.attempts<3 THEN 'pending' ELSE 'failed' END,
                j.last_error='Worker lease expired.', j.updated_at=?
                WHERE r.expires_at < ? AND j.status IN ('processing','sending')");
            $recover->execute([$now, $now]);
            $job = $pdo->query("SELECT * FROM whatsapp_ai_replies WHERE status='pending' AND attempts<3 ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if (!$job) return [200, ['ok'=>true,'job'=>null]];
            $faqContext=afikaFaqContext($pdo,$job['incoming_text']);
            $token = bin2hex(random_bytes(32));
            $lease = $pdo->prepare('INSERT INTO whatsapp_ai_remote (job_id,token_hash,expires_at) VALUES (?,?,?)
                ON DUPLICATE KEY UPDATE token_hash=VALUES(token_hash), expires_at=VALUES(expires_at)');
            $lease->execute([$job['id'], hash('sha256', $token), gmdate('Y-m-d H:i:s', time()+300)]);
            $claim = $pdo->prepare("UPDATE whatsapp_ai_replies SET status='processing', attempts=attempts+1, updated_at=? WHERE id=? AND status='pending'");
            $claim->execute([$now,$job['id']]);
            $previous = $pdo->prepare("SELECT COUNT(*) FROM whatsapp_ai_replies WHERE recipient=? AND id<? AND reply_text IS NOT NULL AND status IN ('queued','sending')");
            $previous->execute([$job['recipient'],$job['id']]);
            return [200, ['ok'=>true,'job'=>[
                'id'=>(int)$job['id'], 'lease_token'=>$token, 'recipient'=>$job['recipient'],
                'incoming_text'=>$job['incoming_text'], 'reply_text'=>$job['reply_text'],
                'is_first_contact'=>(int)$previous->fetchColumn()===0,
                'faqs'=>$faqContext,
                'ai_profile'=>afikaAssistantProfile($pdo),
            ]]];
        }
        $id = $input['id'] ?? null; $token = $input['lease_token'] ?? '';
        if (!is_int($id) || $id < 1 || !is_string($token)) return [422,['ok'=>false,'error'=>'Invalid job.']];
        $find = $pdo->prepare('SELECT j.*, r.token_hash, r.expires_at FROM whatsapp_ai_replies j JOIN whatsapp_ai_remote r ON r.job_id=j.id WHERE j.id=?');
        $find->execute([$id]); $job=$find->fetch(PDO::FETCH_ASSOC);
        if ($job && hash_equals($job['token_hash'],hash('sha256',$token)) && (!$aiEnabled || $job['status']==='cancelled') && in_array($action,['prepare_send','ai_error'],true)) {
            return [200,['ok'=>true,'allowed'=>false,'ai_enabled'=>$aiEnabled]];
        }
        if (!$job || !hash_equals($job['token_hash'], hash('sha256',$token)) || $job['expires_at'] < $now) {
            return [409,['ok'=>false,'error'=>'Invalid or expired job lease.']];
        }
        if ($action === 'prepare_send') {
            $reply = $input['reply_text'] ?? null;
            if ($job['status'] !== 'processing' || !is_string($reply) || trim($reply)==='' || mb_strlen($reply)>3500) {
                return [409,['ok'=>false,'error'=>'Job cannot be sent.']];
            }
            $update=$pdo->prepare("UPDATE whatsapp_ai_replies SET reply_text=?,status='sending',updated_at=? WHERE id=?");
            $update->execute([$reply,$now,$id]);
        } elseif ($action === 'ai_error') {
            if ($job['status'] !== 'processing') return [409,['ok'=>false,'error'=>'Job is not generating.']];
            $update=$pdo->prepare('UPDATE whatsapp_ai_replies SET status=?,last_error=?,updated_at=? WHERE id=?');
            $update->execute([(int)$job['attempts']<3?'pending':'failed','Kenari request failed.',$now,$id]);
        } else {
            $status=$input['status']??''; $gatewayId=$input['gateway_job_id']??null;
            if (!in_array($status,['queued','unknown','failed'],true) || ($gatewayId!==null && (!is_string($gatewayId) || strlen($gatewayId)>191)) || ($status==='queued' && !$gatewayId)) {
                return [422,['ok'=>false,'error'=>'Invalid gateway result.']];
            }
            if ($job['status'] === $status && $job['gateway_job_id'] === $gatewayId) return [200,['ok'=>true]];
            if ($job['status'] !== 'sending') return [409,['ok'=>false,'error'=>'Job is not sending.']];
            $update=$pdo->prepare('UPDATE whatsapp_ai_replies SET status=?,gateway_job_id=?,last_error=?,updated_at=? WHERE id=?');
            $update->execute([$status,$gatewayId,$status==='queued'?null:'Gateway send failed or result unknown.',$now,$id]);
        }
        return [200,['ok'=>true,'allowed'=>true]];
    } finally { $pdo->query("SELECT RELEASE_LOCK('afika_whatsapp_ai_worker')"); }
}

if (defined('AFIKA_AI_LIBRARY_ONLY')) return;
if (PHP_SAPI === 'cli') {
    if (($argv[1] ?? '') !== '--worker') { echo "Usage: php wa-webhook-test.php --worker\n"; exit(0); }
    try {
        $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPassword,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
        afikaAiWorker($pdo, $aiConfig);
    } catch (Throwable $error) {
        fwrite(STDERR, 'AI worker failed: '.get_class($error).' code '.$error->getCode().'. Check configuration and server logs.'.PHP_EOL);
        exit(1);
    }
    exit(0);
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
ini_set('display_errors', '0');

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method === 'GET') {
    afikaReply(200, [
        'ok' => true,
        'message' => 'Webhook aktif. Kirim POST JSON dari WA gateway untuk menguji penyimpanan database.',
        'database_checked' => false,
    ]);
}
if ($method !== 'POST') {
    header('Allow: GET, POST');
    afikaReply(405, ['ok' => false, 'error' => 'Gunakan POST JSON.']);
}
if (($_GET['worker'] ?? '') === '1') {
    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!afikaRemoteAuthorized($authorization,$workerToken)) {
        // Diagnostic flags only: never log tokens, hashes, or authorization values.
        error_log('Afika worker authorization check: '.json_encode([
            'config_file_loaded'=>is_file($configFile),
            'private_env_readable'=>isset($privateEnv) && is_readable($privateEnv),
            'autoload_readable'=>isset($autoload) && is_readable($autoload),
            'env_worker_token_defined'=>isset($envValues) && array_key_exists('WA_AI_WORKER_TOKEN',$envValues),
            'worker_token_valid_length'=>strlen($workerToken)>=32,
            'authorization_received'=>$authorization!=='',
            'authorization_bearer_prefix'=>str_starts_with($authorization,'Bearer '),
        ]));
        afikaReply(401,['ok'=>false,'error'=>'Worker unauthorized or token not configured.']);
    }
    if (!$aiConfig['enabled'] || ($aiConfig['worker_mode']??'cron')!=='remote') afikaReply(409,['ok'=>false,'error'=>'Remote worker is disabled.']);
    try {
        $rawInput=file_get_contents('php://input',false,null,0,2097153);
        if ($rawInput===false || strlen($rawInput)>2097152) afikaReply(413,['ok'=>false,'error'=>'Payload too large.']);
        $input=json_decode($rawInput,true,32,JSON_THROW_ON_ERROR);
        if (!is_array($input)) afikaReply(422,['ok'=>false,'error'=>'JSON object required.']);
        $pdo=new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",$dbUser,$dbPassword,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
        [$status,$result]=afikaRemoteQueue($pdo,$input);
        afikaReply($status,$result);
    } catch (JsonException) { afikaReply(422,['ok'=>false,'error'=>'Invalid JSON.']); }
    catch (Throwable $error) {
        error_log('Afika remote queue error: '.get_class($error).' code '.$error->getCode());
        afikaReply(500,['ok'=>false,'error'=>'Queue unavailable.']);
    }
}
if ($webhookToken !== '') {
    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!hash_equals('Bearer '.$webhookToken, $authorization)) {
        afikaReply(401, ['ok' => false, 'error' => 'Token salah.']);
    }
}

$stage = 'read_payload';
try {
    $raw = file_get_contents('php://input', false, null, 0, 2 * 1024 * 1024 + 1);
    if ($raw === false || strlen($raw) > 2 * 1024 * 1024) {
        afikaReply(413, ['ok' => false, 'error' => 'Maksimal payload 2 MiB.']);
    }
    try { $payload = json_decode($raw, true, 64, JSON_THROW_ON_ERROR); }
    catch (JsonException) { afikaReply(422, ['ok' => false, 'error' => 'JSON tidak valid.']); }

    // Format bawaan gateway: {clientId, key, message, pushName, messageTimestamp, from, sender}.
    $key = is_array($payload) ? ($payload['key'] ?? null) : null;
    if (!is_array($key)) afikaReply(422, ['ok' => false, 'error' => 'Field key wajib diisi.']);
    $messageId = $key['id'] ?? null;
    $chat = $key['remoteJid'] ?? $payload['from'] ?? null;
    foreach ([$messageId, $chat] as $identifier) {
        if (!is_string($identifier) || $identifier === '' || strlen($identifier) > 191) {
            afikaReply(422, ['ok' => false, 'error' => 'key.id dan key.remoteJid wajib berupa string, maksimal 191 karakter.']);
        }
    }
    if (($key['fromMe'] ?? false) === true || $chat === 'status@broadcast') {
        afikaReply(202, ['ok' => true, 'ignored' => true]);
    }
    $content = $payload['message'] ?? null;
    if (!is_array($content) || !$content) afikaReply(422, ['ok' => false, 'error' => 'Field message wajib diisi.']);
    for ($i = 0; $i < 8; $i++) {
        $nested = null;
        foreach (['ephemeralMessage', 'viewOnceMessage', 'viewOnceMessageV2', 'viewOnceMessageV2Extension', 'documentWithCaptionMessage'] as $wrapper) {
            if (isset($content[$wrapper]['message']) && is_array($content[$wrapper]['message'])) {
                $nested = $content[$wrapper]['message']; break;
            }
        }
        if ($nested === null) break;
        $content = $nested;
    }
    $type = 'unknown';
    foreach (array_keys($content) as $candidate) {
        if (is_string($candidate) && ($candidate === 'conversation' || (str_ends_with($candidate, 'Message') && $candidate !== 'senderKeyDistributionMessage'))) {
            $type = $candidate; break;
        }
    }
    if (strlen($type) > 80) $type = 'unknown';
    $node = is_array($content[$type] ?? null) ? $content[$type] : [];
    $body = $content['conversation'] ?? $node['text'] ?? $node['caption'] ?? $node['selectedDisplayText'] ?? $node['title'] ?? null;
    if (!is_string($body)) $body = null;
    $sender = $key['participant'] ?? $payload['sender'] ?? $chat;
    $name = $payload['pushName'] ?? $payload['senderName'] ?? null;
    if (!is_string($sender) || strlen($sender) > 191 || ($name !== null && (!is_string($name) || strlen($name) > 255))) {
        afikaReply(422, ['ok' => false, 'error' => 'Identitas pengirim tidak valid.']);
    }
    $phone = null;
    $alternative = str_ends_with($chat, '@g.us') ? ($key['participantAlt'] ?? null) : ($key['remoteJidAlt'] ?? null);
    foreach ([$alternative, $sender] as $jid) {
        if (is_string($jid) && preg_match('/^([0-9]{5,20})(?::[0-9]+)?@s[.]whatsapp[.]net$/', $jid, $match)) {
            $phone = $match[1]; break;
        }
    }
    $timestamp = $payload['messageTimestamp'] ?? null;
    // Mendukung protobuf Long yang belum diubah menjadi integer oleh gateway.
    if (is_array($timestamp) && isset($timestamp['low'], $timestamp['high']) && is_int($timestamp['low']) && is_int($timestamp['high'])) {
        $timestamp = ($timestamp['high'] * 4294967296) + ($timestamp['low'] & 0xffffffff);
    }
    if ($timestamp !== null && (!is_scalar($timestamp) || !ctype_digit((string) $timestamp) || (float) $timestamp > 4102444800)) {
        afikaReply(422, ['ok' => false, 'error' => 'messageTimestamp harus berupa Unix seconds.']);
    }

    $stage = 'connect_database';
    $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    // Membuat tabel otomatis bila belum ada; kompatibel dengan inbox admin Afika.
    $stage = 'create_table';
    $pdo->exec('CREATE TABLE IF NOT EXISTS whatsapp_messages (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        dedup_key CHAR(64) NOT NULL UNIQUE,
        message_id VARCHAR(191) NOT NULL,
        account VARCHAR(30) NOT NULL,
        chat_jid VARCHAR(191) NOT NULL,
        sender_jid VARCHAR(191) NOT NULL,
        sender_phone VARCHAR(30) NULL,
        sender_name VARCHAR(255) NULL,
        message_type VARCHAR(80) NOT NULL,
        body LONGTEXT NULL,
        raw_payload JSON NOT NULL,
        sent_at DATETIME NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        INDEX whatsapp_messages_chat_jid_index (chat_jid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $dedupKey = hash('sha256', json_encode([$accountNumber, $chat, $messageId], JSON_THROW_ON_ERROR));
    $now = gmdate('Y-m-d H:i:s');
    $stage = 'insert_message';
    $insert = $pdo->prepare('INSERT INTO whatsapp_messages
        (dedup_key, message_id, account, chat_jid, sender_jid, sender_phone, sender_name, message_type, body, raw_payload, sent_at, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    try {
        $insert->execute([$dedupKey, $messageId, $accountNumber, $chat, $sender, $phone, $name, $type, $body, $raw,
            $timestamp === null ? null : gmdate('Y-m-d H:i:s', (int) $timestamp), $now, $now]);
    } catch (PDOException $error) {
        if ((string) $error->getCode() === '23000') {
            $existing = $pdo->prepare('SELECT id FROM whatsapp_messages WHERE dedup_key = ?');
            $existing->execute([$dedupKey]);
            if ($existing->fetchColumn() !== false) {
                $aiQueued = afikaAiEnqueue($pdo, $aiConfig, $payload, $dedupKey, $chat, $phone, $type, $body, $timestamp);
                afikaReply(200, ['ok' => true, 'duplicate' => true, 'message_id' => $messageId, 'ai_queued' => $aiQueued]);
            }
        }
        throw $error;
    }
    $savedId = (int) $pdo->lastInsertId();
    $stage = 'queue_ai_reply';
    $aiQueued = afikaAiEnqueue($pdo, $aiConfig, $payload, $dedupKey, $chat, $phone, $type, $body, $timestamp);
    afikaReply(201, ['ok' => true, 'saved' => true, 'id' => $savedId, 'message_id' => $messageId, 'ai_queued' => $aiQueued]);
} catch (Throwable $error) {
    // Details go to the hosting PHP error log only, never the public response.
    error_log('Afika test webhook failed: '.json_encode([
        'stage' => $stage,
        'exception' => get_class($error),
        'code' => $error->getCode(),
        'detail' => $error->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    afikaReply(500, ['ok' => false, 'error' => 'Gagal menyimpan. Periksa konfigurasi DB, hak CREATE/INSERT, dan error log PHP hosting.']);
}

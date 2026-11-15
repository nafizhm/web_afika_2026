<?php
// Integration check with TEMPORARY MySQL tables; no external API/WA calls.
require dirname(__DIR__,2).'/vendor/autoload.php';
define('AFIKA_AI_LIBRARY_ONLY',true);
require dirname(__DIR__,2).'/public/wa-webhook-test.php';
class AfikaTemporaryPDO extends PDO {
    public function exec(string $statement): int|false {
        return parent::exec(str_replace('CREATE TABLE IF NOT EXISTS','CREATE TEMPORARY TABLE IF NOT EXISTS',$statement));
    }
}
$pdo=new AfikaTemporaryPDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",$dbUser,$dbPassword,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
function check(bool $condition): void { if (!$condition) throw new RuntimeException('Remote queue assertion failed.'); }
afikaAiSchema($pdo);
[$syncStatus]=afikaRemoteQueue($pdo,['action'=>'sync_faqs','faqs'=>[
    ['id'=>1,'question'=>'Jam kantor?','answer'=>'Senin sampai Jumat 08.00–16.00 WIB.','sort_order'=>0,'is_active'=>true],
    ['id'=>2,'question'=>'Nonaktif','answer'=>'Jangan kirim','sort_order'=>1,'is_active'=>false],
    ['id'=>3,'question'=>'Harga?','answer'=>'Rp [harga]','sort_order'=>2,'is_active'=>true],
]]);check($syncStatus===200);
[$syncStatus]=afikaRemoteQueue($pdo,['action'=>'sync_faqs','faqs'=>[['id'=>4]]]);check($syncStatus===422);
check((int)$pdo->query('SELECT COUNT(*) FROM faqs')->fetchColumn()===3);
$pdo->exec("INSERT INTO whatsapp_ai_replies (dedup_key,recipient,incoming_text,created_at,updated_at) VALUES ('test','628111111111','Halo',UTC_TIMESTAMP(),UTC_TIMESTAMP())");
check(!afikaRemoteAuthorized('Bearer wrong',str_repeat('a',64)));
check(!afikaRemoteAuthorized('Bearer ',''));
check(afikaRemoteAuthorized('Bearer '.str_repeat('a',64),str_repeat('a',64)));
[$status,$data]=afikaRemoteQueue($pdo,['action'=>'claim']);check($status===200 && $data['job']['id']===1);
$job=$data['job'];
check(count($job['faqs'])===1 && $job['faqs'][0]['question']==='Jam kantor?');
[$status,$data]=afikaRemoteQueue($pdo,['action'=>'claim']);check($status===200 && $data['job']===null);
[$status]=afikaRemoteQueue($pdo,['action'=>'prepare_send','id'=>1,'lease_token'=>'wrong','reply_text'=>'Halo']);check($status===409);
[$status]=afikaRemoteQueue($pdo,['action'=>'ai_error','id'=>1,'lease_token'=>$job['lease_token']]);check($status===200);
[$status,$data]=afikaRemoteQueue($pdo,['action'=>'claim']);$new=$data['job'];check($new['lease_token']!==$job['lease_token']);
[$status]=afikaRemoteQueue($pdo,['action'=>'prepare_send','id'=>1,'lease_token'=>$job['lease_token'],'reply_text'=>'Halo']);check($status===409);
[$status]=afikaRemoteQueue($pdo,['action'=>'prepare_send','id'=>1,'lease_token'=>$new['lease_token'],'reply_text'=>'Halo']);check($status===200);
$completion=['action'=>'complete','id'=>1,'lease_token'=>$new['lease_token'],'status'=>'queued','gateway_job_id'=>'mock-job'];
[$status]=afikaRemoteQueue($pdo,$completion);check($status===200);
[$status]=afikaRemoteQueue($pdo,$completion);check($status===200);
check($pdo->query('SELECT status FROM whatsapp_ai_replies WHERE id=1')->fetchColumn()==='queued');
$pdo->exec("UPDATE whatsapp_ai_replies SET status='sending'; UPDATE whatsapp_ai_remote SET expires_at='2000-01-01 00:00:00'");
afikaRemoteQueue($pdo,['action'=>'claim']);
check($pdo->query('SELECT status FROM whatsapp_ai_replies WHERE id=1')->fetchColumn()==='unknown');

$pdo->exec("INSERT INTO whatsapp_ai_replies (dedup_key,recipient,incoming_text,created_at,updated_at) VALUES ('control-test','628111111112','Halo',UTC_TIMESTAMP(),UTC_TIMESTAMP())");
[$status,$data]=afikaRemoteQueue($pdo,['action'=>'claim']);$controlJob=$data['job'];check($controlJob!==null);
[$status,$data]=afikaRemoteQueue($pdo,['action'=>'set_ai_enabled','enabled'=>false]);check($status===200 && $data['ai_enabled']===false);
[$status,$data]=afikaRemoteQueue($pdo,['action'=>'claim']);check($data['job']===null);
[$status,$data]=afikaRemoteQueue($pdo,['action'=>'prepare_send','id'=>$controlJob['id'],'lease_token'=>$controlJob['lease_token'],'reply_text'=>'Jangan kirim']);check($status===200 && $data['allowed']===false);
check(!afikaAiEnqueue($pdo,['enabled'=>true,'client_id'=>'cobadong'],['clientId'=>'cobadong'],'disabled-key','628111111112@s.whatsapp.net','628111111112','conversation','Pesan manual',time()));
[$status,$data]=afikaRemoteQueue($pdo,['action'=>'set_ai_enabled','enabled'=>true]);check($data['ai_enabled']===true);
[$status,$data]=afikaRemoteQueue($pdo,['action'=>'claim']);check($data['job']===null);
echo "PASS remote MySQL queue: authentication, claim exclusion, retry, stale leases, completion ACK, expired send. Temporary tables only.\n";

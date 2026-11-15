<?php

namespace Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;

class WhatsappAiWorkerTest extends TestCase
{
    private PDO $pdo;
    private array $config;

    protected function setUp(): void
    {
        if (!defined('AFIKA_AI_LIBRARY_ONLY')) define('AFIKA_AI_LIBRARY_ONLY', true);
        require_once dirname(__DIR__, 2).'/public/wa-webhook-test.php';
        $this->pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->pdo->exec("CREATE TABLE whatsapp_ai_replies (id INTEGER PRIMARY KEY, incoming_text TEXT, recipient TEXT, reply_text TEXT, status TEXT, attempts INTEGER DEFAULT 0, gateway_job_id TEXT, last_error TEXT, updated_at TEXT)");
        $this->pdo->exec("INSERT INTO whatsapp_ai_replies (id,incoming_text,recipient,status) VALUES (1,'Halo','628111111111','pending')");
        $this->config = ['api_key'=>'test-only-secret','model'=>'test-model','base_url'=>'https://kenari.example/v1','gateway_url'=>'https://gateway.example','gateway_token'=>'','client_id'=>'cobadong'];
    }

    private function job(): array { return $this->pdo->query('SELECT * FROM whatsapp_ai_replies WHERE id=1')->fetch(PDO::FETCH_ASSOC); }

    public function test_ai_reply_is_sent_to_correct_session_and_recorded_as_queued(): void
    {
        $calls = [];
        $http = function ($url, $payload, $headers) use (&$calls) {
            $calls[] = [$url, $payload, $headers];
            return count($calls) === 1
                ? [200, ['choices'=>[['message'=>['content'=>'Halo! Ada yang bisa dibantu?']]]]]
                : [202, ['ok'=>true,'data'=>['id'=>'gateway-job-123']]];
        };
        $status = \afikaAiProcess($this->pdo, $this->config, $this->job(), $http);
        $this->assertSame('queued', $status);
        $this->assertSame('cobadong', $calls[1][1]['clientId']);
        $this->assertSame('628111111111', $calls[1][1]['to']);
        $this->assertSame('Halo! Ada yang bisa dibantu?', $calls[1][1]['text']);
        $this->assertSame('gateway-job-123', $this->job()['gateway_job_id']);
    }

    public function test_kenari_error_never_sends_a_whatsapp_message_and_has_bounded_retries(): void
    {
        $calls = 0;
        $http = function () use (&$calls) { $calls++; return [401, null]; };
        $this->assertSame('pending', \afikaAiProcess($this->pdo, $this->config, $this->job(), $http));
        $this->assertSame('pending', \afikaAiProcess($this->pdo, $this->config, $this->job(), $http));
        $this->assertSame('failed', \afikaAiProcess($this->pdo, $this->config, $this->job(), $http));
        $this->assertSame(3, $calls);
        $this->assertNull($this->job()['gateway_job_id']);
    }

    public function test_empty_ai_content_is_not_forwarded(): void
    {
        $calls = 0;
        $http = function () use (&$calls) { $calls++; return [200, ['choices'=>[['message'=>['content'=>'']]]]]; };
        $this->assertSame('pending', \afikaAiProcess($this->pdo, $this->config, $this->job(), $http));
        $this->assertSame(1, $calls);
    }

    public function test_gateway_timeout_keeps_answer_and_does_not_automatically_retry_send(): void
    {
        $calls = 0;
        $http = function () use (&$calls) {
            if (++$calls === 1) return [200, ['choices'=>[['message'=>['content'=>'Halo Afika']]]]];
            throw new \RuntimeException('timeout');
        };
        $this->assertSame('unknown', \afikaAiProcess($this->pdo, $this->config, $this->job(), $http));
        $this->assertSame('Halo Afika', $this->job()['reply_text']);
    }

    public function test_cached_ai_answer_is_reused_without_another_ai_request(): void
    {
        $this->pdo->exec("UPDATE whatsapp_ai_replies SET reply_text='Jawaban tersimpan'");
        $calls = [];
        $http = function ($url) use (&$calls) { $calls[]=$url; return [202,['ok'=>true,'data'=>['id'=>'job-2']]]; };
        $this->assertSame('queued', \afikaAiProcess($this->pdo, $this->config, $this->job(), $http));
        $this->assertSame(['https://gateway.example/messages/text'], $calls);
    }
}

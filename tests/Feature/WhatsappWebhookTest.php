<?php

namespace Tests\Feature;

use App\Models\{User, WhatsappMessage};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-secret-with-more-than-thirty-two-characters';

    protected function setUp(): void
    {
        parent::setUp();
        config(['whatsapp.webhook_secret' => $this->secret, 'whatsapp.number' => '6282221992911']);
    }

    private function payload(array $changes = []): array
    {
        return array_replace_recursive([
            'account' => '6282221992911', 'event_type' => 'notify',
            'message' => [
                'key' => ['id' => 'MESSAGE-001', 'remoteJid' => '628123456789@s.whatsapp.net', 'fromMe' => false],
                'pushName' => 'Calon Pembeli', 'messageTimestamp' => 1794614400,
                'message' => ['conversation' => 'Halo, saya ingin informasi rumah.'],
            ],
        ], $changes);
    }

    private function deliver(array $payload)
    {
        return $this->withToken($this->secret)->postJson('/webhooks/whatsapp', $payload);
    }

    public function test_floating_button_and_admin_inbox_access(): void
    {
        $this->seed();
        $this->get('/')->assertOk()->assertSee('whatsapp-float')->assertSee('https://wa.me/6282221992911');
        $this->get('/admin/pesan-whatsapp')->assertRedirect('/admin/login');
        $this->deliver($this->payload())->assertCreated();
        $this->actingAs(User::first())->get('/admin/pesan-whatsapp')->assertOk()->assertSee('Calon Pembeli')->assertSee('Halo, saya ingin informasi rumah.');
    }

    public function test_authenticated_message_is_saved_once_even_after_retry(): void
    {
        $payload = $this->payload();
        $this->deliver($payload)->assertCreated()->assertJson(['ok' => true, 'duplicate' => false]);
        $this->deliver($payload)->assertOk()->assertJson(['duplicate' => true]);
        $this->assertDatabaseCount('whatsapp_messages', 1);
        $message = WhatsappMessage::firstOrFail();
        $this->assertSame('628123456789', $message->sender_phone);
        $this->assertSame('conversation', $message->message_type);
        $this->assertSame($payload, $message->raw_payload);
        // The same WhatsApp ID in another chat is a separate message.
        $this->deliver($this->payload(['message' => ['key' => ['remoteJid' => '62899999999@s.whatsapp.net']]]))->assertCreated();
        $this->assertDatabaseCount('whatsapp_messages', 2);
    }

    public function test_secret_is_required_and_missing_configuration_fails_closed(): void
    {
        $this->postJson('/webhooks/whatsapp', $this->payload())->assertUnauthorized();
        $this->withToken('wrong')->postJson('/webhooks/whatsapp', $this->payload())->assertUnauthorized();
        config(['whatsapp.webhook_secret' => '']);
        $this->deliver($this->payload())->assertStatus(503);
        $this->assertDatabaseCount('whatsapp_messages', 0);
    }

    public function test_invalid_payloads_are_rejected_without_writes(): void
    {
        $this->deliver($this->payload(['account' => '628111111111']))->assertUnprocessable();
        $this->deliver($this->payload(['message' => ['key' => ['id' => '']]]))->assertUnprocessable();
        $this->deliver($this->payload(['message' => ['key' => ['fromMe' => 'false']]]))->assertUnprocessable();
        $this->deliver($this->payload(['message' => ['messageTimestamp' => 'not-a-number']]))->assertUnprocessable();
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$this->secret];
        $this->call('POST', '/webhooks/whatsapp', [], [], [], $headers, '{broken')->assertUnprocessable();
        $this->call('POST', '/webhooks/whatsapp', [], [], [], $headers, str_repeat('x', 2 * 1024 * 1024 + 1))->assertStatus(413);
        $this->assertDatabaseCount('whatsapp_messages', 0);
    }

    public function test_outgoing_messages_and_status_broadcast_are_ignored(): void
    {
        $this->deliver($this->payload(['message' => ['key' => ['fromMe' => true]]]))->assertStatus(202);
        $this->deliver($this->payload(['message' => ['key' => ['remoteJid' => 'status@broadcast']]]))->assertStatus(202);
        $this->assertDatabaseCount('whatsapp_messages', 0);
    }

    public function test_wrapped_media_group_and_lid_messages_keep_metadata(): void
    {
        $payload = $this->payload();
        $payload['message']['key']['remoteJid'] = '120363123456@g.us';
        $payload['message']['key']['participant'] = '123456789@lid';
        $payload['message']['message'] = ['ephemeralMessage' => ['message' => ['imageMessage' => ['caption' => 'Brosur rumah', 'mimetype' => 'image/jpeg']]]];
        $this->deliver($payload)->assertCreated();
        $message = WhatsappMessage::firstOrFail();
        $this->assertSame('imageMessage', $message->message_type);
        $this->assertSame('Brosur rumah', $message->body);
        $this->assertNull($message->sender_phone);
        $this->assertSame('123456789@lid', $message->sender_jid);
    }
}

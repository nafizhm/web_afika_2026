<?php

namespace Tests\Feature;

use App\Models\{User,Setting};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_control_requires_authentication(): void
    {
        $this->get('/admin/pengaturan-ai')->assertRedirect('/admin/login');
        $this->put('/admin/pengaturan-ai',['enabled'=>false])->assertRedirect('/admin/login');
    }

    public function test_control_reads_hosting_and_confirms_disabled_status(): void
    {
        $this->actingAs(User::factory()->create());
        config(['faq.sync_url'=>'https://hosting.example/webhook.php','faq.sync_token'=>str_repeat('x',64)]);
        Http::fake(fn($request)=>Http::response(['ok'=>true,'ai_enabled'=>$request['action']==='get_ai_status']));
        $this->get('/admin/pengaturan-ai')->assertOk()->assertSee('AI aktif');
        $this->put('/admin/pengaturan-ai',['enabled'=>0])->assertSessionHasNoErrors()->assertRedirect('/admin/pengaturan-ai');
        $this->assertSame('0',Setting::find('wa_ai_enabled')->value);
        Http::assertSent(fn($request)=>$request['action']==='set_ai_enabled' && $request['enabled']===false);
    }

    public function test_remote_failure_does_not_claim_ai_has_been_disabled(): void
    {
        $this->actingAs(User::factory()->create());
        config(['faq.sync_url'=>'https://hosting.example/webhook.php','faq.sync_token'=>str_repeat('x',64)]);
        Http::fake(['*'=>Http::response(['ok'=>false],503)]);
        $this->get('/admin/pengaturan-ai')->assertOk()->assertSee('Belum terkonfirmasi');
        $this->from('/admin/pengaturan-ai')->put('/admin/pengaturan-ai',['enabled'=>0])->assertSessionHasErrors('ai_control');
        $this->assertDatabaseMissing('settings',['key'=>'wa_ai_enabled']);
    }
}

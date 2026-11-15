<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Http,Schema};
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;
class SharedAiControlTest extends TestCase {
    use RefreshDatabase;
    public function test_shared_control_cancels_only_unsent_jobs_and_never_calls_webhook(): void {
        config(['faq.shared_database'=>true]);
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create());
        Schema::create('whatsapp_ai_replies',function(Blueprint $t){$t->id();$t->string('status');$t->string('last_error')->nullable();});
        foreach(['pending','processing','sending','queued','unknown'] as $status) DB::table('whatsapp_ai_replies')->insert(['status'=>$status]);
        $this->get('/admin/pengaturan-ai')->assertOk()->assertSee('AI aktif');
        $this->put('/admin/pengaturan-ai',['enabled'=>0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('whatsapp_ai_control',['id'=>1,'enabled'=>0]);
        $this->assertSame(2,DB::table('whatsapp_ai_replies')->where('status','cancelled')->count());
        foreach(['sending','queued','unknown'] as $status)$this->assertDatabaseHas('whatsapp_ai_replies',['status'=>$status]);
        $this->put('/admin/pengaturan-ai',['enabled'=>1])->assertSessionHasNoErrors();
        $this->assertSame(2,DB::table('whatsapp_ai_replies')->where('status','cancelled')->count());
        Http::assertNothingSent();
    }
    public function test_shared_faq_sync_does_not_replace_faqs(): void {
        config(['faq.shared_database'=>true]);Http::preventStrayRequests();
        $this->actingAs(User::factory()->create());
        $faq=\App\Models\Faq::create(['question'=>'Jam buka?','answer'=>'08.00','sort_order'=>0,'is_active'=>true]);
        $this->get('/admin/faq')->assertOk()->assertDontSee('Sinkronkan FAQ')->assertDontSee('faq-sync-banner');
        $this->post('/admin/faq/sync')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('faqs',['id'=>$faq->id,'answer'=>'08.00']);Http::assertNothingSent();
    }
}

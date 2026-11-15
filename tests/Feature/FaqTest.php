<?php

namespace Tests\Feature;

use App\Models\{Faq, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User { return User::factory()->create(); }

    public function test_faq_routes_require_login_including_mutations(): void
    {
        $faq = Faq::create(['question'=>'Pertanyaan', 'answer'=>'Jawaban', 'sort_order'=>0, 'is_active'=>true]);
        $this->get('/admin/faq')->assertRedirect('/admin/login');
        $this->get('/admin/faq/create')->assertRedirect('/admin/login');
        $this->get('/admin/faq/'.$faq->id.'/edit')->assertRedirect('/admin/login');
        $this->post('/admin/faq', [])->assertRedirect('/admin/login');
        $this->post('/admin/faq/sync')->assertRedirect('/admin/login');
        $this->put('/admin/faq/'.$faq->id, [])->assertRedirect('/admin/login');
        $this->delete('/admin/faq/'.$faq->id)->assertRedirect('/admin/login');
        $this->assertDatabaseCount('faqs', 1);
    }

    public function test_admin_can_create_edit_deactivate_and_delete_faq(): void
    {
        $this->seed();
        $this->actingAs($this->admin());
        $this->get('/admin')->assertOk()->assertSee('FAQ');
        $this->get('/admin/faq/create')->assertOk();
        $data = ['question'=>'Bagaimana cara survei?', 'answer'=>'Hubungi tim untuk membuat janji.', 'sort_order'=>2, 'is_active'=>1];
        $this->post('/admin/faq', $data)->assertSessionHasNoErrors()->assertRedirect('/admin/faq');
        $faq = Faq::firstOrFail();
        $this->assertTrue($faq->is_active);
        $this->get('/admin/faq/'.$faq->id.'/edit')->assertOk()->assertSee('Bagaimana cara survei?');
        $data['answer'] = 'Silakan membuat janji melalui WhatsApp.';
        $data['is_active'] = 0;
        $this->put('/admin/faq/'.$faq->id, $data)->assertSessionHasNoErrors()->assertRedirect('/admin/faq');
        $this->assertFalse($faq->fresh()->is_active);
        $this->get('/admin/faq')->assertSee($data['answer'])->assertSee('Nonaktif');
        $this->delete('/admin/faq/'.$faq->id)->assertRedirect('/admin/faq');
        $this->assertDatabaseCount('faqs', 0);
    }

    public function test_invalid_faq_is_rejected_and_search_filters_results(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/faq', ['question'=>' ', 'answer'=>'', 'sort_order'=>-1, 'is_active'=>1])
            ->assertSessionHasErrors(['question', 'answer', 'sort_order']);
        $this->assertDatabaseCount('faqs', 0);
        Faq::create(['question'=>'Jadwal survei', 'answer'=>'Buat janji terlebih dahulu.', 'sort_order'=>0, 'is_active'=>true]);
        Faq::create(['question'=>'Kontak kantor', 'answer'=>'Hubungi admin.', 'sort_order'=>1, 'is_active'=>true]);
        $this->get('/admin/faq?q=survei')->assertOk()->assertSee('Jadwal survei')->assertDontSee('Kontak kantor');
    }

    public function test_sync_sends_faq_snapshot_to_authenticated_hosting_endpoint(): void
    {
        $this->actingAs($this->admin());
        config(['faq.sync_token'=>str_repeat('x',64),'faq.sync_url'=>'https://hosting.example/wa-webhook-test.php']);
        \Illuminate\Support\Facades\Http::fake(['hosting.example/*'=>\Illuminate\Support\Facades\Http::response(['ok'=>true,'count'=>1])]);
        Faq::create(['question'=>'Jam kantor?','answer'=>'08.00–16.00 WIB.','sort_order'=>0,'is_active'=>true]);
        $this->from('/admin/faq')->post('/admin/faq/sync')->assertRedirect('/admin/faq')->assertSessionHas('success');
        \Illuminate\Support\Facades\Http::assertSent(fn($request)=>$request->url()==='https://hosting.example/wa-webhook-test.php?worker=1'
            && $request->hasHeader('Authorization','Bearer '.str_repeat('x',64))
            && $request['action']==='sync_faqs' && $request['faqs'][0]['answer']==='08.00–16.00 WIB.');
    }

    public function test_sync_failure_keeps_local_faqs_and_shows_error(): void
    {
        $this->actingAs($this->admin());
        config(['faq.sync_token'=>str_repeat('x',64),'faq.sync_url'=>'https://hosting.example/wa-webhook-test.php']);
        \Illuminate\Support\Facades\Http::fake(['hosting.example/*'=>\Illuminate\Support\Facades\Http::response(['ok'=>false],401)]);
        Faq::create(['question'=>'Jam kantor?','answer'=>'08.00–16.00 WIB.','sort_order'=>0,'is_active'=>true]);
        $this->from('/admin/faq')->post('/admin/faq/sync')->assertSessionHasErrors('faq_sync');
        $this->assertDatabaseCount('faqs',1);
    }
}


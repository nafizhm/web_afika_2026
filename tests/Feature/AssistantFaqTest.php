<?php
namespace Tests\Feature;
use App\Models\{Faq,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AssistantFaqTest extends TestCase {
 use RefreshDatabase;
 public function test_internal_faqs_stay_hidden_even_if_public_checkbox_is_checked(): void {
  $this->actingAs(User::factory()->create());
  $this->post('/admin/faq',['question'=>'Nama internal','answer'=>'Naya','faq_type'=>'identity','is_public'=>1,'is_active'=>1,'sort_order'=>0])->assertSessionHasNoErrors();
  $this->assertFalse(Faq::first()->is_public);
  Faq::create(['question'=>'Rahasia pengalihan','answer'=>'Petunjuk internal khusus','faq_type'=>'handoff','is_public'=>true,'is_active'=>true,'sort_order'=>0]);
  Faq::create(['question'=>'Jam kunjungan publik','answer'=>'09.00','faq_type'=>'general','is_public'=>true,'is_active'=>true,'sort_order'=>1]);
  $this->get('/')->assertOk()->assertSee('Jam kunjungan publik')->assertDontSee('Nama internal')->assertDontSee('Rahasia pengalihan');
 }
}

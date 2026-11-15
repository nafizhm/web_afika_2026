<?php
namespace Tests\Feature;

use App\Models\{Faq, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqAudienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_audience_changes_persist_and_control_website_and_admin_filters(): void
    {
        $this->actingAs(User::factory()->create());
        $data = ['question'=>'Pertanyaan audience uji', 'answer'=>'Jawaban audience uji', 'sort_order'=>0, 'is_active'=>1, 'faq_type'=>'general', 'audience'=>'both'];
        $this->post('/admin/faq', $data)->assertSessionHasNoErrors();
        $faq = Faq::firstOrFail();
        foreach (['ai'=>[false,true], 'web'=>[true,false], 'both'=>[true,true]] as $audience => [$public,$ai]) {
            $data['audience'] = $audience;
            $this->put('/admin/faq/'.$faq->id, $data)->assertSessionHasNoErrors();
            $this->assertSame($public, $faq->fresh()->is_public);
            $this->assertSame($ai, $faq->fresh()->is_ai);
            $this->get('/admin/faq/'.$faq->id.'/edit')->assertOk()->assertSee('value="'.$audience.'" selected', false);
            $response = $this->get('/')->assertOk();
            if ($public) $response->assertSee($data['question']);
            else $response->assertDontSee($data['question']);
            foreach (['ai','web','both'] as $filter) {
                $this->getJson('/admin/tables/faqs?audience='.$filter)->assertOk()->assertJsonPath('recordsFiltered', $filter === $audience ? 1 : 0);
            }
        }
        $data['is_active'] = 0;
        $this->put('/admin/faq/'.$faq->id, $data)->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee($data['question']);
    }

    public function test_internal_faq_stays_ai_only_even_when_web_is_submitted(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/admin/faq', ['question'=>'Nama internal uji','answer'=>'Fika','sort_order'=>0,'is_active'=>1,'faq_type'=>'identity','audience'=>'web'])->assertSessionHasNoErrors();
        $this->assertFalse(Faq::first()->is_public);
        $this->assertTrue(Faq::first()->is_ai);
        $this->get('/')->assertDontSee('Nama internal uji');
    }
}

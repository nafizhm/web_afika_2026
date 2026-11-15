<?php

namespace Tests\Feature;

use App\Models\{Faq, Property, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_endpoints_require_login(): void
    {
        foreach (['properties','faqs','messages'] as $type) {
            $this->get('/admin/tables/'.$type)->assertRedirect('/admin/login');
        }
    }

    public function test_faq_search_covers_records_beyond_first_page_and_returns_correct_counts(): void
    {
        $this->actingAs(User::factory()->create());
        foreach(range(1,35) as $number) Faq::create(['question'=>'Pertanyaan '.$number,'answer'=>'Jawaban '.$number,'sort_order'=>$number,'is_active'=>true]);
        $query=http_build_query(['draw'=>2,'start'=>0,'length'=>10,'search'=>['value'=>'Pertanyaan 35']]);
        $this->getJson('/admin/tables/faqs?'.$query)->assertOk()->assertJsonPath('draw',2)
            ->assertJsonPath('recordsTotal',35)->assertJsonPath('recordsFiltered',1)
            ->assertJsonCount(1,'data')->assertJsonPath('data.0.question','Pertanyaan 35');
        $this->getJson('/admin/tables/faqs?start=10&length=10')->assertOk()->assertJsonCount(10,'data')->assertJsonPath('data.0.sort_order',11);
    }

    public function test_price_sorting_is_numeric_and_invalid_parameters_are_rejected(): void
    {
        $this->seed();
        $this->actingAs(User::first());
        $properties=Property::orderBy('id')->get();
        foreach($properties as $index=>$property) $property->update(['price'=>[900000000,100000000,500000000][$index]]);
        $query=http_build_query(['length'=>10,'order'=>[['column'=>2,'dir'=>'asc']]]);
        $this->getJson('/admin/tables/properties?'.$query)->assertOk()->assertJsonPath('data.0.price','Rp 100.000.000');
        $this->getJson('/admin/tables/faqs?length=9999')->assertUnprocessable();
        $this->getJson('/admin/tables/faqs?draw=%3Cscript%3E')->assertUnprocessable();
        $this->getJson('/admin/tables/unknown')->assertNotFound();
    }

    public function test_sidebar_pages_render_and_actions_remain_protected(): void
    {
        $this->seed();
        $this->actingAs(User::first());
        foreach(['/admin','/admin/properti','/admin/faq','/admin/pesan-whatsapp','/admin/konten','/admin/akun'] as $url) {
            $this->get($url)->assertOk()->assertSee('admin-sidebar')->assertSee('Konten Website');
        }
        $this->getJson('/admin/tables/properties')->assertOk()->assertSee('_token')->assertSee('DELETE');
    }
}

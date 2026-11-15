<?php
namespace Tests\Feature;

use App\Models\{Property, User};
use Database\Seeders\AfikaHousingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_type_buttons_open_only_the_selected_payment_details(): void
    {
        $this->seed(AfikaHousingSeeder::class);
        foreach (Property::all() as $property) {
            foreach ($property->house_types as $type) {
                $url = route('property.type', ['property'=>$property, 'type'=>$type['type']]);
                $this->get('/')->assertSee($url);
                $response = $this->get($url)->assertOk();
                if ($type['available']) {
                    $response->assertSee('Rp '.number_format($type['cash_price'], 0, ',', '.'));
                    foreach ($type['installments'] as $amount) $response->assertSee('Rp '.number_format($amount, 0, ',', '.'));
                    foreach ($property->house_types as $other) {
                        if ($other['available'] && $other['type'] !== $type['type']) $response->assertDontSee('Rp '.number_format($other['cash_price'],0,',','.'));
                    }
                } else $response->assertSee('Tipe 60 tidak tersedia')->assertDontSee('Angsuran per bulan');
            }
            $this->get('/properti/'.$property->id.'/tipe/999')->assertNotFound();
        }
        $property = Property::first();
        $property->update(['is_published'=>false]);
        $this->get('/properti/'.$property->id.'/tipe/36')->assertNotFound();
    }

    public function test_admin_can_update_type_details_and_public_page_uses_saved_data(): void
    {
        $this->seed(AfikaHousingSeeder::class);
        $property = Property::where('name','Afika Park')->firstOrFail();
        $this->get('/admin/properti/'.$property->id.'/edit')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create());
        $this->get('/admin/properti')->assertOk()->assertSee('Edit tipe Afika Park');
        $data = $property->only(['name','location','price','land_area','building_area','bedrooms','bathrooms','description','is_published','house_types']);
        $data['house_types'][0]['description'] = 'Detail resmi tipe 36 dari admin';
        $data['house_types'][0]['cash_price'] = 215000000;
        $data['house_types'][0]['installments'][5] = 2400000;
        $this->put('/admin/properti/'.$property->id, $data)->assertSessionHasNoErrors();
        $this->get('/properti/'.$property->id.'/tipe/36')->assertOk()->assertSee('215.000.000')->assertSee('2.400.000')->assertSee('Detail resmi tipe 36 dari admin');
        $this->assertEquals(215000000, $property->fresh()->price);
        $this->get('/properti/'.$property->id.'/tipe/60')->assertOk()->assertSee('480.000.000')->assertDontSee('215.000.000');
        $data['house_types'][1]['type'] = 36;
        $this->put('/admin/properti/'.$property->id, $data)->assertSessionHasErrors('house_types.1.type');
    }
}

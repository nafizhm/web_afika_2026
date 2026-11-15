<?php
namespace Tests\Feature;

use App\Models\{Property, Faq};
use Database\Seeders\AfikaHousingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AfikaHousingTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_catalog_is_repeatable_and_pages_show_correct_prices(): void
    {
        $this->seed(AfikaHousingSeeder::class);
        $this->seed(AfikaHousingSeeder::class);
        $this->assertSame(3, Property::count());
        $this->assertSame(8, Faq::where('is_active', true)->where('is_ai', true)->count());
        $this->get('/')->assertOk()->assertSee('Afika Park')->assertSee('Afika Land')->assertSee('Afika Residence');
        foreach (['Afika Park' => '210.000.000', 'Afika Land' => '200.000.000', 'Afika Residence' => '190.000.000'] as $name => $price) {
            $property = Property::where('name', $name)->firstOrFail();
            $this->get('/properti/'.$property->id)->assertOk()->assertSee($price)->assertSee('1.760.000')->assertSee('Foto sementara');
        }
        $land = Property::where('name', 'Afika Land')->firstOrFail();
        $this->get('/properti/'.$land->id)->assertSee('470.000.000')->assertSee('15.000.000')->assertSee('Gratis');
        $residence = Property::where('name', 'Afika Residence')->firstOrFail();
        $this->assertFalse($residence->house_types[1]['available']);
        $this->get('/properti/'.$residence->id)->assertSee('Tipe 60 tidak tersedia');
    }

    public function test_only_unchanged_trial_answers_are_retired(): void
    {
        $trial = json_decode(file_get_contents(database_path('seeders/data/afika_faq_trial.json')), true);
        Faq::create($trial[0]);
        Faq::create(array_replace($trial[1], ['answer' => 'Jawaban yang sudah dikoreksi admin']));
        $this->seed(AfikaHousingSeeder::class);
        $this->assertFalse(Faq::where('question', $trial[0]['question'])->first()->is_active);
        $this->assertTrue(Faq::where('answer', 'Jawaban yang sudah dikoreksi admin')->first()->is_active);
    }

    public function test_admin_can_save_photo_form_without_inventing_unknown_specs(): void
    {
        $this->seed(AfikaHousingSeeder::class);
        $this->actingAs(\App\Models\User::factory()->create());
        $property = Property::where('name', 'Afika Park')->firstOrFail();
        $this->get('/admin/properti/'.$property->id.'/edit')->assertOk()->assertSee('Tipe rumah');
        $data = $property->only(['name','location','price','land_area','building_area','bedrooms','bathrooms','description','is_published','house_types']);
        $this->put('/admin/properti/'.$property->id, $data)->assertSessionHasNoErrors();
        $this->assertSame(0, $property->fresh()->land_area);
        $this->assertSame(2300000, $property->fresh()->house_types[0]['installments'][5]);
        $data['house_types'][0]['installments'][5] = null;
        $this->put('/admin/properti/'.$property->id, $data)->assertSessionHasErrors('house_types.0.installments.5');
    }
}

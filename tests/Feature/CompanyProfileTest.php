<?php

namespace Tests\Feature;

use App\Models\{Setting, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_is_only_accessible_after_login(): void
    {
        $this->get('/admin/profil')->assertRedirect('/admin/login');
        $this->put('/admin/profil',[])->assertRedirect('/admin/login');
    }

    public function test_profile_and_branding_update_public_identity(): void
    {
        $this->seed();
        Storage::fake('public');
        $this->actingAs(User::first());
        $this->get('/admin/profil')->assertOk()->assertSee('Profil perusahaan');
        $data=[
            'company_name'=>'PT Contoh Profil', 'address'=>'Jalan Uji Nomor 10',
            'phone'=>'082221992911', 'email'=>'kantor@example.com',
            'google_maps_embed'=>'<iframe src="https://www.google.com/maps/embed?pb=contoh" width="600" height="450" onload="alert(1)"></iframe>',
            'logo'=>UploadedFile::fake()->image('logo.png'),
            'favicon'=>UploadedFile::fake()->image('favicon.png',32,32),
        ];
        $this->put('/admin/profil',$data)->assertSessionHasNoErrors()->assertRedirect('/admin/profil');
        $settings=Setting::pluck('value','key');
        Storage::disk('public')->assertExists($settings['logo']);
        Storage::disk('public')->assertExists($settings['favicon']);
        $this->assertSame('https://www.google.com/maps/embed?pb=contoh',$settings['google_maps_url']);
        $this->get('/')->assertOk()->assertSee('PT Contoh Profil')->assertSee('Jalan Uji Nomor 10')
            ->assertSee('mailto:kantor@example.com')->assertSee('tel:082221992911')
            ->assertSee('rel="icon"',false)->assertDontSee('onload="alert(1)"',false);
        unset($data['logo'],$data['favicon']);
        $data['google_maps_embed']='';
        $this->put('/admin/profil',$data)->assertSessionHasNoErrors();
        $this->assertSame($settings['logo'],Setting::find('logo')->value);
        $this->assertSame($settings['favicon'],Setting::find('favicon')->value);
        $this->assertNull(Setting::find('google_maps_url')->value);
    }

    public function test_maps_rejects_unsafe_urls_and_invalid_fields_without_saving(): void
    {
        $this->actingAs(User::factory()->create());
        foreach(['javascript:alert(1)','https://www.google.com.evil.example/maps/embed','<iframe src="https://evil.example/maps/embed"></iframe>'] as $embed) {
            $this->put('/admin/profil',['company_name'=>'Uji','google_maps_embed'=>$embed])->assertSessionHasErrors('google_maps_embed');
        }
        $this->put('/admin/profil',['company_name'=>'','phone'=>'<script>','email'=>'bukan-email'])->assertSessionHasErrors(['company_name','phone','email']);
        $this->assertDatabaseCount('settings',0);
    }
}

<?php
namespace Tests\Feature;
use App\Models\{User,Property,Setting};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class WebsiteTest extends TestCase {
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); $this->seed(); }
    public function test_public_site_and_property_visibility(): void {
        $this->get('/')->assertOk()->assertSee('Afika')->assertSee('Afika Park')->assertSee('Afika Land')->assertSee('Afika Residence')->assertDontSee('Contoh Tipe');
        $property=Property::first();
        $this->get('/properti/'.$property->id)->assertOk();
        $property->update(['is_published'=>false]);
        $this->get('/properti/'.$property->id)->assertNotFound();
    }
    public function test_admin_requires_authentication_and_username_login(): void {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->post('/admin/login',['username'=>'admin','password'=>'wrong'])->assertSessionHasErrors('username');
        $this->post('/admin/login',['username'=>'admin','password'=>'Afika123!'])->assertRedirect('/admin');
        $this->assertAuthenticated(); $this->get('/admin')->assertOk();
        $this->post('/admin/logout')->assertRedirect('/admin/login'); $this->assertGuest();
    }
    public function test_admin_can_manage_property_and_upload_image(): void {
        Storage::fake('public'); $this->actingAs(User::first());
        $data=['name'=>'Rumah Uji','location'=>'Lokasi uji','land_area'=>90,'building_area'=>45,'bedrooms'=>2,'bathrooms'=>1,'description'=>'Deskripsi uji','is_published'=>1,'image'=>UploadedFile::fake()->image('rumah.jpg')];
        $this->post('/admin/properti',$data)->assertSessionHasNoErrors()->assertRedirect('/admin');
        $p=Property::where('name','Rumah Uji')->firstOrFail(); Storage::disk('public')->assertExists($p->image);
        unset($data['image']); $data['is_published']=0;
        $this->put('/admin/properti/'.$p->id,$data)->assertSessionHasNoErrors();
        $this->assertFalse($p->fresh()->is_published);
        $this->get('/admin/properti/'.$p->id.'/edit')->assertOk();
        $this->delete('/admin/properti/'.$p->id)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('properties',['id'=>$p->id]);
    }
    public function test_content_and_password_updates_are_validated(): void {
        $user=User::first(); $this->actingAs($user);
        $data=['hero_title'=>'Judul baru','hero_description'=>'Deskripsi baru','about'=>'Tentang kami','whatsapp'=>'6281234567890','address'=>'Alamat uji','instagram'=>'https://www.instagram.com/afika'];
        $this->put('/admin/konten',$data)->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Judul baru')->assertSee('wa.me/6281234567890');
        $this->put('/admin/password',['current_password'=>'wrong','password'=>'Baru12345!','password_confirmation'=>'Baru12345!'])->assertSessionHasErrors('current_password');
        $this->put('/admin/password',['current_password'=>'Afika123!','password'=>'Baru12345!','password_confirmation'=>'Baru12345!'])->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Baru12345!',$user->fresh()->password));
    }
}

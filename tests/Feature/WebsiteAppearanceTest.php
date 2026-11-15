<?php
namespace Tests\Feature;
use App\Models\{User,Setting,Faq};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class WebsiteAppearanceTest extends TestCase {
 use RefreshDatabase;
 public function test_editor_is_authenticated_and_validates_theme_and_links(): void {
  $this->get('/admin/tampilan-website')->assertRedirect('/admin/login');
  $this->put('/admin/tampilan-website',[])->assertRedirect('/admin/login');
  $this->actingAs(User::factory()->create());
  $this->get('/admin/tampilan-website')->assertOk()->assertSee('Foto website');
  $this->put('/admin/tampilan-website',['theme_primary'=>'red; color:white','hero_image'=>'javascript:alert(1)','instagram'=>'javascript:alert(1)'])->assertSessionHasErrors(['theme_primary','hero_image','instagram']);
  $this->assertDatabaseMissing('settings',['key'=>'theme_primary']);
 }
 public function test_saved_copy_visibility_and_images_reach_public_pages_and_upload_is_preserved(): void {
  Storage::fake('public');$this->actingAs(User::factory()->create());
  $this->put('/admin/tampilan-website',['brand_name'=>'Rumah Baru','about_title'=>'Judul khusus','theme_primary'=>'#112233','show_gallery'=>0,'hero_image_upload'=>UploadedFile::fake()->image('rumah.jpg')])->assertSessionHasNoErrors();
  $image=Setting::find('hero_image')->value;Storage::disk('public')->assertExists(substr($image,8));
  $this->get('/')->assertOk()->assertSee('Rumah Baru')->assertSee('Judul khusus')->assertSee('#112233')->assertSee($image)->assertDontSee('class="container estate-gallery"',false);
  $this->put('/admin/tampilan-website',['hero_image'=>'','about_title'=>'<script>alert(1)</script>'])->assertSessionHasNoErrors();
  $this->assertSame($image,Setting::find('hero_image')->value);
  $this->get('/')->assertSee('&lt;script&gt;',false)->assertDontSee('<script>alert(1)</script>',false);
 }
 public function test_public_faq_limit_can_show_all_without_exposing_private_faqs(): void {
  for($i=0;$i<10;$i++)Faq::create(['question'=>'Question '.$i,'answer'=>'Answer','sort_order'=>$i,'is_active'=>true,'is_public'=>true]);
  Faq::create(['question'=>'PRIVATE ANSWER','answer'=>'Private','sort_order'=>0,'is_active'=>true,'is_public'=>false]);
  Setting::create(['key'=>'faq_limit','value'=>'0']);
  $this->get('/')->assertOk()->assertSee('Question 9')->assertDontSee('PRIVATE ANSWER');
 }
}

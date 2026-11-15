<?php
namespace Database\Seeders;
use App\Models\{User,Property,Setting};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder {
public function run(): void {
User::firstOrCreate(['username'=>'admin'],['name'=>'Administrator','password'=>Hash::make('Afika123!')]);
foreach(['hero_title'=>'Ruang untuk tumbuh. Tempat untuk pulang.','hero_description'=>'Temukan inspirasi hunian bersama Afika Residence. Awal cerita baru untuk Anda dan keluarga.','about'=>'Afika Residence merupakan brand perumahan PT. Sahir Propertindo Niaga. Kami hadir untuk membantu Anda menemukan hunian yang sesuai dengan kebutuhan keluarga. Hubungi tim kami untuk informasi pilihan rumah, lokasi, dan ketersediaan unit.','whatsapp'=>'','address'=>'','instagram'=>''] as $key=>$value) Setting::firstOrCreate(['key'=>$key],['value'=>$value]);
foreach([[36,72],[45,90],[60,120]] as [$building,$land]) Property::firstOrCreate(['name'=>'Contoh Tipe '.$building],['location'=>'Lokasi akan diperbarui','price'=>null,'land_area'=>$land,'building_area'=>$building,'bedrooms'=>2,'bathrooms'=>1,'description'=>'Konten contoh untuk pratinjau desain. Spesifikasi, lokasi, harga, dan ketersediaan akan diperbarui oleh pengelola.','is_published'=>true]);
}}

<?php
namespace Database\Seeders;
use App\Models\Faq;
use Illuminate\Database\Seeder;
class AssistantFaqSeeder extends Seeder {
 public function run(): void {
  foreach ([['identity','Nama asisten','Fika'],['handoff','Jika informasi belum tersedia atau perlu pengecekan admin','Untuk informasi tersebut, saya belum bisa memastikan, Kak. Admin kami akan mengambil alih obrolan ini pada jam kerja. Kakak cukup melanjutkan percakapan di sini.']] as [$type,$question,$answer]) {
   Faq::firstOrCreate(['faq_type'=>$type],['question'=>$question,'answer'=>$answer,'sort_order'=>0,'is_active'=>true,'is_public'=>false]);
  }
 }
}

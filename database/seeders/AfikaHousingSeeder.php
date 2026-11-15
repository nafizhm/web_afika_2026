<?php
namespace Database\Seeders;

use App\Models\{Property, Faq, Setting};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AfikaHousingSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('seeders/data/afika_housing.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($data) {
            // Retire the old trial answers so AI cannot mix simulated prices with client data.
            $trial = json_decode(file_get_contents(database_path('seeders/data/afika_faq_trial.json')), true, 512, JSON_THROW_ON_ERROR);
            foreach ($trial as $row) {
                Faq::where('question', $row['question'])->where('answer', $row['answer'])->update(['is_active' => false]);
            }
            Property::whereIn('name', ['Contoh Tipe 36', 'Contoh Tipe 45', 'Contoh Tipe 60'])->update(['is_published' => false]);
            foreach ($data['properties'] as $property) {
                Property::updateOrCreate(['name' => $property['name']], $property);
            }
            foreach ($data['faqs'] as $index => $faq) {
                Faq::updateOrCreate(['question' => $faq['question']], $faq + ['sort_order' => $index, 'is_active' => true, 'faq_type' => 'general', 'is_public' => true, 'is_ai' => true]);
            }
            foreach ($data['settings'] as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });
    }
}

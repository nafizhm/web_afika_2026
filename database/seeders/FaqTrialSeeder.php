<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Data simulasi untuk trial; jalankan secara eksplisit, bukan seeder produksi default. */
class FaqTrialSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(file_get_contents(database_path('seeders/data/afika_faq_trial.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                // Jangan menimpa FAQ yang sudah dikoreksi client saat seeder dijalankan lagi.
                Faq::firstOrCreate(['question' => $row['question']], $row);
            }
        });
    }
}

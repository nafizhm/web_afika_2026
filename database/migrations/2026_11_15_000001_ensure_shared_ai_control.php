<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('whatsapp_ai_control')) {
            Schema::create('whatsapp_ai_control', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->boolean('enabled')->default(true);
                $table->dateTime('updated_at');
            });
        }
        DB::table('whatsapp_ai_control')->insertOrIgnore(['id'=>1,'enabled'=>1,'updated_at'=>gmdate('Y-m-d H:i:s')]);
    }
    public function down(): void {
        // Shared webhook state must survive Laravel rollback.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->char('dedup_key', 64)->unique();
            $table->string('message_id', 191);
            $table->string('account', 30);
            $table->string('chat_jid', 191)->index();
            $table->string('sender_jid', 191);
            $table->string('sender_phone', 30)->nullable();
            $table->string('sender_name')->nullable();
            $table->string('message_type', 80);
            $table->longText('body')->nullable();
            $table->json('raw_payload');
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('whatsapp_messages'); }
};


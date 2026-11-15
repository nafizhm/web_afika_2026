<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('faqs',function(Blueprint $t){$t->string('faq_type',20)->default('general');$t->boolean('is_public')->default(true);}); }
 public function down(): void { Schema::table('faqs',fn(Blueprint $t)=>$t->dropColumn(['faq_type','is_public'])); }
};

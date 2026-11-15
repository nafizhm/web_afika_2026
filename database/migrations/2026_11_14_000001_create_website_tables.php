<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
public function up(): void {
Schema::table('users', function(Blueprint $t) { $t->string('username')->unique(); $t->string('email')->nullable()->change(); });
Schema::create('settings', function(Blueprint $t) { $t->string('key')->primary(); $t->text('value')->nullable(); });
Schema::create('properties', function(Blueprint $t) {
$t->id(); $t->string('name'); $t->string('location'); $t->unsignedBigInteger('price')->nullable(); $t->unsignedInteger('land_area'); $t->unsignedInteger('building_area'); $t->unsignedTinyInteger('bedrooms')->default(2); $t->unsignedTinyInteger('bathrooms')->default(1); $t->text('description'); $t->string('image')->nullable(); $t->boolean('is_published')->default(false); $t->timestamps();
}); }
public function down(): void { Schema::dropIfExists('properties'); Schema::dropIfExists('settings'); Schema::table('users', fn(Blueprint $t) => $t->dropColumn('username')); }
};

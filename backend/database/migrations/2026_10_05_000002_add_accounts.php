<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('users',function(Blueprint $t){$t->id();$t->string('username',50)->unique();$t->string('name',150);$t->string('password');$t->string('role',20);$t->foreignId('sinh_vien_id')->nullable()->unique()->constrained('SINHVIEN')->restrictOnDelete();$t->timestamps();});
  Schema::create('access_tokens',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained('users')->cascadeOnDelete();$t->string('token_hash',64)->unique();$t->dateTime('expires_at');$t->timestamps();});
  Schema::table('DETAI',function(Blueprint $t){$t->foreignId('giang_vien_id')->nullable()->constrained('users')->restrictOnDelete();});
 }
 public function down(): void {
  Schema::table('DETAI',function(Blueprint $t){$t->dropConstrainedForeignId('giang_vien_id');});
  Schema::dropIfExists('access_tokens');Schema::dropIfExists('users');
 }
};

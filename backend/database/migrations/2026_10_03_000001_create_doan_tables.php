<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('SINHVIEN',function(Blueprint $t) { $t->id(); $t->string('ma_sinh_vien',30)->unique(); $t->string('ho_ten',150); $t->string('email',150)->unique(); $t->string('lop',50); $t->string('nganh',150); $t->timestamps(); });
        Schema::create('DETAI',function(Blueprint $t) { $t->id(); $t->string('ma_de_tai',30)->unique(); $t->string('ten_de_tai'); $t->text('mo_ta')->nullable(); $t->string('giang_vien',150); $t->unsignedInteger('so_luong_sinh_vien'); $t->string('trang_thai',20)->default('con_trong'); $t->timestamps(); });
        Schema::create('DANGKY',function(Blueprint $t) { $t->id(); $t->foreignId('sinh_vien_id')->constrained('SINHVIEN')->restrictOnDelete(); $t->foreignId('de_tai_id')->constrained('DETAI')->restrictOnDelete(); $t->dateTime('ngay_dang_ky'); $t->string('trang_thai',20)->default('cho_duyet'); $t->timestamps(); $t->unique(['sinh_vien_id','de_tai_id']); $t->index(['de_tai_id','trang_thai']); });
    }
    public function down(): void { Schema::dropIfExists('DANGKY'); Schema::dropIfExists('DETAI'); Schema::dropIfExists('SINHVIEN'); }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('DANGKY', function (Blueprint $table) {
            foreach (['diem_huong_dan', 'diem_phan_bien', 'diem_bao_ve'] as $field) {
                $table->decimal($field, 4, 2)->nullable();
            }
        });
    }
    public function down(): void {
        Schema::table('DANGKY', fn (Blueprint $table) => $table->dropColumn(['diem_huong_dan', 'diem_phan_bien', 'diem_bao_ve']));
    }
};

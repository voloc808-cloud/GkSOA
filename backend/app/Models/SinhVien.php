<?php
namespace App\Models;
class SinhVien extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'SINHVIEN';
    protected $fillable = ['ma_sinh_vien','ho_ten','email','lop','nganh'];
    protected $casts = [];
}

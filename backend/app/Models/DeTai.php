<?php
namespace App\Models;
class DeTai extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'DETAI';
    protected $fillable = ['ma_de_tai','ten_de_tai','mo_ta','giang_vien','giang_vien_id','so_luong_sinh_vien','trang_thai'];
    protected $casts = ['so_luong_sinh_vien'=>'integer','giang_vien_id'=>'integer'];
}

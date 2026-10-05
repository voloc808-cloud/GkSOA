<?php
namespace App\Models;
class DangKy extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'DANGKY';
    protected $fillable = ['sinh_vien_id','de_tai_id','ngay_dang_ky','trang_thai'];
    protected $casts = ['sinh_vien_id'=>'integer', 'de_tai_id'=>'integer'];
}

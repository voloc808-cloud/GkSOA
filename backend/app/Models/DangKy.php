<?php
namespace App\Models;
class DangKy extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'DANGKY';
    protected $fillable = ['sinh_vien_id','de_tai_id','ngay_dang_ky','trang_thai'];
    protected $casts = ['sinh_vien_id'=>'integer', 'de_tai_id'=>'integer', 'diem_huong_dan'=>'float', 'diem_phan_bien'=>'float', 'diem_bao_ve'=>'float'];
    protected $appends = ['diem_tong_ket', 'ket_qua'];
    public function getDiemTongKetAttribute() {
        if ($this->trang_thai !== 'da_duyet' || $this->diem_huong_dan === null || $this->diem_phan_bien === null || $this->diem_bao_ve === null) return null;
        return round(($this->diem_huong_dan * 30 + $this->diem_phan_bien * 30 + $this->diem_bao_ve * 40) / 100, 2);
    }
    public function getKetQuaAttribute() {
        $total = $this->diem_tong_ket;
        return $total === null ? 'chua_cham' : ($total >= 5 ? 'dat' : 'khong_dat');
    }
}

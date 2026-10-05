<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
class DeTaiRequest {
    public static function rules($id = null): array {
        return ['giang_vien_id'=>['nullable','integer',Rule::exists('users','id')->where('role','giangvien')],'ma_de_tai'=>['required','string','max:30',Rule::unique('DETAI')->ignore($id)],'ten_de_tai'=>'required|string|max:255','mo_ta'=>'nullable|string','giang_vien'=>'required|string|max:150','so_luong_sinh_vien'=>'required|integer|min:1|max:1000','trang_thai'=>'required|in:con_trong,da_du,ngung_nhan'];
    }
}

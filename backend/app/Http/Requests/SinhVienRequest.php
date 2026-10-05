<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
class SinhVienRequest {
    public static function rules($id = null): array {
        return ['ma_sinh_vien'=>['required','string','max:30',Rule::unique('SINHVIEN')->ignore($id)],'ho_ten'=>'required|string|max:150','email'=>['required','email','max:150',Rule::unique('SINHVIEN')->ignore($id)],'lop'=>'required|string|max:50','nganh'=>'required|string|max:150'];
    }
}

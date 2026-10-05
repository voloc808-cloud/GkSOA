<?php
namespace App\Http\Requests;
class DangKyRequest {
    public static function rules($id = null): array {
        return $id ? ['trang_thai'=>'required_without:de_tai_id|in:cho_duyet,da_duyet,tu_choi,da_huy','de_tai_id'=>'sometimes|required|integer|min:1'] : ['sinh_vien_id'=>'required|integer|min:1','de_tai_id'=>'required|integer|min:1'];
    }
}

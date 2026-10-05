<?php
namespace App\Services;
use App\Models\SinhVien;
use App\Models\DangKy;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
class SinhVienService {
    public function getAll(array $filters = []) {
        $query = AccessPolicy::scope(SinhVien::query(),'sinhvien');
        if (!empty($filters['sinh_vien_id'])) $query->whereKey($filters['sinh_vien_id']);
        if (!empty($filters['q'])) $query->where(function($q) use ($filters) { $q->where('ma_sinh_vien','like','%'.$filters['q'].'%')->orWhere('ho_ten','like','%'.$filters['q'].'%'); });
        return $query->orderBy('id','desc')->get();
    }
    public function search(string $q) { return $this->getAll(['q'=>$q]); }
    public function getById($id) { return AccessPolicy::scope(SinhVien::query(),'sinhvien')->findOrFail($id); }
    public function create(array $data) { return SinhVien::create($data); }
    public function update($id, array $data) { $row=$this->getById($id); $row->update($data); return $row->fresh(); }
    public function delete($id) {
        DB::transaction(function() use ($id) {
            $row=SinhVien::whereKey($id)->lockForUpdate()->firstOrFail();
            if (\App\Models\User::where('sinh_vien_id',$id)->exists()) throw new HttpException(409,'Hãy xoá tài khoản liên kết của sinh viên trước.');
            if (DangKy::where('sinh_vien_id',$id)->exists()) throw new HttpException(409,'Hãy xoá các đăng ký của sinh viên trước.');
            $row->delete();
        });
    }
}

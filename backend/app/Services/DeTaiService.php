<?php
namespace App\Services;
use App\Models\DeTai;
use App\Models\DangKy;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
class DeTaiService {
    public function getAll(array $filters = []) {
        $query=AccessPolicy::scope(DeTai::query(),'detai');
        if (!empty($filters['q'])) $query->where(function($q) use ($filters) { $q->where('ma_de_tai','like','%'.$filters['q'].'%')->orWhere('ten_de_tai','like','%'.$filters['q'].'%'); });
        if (!empty($filters['trang_thai'])) $query->where('trang_thai',$filters['trang_thai']);
        return $query->orderBy('id','desc')->get();
    }
    public function search(string $q) { return $this->getAll(['q'=>$q]); }
    public function getById($id) { return AccessPolicy::scope(DeTai::query(),'detai')->findOrFail($id); }
    public function create(array $data) {
        if (!empty($data['giang_vien_id'])) $data['giang_vien']=\App\Models\User::findOrFail($data['giang_vien_id'])->name;
        if ($data['trang_thai']==='da_du') throw new HttpException(422,'Đề tài mới chưa có đăng ký nên không thể ở trạng thái đã đủ.');
        return DeTai::create($data);
    }
    public function update($id, array $data) {
        if (!empty($data['giang_vien_id'])) $data['giang_vien']=\App\Models\User::findOrFail($data['giang_vien_id'])->name;
        return DB::transaction(function() use ($id,$data) {
            $row=DeTai::whereKey($id)->lockForUpdate()->firstOrFail();
            $count=DangKyService::activeCount($id);
            if ($data['so_luong_sinh_vien'] < $count) throw new HttpException(409,'Sức chứa nhỏ hơn số đăng ký đang hoạt động.');
            $actual=$count >= $data['so_luong_sinh_vien'] ? 'da_du' : 'con_trong';
            if ($data['trang_thai']==='da_du' && $actual!=='da_du') throw new HttpException(422,'Trạng thái đã đủ được tính từ số đăng ký.');
            if ($data['trang_thai']!=='ngung_nhan') $data['trang_thai']=$actual;
            $row->update($data); return $row->fresh();
        });
    }
    public function delete($id) {
        DB::transaction(function() use ($id) {
            $row=DeTai::whereKey($id)->lockForUpdate()->firstOrFail();
            if (DangKy::where('de_tai_id',$id)->exists()) throw new HttpException(409,'Hãy xoá các đăng ký của đề tài trước.');
            $row->delete();
        });
    }
}

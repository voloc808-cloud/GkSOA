<?php
namespace App\Services;
use App\Models\{DangKy, DeTai, SinhVien};
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
class DiemService {
    public function save($id, array $data) {
        $actor = AccessPolicy::actor();
        if (!$actor || !in_array($actor->role, ['admin', 'giangvien'])) throw new HttpException(403, 'Bạn không có quyền chấm điểm.');
        $initial = AccessPolicy::scope(DangKy::query(), 'dangky')->findOrFail($id);
        return DB::transaction(function () use ($id, $data, $initial, $actor) {
            SinhVien::whereKey($initial->sinh_vien_id)->lockForUpdate()->firstOrFail();
            $topic = DeTai::whereKey($initial->de_tai_id)->lockForUpdate()->firstOrFail();
            $row = DangKy::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($row->de_tai_id !== $initial->de_tai_id) throw new HttpException(409, 'Đăng ký vừa thay đổi. Vui lòng tải lại.');
            if ($actor->role === 'giangvien' && (int) $topic->giang_vien_id !== $actor->id) throw new HttpException(403, 'Chỉ được chấm đề tài do mình phụ trách.');
            if ($row->trang_thai !== 'da_duyet') throw new HttpException(409, 'Chỉ được chấm điểm đăng ký đã duyệt.');
            foreach (['diem_huong_dan', 'diem_phan_bien', 'diem_bao_ve'] as $field) $row->$field = $data[$field];
            $row->save();
            return $row;
        });
    }
}

<?php
namespace App\Services;
use App\Models\{DangKy,DeTai,SinhVien};
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
class DangKyService {
    const ACTIVE = ['cho_duyet','da_duyet'];
    public static function activeCount($id) { return DangKy::where('de_tai_id',$id)->whereIn('trang_thai',self::ACTIVE)->count(); }
    public function getAll(array $filters = []) {
        $query=AccessPolicy::scope(DangKy::query(),'dangky');
        if (!empty($filters['giang_vien_id'])) $query->whereIn('de_tai_id',DeTai::where('giang_vien_id',$filters['giang_vien_id'])->select('id'));
        foreach (['sinh_vien_id','de_tai_id','trang_thai'] as $key) if (!empty($filters[$key])) $query->where($key,$filters[$key]);
        return $query->orderBy('id','desc')->get();
    }
    public function getById($id) { return AccessPolicy::scope(DangKy::query(),'dangky')->findOrFail($id); }
    public function checkDuplicate($student,$topic) { return DangKy::where('sinh_vien_id',$student)->where('de_tai_id',$topic)->exists(); }
    public function checkTopicCapacity(DeTai $topic) {
        if ($topic->trang_thai==='ngung_nhan') throw new HttpException(409,'Đề tài đã ngừng nhận đăng ký.');
        if (self::activeCount($topic->id) >= $topic->so_luong_sinh_vien) throw new HttpException(409,'Đề tài đã đủ số lượng sinh viên.');
    }
    private function sync(DeTai $topic) {
        if ($topic->trang_thai!=='ngung_nhan') { $topic->trang_thai=self::activeCount($topic->id)>=$topic->so_luong_sinh_vien ? 'da_du':'con_trong'; $topic->save(); }
    }
    public function create(array $data) {
        $lookup=new ServiceLookup();
        $lookup->find('sinhvien',(int)$data['sinh_vien_id']);
        $lookup->find('detai',(int)$data['de_tai_id']);
        return DB::transaction(function() use ($data) {
            SinhVien::whereKey($data['sinh_vien_id'])->lockForUpdate()->firstOrFail();
            $topic=DeTai::whereKey($data['de_tai_id'])->lockForUpdate()->firstOrFail();
            if (AccessPolicy::actor()->role==='sinhvien' && !\App\Models\User::whereKey($topic->giang_vien_id)->where('role','giangvien')->exists()) throw new HttpException(422,'Chỉ được đăng ký đề tài có giảng viên phụ trách.');
            if ($this->checkDuplicate($data['sinh_vien_id'],$data['de_tai_id'])) throw new HttpException(409,'Sinh viên đã đăng ký đề tài này.');
            $this->checkTopicCapacity($topic);
            $row=DangKy::create(['sinh_vien_id'=>$data['sinh_vien_id'],'de_tai_id'=>$data['de_tai_id'],'ngay_dang_ky'=>date('Y-m-d H:i:s'),'trang_thai'=>'cho_duyet']);
            $this->sync($topic); return $row;
        });
    }
    public function update($id,array $data) {
        $initial=$this->getById($id);
        $targetId=(int)($data['de_tai_id']??$initial->de_tai_id);
        if ($targetId!==(int)$initial->de_tai_id) (new ServiceLookup())->find('detai',$targetId);
        return DB::transaction(function() use ($id,$data,$initial,$targetId) {
            SinhVien::whereKey($initial->sinh_vien_id)->lockForUpdate()->firstOrFail();
            $topics=DeTai::whereIn('id',[$initial->de_tai_id,$targetId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $topic=$topics->get($initial->de_tai_id); $target=$topics->get($targetId);
            if (!$topic || !$target) throw new HttpException(404,'Không tìm thấy đề tài.');
            $row=DangKy::whereKey($id)->lockForUpdate()->firstOrFail();
            if ((int)$row->de_tai_id!==(int)$initial->de_tai_id) throw new HttpException(409,'Đăng ký vừa thay đổi. Vui lòng tải lại.');
            $status=$data['trang_thai']??$row->trang_thai;
            $moving=$targetId!==(int)$row->de_tai_id;
            if ($moving && DangKy::where('sinh_vien_id',$row->sinh_vien_id)->where('de_tai_id',$targetId)->where('id','<>',$id)->exists()) throw new HttpException(409,'Sinh viên đã có đăng ký tại đề tài đích.');
            if (in_array($status,self::ACTIVE) && ($moving || !in_array($row->trang_thai,self::ACTIVE))) $this->checkTopicCapacity($target);
            $row->update(['trang_thai'=>$status,'de_tai_id'=>$targetId]);
            if ($moving || $status !== 'da_duyet') {
                $row->diem_huong_dan = $row->diem_phan_bien = $row->diem_bao_ve = null;
                $row->save();
            }
            $this->sync($topic); if ($moving) $this->sync($target);
            return $row->fresh();
        });
    }
    public function delete($id) {
        DB::transaction(function() use ($id) {
            $initial=$this->getById($id);
            SinhVien::whereKey($initial->sinh_vien_id)->lockForUpdate()->firstOrFail();
            $topic=DeTai::whereKey($initial->de_tai_id)->lockForUpdate()->firstOrFail();
            $row=DangKy::whereKey($id)->lockForUpdate()->firstOrFail();
            if ((int)$row->de_tai_id!==(int)$initial->de_tai_id) throw new HttpException(409,'Đăng ký vừa thay đổi. Vui lòng tải lại.');
            $row->delete(); $this->sync($topic);
        });
    }
}

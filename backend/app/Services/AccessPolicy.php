<?php
namespace App\Services;
use App\Models\{User,DeTai,DangKy};
use Symfony\Component\HttpKernel\Exception\HttpException;
class AccessPolicy {
 public static function actor(): ?User {return app('request')->attributes->get('actor');}
 public static function scope($query,string $resource) {
  $user=self::actor();
  if (!$user) throw new HttpException(401,'Vui lòng đăng nhập.');
  if ($resource==='sinhvien' && $user->role==='sinhvien') $query->where('id',$user->sinh_vien_id??0);
  if ($resource==='sinhvien' && $user->role==='giangvien') $query->whereIn('id',DangKy::whereIn('de_tai_id',DeTai::where('giang_vien_id',$user->id)->select('id'))->select('sinh_vien_id'));
  if ($resource==='detai' && $user->role==='giangvien') $query->where('giang_vien_id',$user->id);
  if ($resource==='detai' && $user->role==='sinhvien') $query->whereIn('giang_vien_id',User::where('role','giangvien')->select('id'));
  if ($resource==='dangky') {
   if ($user->role==='sinhvien') $query->where('sinh_vien_id',$user->sinh_vien_id??0);
   if ($user->role==='giangvien') $query->whereIn('de_tai_id',DeTai::where('giang_vien_id',$user->id)->select('id'));
  }
  return $query;
 }
}

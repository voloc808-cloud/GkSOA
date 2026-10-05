<?php
namespace App\Http\Middleware;
use Closure;
use App\Models\{AccessToken,User,DeTai,DangKy};
use Symfony\Component\HttpKernel\Exception\HttpException;
class Authenticate {
 public function handle($request,Closure $next) {
  if ($request->path()==='health' || $request->path()==='api/auth/login') return $next($request);
  $raw=$request->bearerToken();
  $session=$raw?AccessToken::where('token_hash',hash('sha256',$raw))->where('expires_at','>',date('Y-m-d H:i:s'))->first():null;
  $user=$session?User::find($session->user_id):null;
  if (!$user || !in_array($user->role,['admin','giangvien','sinhvien'])) throw new HttpException(401,'Vui lòng đăng nhập lại.');
  $request->attributes->set('actor',$user);
  if (preg_match('#^api/(sinhvien|detai|dangky|users)(?:/([0-9]+))?$#',$request->path(),$parts)) {
   $resource=$parts[1];$id=$parts[2]??null;$method=$request->method();
   if ($resource==='users' && $user->role!=='admin') $this->deny();
   if ($method==='GET' || $user->role==='admin') return $next($request);
   if ($resource==='sinhvien') $this->deny();
   if ($resource==='detai') {
    if ($user->role!=='giangvien' || $method!=='POST') $this->deny();
    $request->merge(['giang_vien_id'=>$user->id,'giang_vien'=>$user->name]);
   }
   if ($resource==='dangky') {
    if ($method!=='POST') $this->deny();
    if ($method==='POST') {
     if ($user->role!=='sinhvien' || !$user->sinh_vien_id || (int)$request->input('sinh_vien_id')!==$user->sinh_vien_id) $this->deny();
    }
   }
  }
  return $next($request);
 }
 private function deny(): void {throw new HttpException(403,'Bạn không có quyền thực hiện thao tác này.');}
}

<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{User,AccessToken};
class AuthController extends \Laravel\Lumen\Routing\Controller {
 public function login(Request $request) {
  $data=$this->validate($request,['username'=>'required|string|max:50','password'=>'required|string|max:1024']);
  $user=User::where('username',$data['username'])->first();
  $valid=password_verify($data['password'],$user?$user->password:'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
  if (!$user || !$valid) return response()->json(['success'=>false,'message'=>'Tên đăng nhập hoặc mật khẩu không đúng.'],401);
  $raw=bin2hex(random_bytes(32));
  AccessToken::create(['user_id'=>$user->id,'token_hash'=>hash('sha256',$raw),'expires_at'=>date('Y-m-d H:i:s',time()+28800)]);
  return response()->json(['success'=>true,'data'=>['access_token'=>$raw,'token_type'=>'Bearer','expires_in'=>28800,'user'=>$user]])->header('Cache-Control','no-store');
 }
 public function me(Request $request) {return response()->json(['success'=>true,'data'=>$request->attributes->get('actor')])->header('Cache-Control','no-store');}
 public function logout(Request $request) {
  AccessToken::where('token_hash',hash('sha256',$request->bearerToken()))->delete();
  return response()->json(['success'=>true,'message'=>'Đã đăng xuất.']);
 }
}

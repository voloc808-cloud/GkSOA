<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\{User,AccessToken,DeTai};
use Symfony\Component\HttpKernel\Exception\HttpException;
class UserController extends \Laravel\Lumen\Routing\Controller {
 public function index() {return response()->json(['success'=>true,'data'=>User::orderBy('id')->get()]);}
 public function show($id) {return response()->json(['success'=>true,'data'=>User::findOrFail($id)]);}
 private function data(Request $r,$id=null): array {
  $data=$this->validate($r,['username'=>['required','string','max:50',Rule::unique('users')->ignore($id)],'name'=>'required|string|max:150','password'=>($id?'nullable':'required').'|string|min:8|max:128','role'=>'required|in:admin,giangvien,sinhvien','sinh_vien_id'=>['nullable','integer','exists:SINHVIEN,id',Rule::unique('users')->ignore($id)]]);
  if ($data['role']==='sinhvien' && empty($data['sinh_vien_id'])) throw new HttpException(422,'Tài khoản sinh viên phải liên kết một sinh viên.');
  if ($data['role']!=='sinhvien') $data['sinh_vien_id']=null;
  if (!empty($data['password'])) $data['password']=password_hash($data['password'],PASSWORD_BCRYPT); else unset($data['password']);
  return $data;
 }
 public function store(Request $r) {return response()->json(['success'=>true,'data'=>User::create($this->data($r))],201);}
 public function update(Request $r,$id) {
  $user=User::findOrFail($id);$data=$this->data($r,$id);
  if ((int)$id===$r->attributes->get('actor')->id && $data['role']!=='admin') throw new HttpException(409,'Không thể tự hạ quyền admin.');
  if ($data['role']!=='giangvien' && DeTai::where('giang_vien_id',$id)->exists()) throw new HttpException(409,'Chuyển đề tài sang giảng viên khác trước khi đổi vai trò.');
  DB::transaction(function() use($user,$data){$user->update($data);AccessToken::where('user_id',$user->id)->delete();});
  return response()->json(['success'=>true,'data'=>$user->fresh()]);
 }
 public function destroy(Request $r,$id) {
  $user=User::findOrFail($id);
  if ($user->id===$r->attributes->get('actor')->id) throw new HttpException(409,'Không thể xoá tài khoản đang đăng nhập.');
  if (DeTai::where('giang_vien_id',$id)->exists()) throw new HttpException(409,'Chuyển đề tài sang giảng viên khác trước khi xoá.');
  $user->delete();return response()->json(['success'=>true,'message'=>'Đã xoá tài khoản.']);
 }
}

<?php
putenv('DB_CONNECTION=sqlite');putenv('DB_DATABASE=:memory:');putenv('SERVICE_MODULE=all');
putenv('STUDENT_SERVICE_URL');putenv('TOPIC_SERVICE_URL');
$app=require __DIR__.'/../bootstrap/app.php';
set_exception_handler(function($e){fwrite(STDERR,$e->getMessage()."\n".$e->getTraceAsString());exit(1);});
$app->make(Illuminate\Contracts\Console\Kernel::class)->call('migrate',['--force'=>true]);$app->boot();
use App\Models\{User,SinhVien,DeTai,AccessToken};
$checks=0;
function check($actual,$expected,$label){global $checks;$checks++;if($actual!==$expected)throw new RuntimeException($label.': '.json_encode($actual));}
function callApi($method,$path,$data=null,$token=null,$status=200){
 global $app;
 $r=Illuminate\Http\Request::create('/api'.$path,$method,[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_ACCEPT'=>'application/json'], $data===null?null:json_encode($data));
 if($token)$r->headers->set('Authorization','Bearer '.$token);$app['request']=$r;
 $response=$app->handle($r);$body=json_decode($response->getContent(),true);
 check($response->getStatusCode(),$status,$method.' '.$path.' '.json_encode($body));
 check($body['success'],$status<400,'success envelope');return $body['data']??null;
}
function login($username){return callApi('POST','/auth/login',['username'=>$username,'password'=>'Test@12345'])['access_token'];}
$student=['ma_sinh_vien'=>'S1','ho_ten'=>'Student 1','email'=>'one@example.com','lop'=>'01','nganh'=>'CNTT'];
$s1=SinhVien::create($student);$s2=SinhVien::create(array_merge($student,['ma_sinh_vien'=>'S2','email'=>'two@example.com']));
foreach([['admin','admin',null],['teacher1','giangvien',null],['teacher2','giangvien',null],['student1','sinhvien',$s1->id],['student2','sinhvien',$s2->id]] as [$name,$role,$sid])User::create(['username'=>$name,'name'=>$name,'role'=>$role,'sinh_vien_id'=>$sid,'password'=>password_hash('Test@12345',PASSWORD_BCRYPT)]);
callApi('GET','/detai',null,null,401);callApi('GET','/users',null,'invalid',401);
callApi('POST','/auth/login',['username'=>'admin','password'=>'wrong'],null,401);
callApi('POST','/auth/login',[],null,422);
$admin=login('admin');$teacher=login('teacher1');$otherTeacher=login('teacher2');$studentToken=login('student1');$otherStudent=login('student2');
check(callApi('GET','/auth/me',null,$studentToken)['role'],'sinhvien','me');
check(array_key_exists('password',callApi('GET','/auth/me',null,$admin)),false,'password hidden');
callApi('GET','/users',null,$studentToken,403);callApi('GET','/users',null,$teacher,403);
check(count(callApi('GET','/sinhvien',null,$studentToken)),1,'own profile list');
callApi('GET','/sinhvien/'.$s2->id,null,$studentToken,404);
callApi('POST','/sinhvien',$student,$teacher,403);
callApi('PUT','/sinhvien/'.$s1->id,$student,$studentToken,403);
$topic=['ma_de_tai'=>'T1','ten_de_tai'=>'Test SOA','mo_ta'=>'Test','so_luong_sinh_vien'=>3,'trang_thai'=>'con_trong'];
callApi('POST','/detai',$topic,$studentToken,403);
$t=callApi('POST','/detai',$topic,$teacher,201);
check($t['giang_vien_id'],User::where('username','teacher1')->first()->id,'owner forced');
callApi('PUT','/detai/'.$t['id'],$topic,$otherTeacher,403);
callApi('DELETE','/detai/'.$t['id'],null,$studentToken,403);
callApi('POST','/dangky',['sinh_vien_id'=>$s2->id,'de_tai_id'=>$t['id']],$studentToken,403);
callApi('POST','/dangky',['sinh_vien_id'=>$s1->id,'de_tai_id'=>$t['id']],$teacher,403);
$r=callApi('POST','/dangky',['sinh_vien_id'=>$s1->id,'de_tai_id'=>$t['id']],$studentToken,201);
$r2=callApi('POST','/dangky',['sinh_vien_id'=>$s2->id,'de_tai_id'=>$t['id']],$otherStudent,201);
check(count(callApi('GET','/dangky',null,$studentToken)),1,'own registrations');
check(count(callApi('GET','/dangky?sinh_vien_id='.$s2->id,null,$studentToken)),0,'cannot bypass list scope');
check(count(callApi('GET','/dangky',null,$otherTeacher)),0,'teacher own list');
callApi('GET','/dangky/'.$r2['id'],null,$studentToken,404);
callApi('PUT','/dangky/'.$r['id'],['trang_thai'=>'da_duyet'],$studentToken,403);
callApi('PUT','/dangky/'.$r['id'],['trang_thai'=>'da_duyet'],$otherTeacher,403);
callApi('PUT','/dangky/'.$r['id'],['trang_thai'=>'da_duyet'],$teacher);
callApi('PUT','/dangky/'.$r2['id'],['trang_thai'=>'da_huy'],$studentToken,403);
callApi('DELETE','/dangky/'.$r['id'],null,$studentToken,403);
callApi('PUT','/dangky/'.$r['id'],['trang_thai'=>'da_huy'],$studentToken);
callApi('PUT','/dangky/'.$r['id'],['trang_thai'=>'cho_duyet'],$studentToken,403);
$account=['username'=>'newteacher','name'=>'New Teacher','role'=>'giangvien','password'=>'Test@12345'];
$new=callApi('POST','/users',$account,$admin,201);
callApi('POST','/users',array_merge($account,['username'=>'badstudent','role'=>'sinhvien']),$admin,422);
$newToken=login('newteacher');
callApi('PUT','/users/'.$new['id'],array_merge($account,['password'=>'Changed@12345']),$admin);
callApi('GET','/auth/me',null,$newToken,401);
callApi('DELETE','/users/'.User::where('username','admin')->first()->id,null,$admin,409);
callApi('PUT','/users/'.User::where('username','admin')->first()->id,array_merge($account,['username'=>'admin','role'=>'giangvien']),$admin,409);
callApi('POST','/auth/logout',null,$studentToken);
callApi('GET','/auth/me',null,$studentToken,401);
AccessToken::where('token_hash',hash('sha256',$otherStudent))->update(['expires_at'=>date('Y-m-d H:i:s',time()-10)]);
callApi('GET','/auth/me',null,$otherStudent,401);
echo "PASS auth: $checks assertions\n";


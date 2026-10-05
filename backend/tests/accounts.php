<?php
putenv('DB_CONNECTION=sqlite');putenv('DB_DATABASE=:memory:');putenv('SERVICE_MODULE=all');
putenv('STUDENT_SERVICE_URL');putenv('TOPIC_SERVICE_URL');
$app=require __DIR__.'/../bootstrap/app.php';
set_exception_handler(function($e){fwrite(STDERR,$e->getMessage()."\n");exit(1);});
$app->make(Illuminate\Contracts\Console\Kernel::class)->call('migrate',['--force'=>true]);
$app->boot();
$checks=0;$token=null;
function callApi($method,$path,$data=null,$status=200){
 global $app,$token,$checks;
 $r=Illuminate\Http\Request::create('/api'.$path,$method,[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_ACCEPT'=>'application/json'],json_encode($data));
 if($token)$r->headers->set('Authorization','Bearer '.$token);
 $app['request']=$r;$response=$app->handle($r);$body=json_decode($response->getContent(),true);$checks++;
 if($response->getStatusCode()!==$status)throw new RuntimeException("$method $path: ".$response->getContent());return $body['data']??null;
}
function check($condition){global $checks;$checks++;if(!$condition)throw new RuntimeException('Account assertion failed');}
App\Models\User::create(['username'=>'admin','name'=>'Admin','password'=>password_hash('admin-password',PASSWORD_BCRYPT),'role'=>'admin']);
callApi('GET','/users',null,401);
callApi('POST','/auth/login',['username'=>'missing','password'=>'password'],401);
$token=callApi('POST','/auth/login',['username'=>'admin','password'=>'admin-password'])['access_token'];$adminToken=$token;
$s=callApi('POST','/sinhvien',['ma_sinh_vien'=>'S1','ho_ten'=>'Student','email'=>'student@example.com','lop'=>'A','nganh'=>'IT'],201);
$lecturerData=['username'=>'lecturer','name'=>'Lecturer','password'=>'test-password','role'=>'giangvien'];
$lecturer=callApi('POST','/users',$lecturerData,201);check(!isset($lecturer['password']));
callApi('POST','/users',['username'=>'invalid','name'=>'Student','password'=>'test-password','role'=>'sinhvien'],422);
$student=callApi('POST','/users',['username'=>'student','name'=>'Student','password'=>'test-password','role'=>'sinhvien','sinh_vien_id'=>$s['id']],201);
$token=callApi('POST','/auth/login',['username'=>'lecturer','password'=>'test-password'])['access_token'];$lecturerToken=$token;
$topic=callApi('POST','/detai',['ma_de_tai'=>'D1','ten_de_tai'=>'Topic','mo_ta'=>'Demo','giang_vien'=>'Spoofed','giang_vien_id'=>1,'so_luong_sinh_vien'=>2,'trang_thai'=>'con_trong'],201);
check((int)$topic['giang_vien_id']===$lecturer['id'] && $topic['giang_vien']==='Lecturer');
callApi('GET','/users',null,403);callApi('POST','/users',$lecturerData,403);
callApi('PUT','/users/'.$student['id'],['role'=>'admin'],403);callApi('DELETE','/users/'.$student['id'],null,403);
$token=callApi('POST','/auth/login',['username'=>'student','password'=>'test-password'])['access_token'];
callApi('GET','/users',null,403);callApi('PUT','/users/'.$student['id'],['role'=>'admin'],403);
check(count(callApi('GET','/sinhvien'))===1);
callApi('POST','/sinhvien',[],403);
$token=$adminToken;
callApi('PUT','/users/'.$lecturer['id'],array_merge($lecturerData,['password'=>'new-password']));
$token=$lecturerToken;callApi('GET','/auth/me',null,401);
$token=null;callApi('POST','/auth/login',['username'=>'lecturer','password'=>'test-password'],401);
$token=callApi('POST','/auth/login',['username'=>'lecturer','password'=>'new-password'])['access_token'];
callApi('POST','/auth/logout');callApi('GET','/auth/me',null,401);
$token=$adminToken;callApi('DELETE','/users/1',null,409);
callApi('PUT','/users/1',['username'=>'admin','name'=>'Admin','role'=>'giangvien'],409);
callApi('DELETE','/users/'.$student['id']);
check(count(callApi('GET','/users'))===2);
echo "PASS: $checks account checks\n";

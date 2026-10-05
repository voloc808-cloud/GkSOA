<?php
// Isolated SQLite memory database: never uses the configured MySQL database.
putenv('DB_CONNECTION=sqlite'); putenv('DB_DATABASE=:memory:'); putenv('SERVICE_MODULE=all');
putenv('STUDENT_SERVICE_URL'); putenv('TOPIC_SERVICE_URL');
$app=require __DIR__.'/../bootstrap/app.php';
set_exception_handler(function($e){fwrite(STDERR,$e->getMessage()."\n".$e->getTraceAsString()."\n");exit(1);});
$app->make(Illuminate\Contracts\Console\Kernel::class)->call('migrate',['--force'=>true]);
$app->boot();
$checks=0;
function expect($actual,$expected,string $message): void {
    global $checks; $checks++;
    if ($actual!==$expected) throw new RuntimeException($message.': '.json_encode($actual).' != '.json_encode($expected));
}
function request(string $method,string $path,?array $data=null,int $status=200): array {
    global $app,$authToken;
    $request=Illuminate\Http\Request::create('/api'.$path,$method,[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_ACCEPT'=>'application/json'], $data===null?null:json_encode($data));
    $app['request']=$request;
    if (!empty($authToken)) $request->headers->set('Authorization','Bearer '.$authToken);
    $response=$app->handle($request);
    $body=json_decode($response->getContent(),true);
    expect($response->getStatusCode(),$status,$method.' '.$path.' '.json_encode($body));
    expect(is_bool($body['success']??null),true,'JSON envelope');
    return $body;
}
App\Models\User::create(['username'=>'admin','name'=>'Admin','password'=>password_hash('test-password',PASSWORD_BCRYPT),'role'=>'admin']);
request('GET','/users',null,401);
$authToken=request('POST','/auth/login',['username'=>'admin','password'=>'test-password'])['data']['access_token'];
$student=['ma_sinh_vien'=>'SVTEST1','ho_ten'=>'Nguyễn An','email'=>'test1@example.com','lop'=>'CNTT01','nganh'=>'CNTT'];
$topic=['ma_de_tai'=>'DTTEST1','ten_de_tai'=>'SOA','mo_ta'=>'Demo','giang_vien'=>'GV A','so_luong_sinh_vien'=>1,'trang_thai'=>'con_trong'];
$s1=request('POST','/sinhvien',$student,201)['data']['id'];
$s2=request('POST','/sinhvien',array_merge($student,['ma_sinh_vien'=>'SVTEST2','email'=>'test2@example.com']),201)['data']['id'];
$t1=request('POST','/detai',$topic,201)['data']['id'];
request('GET','/sinhvien');request('GET','/sinhvien/'.$s1);request('PUT','/sinhvien/'.$s1,array_merge($student,['ho_ten'=>'Nguyễn Bình']));
expect(count(request('GET','/sinhvien?q=SVTEST1')['data']),1,'student search');
request('POST','/sinhvien',$student,422);request('POST','/sinhvien',[],422);
request('GET','/sinhvien/999',null,404);request('PUT','/sinhvien/999',$student,404);
request('GET','/detai');request('GET','/detai/'.$t1);
expect(count(request('GET','/detai?q=SOA')['data']),1,'topic search');
request('POST','/detai',array_merge($topic,['ma_de_tai'=>'INVALID','so_luong_sinh_vien'=>0]),422);
request('POST','/dangky',['sinh_vien_id'=>999,'de_tai_id'=>$t1],404);
request('POST','/dangky',['sinh_vien_id'=>$s1,'de_tai_id'=>999],404);
request('POST','/dangky',[],422);
$r1=request('POST','/dangky',['sinh_vien_id'=>$s1,'de_tai_id'=>$t1],201)['data']['id'];
expect(request('GET','/detai/'.$t1)['data']['trang_thai'],'da_du','full topic');
request('POST','/dangky',['sinh_vien_id'=>$s1,'de_tai_id'=>$t1],409);
request('POST','/dangky',['sinh_vien_id'=>$s2,'de_tai_id'=>$t1],409);
request('DELETE','/sinhvien/'.$s1,null,409);request('DELETE','/detai/'.$t1,null,409);
request('GET','/dangky');request('GET','/dangky/'.$r1);
expect(count(request('GET','/dangky?sinh_vien_id='.$s1)['data']),1,'student registrations');
expect(count(request('GET','/dangky?de_tai_id='.$t1)['data']),1,'topic registrations');
request('PUT','/dangky/'.$r1,['trang_thai'=>'invalid'],422);
request('PUT','/dangky/'.$r1,['trang_thai'=>'da_duyet']);
request('PUT','/dangky/'.$r1,['trang_thai'=>'da_huy']);
expect(request('GET','/detai/'.$t1)['data']['trang_thai'],'con_trong','cancel frees slot');
request('POST','/dangky',['sinh_vien_id'=>$s1,'de_tai_id'=>$t1],409);
$r2=request('POST','/dangky',['sinh_vien_id'=>$s2,'de_tai_id'=>$t1],201)['data']['id'];
request('PUT','/dangky/'.$r1,['trang_thai'=>'cho_duyet'],409);
request('PUT','/detai/'.$t1,array_merge($topic,['so_luong_sinh_vien'=>2]));
expect(request('GET','/detai/'.$t1)['data']['trang_thai'],'con_trong','increase capacity');
request('PUT','/dangky/'.$r1,['trang_thai'=>'cho_duyet']);
request('PUT','/detai/'.$t1,$topic,409);
request('PUT','/dangky/'.$r1,['trang_thai'=>'tu_choi']);
request('PUT','/detai/'.$t1,array_merge($topic,['trang_thai'=>'ngung_nhan']));
request('PUT','/dangky/'.$r1,['trang_thai'=>'cho_duyet'],409);
request('DELETE','/dangky/'.$r1);request('POST','/dangky',['sinh_vien_id'=>$s1,'de_tai_id'=>$t1],409);
request('DELETE','/dangky/'.$r2);
expect(request('GET','/detai/'.$t1)['data']['trang_thai'],'ngung_nhan','closed remains closed');
request('PUT','/detai/'.$t1,$topic);
request('PUT','/detai/'.$t1,array_merge($topic,['trang_thai'=>'da_du']),422);
request('DELETE','/detai/'.$t1);request('DELETE','/sinhvien/'.$s1);request('DELETE','/sinhvien/'.$s2);
request('DELETE','/dangky/999',null,404);
request('GET','/missing',null,404);request('PATCH','/sinhvien/1',[],405);
echo "PASS: $checks assertions\n";


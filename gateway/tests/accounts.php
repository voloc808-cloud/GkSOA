<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
$checks=0;
$fakeStatus=200;
Http::fake(function()use(&$fakeStatus){return Http::response(['success'=>$fakeStatus<400,'data'=>['id'=>42]],$fakeStatus);});
foreach ([['GET','/api/users',200],['POST','/api/users',201],['PUT','/api/users/42',200],['DELETE','/api/users/42',200],['GET','/api/auth/me',200],['POST','/api/auth/login',401],['GET','/api/users',403]] as [$method,$path,$status]) {
 $fakeStatus=$status;
 $r=Request::create($path,$method,[],[],[],['HTTP_ACCEPT'=>'application/json','HTTP_AUTHORIZATION'=>'Bearer test-token','CONTENT_TYPE'=>'application/json'],json_encode(['username'=>'teacher','role'=>'giangvien']));
 $response=$kernel->handle($r);
 if ($response->getStatusCode()!==$status)throw new RuntimeException($path.': '.$response->getContent());
 $sent=Http::recorded(function($request)use($method,$path){return $request->method()===$method && $request->url()===rtrim(config('services.sinhvien'),'/').$path && $request->hasHeader('Authorization','Bearer test-token');});
 if ($sent->isEmpty())throw new RuntimeException('Gateway did not forward the expected request: '.$path);
 $checks++;
}
echo "PASS: $checks gateway account checks\n";

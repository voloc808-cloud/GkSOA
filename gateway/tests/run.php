<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->instance('request', Illuminate\Http\Request::create('http://localhost'));
$kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
set_exception_handler(function($e){fwrite(STDERR,$e->getMessage()."\n");exit(1);});
use Illuminate\Support\Facades\Http;
$controller=new App\Http\Controllers\DoAnController;
Http::fake(['*'=>Http::sequence()->push(['success'=>false,'message'=>'Hết chỗ'],409)->push('broken',200)->pushFailedConnection()]);
$request=Illuminate\Http\Request::create('/api/dangky','POST',[],[],[],['CONTENT_TYPE'=>'application/json'],json_encode(['sinh_vien_id'=>1,'de_tai_id'=>1]));
$response=$controller->proxy($request,'dangky');
if($response->getStatusCode()!==409||$response->getData(true)['message']!=='Hết chỗ')throw new RuntimeException('Upstream status not preserved');
if($controller->proxy($request,'dangky')->getStatusCode()!==502)throw new RuntimeException('Invalid upstream must return 502');
if($controller->proxy($request,'dangky')->getStatusCode()!==503)throw new RuntimeException('Unavailable upstream must return 503');
echo "PASS: gateway upstream 409/502/503\n";


<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\Http;
Http::fake(['*'=>Http::response(['success'=>true,'data'=>['diem_tong_ket'=>8.1]],200)]);
$data=['diem_huong_dan'=>8,'diem_phan_bien'=>7,'diem_bao_ve'=>9];
foreach (['PUT','DELETE'] as $method) {
    $request=Illuminate\Http\Request::create('/api/dangky/12/diem',$method,[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_ACCEPT'=>'application/json','HTTP_AUTHORIZATION'=>'Bearer grading-test'],$method==='PUT'?json_encode($data):null);
    $response=$kernel->handle($request);
    if ($response->getStatusCode()!==200 || json_decode($response->getContent(),true)['data']['diem_tong_ket']!==8.1) throw new RuntimeException('Grade route failed: '.$response->getContent());
    $sent=Http::recorded(function ($upstream) use ($method,$data) {
        return $upstream->method()===$method && $upstream->url()===rtrim(config('services.dangky'),'/').'/api/dangky/12/diem' && $upstream->hasHeader('Authorization','Bearer grading-test') && ($method==='DELETE' || $upstream['diem_huong_dan']===$data['diem_huong_dan']);
    });
    if ($sent->isEmpty()) throw new RuntimeException('Grade request/token not forwarded');
}
echo "PASS: gateway grading routes, payload and Bearer forwarding\n";

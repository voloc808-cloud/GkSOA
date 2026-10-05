<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
class DoAnController {
    public function proxy(Request $request, string $resource, $id = null) {
        $url=config('services.'.(in_array($resource,['auth','users'])?'sinhvien':$resource));
        if (!$url) return response()->json(['success'=>false,'message'=>'Dịch vụ chưa được cấu hình.'],503);
        try {
            $options=['query'=>$request->query()];
            if (in_array($request->method(),['POST','PUT'])) $options['json']=$request->all();
            $upstream=Http::acceptJson()->withToken($request->bearerToken()??'')->connectTimeout(3)->timeout(10)->send($request->method(),rtrim($url,'/').'/api/'.$resource.($id!==null?'/'.$id:''),$options);
            $body=$upstream->json();
            if (!is_array($body) || !array_key_exists('success',$body)) return response()->json(['success'=>false,'message'=>'Phản hồi dịch vụ không hợp lệ.'],502);
            return response()->json($body,$upstream->status());
        } catch (ConnectionException $e) { return response()->json(['success'=>false,'message'=>'Không kết nối được dịch vụ.'],503); }
    }
    public function dashboard(Request $request) {
        $counts=[];
        try {
            foreach (['sinhvien','detai','dangky'] as $resource) {
                $res=Http::acceptJson()->withToken($request->bearerToken()??'')->connectTimeout(3)->timeout(10)->get(rtrim(config('services.'.$resource),'/').'/api/'.$resource);
                if (in_array($res->status(),[401,403])) return response()->json($res->json(),$res->status());
                if (!$res->successful() || !is_array($res->json('data'))) return response()->json(['success'=>false,'message'=>'Không thể tải thống kê.'],503);
                $data=$res->json('data');
                $counts[$resource]=count($data);
                if ($resource==='detai') $counts['con_trong']=count(array_filter($data,fn($row)=>$row['trang_thai']==='con_trong'));
            }
            return response()->json(['success'=>true,'data'=>$counts]);
        } catch (ConnectionException $e) { return response()->json(['success'=>false,'message'=>'Không kết nối được dịch vụ.'],503); }
    }
}

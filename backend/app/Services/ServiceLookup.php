<?php
namespace App\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;
class ServiceLookup {
    public function find(string $resource,int $id): void {
        $key=$resource==='sinhvien'?'STUDENT_SERVICE_URL':'TOPIC_SERVICE_URL';
        $base=env($key);
        if (!$base) {
            if (env('SERVICE_MODULE','all')!=='all') throw new HttpException(503,'Chưa cấu hình URL dịch vụ.');
            return;
        }
        $context=stream_context_create(['http'=>['timeout'=>5,'ignore_errors'=>true,'header'=>"Accept: application/json\r\nAuthorization: Bearer ".app('request')->bearerToken()."\r\n"]]);
        $raw=@file_get_contents(rtrim($base,'/').'/api/'.$resource.'/'.$id,false,$context);
        $status=isset($http_response_header[0]) ? (int) explode(' ',$http_response_header[0])[1] : 0;
        if ($status===404) throw new HttpException(404,'Không tìm thấy sinh viên hoặc đề tài.');
        $body=is_string($raw)?json_decode($raw,true):null;
        if ($status!==200 || !is_array($body) || ($body['success']??false)!==true) throw new HttpException(503,'Không kết nối được dịch vụ sinh viên hoặc đề tài.');
    }
}


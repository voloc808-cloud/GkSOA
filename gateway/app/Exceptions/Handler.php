<?php
namespace App\Exceptions;
class Handler extends \Illuminate\Foundation\Exceptions\Handler
{
    protected $dontFlash = ['password', 'password_confirmation'];
    public function render($request, \Throwable $e) {
        $response = parent::render($request, $e);
        return response()->json(['success'=>false,'message'=>$response->getStatusCode()>=500?'Lỗi hệ thống.':'Yêu cầu không hợp lệ.'], $response->getStatusCode());
    }
}

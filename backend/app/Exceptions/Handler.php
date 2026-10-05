<?php
namespace App\Exceptions;
use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
class Handler extends \Laravel\Lumen\Exceptions\Handler
{
    public function render($request, Throwable $e)
    {
        $status = $e instanceof ValidationException ? 422 : ($e instanceof ModelNotFoundException ? 404 : ($e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500));
        $message = $status === 500 ? 'Lỗi hệ thống.' : ($status === 404 ? 'Không tìm thấy dữ liệu.' : $e->getMessage());
        $body = ['success'=>false, 'message'=>$message];
        if ($e instanceof ValidationException) $body['errors'] = $e->errors();
        return response()->json($body, $status);
    }
}

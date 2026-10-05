<?php
namespace Pharmacy\Support;

use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ApiExceptionHandler extends \Laravel\Lumen\Exceptions\Handler
{
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ValidationException) {
            return response()->json(['status' => 'error', 'data' => $exception->errors(), 'message' => 'Validation failed.'], 422);
        }
        $code = $exception instanceof ModelNotFoundException ? 404 : ($exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500);
        return response()->json(['status' => 'error', 'data' => null, 'message' => $code >= 500 ? 'Service request failed.' : ($exception->getMessage() ?: 'Resource not found.')], $code);
    }
}

<?php
namespace Pharmacy\Support;

use Laravel\Lumen\Routing\Controller;

abstract class ApiController extends Controller
{
    protected function ok($data = null, string $message = 'OK', int $code = 200)
    {
        return response()->json(['status' => 'success', 'data' => $data, 'message' => $message], $code);
    }

    protected function page($query)
    {
        return $this->ok($query->paginate(min(100, max(1, (int) request('per_page', 20)))));
    }
}

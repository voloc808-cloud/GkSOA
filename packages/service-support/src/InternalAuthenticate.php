<?php
namespace Pharmacy\Support;

use Closure;

class InternalAuthenticate
{
    public function handle($request, Closure $next)
    {
        $key = config('services.key');
        if (!is_string($key) || strlen($key) < 32 || !hash_equals($key, (string) $request->header('X-Service-Key'))) {
            return response()->json(['status' => 'error', 'data' => null, 'message' => 'Invalid service credentials.'], 401);
        }
        return $next($request);
    }
}

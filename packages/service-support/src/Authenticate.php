<?php
namespace Pharmacy\Support;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

class Authenticate
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!$request->bearerToken()) {
            return response()->json(['status' => 'error', 'data' => null, 'message' => 'Authentication required.'], 401);
        }
        try {
            $response = (new Client(['connect_timeout' => 3, 'timeout' => 5]))->get(rtrim(config('services.auth'), '/').'/auth', [
                'headers' => ['Accept' => 'application/json', 'Authorization' => 'Bearer '.$request->bearerToken()],
                'http_errors' => false,
            ]);
        } catch (TransferException $e) {
            return response()->json(['status' => 'error', 'data' => null, 'message' => 'Authentication service unavailable.'], 503);
        }
        if ($response->getStatusCode() !== 200) {
            $code = $response->getStatusCode() === 401 ? 401 : 503;
            return response()->json(['status' => 'error', 'data' => null, 'message' => 'Authentication failed.'], $code);
        }
        $user = json_decode((string) $response->getBody(), true)['user'] ?? null;
        if (!is_array($user) || !isset($user['IdUser'], $user['Role'])) {
            return response()->json(['status' => 'error', 'data' => null, 'message' => 'Invalid authentication response.'], 502);
        }
        if ($roles && !in_array($user['Role'], $roles, true)) {
            return response()->json(['status' => 'error', 'data' => null, 'message' => 'Permission denied.'], 403);
        }
        $request->attributes->set('actor', $user);
        return $next($request);
    }
}

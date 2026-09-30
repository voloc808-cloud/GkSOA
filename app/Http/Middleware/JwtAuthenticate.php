<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use UnexpectedValueException;

class JwtAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return $this->unauthorized();
        }
        try {
            $claims = app(JwtService::class)->verify($token);
        } catch (UnexpectedValueException $exception) {
            return $this->unauthorized();
        }
        $user = User::find($claims->sub);
        if (!$user || !is_string($user->Token) || !hash_equals($user->Token, hash('sha256', $token))) {
            return $this->unauthorized();
        }
        $request->setUserResolver(fn () => $user);
        return $next($request);
    }

    private function unauthorized()
    {
        return response()->json(['message' => 'Missing, invalid or expired token.'], 401)
            ->header('WWW-Authenticate', 'Bearer');
    }
}

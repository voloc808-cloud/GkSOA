<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use UnexpectedValueException;

class JwtService
{
    private function secret(): string
    {
        $secret = config('jwt.secret');
        if (!is_string($secret) || strlen($secret) < 32 || config('jwt.ttl') < 1) {
            throw new \LogicException('Configure JWT_SECRET (at least 32 bytes) and a positive JWT_TTL.');
        }
        return $secret;
    }

    public function issue(User $user): string
    {
        $now = time();
        return JWT::encode([
            'iss' => config('jwt.issuer'),
            'sub' => (string) $user->IdUser,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + config('jwt.ttl'),
            'jti' => bin2hex(random_bytes(16)),
        ], $this->secret(), 'HS256');
    }

    public function verify(string $token): \stdClass
    {
        $claims = JWT::decode($token, new Key($this->secret(), 'HS256'));
        if (($claims->iss ?? null) !== config('jwt.issuer')
            || !isset($claims->sub, $claims->exp, $claims->iat, $claims->nbf, $claims->jti)
            || !is_string($claims->sub) || !ctype_digit($claims->sub)
            || !is_int($claims->exp) || !is_int($claims->iat) || !is_int($claims->nbf)
            || $claims->iat > time() || $claims->exp <= time()) {
            throw new UnexpectedValueException('Invalid token claims.');
        }
        return $claims;
    }
}

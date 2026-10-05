<?php

namespace Tests;

use App\Models\User;
use Firebase\JWT\JWT;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
        $this->app['config']->set('jwt.secret', str_repeat('test-secret-', 4));
        $this->artisan('migrate');
        $this->artisan('db:seed');
    }

    private function login(): string
    {
        $this->json('POST', '/', ['userName' => 'student', 'password' => base64_encode('123456')]);
        $this->assertResponseStatus(200);
        $body = json_decode($this->response->getContent(), true);
        $this->assertArrayNotHasKey('Password', $body['user']);
        $this->assertArrayNotHasKey('Token', $body['user']);
        return $body['access_token'];
    }

    public function test_login_and_protected_routes(): void
    {
        $token = $this->login();
        $this->assertSame(hash('sha256', $token), User::first()->Token);
        $this->json('GET', '/auth', [], ['Authorization' => 'Bearer '.$token]);
        $this->assertResponseStatus(200);
        $this->seeJson(['UserName' => 'student']);
        $this->json('GET', '/hello', [], ['Authorization' => 'Bearer '.$token]);
        $this->assertResponseStatus(200);
        $this->seeJsonEquals(['message' => 'Hello World']);
    }

    public function test_invalid_login_inputs(): void
    {
        foreach ([['userName' => 'student', 'password' => '%%%'], []] as $body) {
            $this->json('POST', '/login', $body);
            $this->assertResponseStatus(422);
        }
        foreach (['student', 'unknown'] as $name) {
            $this->json('POST', '/login', ['userName' => $name, 'password' => base64_encode('wrong')]);
            $this->assertResponseStatus(401);
        }
    }

    public function test_missing_malformed_and_tampered_tokens(): void
    {
        $token = $this->login();
        $parts = explode('.', $token);
        $parts[1] = JWT::urlsafeB64Encode(json_encode(['sub' => '999', 'exp' => time() + 3600]));
        foreach (['', 'garbage', implode('.', $parts)] as $bad) {
            foreach (['/auth', '/hello'] as $path) {
                $this->json('GET', $path, [], ['Authorization' => 'Bearer '.$bad]);
                $this->assertResponseStatus(401);
            }
        }
    }

    public function test_claims_and_algorithm_are_validated(): void
    {
        $base = ['iss' => config('jwt.issuer'), 'sub' => '1', 'iat' => time(),
            'nbf' => time(), 'exp' => time() + 3600, 'jti' => 'test'];
        foreach ([['exp' => time() - 1], ['iss' => 'other'], ['nbf' => time() + 60],
            ['exp' => null], ['iat' => time() + 60]] as $change) {
            $token = JWT::encode(array_merge($base, $change), config('jwt.secret'), 'HS256');
            User::first()->update(['Token' => hash('sha256', $token)]);
            $this->json('GET', '/auth', [], ['Authorization' => 'Bearer '.$token]);
            $this->assertResponseStatus(401);
        }
        $token = JWT::encode($base, str_repeat('x', 64), 'HS384');
        $this->json('GET', '/auth', [], ['Authorization' => 'Bearer '.$token]);
        $this->assertResponseStatus(401);
    }

    public function test_new_login_revokes_previous_token_and_deleted_user_is_rejected(): void
    {
        $old = $this->login();
        $new = $this->login();
        $this->assertNotSame($old, $new);
        $this->json('GET', '/auth', [], ['Authorization' => 'Bearer '.$old]);
        $this->assertResponseStatus(401);
        User::first()->delete();
        $this->json('GET', '/auth', [], ['Authorization' => 'Bearer '.$new]);
        $this->assertResponseStatus(401);
    }
}

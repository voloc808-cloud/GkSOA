<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request, JwtService $jwt)
    {
        $this->validate($request, [
            'userName' => 'required|string|max:255',
            'password' => 'required|string|max:1024',
        ]);
        $password = base64_decode($request->input('password'), true);
        if ($password === false || $password === '' || base64_encode($password) !== $request->input('password')) {
            return response()->json(['message' => 'password must be valid Base64.'], 422);
        }
        $user = User::where('UserName', $request->input('userName'))->first();
        if (!$user || !password_verify($password, $user->Password)) {
            return response()->json(['message' => 'Invalid username or password.'], 401);
        }
        $token = $jwt->issue($user);
        $user->Token = hash('sha256', $token);
        $user->save();
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl'),
            'user' => $user,
        ])->header('Cache-Control', 'no-store');
    }

    public function authenticate(Request $request)
    {
        return response()->json(['message' => 'Token is valid.', 'user' => $request->user()]);
    }
}

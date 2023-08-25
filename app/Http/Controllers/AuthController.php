<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        Log::info('Request:', $request->all());

        // Валидация только необходимых полей
        $request->validate([
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|same:confirmPassword',
            'role' => 'required|string|in:Учитель,Ученик', // Убедитесь, что роль является одной из двух опций
        ]);

        $user = new User([
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            // Начальные значения для других полей
            'name' => '',
            'phone' => '',
            'date_of_birth' => null,
            'parent' => null,
            'status' => 'не оплачен',
        ]);

        Log::info('User before save:', $user->toArray());
        $user->save();
        Log::info('User after save:', $user->toArray());

        // Создание токена доступа
        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json([
            'message' => 'Пользователь успешно зарегистрирован!',
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json(['token' => $token]);
    }
}

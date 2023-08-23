<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Валидация только необходимых полей
        $request->validate([
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed',
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

        $user->save();

        // Создание токена доступа
        $token = $user->createToken('Personal Access Token')->accessToken;

        return response()->json([
            'message' => 'Пользователь успешно зарегистрирован!',
            'token' => $token,
        ], 201);
    }


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only(['email', 'password']))) {
            return response()->json([
                'message' => 'Неправильный email или пароль!',
            ], 401);
        }

        $user = $request->user();
        $token = $user->createToken('Personal Access Token')->accessToken;

        return response()->json([
            'token' => $token,
        ]);
    }
}
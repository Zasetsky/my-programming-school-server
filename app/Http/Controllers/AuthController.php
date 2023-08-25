<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Валидация только необходимых полей
        $request->validate([
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|same:confirmPassword',
            'role' => 'required|string|in:Учитель,Ученик',
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
        // Валидация входящих данных
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'role' => 'required|string|in:Учитель,Ученик', // Валидация роли
        ]);

        // Получаем входные данные
        $login = $request->input('login');
        $password = $request->input('password');
        $role = $request->input('role'); // Получаем роль

        // Проверяем, является ли введенный логин адресом электронной почты
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $login)->first();
        } else {
            $user = User::where('phone', $login)->first();
        }

        // Проверяем существование пользователя и совпадение роли
        if (!$user || !Hash::check($password, $user->password) || $user->role !== $role) {
            return response()->json(['error' => 'invalid_credentials'], 401);
        }

        // Создаем токен
        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json(['token' => $token]);
    }
}
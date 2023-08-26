<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;

// use Illuminate\Support\Facades\Log;


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

        // Генерация случайного уникального номера
        do {
            $userNumber = random_int(100000, 999999);
        } while (User::where('user_number', $userNumber)->exists());

        // Создание пользователя
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
            'user_number' => $userNumber,
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
            'uniqueID' => $userNumber,
        ], 201);
    }

    public function login(Request $request)
    {
        // Валидация входящих данных
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'role' => 'required|string|in:Учитель,Ученик',
        ]);

        // Получаем входные данные
        $login = $request->input('login');
        $password = $request->input('password');
        $role = $request->input('role');

        // Проверяем, является ли введенный логин адресом электронной почты
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $login)->first();
        } else {
            $user = User::where('phone', $login)->first();
        }

        // Поля, которые могут быть неверными
        $fields = [];
        $errorMessage = 'Неверные учетные данные';

        // Проверяем существование пользователя, совпадение роли и пароля
        if (!$user) {
            $fields['login'] = true;
            $errorMessage = 'Пользователь с таким логином не найден';
        } elseif (!Hash::check($password, $user->password)) {
            $fields['password'] = true;
            $errorMessage = 'Неверный пароль';
        } elseif ($user->role !== $role) {
            $errorMessage = 'Пользователь с такой ролью не найден';
        } else {
            // Если все хорошо, создаем токен
            try {
                $token = JWTAuth::fromUser($user);

                // Возвращаем существующий уникальный номер пользователя
                return response()->json(['token' => $token, 'uniqueID' => $user->user_number]);
            } catch (JWTException $e) {
                return response()->json(['error' => 'could_not_create_token'], 500);
            }
        }

        return response()->json([
            'error' => $errorMessage,
            'fields' => $fields
        ], 401);
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Валидация полей, где 'parent' является необязательным
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed',
            'phone' => 'required|string',
            'date_of_birth' => 'required|date',
            'parent' => 'nullable|string', // Необязательное поле
        ]);
    
        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'parent' => $request->parent, // Необязательное поле
            'status' => 'не оплачен', // Вы можете установить начальный статус здесь
        ]);
    
        $user->save();
    
        return response()->json([
            'message' => 'Пользователь успешно зарегистрирован!',
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

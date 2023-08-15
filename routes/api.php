<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Маршрут для получения информации о текущем авторизованном пользователе
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Маршруты для аутентификации и регистрации
// Они доступны всем пользователям, даже неавторизованным, поэтому middleware здесь не используется
Route::post('/register', 'AuthController@register'); // Регистрация пользователя
Route::post('/login', 'AuthController@login');       // Вход пользователя
Route::post('/logout', 'AuthController@logout')      // Выход пользователя
    ->middleware('auth:sanctum'); // Middleware здесь используется, так как только авторизованные пользователи могут выходить

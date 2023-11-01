<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate(); // tymon/jwt-auth

            $uuid = $user->id; // Получаем UUID из модели пользователя

            if (!$uuid) {
                return response()->json(['error' => 'UUID is required'], 401);
            }

        } catch (Exception $e) {
            // Обработка ошибок (токен недействителен, истек срок и так далее)
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return parent::handle($request, $next, ...$guards);
    }

    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}
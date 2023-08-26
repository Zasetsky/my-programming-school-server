<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject; // Интерфейс для JWT

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string, mixed>
     */
    protected $fillable = [
        'name',           // Имя
        'email',          // Электронная почта
        'password',       // Пароль
        'phone',          // Телефон
        'date_of_birth',  // Дата рождения
        'parent',         // Родитель (не обязательное поле)
        'role',
        'status',         // Статус (оплачен, не оплачен)
        'user_number',    // Порядковый номер пользователя
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'datetime', // Предполагаем, что дата рождения хранится в формате даты и времени
        'password' => 'hashed',
    ];

    /**
     * Получить идентификатор, который будет сохранен в Tymon\JWTAuth\Contracts\JWTSubject.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Вернуть ключевую пару значений, которая должна быть связана с идентификатором пользователя в Tymon\JWTAuth\Contracts\JWTSubject.
     *
     * @return array<mixed>
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}

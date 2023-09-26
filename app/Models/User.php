<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject; // Интерфейс для JWT
use Illuminate\Support\Str;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, HasFactory;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string, mixed>
     */
    protected $fillable = [
        'name',
        // Имя
        'email',
        // Электронная почта
        'password',
        // Пароль
        'phone',
        // Телефон
        'date_of_birth',
        // Дата рождения
        'parent',
        // Родитель (не обязательное поле)
        'role',
        'status',
        // Статус (paid, unpaid)
        'user_number',
        // Порядковый номер пользователя
    ];

       /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

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
        // 'email_verified_at' => 'datetime',
        'date_of_birth' => 'datetime',
        // Предполагаем, что дата рождения хранится в формате даты и времени
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

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }
}
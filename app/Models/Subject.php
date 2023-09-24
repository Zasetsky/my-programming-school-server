<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'subject_code',
        'user_id',
        'name',
        'modules' // JSON-представление модулей
    ];

    protected $casts = [
        'modules' => 'array',
        // Laravel автоматически преобразует JSON в массив и обратно
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'subject_name',
        'lesson_date',
        'start_time',
        'end_time',
        'homework',
        'status'
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}

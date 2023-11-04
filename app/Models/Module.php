<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'name',
        'total_lesson_count',
        'completed_lesson_count',
        'start_date',
        'end_date',
        'lesson_days',
        'start_time',
        'duration',
        'grade',
        'status',
        'comment',
        'next_lesson_date',
    ];

    protected $casts = [
        'lesson_days' => 'array'
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}

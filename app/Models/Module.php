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
        'totalLessonCount',
        'completedLessonCount',
        'startDate',
        'lessonDays',
        'startTime',
        'duration',
        'grade',
        'status',
    ];

    protected $casts = [
        'lessonDays' => 'array'
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

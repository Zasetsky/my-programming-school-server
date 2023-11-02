<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Module;

class LessonDateService
{
    /**
     * Генерация уроков для модуля.
     *
     * @param Module $module
     * @return array
     */
    public function generateLessonsForModule(Module $module)
    {
        $lessons = [];
        $startDate = Carbon::createFromFormat('d-m-Y', $module->startDate);
        $startTime = Carbon::createFromFormat('H:i', $module->startTime);
        $duration = $module->duration; // Продолжительность урока в минутах
        $lessonDays = $module->lessonDays; // Дни недели
        $totalLessonCount = $module->totalLessonCount; // Общее количество уроков

        $currentLessonCount = 0;
        $currentDate = clone $startDate;

        while ($currentLessonCount < $totalLessonCount) {
            if (in_array($currentDate->format('l'), $lessonDays)) {
                $currentDateTime = Carbon::now(); // текущая дата и время

                $lessonStartTime = clone $currentDate;
                $lessonStartTime->setTimeFromTimeString($startTime->toTimeString());

                // Если время начала урока больше текущего времени, добавляем урок
                if ($lessonStartTime->greaterThan($currentDateTime) || $currentDate->greaterThan($currentDateTime)) {
                    $lessonEndTime = clone $lessonStartTime;
                    $lessonEndTime->addMinutes($duration);

                    $lessons[] = [
                        'subject_name' => $module->subject->name,
                        'lesson_date' => $currentDate->toDateString(),
                        'start_time' => $lessonStartTime->toTimeString(),
                        'end_time' => $lessonEndTime->toTimeString(),
                        'status' => 'not_active'
                    ];

                    $currentLessonCount++;
                }
            }

            if ($currentLessonCount >= $totalLessonCount) {
                break;
            }

            $currentDate->addDay();
        }

        return $lessons;
    }
}

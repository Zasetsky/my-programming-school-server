<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Lesson;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;

class LessonController extends Controller
{
    /**
     * Установить или обновить домашнюю работу урока.
     */
    public function setHomework(Request $request, $lessonId)
    {
        $request->validate([
            'homework' => 'required|string'
        ]);

        $lesson = Lesson::find($lessonId);
        if (!$lesson) {
            return response()->json(['message' => 'Lesson not found'], 404);
        }

        $message = 'Homework set successfully';
        if ($lesson->homework) {
            $message = 'Homework updated successfully';
        }

        $lesson->homework = $request->homework;
        $lesson->save();

        return response()->json(['message' => $message], 200);
    }

    /**
     * Перенести урок.
     */
    public function rescheduleLesson(Request $request, $lessonId)
    {
        try {
            Log::info('rescheduleLesson called', ['request' => $request->all()]);
            $request->validate([
                'new_date' => 'sometimes|date_format:Y-m-d',
                'new_time' => 'sometimes|date_format:H:i'
            ]);

            Log::info('Validation passed');

            $lesson = Lesson::find($lessonId);
            if (!$lesson) {
                return response()->json(['message' => 'Lesson not found'], 404);
            }

            Log::info('Lesson found', ['subject' => $lesson]);

            // Получаем модуль, к которому принадлежит урок
            $module = $lesson->module;

            // Если предоставлено новое время
            if ($request->has('new_time')) {
                $newStartTime = Carbon::createFromFormat('H:i', $request->new_time);
                $newEndTime = (clone $newStartTime)->addMinutes($module->duration);
                $lesson->start_time = $newStartTime->toTimeString();
                $lesson->end_time = $newEndTime->toTimeString();
            }



            // Если предоставлена новая дата
            if ($request->has('new_date')) {
                $lesson->lesson_date = $request->new_date;
            }

            $lesson->save();

            Log::info('Lesson saved', ['module' => $lesson]);

            return response()->json(['message' => 'Lesson rescheduled successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error in addModule', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Получить все уроки конкретного пользователя со статусами "scheduled" и "rescheduled".
     */
    public function getAllLessonsForUser()
    {
        $userId = Auth::id();

        $lessons = Lesson::whereHas('module.subject', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->whereIn('status', ['scheduled', 'rescheduled'])
            ->orderBy('lesson_date', 'asc') // Сортировка по возрастанию даты
            ->get();

        return response()->json(['lessons' => $lessons], 200);
    }
}

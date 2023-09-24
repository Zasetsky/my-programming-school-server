<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Log;

class SubjectController extends Controller
{
    public function addSubject(Request $request)
    {
        try {
            Log::info('addSubject called', ['request' => $request->all()]);

            $request->validate([
                'subject_code' => [
                    'required',
                    'string',
                    Rule::unique('subjects')->where(function ($query) {
                        return $query->where('user_id', auth()->id());
                    }),
                ],
                'name' => 'required|string',
            ]);

            $subject = new Subject();
            $subject->subject_code = $request->subject_code;
            $subject->user_id = Auth::id(); // Получаем ID текущего пользователя
            $subject->name = $request->name;
            $subject->modules = [];

            $subject->save();

            Log::info('Subject added successfully', ['subject' => $subject]);

            return response()->json(['message' => 'Subject added successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error in addSubject', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    public function updateSubject(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string'
        ]);

        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }

        $subject->name = $request->name;

        $subject->save();

        return response()->json(['message' => 'Subject updated successfully'], 200);
    }

    public function addModuleToSubject(Request $request, $subjectId)
    {
        // Полная валидация входящих данных модуля
        $request->validate([
            'id' => 'required|string',
            'name' => 'required|string',
            'totalLessonCount' => 'required|integer',
            'completedLessonCount' => 'required|integer',
            'status' => 'required|in:paid,unpaid',
            'startDate' => 'required|date_format:d-m-Y',
            'endDate' => 'required|date_format:d-m-Y',
            'grade' => 'required|in:repeat,danger,success,not_set',
            'comment' => 'required|string',
            'lessonDays' => 'required|array',
            'lessonDays.*' => 'required|string',
            'startTime' => 'required|date_format:H:i',
            'duration' => 'required|string',
            'nextLessonDate' => 'required|date_format:d-m-Y',
        ]);

        // Поиск предмета по ID
        $subject = Subject::find($subjectId);

        // Проверка существования предмета
        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }

        // Получение нового модуля из запроса после валидации
        $newModule = $request->all();

        // Получение текущего массива модулей
        $modules = json_decode($subject->modules, true);

        // Добавление нового модуля
        $modules[] = $newModule;

        // Обновление поля modules
        $subject->modules = json_encode($modules);

        // Сохранение изменений
        $subject->save();

        return response()->json(['message' => 'Module added to subject successfully'], 200);
    }

    public function updateModuleInSubject(Request $request, $subjectId, $moduleId)
    {
        // Валидация входящих данных модуля
        $request->validate([
            'id' => 'required|string',
            'name' => 'required|string',
            'totalLessonCount' => 'required|integer',
            'completedLessonCount' => 'required|integer',
            'status' => 'required|in:paid,unpaid',
            'startDate' => 'required|date_format:d-m-Y',
            'endDate' => 'required|date_format:d-m-Y',
            'grade' => 'required|in:repeat,danger,success,not_set',
            'comment' => 'required|string',
            'lessonDays' => 'required|array',
            'lessonDays.*' => 'required|string',
            'startTime' => 'required|date_format:H:i',
            'duration' => 'required|string',
            'nextLessonDate' => 'required|date_format:d-m-Y',
        ]);

        // Поиск предмета по ID
        $subject = Subject::find($subjectId);

        // Проверка существования предмета
        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }

        // Получение текущего массива модулей
        $modules = json_decode($subject->modules, true);

        // Поиск модуля по moduleId
        $moduleIndex = array_search($moduleId, array_column($modules, 'id'));

        // Проверка существования модуля
        if ($moduleIndex === false) {
            return response()->json(['message' => 'Module not found'], 404);
        }

        // Обновление модуля
        $modules[$moduleIndex] = $request->all();

        // Обновление поля modules
        $subject->modules = json_encode($modules);

        // Сохранение изменений
        $subject->save();

        return response()->json(['message' => 'Module updated successfully'], 200);
    }

    public function getSubjects()
    {
        // Извлечение всех предметов
        $subjects = Subject::all();

        return response()->json(['subjects' => $subjects], 200);
    }
}
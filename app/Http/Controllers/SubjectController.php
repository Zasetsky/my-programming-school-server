<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Services\LessonDateService;

use Illuminate\Support\Facades\Log;


class SubjectController extends Controller
{

    protected $lessonDateService;

    public function __construct(LessonDateService $lessonDateService)
    {
        $this->lessonDateService = $lessonDateService;
    }

    public function addSubject(Request $request)
    {
        try {
            Log::info('addSubject called', ['request' => $request->all()]);

            Log::info('Auth ID', ['id' => auth()->id()]);

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
        // Валидация данных
        $request->validate([
            'name' => 'required|string',
            'totalLessonCount' => 'required|integer',
            'startDate' => 'required|date_format:d-m-Y',
            'lessonDays' => 'required|array',
            'lessonDays.*' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'startTime' => 'required|date_format:H:i',
            'duration' => 'required|string'
        ]);

        // Поиск предмета по ID
        $subject = Subject::find($subjectId);
        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }

        // Получение данных из запроса после валидации
        $newModule = $request->all();

        // Генерация ID на сервере
        $newModule['id'] = uniqid();
        $newModule['comment'] = '';
        $newModule['grade'] = 'not_set';
        $newModule['status'] = 'unpaid';
        $newModule['completedLessonCount'] = 0;

        // Вычисление nextLessonDate и endDate
        $newModule = $this->lessonDateService->calculateDates($newModule, 0, true);

        // Получение текущего массива модулей и добавление нового
        $modules = $subject->modules;

        $modules[] = $newModule;

        // Сохранение
        $subject->modules = $modules;
        $subject->save();

        return response()->json(['message' => 'Module added to subject successfully'], 200);
    }



    public function updateModuleInSubject(Request $request, $subjectId, $moduleId)
    {
        // Валидация входящих данных модуля
        $request->validate([
            'name' => 'sometimes|required|string',
            'totalLessonCount' => 'sometimes|required|integer',
            'completedLessonCount' => 'sometimes|required|integer',
            'status' => 'sometimes|required|in:paid,unpaid',
            'endDate' => 'sometimes|required|date_format:d-m-Y',
            'grade' => 'sometimes|required|in:repeat,danger,success,not_set',
            'comment' => 'sometimes|required|string',
            'lessonDays' => 'sometimes|required|array',
            'lessonDays.*' => 'required_with:lessonDays|string',
            'startTime' => 'sometimes|required|date_format:H:i',
            'duration' => 'sometimes|required|string',
            'nextLessonDate' => 'sometimes|required|date_format:d-m-Y',
            'rescheduledLessons' => 'sometimes|required|array|max:2',
            'rescheduledLessons.*' => 'required_with:rescheduledLessons|date_format:d-m-Y',
        ]);

        // Поиск предмета по ID
        $subject = Subject::find($subjectId);

        // Проверка существования предмета
        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }

        // Получение текущего массива модулей
        $modules = $subject->modules;

        // Поиск модуля по moduleId
        $moduleIndex = array_search($moduleId, array_column($modules, 'id'));

        // Проверка существования модуля
        if ($moduleIndex === false) {
            return response()->json(['message' => 'Module not found'], 404);
        }

        // Получение обновленных данных
        $updatedData = $request->all();

        // Проверка наличия изменений, которые требуют пересчета дат
        if (isset($updatedData['completedLessonCount']) || isset($updatedData['lessonDays']) || isset($updatedData['nextLessonDate'])) {
            $updatedData = $this->lessonDateService->calculateDates(
                array_merge($modules[$moduleIndex], $updatedData),
                $updatedData['completedLessonCount'] ?? $modules[$moduleIndex]['completedLessonCount']
            );
        }

        // Обновление модуля
        $modules[$moduleIndex] = array_merge($modules[$moduleIndex], $updatedData);

        // Обновление поля modules
        $subject->modules = $modules;

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
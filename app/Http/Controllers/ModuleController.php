<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Module;
use App\Models\Subject;
use App\Models\Lesson;
use Carbon\Carbon;
use App\Services\LessonDateService;

use Illuminate\Support\Facades\Log;

class ModuleController extends Controller
{
    protected $lessonDateService;

    public function __construct(LessonDateService $lessonDateService)
    {
        $this->lessonDateService = $lessonDateService;
    }

    public function addModule(Request $request, $subjectId)
    {
        try {
            // Валидация входящего запроса
            $request->validate([
                'name' => 'required|string',
                'total_lesson_count' => 'required|integer',
                'start_date' => 'required|date_format:d-m-Y',
                'lesson_days' => 'required|array',
                'lesson_days.*' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'start_time' => 'required|date_format:H:i',
                'duration' => 'required|string'
            ]);

            $requestData = $request->all();
            Log::info('Request data validated:', $requestData);

            // Поиск предмета по ID
            $subject = Subject::find($subjectId);
            if (!$subject) {
                return response()->json(['message' => 'Subject not found'], 404);
            }



            // Создание нового модуля
            $module = new Module();
            $module->subject_id = $subjectId;
            $module->name = $request->name;
            $module->total_lesson_count = $request->total_lesson_count;
            $module->start_date = $request->start_date;
            $module->lesson_days = $request->lesson_days;
            $module->start_time = $request->start_time;
            $module->duration = $request->duration;
            // Сохраняем модуль, чтобы у нас был ID для связанных уроков
            $module->save();

            // Создание уроков для модуля и получение дополнительной информации
            $lessonsData = $this->lessonDateService->generateLessonsForModule($module);
            $lessons = $lessonsData['lessons'];
            $nextLessonDate = $lessonsData['nextLessonDate'];

            Log::info('Generated lessons data:', $lessonsData);

            // Проверка, соответствует ли предложенная startDate первой дате урока
            $firstLessonDate = Carbon::createFromFormat('Y-m-d', $lessons[0]['lesson_date']);
            $proposedStartDate = Carbon::createFromFormat('d-m-Y', $request->start_date);

            // Если предложенная дата не соответствует, установить startDate на дату первого урока
            if ($firstLessonDate != $proposedStartDate) {
                $module->start_date = $firstLessonDate->format('Y-m-d');
                $module->save();
            }


            // Сохранение сгенерированных уроков и получение даты последнего урока
            $lastLessonDate = null;
            foreach ($lessons as $lessonData) {
                Log::info('Lesson data before create:', $lessonData);
                $lesson = new Lesson($lessonData);
                $lesson->module_id = $module->id;
                Log::info('Lesson data before save:', $lesson->attributesToArray());
                $lesson->save();
                $lastLessonDate = $lessonData['lesson_date']; // обновляем с каждым уроком, последний будет датой последнего урока
            }

            // Обновление модуля с новой next_lesson_date и end_date
            $module->next_lesson_date = $nextLessonDate; // Устанавливаем дату ближайшего урока
            $module->end_date = $lastLessonDate; // Устанавливаем дату последнего урока
            $module->save();
            return response()->json(['message' => 'Module and associated lessons added successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error in addModule', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    public function updateModule(Request $request, $moduleId)
    {
        $request->validate([
            'name' => 'sometimes|required|string',
            'totalLessonCount' => 'sometimes|required|integer',
            'startDate' => 'sometimes|required|date_format:d-m-Y',
            'lessonDays' => 'sometimes|required|array',
            'lessonDays.*' => 'required_with:lessonDays|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'startTime' => 'sometimes|required|date_format:H:i',
            'duration' => 'sometimes|required|string'
        ]);

        $module = Module::find($moduleId);
        if (!$module) {
            return response()->json(['message' => 'Module not found'], 404);
        }

        $module->update($request->all());

        return response()->json(['message' => 'Module updated successfully'], 200);
    }

    public function getModulesForSubject($subjectId)
    {
        // Найти предмет по ID
        $subject = Subject::find($subjectId);

        // Проверить, существует ли предмет
        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }

        // Получить все модули для этого предмета
        $modules = $subject->modules; // Предполагается, что у вас есть отношение `modules` в модели `Subject`

        // Вернуть модули в ответе
        return response()->json(['modules' => $modules], 200);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Module;
use App\Models\Subject;
use App\Models\Lesson;
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
            // Log::info('addModule called', ['request' => $request->all()]);
            $request->validate([
                'name' => 'required|string',
                'totalLessonCount' => 'required|integer',
                'startDate' => 'required|date_format:d-m-Y',
                'lessonDays' => 'required|array',
                'lessonDays.*' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'startTime' => 'required|date_format:H:i',
                'duration' => 'required|string'
            ]);

            // Log::info('Validation passed');

            $subject = Subject::find($subjectId);
            if (!$subject) {
                return response()->json(['message' => 'Subject not found'], 404);
            }

            // Log::info('Subject found', ['subject' => $subject]);

            $module = new Module();
            $module->subject_id = $subjectId;
            $module->name = $request->name;
            $module->totalLessonCount = $request->totalLessonCount;
            $module->startDate = $request->startDate;
            $module->lessonDays = $request->lessonDays;
            $module->startTime = $request->startTime;
            $module->duration = $request->duration;

            $module->save();

            // Log::info('Module saved', ['module' => $module]);

            // Создание уроков для модуля
            $lessons = $this->lessonDateService->generateLessonsForModule($module);

            // Log::info('Lessons generated', ['lessons' => $lessons]);

            foreach ($lessons as $lessonData) {
                $lesson = new Lesson($lessonData);
                $lesson->module_id = $module->id;
                $lesson->save();
            }

            // Log::info('Lessons saved');

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

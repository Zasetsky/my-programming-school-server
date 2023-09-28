<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;

class HomeworkController extends Controller
{
    public function addHomeworkToModule(Request $request)
    {
        $userId = $request->user()->id;
        $subjectId = $request->input('subjectId');
        $moduleId = $request->input('moduleId');
        $homeworkDate = $request->input('homeworkDate');
        $homeworkText = $request->input('homeworkText');

        $subject = Subject::where('id', $subjectId)->where('user_id', $userId)->first();

        if (!$subject) {
            return response()->json(['error' => 'Subject not found'], 404);
        }

        $modules = $subject->modules; // Получаем текущий массив модулей
        $moduleFound = false;

        foreach ($modules as &$module) {
            if ($module['id'] == $moduleId) {
                $moduleFound = true;

                $homework = [
                    'homeworkDate' => $homeworkDate,
                    'homeworkText' => $homeworkText
                ];

                if (!isset($module['homeworks'])) {
                    $module['homeworks'] = [];
                }

                $module['homeworks'][] = $homework;
                break;
            }
        }

        if (!$moduleFound) {
            return response()->json(['error' => 'Module not found'], 404);
        }

        $subject->setAttribute('modules', $modules); // Присваиваем модифицированный массив обратно
        $subject->save();

        return response()->json(['message' => 'Homework added successfully']);
    }

    public function getAllHomeworks(Request $request)
    {
        $userId = $request->user()->id;
        $subjects = Subject::where('user_id', $userId)->get();

        $homeworks = [];

        foreach ($subjects as $subject) {
            $modules = $subject->modules;

            foreach ($modules as $module) {
                if (isset($module['homeworks'])) {
                    foreach ($module['homeworks'] as $homework) {
                        $homeworks[] = [
                            'subjectName' => $subject->name,
                            'homeworkDate' => $homework['homeworkDate'],
                            'homeworkText' => $homework['homeworkText'],
                            'startTime' => $module['startTime']
                        ];
                    }
                }
            }
        }

        return response()->json($homeworks);
    }
}
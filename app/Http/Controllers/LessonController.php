<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Services\LessonDateService;
use DateTime;
use DateTimeZone;
use DateInterval;

class LessonController extends Controller
{
    protected $lessonDateService;

    public function __construct(LessonDateService $lessonDateService)
    {
        $this->lessonDateService = $lessonDateService;
    }

    public function getAllUserLessons(Request $request)
    {
        $userId = $request->user()->id;
        $subjects = Subject::where('user_id', $userId)->get();

        $lessons = [];
        $now = new DateTime('now', new DateTimeZone('Europe/Moscow'));

        foreach ($subjects as $subject) {
            $subjectId = $subject->id;

            foreach ($subject->modules as $module) {
                $moduleId = $module['id']; // Получаем уникальный ID модуля

                $nextLessonDate = DateTime::createFromFormat('d-m-Y', $module['nextLessonDate']);
                $lessonDays = $module['lessonDays'];
                $totalLessonCount = $module['totalLessonCount'];
                $completedLessonCount = $module['completedLessonCount'];
                $startTime = DateTime::createFromFormat('H:i', $module['startTime']);
                $durationArray = explode(' ', $module['duration']);
                $hours = (int) $durationArray[0];
                $minutes = (int) $durationArray[2];
                $interval = new DateInterval("PT{$hours}H{$minutes}M");

                $lastLessonDateTime = clone $nextLessonDate;
                $lastLessonDateTime->setTime($startTime->format('H'), $startTime->format('i'));
                $lastLessonDateTime->add($interval);

                if ($now < $lastLessonDateTime || $completedLessonCount < $totalLessonCount) {
                    for ($i = $completedLessonCount; $i < $totalLessonCount; $i++) {
                        $lessonDate = $this->lessonDateService->calculateNextLessonDate(
                            $nextLessonDate,
                            $lessonDays,
                            $i - $completedLessonCount
                        );

                        $originalDateFormatted = $lessonDate->format('d-m-Y');
                        $rescheduledDate = null;
                        if (isset($module['rescheduledLessons'])) {
                            foreach ($module['rescheduledLessons'] as $rescheduledLesson) {
                                if ($rescheduledLesson['originalDate'] === $originalDateFormatted) {
                                    $rescheduledDate = $rescheduledLesson['newDate'];
                                    break;
                                }
                            }
                        }

                        $lessonDateTime = clone $lessonDate;
                        $lessonDateTime->setTime($startTime->format('H'), $startTime->format('i'));
                        $lessonDateTime->add($interval);

                        if ($now < $lessonDateTime) {
                            $lessons[] = [
                                'subjectId' => $subjectId,
                                'moduleId' => $moduleId,
                                // Используем уникальный ID
                                'subjectName' => $subject->name,
                                'moduleName' => $module['name'],
                                'lessonDate' => $rescheduledDate ? $rescheduledDate : $lessonDate->format('d-m-Y'),
                                'startTime' => $startTime->format('H:i')
                            ];
                        }
                    }
                }
            }
        }

        return response()->json($lessons);
    }

    public function rescheduleLesson(Request $request)
    {
        $userId = $request->user()->id;
        $subjectId = $request->input('subjectId');
        $moduleId = $request->input('moduleId');
        $newDate = $request->input('newDate'); // Новая дата в формате d-m-Y
        $originalDate = $request->input('originalDate'); // Изначальная дата в формате d-m-Y

        // Поиск соответствующего предмета
        $subject = Subject::where('id', $subjectId)->where('user_id', $userId)->first();

        if (!$subject) {
            return response()->json(['error' => 'Subject not found'], 404);
        }

        $moduleFound = false;
        $now = new DateTime('now', new DateTimeZone('Europe/Moscow'));

        foreach ($subject->modules as &$module) {
            if ($module['id'] == $moduleId) {
                $moduleFound = true;

                // Проверка на активность модуля
                $nextLessonDate = DateTime::createFromFormat('d-m-Y', $module['nextLessonDate']);
                $startTime = DateTime::createFromFormat('H:i', $module['startTime']);
                $durationArray = explode(' ', $module['duration']);
                $hours = (int) $durationArray[0];
                $minutes = (int) $durationArray[2];
                $interval = new DateInterval("PT{$hours}H{$minutes}M");

                $lastLessonDateTime = clone $nextLessonDate;
                $lastLessonDateTime->setTime($startTime->format('H'), $startTime->format('i'));
                $lastLessonDateTime->add($interval);

                if (!($now < $lastLessonDateTime || $module['completedLessonCount'] < $module['totalLessonCount'])) {
                    return response()->json(['error' => 'Module is not active, cannot reschedule'], 400);
                }

                // Проверка на количество переносов
                if (isset($module['rescheduledLessons']) && count($module['rescheduledLessons']) >= 2) {
                    return response()->json(['error' => 'Cannot reschedule more than 2 lessons for a single module'], 400);
                }

                // Добавление нового объекта перенесенной даты
                $rescheduledLesson = [
                    'originalDate' => $originalDate,
                    // изначальная дата из запроса
                    'newDate' => $newDate // новая дата из запроса
                ];
                $module['rescheduledLessons'][] = $rescheduledLesson;

                break;
            }
        }

        if (!$moduleFound) {
            return response()->json(['error' => 'Module not found'], 404);
        }

        $subject->save();

        return response()->json(['message' => 'Lesson rescheduled successfully']);
    }
}
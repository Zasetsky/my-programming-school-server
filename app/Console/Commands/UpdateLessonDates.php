<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subject;
use App\Services\LessonDateService;

use DateTime;
use DateTimeZone;
use DateInterval;

class UpdateLessonDates extends Command
{
    // Инжектим зависимость на LessonDateService
    protected $lessonDateService;

    protected $signature = 'update:lesson-dates';
    protected $description = 'Update lesson dates for completed lessons';

    public function __construct(LessonDateService $lessonDateService)
    {
        parent::__construct();
        $this->lessonDateService = $lessonDateService;
    }

    public function handle()
    {
        // Получаем все предметы
        $subjects = Subject::all();

        foreach ($subjects as $subject) {
            $modules = json_decode($subject->modules, true);

            foreach ($modules as &$module) {
                // Проверяем, нужно ли обновить этот модуль
                if ($this->shouldUpdateModule($module)) {
                    // Обновляем completedLessonCount
                    $module['completedLessonCount'] += 1;

                    // Обновляем nextLessonDate
                    // Здесь вызываем метод из инжектированного сервиса для обновления даты следующего урока и счетчика завершенных уроков
                    $module = $this->lessonDateService->calculateDates($module, $module['completedLessonCount']);
                }
            }

            // Сохраняем обновленные модули
            $subject->modules = json_encode($modules);
            $subject->save();
        }
    }

    // Проверяем, нужно ли обновлять данный модуль. Сравниваем текущее время с временем окончания урока
    private function shouldUpdateModule(array $module): bool
    {
        // Получаем текущее время
        $now = new DateTime('now', new DateTimeZone('Europe/Moscow'));

         // Преобразуем nextLessonDate и startTime в объекты DateTime
        $nextLessonDate = DateTime::createFromFormat('d-m-Y', $module['nextLessonDate'], new DateTimeZone('Europe/Moscow'));
        $startTime = DateTime::createFromFormat('H:i', $module['startTime'], new DateTimeZone('Europe/Moscow'));

        // Преобразуем startTime в объект DateTime, который включает и дату, и время
        $nextLessonDateTime = clone $nextLessonDate;
        $nextLessonDateTime->setTime($startTime->format('H'), $startTime->format('i'));

        // Добавляем продолжительность урока
        $durationArray = explode(' ', $module['duration']); // предположим, что формат "1 hour 30 minutes"
        $hours = (int) $durationArray[0];
        $minutes = (int) $durationArray[2];

        $interval = new DateInterval("PT{$hours}H{$minutes}M");
        $nextLessonDateTime->add($interval);

        // Добавляем к времени начала урока его продолжительность и сравниваем с текущим временем
        return $now >= $nextLessonDateTime;
    }
}
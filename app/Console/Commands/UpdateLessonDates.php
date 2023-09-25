<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subject;
use App\Services\LessonDateService;
use Illuminate\Support\Facades\Log;
use DateTime;
use DateTimeZone;
use DateInterval;

class UpdateLessonDates extends Command
{
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
        Log::info('Handle method called');

        Subject::chunk(200, function ($subjects) {
            foreach ($subjects as $subject) {
                $this->processSubject($subject);
            }
        });
    }

    private function processSubject($subject)
    {
        $modules = $subject->modules;

        foreach ($modules as &$module) {
            if (!$this->isValidModule($module)) {
                Log::warning("Invalid module data. Skipping...");
                continue;
            }

            if ($this->shouldUpdateModule($module)) {
                $module['completedLessonCount']++;
                $module = $this->lessonDateService->calculateDates($module, $module['completedLessonCount']);
            }
        }

        $subject->modules = $modules;
        $subject->save();
    }

    private function isValidModule(array $module): bool
    {
        return isset($module['nextLessonDate'], $module['startTime'], $module['duration']);
    }

    private function shouldUpdateModule(array $module): bool
    {
        $now = new DateTime('now', new DateTimeZone('Europe/Moscow'));
        $nextLessonDate = DateTime::createFromFormat('d-m-Y', $module['nextLessonDate'], new DateTimeZone('Europe/Moscow'));

        if ($nextLessonDate === false) {
            // Здесь код обработки ошибки
            \Log::error("Received nextLessonDate: " . $module['nextLessonDate']);
            throw new \Exception('Неверный формат даты в nextLessonDate');
        }

        $startTime = DateTime::createFromFormat('H:i', $module['startTime'], new DateTimeZone('Europe/Moscow'));

        $nextLessonDateTime = clone $nextLessonDate;
        $nextLessonDateTime->setTime($startTime->format('H'), $startTime->format('i'));

        $durationArray = explode(' ', $module['duration']); // предполагается, что формат "1 hour 30 minutes"
        $hours = (int) $durationArray[0];
        $minutes = (int) $durationArray[2];

        $interval = new DateInterval("PT{$hours}H{$minutes}M");
        $nextLessonDateTime->add($interval);

        return $now >= $nextLessonDateTime;
    }
}
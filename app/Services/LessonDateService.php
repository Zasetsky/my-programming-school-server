<?php

namespace App\Services;

use DateTime;
use DateTimeZone;

class LessonDateService
{
    /**
     * Вычисляет дату следующего урока и дату окончания на основе данных модуля.
     *
     * @param array $moduleData - Данные модуля.
     * @param int $completedLessonCount - Количество завершенных уроков.
     * @param bool $isNewModule - Является ли это новым модулем.
     *
     * @return array - Обновленные данные модуля.
     */
    public function calculateDates(array $moduleData, int $completedLessonCount = 0, bool $isNewModule = false): array
    {
        $lessonDays = $moduleData['lessonDays'];
        $totalLessonCount = $moduleData['totalLessonCount'];

        if ($isNewModule) {
            $startDate = DateTime::createFromFormat('d-m-Y', $moduleData['startDate'], new DateTimeZone('Europe/Moscow'));
            $nextLessonDate = $this->calculateNextLessonDate($startDate, $lessonDays);
        } else {
            $nextLessonDate = DateTime::createFromFormat('d-m-Y', $moduleData['nextLessonDate'], new DateTimeZone('Europe/Moscow'));
        }

        // Вычисление даты следующего урока
        $nextLessonDate = $this->calculateNextLessonDate($nextLessonDate, $lessonDays, $completedLessonCount);
        $moduleData['nextLessonDate'] = $nextLessonDate->format('d-m-Y');

        // Вычисление даты окончания
        $endDate = $this->calculateEndDate($nextLessonDate, $lessonDays, $totalLessonCount - $completedLessonCount);
        $moduleData['endDate'] = $endDate->format('d-m-Y');

        return $moduleData;
    }

    /**
     * Вычисляет дату следующего урока на основе даты начала и дней уроков.
     *
     * @param DateTime $startDate - Дата начала.
     * @param array $lessonDays - Дни, в которые проходят уроки.
     * @param int $completedLessonCount - Количество завершенных уроков.
     *
     * @return DateTime - Дата следующего урока.
     */
    public function calculateNextLessonDate(DateTime $startDate, array $lessonDays, int $completedLessonCount = 0): DateTime
    {
        $nextLessonDate = clone $startDate;

        // Учёт уже проведенных уроков
        for ($i = 0; $i < $completedLessonCount; $i++) {
            do {
                $nextLessonDate->modify('+1 day');
            } while (!in_array($nextLessonDate->format('l'), $lessonDays));
        }
        return $nextLessonDate;
    }

    /**
     * Вычисляет дату окончания на основе даты следующего урока, дней уроков и оставшегося количества уроков.
     *
     * @param DateTime $nextLessonDate - Дата следующего урока.
     * @param array $lessonDays - Дни, в которые проходят уроки.
     * @param int $remainingLessonCount - Количество оставшихся уроков.
     *
     * @return DateTime - Дата окончания.
     */
    public function calculateEndDate(DateTime $nextLessonDate, array $lessonDays, int $remainingLessonCount): DateTime
    {
        $endDate = clone $nextLessonDate;
        $count = 0;

        while ($count < $remainingLessonCount) {
            $endDate->modify('+1 day');
            if (in_array($endDate->format('l'), $lessonDays)) {
                $count++;
            }
        }
        return $endDate;
    }
}

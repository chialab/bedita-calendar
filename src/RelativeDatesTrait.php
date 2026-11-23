<?php
declare(strict_types=1);

namespace Chialab\Calendar;

use Cake\I18n\DateTime;

trait RelativeDatesTrait
{
    /**
     * Create `from` and `to` values for "today".
     *
     * @param bool $fullDay Return the full day or just the remaining time.
     * @return array<\Cake\I18n\DateTime>
     */
    public function today(bool $fullDay = true): array
    {
        $now = DateTime::now();

        return [$fullDay ? $now->startOfDay() : $now, $now->endOfDay()];
    }

    /**
     * Create `from` and `to` values for "tomorrow".
     *
     * @return array<\Cake\I18n\DateTime>
     */
    public function tomorrow(): array
    {
        $tomorrow = DateTime::tomorrow();

        return [$tomorrow->startOfDay(), $tomorrow->endOfDay()];
    }

    /**
     * Create `from` and `to` values for "aftertomorrow".
     *
     * @return array<\Cake\I18n\DateTime>
     */
    public function afterTomorrow(): array
    {
        $afterTomorrow = DateTime::now()->addDays(2);

        return [$afterTomorrow->startOfDay(), $afterTomorrow->endOfDay()];
    }

    /**
     * Create `from` and `to` values for the current week.
     *
     * @param bool $fullWeek Return the full week range or just the remaining time.
     * @return array<\Cake\I18n\DateTime>
     */
    public function thisWeek(bool $fullWeek = true): array
    {
        $now = DateTime::now();

        return [$fullWeek ? $now->startOfWeek() : $now, $now->endOfWeek()];
    }

    /**
     * Create `from` and `to` values for the current weekend.
     *
     * @param bool $fullWeekend Return the full weekend range or just the remaining time.
     * @return array<\Cake\I18n\DateTime>
     */
    public function thisWeekend(bool $fullWeekend = true): array
    {
        $now = DateTime::now();
        switch ($now->dayOfWeek) {
            case DateTime::SATURDAY:
                return [$fullWeekend ? $now->startOfDay() : $now, $now->addDays(1)->endOfDay()];
            case DateTime::SUNDAY:
                return [$fullWeekend ? $now->subDays(1)->startOfDay() : $now, $now->endOfDay()];
            default:
                return [$now->next(DateTime::SATURDAY)->startOfDay(), $now->next(DateTime::SUNDAY)->endOfDay()];
        }
    }

    /**
     * Create `from` and `to` values for the current month.
     *
     * @param bool $fullMonth Return the full month range or just the remaining time.
     * @return array<\Cake\I18n\DateTime>
     */
    public function thisMonth(bool $fullMonth = true): array
    {
        $now = DateTime::now();

        return [$fullMonth ? $now->startOfMonth() : $now, $now->endOfMonth()];
    }
}

<?php
declare(strict_types=1);

namespace Chialab\Calendar;

use Cake\I18n\FrozenTime;

trait RelativeDatesTrait
{
    /**
     * Create `from` and `to` values for "today".
     *
     * @param bool $fullDay Return the full day or just the remaining time.
     * @return array<\Cake\I18n\FrozenTime>
     */
    public function today(bool $fullDay = true): array
    {
        $now = FrozenTime::now();

        return [$fullDay ? $now->startOfDay() : $now, $now->endOfDay()];
    }

    /**
     * Create `from` and `to` values for "tomorrow".
     *
     * @return array<\Cake\I18n\FrozenTime>
     */
    public function tomorrow(): array
    {
        $tomorrow = FrozenTime::tomorrow();

        return [$tomorrow->startOfDay(), $tomorrow->endOfDay()];
    }

    /**
     * Create `from` and `to` values for "aftertomorrow".
     *
     * @return array<\Cake\I18n\FrozenTime>
     */
    public function afterTomorrow(): array
    {
        $afterTomorrow = FrozenTime::now()->addDay(2);

        return [$afterTomorrow->startOfDay(), $afterTomorrow->endOfDay()];
    }

    /**
     * Create `from` and `to` values for the current week.
     *
     * @param bool $fullWeek Return the full week range or just the remaining time.
     * @return array<\Cake\I18n\FrozenTime>
     */
    public function thisWeek(bool $fullWeek = true): array
    {
        $now = FrozenTime::now();

        return [$fullWeek ? $now->startOfWeek() : $now, $now->endOfWeek()];
    }

    /**
     * Create `from` and `to` values for the current weekend.
     *
     * @param bool $fullWeekend Return the full weekend range or just the remaining time.
     * @return array<\Cake\I18n\FrozenTime>
     */
    public function thisWeekend(bool $fullWeekend = true): array
    {
        $now = FrozenTime::now();
        switch ($now->dayOfWeek) {
            case FrozenTime::SATURDAY:
                return [$fullWeekend ? $now->startOfDay() : $now, $now->addDay()->endOfDay()];
            case FrozenTime::SUNDAY:
                return [$fullWeekend ? $now->subDay()->startOfDay() : $now, $now->endOfDay()];
            default:
                return [$now->next(FrozenTime::SATURDAY)->startOfDay(), $now->next(FrozenTime::SUNDAY)->endOfDay()];
        }
    }

    /**
     * Create `from` and `to` values for the current month.
     *
     * @param bool $fullMonth Return the full month range or just the remaining time.
     * @return array<\Cake\I18n\FrozenTime>
     */
    public function thisMonth(bool $fullMonth = true): array
    {
        $now = FrozenTime::now();

        return [$fullMonth ? $now->startOfMonth() : $now, $now->endOfMonth()];
    }
}

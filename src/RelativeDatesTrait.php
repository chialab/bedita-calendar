<?php

namespace Chialab\Calendar;

use Cake\I18n\FrozenTime;

trait RelativeDatesTrait
{
    /**
     * Create `from` and `to` values for "today".
     *
     * @param bool $fullDay Return the full day or just the remaining time.
     * @return \Cake\I18n\FrozenTime[]
     */
    public function today(bool $fullDay = true): array
    {
        $now = FrozenTime::now();

        return [$fullDay ? $now->startOfDay() : $now, $now->endOfDay()];
    }

    /**
     * Create `from` and `to` values for "tomorrow".
     *
     * @return \Cake\I18n\FrozenTime[]
     */
    public function tomorrow(): array
    {
        $tomorrow = FrozenTime::tomorrow();

        return [$tomorrow->startOfDay(), $tomorrow->endOfDay()];
    }

    /**
     * Create `from` and `to` values the given number of days.
     *
     * @param int $days The number of days.
     * @return \Cake\I18n\FrozenTime[]
     */
    public function nextDays(int $days): array
    {
        $now = FrozenTime::now();

        return [$now, $now->addDays($days)];
    }

    /**
     * Create `from` and `to` values for the current weekend.
     *
     * @param bool $fullWeekend Return the full weekend range or just the remaining time.
     * @return \Cake\I18n\FrozenTime[]
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
}

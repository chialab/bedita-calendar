<?php
declare(strict_types=1);

namespace Chialab\Calendar\Model\Behavior;

use BEdita\Core\Model\Entity\DateRange;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Chronos\ChronosInterface;
use Cake\Collection\CollectionInterface;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Closure;
use Generator;

/**
 * Apply closing days to date ranges.
 */
class ClosingDaysBehavior extends Behavior
{
    /**
     * Map of days of week from the name as specified in {@see \BEdita\Core\Model\Entity\DateRange::$params}
     * to {@see \Cake\Chronos\ChronosInterface} constants.
     *
     * @var array<string, int>
     */
    protected const WEEKDAYS = [
        'monday' => ChronosInterface::MONDAY,
        'tuesday' => ChronosInterface::TUESDAY,
        'wednesday' => ChronosInterface::WEDNESDAY,
        'thursday' => ChronosInterface::THURSDAY,
        'friday' => ChronosInterface::FRIDAY,
        'saturday' => ChronosInterface::SATURDAY,
        'sunday' => ChronosInterface::SUNDAY,
    ];

    /**
     * @inheritDoc
     */
    protected $_defaultConfig = [
        'implementedFinders' => [
            'closingDays' => 'findClosingDays',
        ],
    ];

    /**
     * Finder to filter and apply closing days when filtering by date ranges.
     *
     * @param \Cake\ORM\Query $query Query object.
     * @return \Cake\ORM\Query
     */
    public function findClosingDays(Query $query): Query
    {
        return $query->formatResults(
            fn (CollectionInterface $results) => $query->isHydrationEnabled()
            ? $results->map(
                fn (ObjectEntity $object): ObjectEntity => !$object->has('filtered_date_ranges') && $object->has('date_ranges')
                    ? $object
                        ->set(
                            'filtered_date_ranges',
                            collection((array)$object->get('date_ranges'))->unfold(Closure::fromCallable([$this, 'applyClosingDays']))
                        )
                        ->setAccess('filtered_date_ranges', false)
                        ->setVirtual(['filtered_date_ranges'], true)
                    : $object
            )
            : $results,
            Query::PREPEND,
        );
    }

    /**
     * Apply closing days to a {@see \BEdita\Core\Model\Entity\DateRange}.
     *
     * @param \BEdita\Core\Model\Entity\DateRange $dr Date range.
     * @return \Generator<\BEdita\Core\Model\Entity\DateRange>
     */
    protected function applyClosingDays(DateRange $dr): Generator
    {
        $params = is_string($dr->params) ? json_decode($dr->params, true) : $dr->params;
        $closingDays = array_intersect_key(
            static::WEEKDAYS,
            array_filter((array)($params['weekdays'] ?? []), fn ($val): bool => $val === false),
        );
        if (empty($closingDays)) {
            // No closing days for this DateRange. Let's avoid useless expensive computation.
            yield $dr;

            return;
        }

        $start = new FrozenTime($dr->start_date);
        if ($dr->end_date === null && in_array($start->dayOfWeek, $closingDays)) {
            // This is a unit set where its only element is also excluded by its parameters as it is a closing day.
            // Albeit weird, we should be consistent and return an empty set.
            Log::debug(sprintf('Date range #%d for object #%d (%s) does not have a end date, and its only date is also a closing day.', $dr->id, $dr->object_id, $start->toIso8601String()));

            return;
        }

        // Go back to start of first week, then go back another hour so that "next monday" doesn't jump to the next week already.
        $it = $start->startOfWeek()->subHour();
        $closingDays = array_values($closingDays);
        $subRanges = [];
        while ($it <= $dr->end_date) {
            $day = current($closingDays);
            /** @var \Cake\I18n\FrozenTime $it */
            $it = $it->next($day);
            $subRanges[] = new DateRange(['start_date' => $it->subDay()->endOfDay(), 'end_date' => $it->addDay()]);

            if (next($closingDays) === false) {
                reset($closingDays);
            }
        }

        yield from DateRange::diff([$dr], $subRanges);
    }
}

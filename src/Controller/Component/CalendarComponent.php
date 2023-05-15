<?php
declare(strict_types=1);

namespace Chialab\Calendar\Controller\Component;

use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Controller\Component;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Chialab\Calendar\RelativeDatesTrait;
use Generator;
use InvalidArgumentException;

/**
 * Calendar component.
 *
 * @property \Chialab\FrontendKit\Controller\Component\ObjectsComponent $Objects
 */
class CalendarComponent extends Component
{
    use RelativeDatesTrait;

    /**
     * The name of the view var set by the component.
     *
     * @var string
     */
    const VIEW_PARAMS = '_calendar';

    /**
     * {@inheritDoc}
     */
    public $components = ['Chialab/FrontendKit.Objects'];

    /**
     * Date filter.
     *
     * @var \Cake\I18n\FrozenTime|null
     */
    protected ?FrozenTime $dateFilter = null;

    /**
     * Range filter.
     *
     * @var array|string|null
     */
    protected $rangeFilter = null;

    /**
     * Categories list filter.
     *
     * @var string[]
     */
    protected array $categoriesFilter = [];

    /**
     * Tags list filter.
     *
     * @var string[]
     */
    protected array $tagsFilter = [];

    /**
     * Search text filter.
     *
     * @var string|null
     */
    protected ?string $searchFilter = null;

    /**
     * Day filter.
     *
     * @var int|null
     */
    protected ?int $dayFilter = null;

    /**
     * Month filter.
     *
     * @var int|null
     */
    protected ?int $monthFilter = null;

    /**
     * Year filter.
     *
     * @var int|null
     */
    protected ?int $yearFilter = null;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'params' => [
            'range' => 'range',
            'categories' => 'categories',
            'tags' => 'tags',
            'search' => 'q',
        ],
    ];

    /**
     * Events supported by this component.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Controller.beforeRender' => 'beforeRender',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $request = $this->getController()->getRequest();

        if (!empty($this->getConfig('params.range'))) {
            $this->setRangeFilter($request->getQuery($this->getConfig('params.range')));
        }
        if (!empty($this->getConfig('params.categories'))) {
            $this->setCategoriesFilter($request->getQuery($this->getConfig('params.categories')) ?? []);
        }
        if (!empty($this->getConfig('params.tags'))) {
            $this->setTagsFilter($request->getQuery($this->getConfig('params.tags')) ?? []);
        }
        if (!empty($this->getConfig('params.search'))) {
            $this->setSearchFilter($request->getQuery($this->getConfig('params.search')));
        }
    }

    /**
     * Set calendar view variable.
     *
     * @return void
     */
    public function beforeRender()
    {
        $this->getController()->set(static::VIEW_PARAMS, [
            'range' => $this->getRangeFilter(),
            'categories' => $this->getCategoriesFilter(),
            'tags' => $this->getTagsFilter(),
            'search' => $this->getSearchFilter(),
            'computed' => $this->getComputedRange(),
            'params' => $this->getConfig('params'),
        ]);
    }

    /**
     * Get the range filter.
     *
     * @return array|string|null
     */
    public function getRangeFilter()
    {
        return $this->rangeFilter;
    }

    /**
     * Set the range filter.
     *
     * @param array|string|null $date Date filter.
     * @return void
     */
    public function setRangeFilter($range): void
    {
        $this->rangeFilter = $range;
    }

    /**
     * Get the categories list filter.
     *
     * @return array
     */
    public function getCategoriesFilter(): array
    {
        return $this->categoriesFilter;
    }

    /**
     * Set the categories list filter.
     *
     * @param string[] $categories Categories filter.
     * @return void
     */
    public function setCategoriesFilter(array $categories): void
    {
        $this->categoriesFilter = $categories;
    }

    /**
     * Get the tags list filter.
     *
     * @return array
     */
    public function getTagsFilter(): array
    {
        return $this->tagsFilter;
    }

    /**
     * Set the tags list filter.
     *
     * @param string[] $tags Tags filter.
     * @return void
     */
    public function setTagsFilter(array $tags): void
    {
        $this->tagsFilter = $tags;
    }

    /**
     * Get the request search text filter.
     *
     * @return string|null
     */
    public function getSearchFilter(): ?string
    {
        return $this->searchFilter;
    }

    /**
     * Set the request search text filter.
     *
     * @param string|null $value The value to set.
     * @return void
     */
    public function setSearchFilter(?string $value): void
    {
        $this->searchFilter = $value;
    }

    /**
     * Get the computed date range filter.
     *
     * @return array
     */
    public function getComputedRange(): array
    {
        $startDate = new FrozenTime();
        $endDate = null;
        $range = $this->getRangeFilter();
        if (!empty($range)) {
            if (is_array($range)) {
                $startDate = (new FrozenTime($range[0] ?? 'now'))->startOfDay();
                $endDate = !empty($range[1]) ? (new FrozenTime($range[1]))->endOfDay() : null;
            } else {
                switch ($range) {
                    case 'today':
                        [$startDate, $endDate] = $this->today();
                        break;
                    case 'tomorrow':
                        [$startDate, $endDate] = $this->tomorrow();
                        break;
                    case 'after-tomorrow':
                        [$startDate, $endDate] = $this->afterTomorrow();
                        break;
                    case 'this-week':
                        [$startDate, $endDate] = $this->thisWeek();
                        break;
                    case 'this-weekend':
                        [$startDate, $endDate] = $this->thisWeekend();
                        break;
                    case 'this-month':
                        [$startDate, $endDate] = $this->thisMonth();
                        break;
                    default:
                        $startDate = (new FrozenTime($range))->startOfDay();
                }
            }
        }

        return [$startDate, $endDate];
    }

    /**
     * An array of i18n months names, useful for building a select input.
     *
     * @return array
     */
    public function monthsLabels(): array
    {
        $months = range(1, 12);

        return array_combine($months, array_map(
            fn ($monthNum): string => FrozenDate::now()->month($monthNum)->i18nFormat('MMMM'),
            $months
        ));
    }

    /**
     * Get sub-query for joining with date boundaries.
     *
     * @param \Cake\ORM\Table $dateRanges Date ranges table instance.
     * @param \Cake\I18n\FrozenTime $from From.
     * @param \Cake\I18n\FrozenTime|null $to To.
     * @return \Cake\ORM\Query
     */
    protected function getDateBoundariesSubQuery(Table $dateRanges, FrozenTime $from, ?FrozenTime $to): Query
    {
        $query = $dateRanges->find();

        return $query
            ->find('dateRanges', [
                'from_date' => $from->toIso8601String(),
                'to_date' => $to !== null ? $to->toIso8601String() : null,
            ])
            ->select([
                'object_id' => $dateRanges->aliasField('object_id'),
                'closest_start_date' => $query->func()->min('start_date'),
                'closest_end_date' => $query->func()->min('end_date'),
            ])
            ->group($dateRanges->aliasField('object_id'));
    }

    /**
     * Add filtering and sorting to a query.
     *
     * Objects are filtered by the requested range (from/to). Objects are sorted so that:
     *
     * 1. objects that are in progress at the range start appear first. Within this class, event ending sooner appear first.
     * 2. objects starting after the range start are sorted with objects starting sooner appearing first.
     * 3. when two future objects start at the same time, the one that ends sooner appear first.
     *
     * @param \Cake\ORM\Query $query Query object.
     * @param \Cake\I18n\FrozenTime $from Range start.
     * @param \Cake\I18n\FrozenTime|null $to Range end.
     * @return \Cake\ORM\Query
     * @throws \InvalidArgumentException Throws an exception when the table being queried is not linked with DateRanges.
     */
    public function findInRange(Query $query, FrozenTime $from, ?FrozenTime $to = null): Query
    {
        /** @var \Cake\ORM\Table */
        $table = $query->getRepository();
        if (!$table->hasAssociation('DateRanges')) {
            throw new InvalidArgumentException('Table must be associated with DateRanges');
        }

        $dateRanges = $table->getAssociation('DateRanges')->getTarget();

        return $query
            // Add join with DateRanges table.
            ->innerJoin(
                ['DateBoundaries' => $this->getDateBoundariesSubQuery($dateRanges, $from, $to)],
                fn (QueryExpression $exp): QueryExpression => $exp->equalFields(
                    'DateBoundaries.object_id',
                    $table->aliasField('id')
                )
            )
            // Sort by closest events.
            ->orderAsc(new FunctionExpression('GREATEST', ['DateBoundaries.closest_start_date' => 'identifier', $from->toIso8601String()]), true)
            ->orderDesc(fn (QueryExpression $exp): QueryExpression => $exp->isNull('DateBoundaries.closest_end_date'))
            ->orderAsc('DateBoundaries.closest_end_date');
    }

    /**
     * Group objects by the day in which they occurr.
     *
     * @param \Cake\ORM\Query $query The query object.
     * @param \Cake\I18n\FrozenTime $from Range start.
     * @param \Cake\I18n\FrozenTime|null $to Range end.
     * @return \Cake\ORM\Query
     */
    public function findGroupedByDay(Query $query, FrozenTime $from, ?FrozenTime $to = null): Query
    {
        $to = $to ?? $from->addWeek();

        return $this->findInRange($query, $from, $to)
            ->contain(['DateRanges'])
            ->find('closingDays')
            ->formatResults(function (iterable $results) use ($from, $to): iterable {
                $grouped = collection($results)
                    ->unfold(function (ObjectEntity $object) use ($from, $to): Generator {
                        foreach ($object->filtered_date_ranges as $dr) {
                            $start = (new FrozenTime($dr->start_date))->startOfDay();
                            $end = (new FrozenTime($dr->end_date ?: $dr->start_date))->endOfDay();
                            if ($start->gte($to) || $end->lt($from)) {
                                continue;
                            }

                            $start = $start->max($from);
                            while ($start->lte($end) && $start->lte($to)) {
                                $day = $start->format('Y-m-d');
                                $start = $start->addDay();
                                $event = clone $object;
                                $event->set('primary_date_range', $dr);

                                yield compact('event', 'day');
                            }
                        }
                    })
                    ->groupBy('day')
                    ->map(fn (array $items): array => collection($items)
                        ->extract('event')
                        ->sortBy(
                            fn (ObjectEntity $event): string => $event->get('primary_date_range')->start_date->format('c'),
                            SORT_ASC,
                            SORT_NATURAL
                        )
                        ->toList())
                    ->toArray();

                ksort($grouped, SORT_STRING);

                return collection($grouped);
            });
    }
}

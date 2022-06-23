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
     * Search text filter.
     *
     * @var string|null
     */
    protected ?string $searchFilter = null;

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
     * Date filter.
     *
     * @var mixed
     */
    protected $dateFilter = null;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'params' => [
            'search' => 'q',
            'categories' => 'categories',
            'tags' => 'tags',
            'date' => 'date',
            'day' => 'day',
            'month' => 'month',
            'year' => 'year',
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

        if (!empty($this->getConfig('params.search'))) {
            $this->searchFilter = $request->getQuery($this->getConfig('params.search'));
        }
        if (!empty($this->getConfig('params.categories'))) {
            $this->categoriesFilter = $request->getQuery($this->getConfig('params.categories')) ?? [];
        }
        if (!empty($this->getConfig('params.tags'))) {
            $this->tagsFilter = $request->getQuery($this->getConfig('params.tags')) ?? [];
        }
        if (!empty($this->getConfig('params.date'))) {
            $this->dateFilter = $request->getQuery($this->getConfig('params.date'));
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
            'date' => $this->getDateFilter(),
            'range' => $this->getRangeFilter(),
            'search' => $this->getSearchFilter(),
            'categories' => $this->getCategoriesFilter(),
            'tags' => $this->getTagsFilter(),
            'params' => $this->getConfig('params'),
        ]);
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
     * Get the date filter.
     *
     * @return mixed
     */
    public function getDateFilter()
    {
        return $this->dateFilter;
    }

    /**
     * Set the tags list filter.
     *
     * @param mixed $date Date filter.
     * @return void
     */
    public function setDateFilter($date): void
    {
        $this->dateFilter = $date;
    }

    /**
     * Get the date range filter.
     *
     * @return array
     */
    public function getRangeFilter(): array
    {
        // Filter by date.
        [$startDate, $endDate] = [new FrozenTime(), null];
        $date = $this->getDateFilter();
        if (empty($date)) {
            $request = $this->getController()->getRequest();
            $day = filter_var($request->getQuery($this->getConfig('params.day')), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            $month = filter_var($request->getQuery($this->getConfig('params.month')), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            $year = filter_var($request->getQuery($this->getConfig('params.year')), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

            if (!empty($month) && !empty($year)) {
                $startDate = FrozenTime::create($year, $month, $day ?? 1);
                $endDate = $startDate->addDays(30);
            }
        } elseif (is_array($date)) {
            $startDate = new FrozenTime($date[0] ?? 'now');
            $endDate = !empty($date[1]) ? new FrozenTime($date[1]) : null;
        } else {
            switch ($date) {
                case 'today':
                    [$startDate, $endDate] = $this->today();
                    break;
                case 'tomorrow':
                    [$startDate, $endDate] = $this->tomorrow();
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
                    $startDate = new FrozenTime($date);
            }
        }

        return [$startDate->startOfDay(), $endDate !== null ? $endDate->endOfDay() : null];
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
            ->formatResults(function (iterable $results) use ($from, $to): iterable {
                $grouped = collection($results)->unfold(function (ObjectEntity $event) use ($from, $to): \Generator {
                    foreach ($event->date_ranges as $dr) {
                        $start = new FrozenDate($dr->start_date);
                        $end = new FrozenDate($dr->end_date ?: $dr->start_date);
                        if ($start->gte($to) || $end->lt($from)) {
                            continue;
                        }

                        $start = $start->max($from);
                        while ($start->lte($end) && $start->lte($to)) {
                            $day = $start->format('Y-m-d');
                            $start = $start->addDay();

                            yield compact('event', 'day');
                        }
                    }
                })
                ->groupBy('day')
                ->map(fn (array $items): array => array_column($items, 'event'))
                ->toArray();

                ksort($grouped, SORT_STRING);

                return collection($grouped);
            });
    }

    /**
     * Load calendar items from a folder.
     *
     * @param string $parent The parent folder uname.
     * @param \Cake\I18n\FrozenTime $from Range start.
     * @param \Cake\I18n\FrozenTime|null $to Range end.
     * @return \Cake\ORM\Query
     */
    public function calendarFolder(string $parent, FrozenTime $from, ?FrozenTime $to): Query
    {
        return $this->findGroupedByDay(
            $this->Objects->loadObjects(['parent' => $parent], 'objects'),
            $from,
            $to,
        );
    }
}

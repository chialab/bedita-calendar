<?php
declare(strict_types=1);

namespace Chialab\Calendar\View\Helper;

use BEdita\Core\Model\Entity\Category;
use BEdita\Core\Model\Entity\Tag;
use Cake\I18n\FrozenTime;
use Chialab\FrontendKit\View\Helper\DateRangesHelper;

/**
 * Calendar helper
 *
 * @property \Cake\View\Helper\FormHelper $Form
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class CalendarHelper extends DateRangesHelper
{
    /**
     * @inheritdoc
     */
    public $helpers = ['Form', 'Html', 'Url'];

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'searchParam' => 'q',
        'categoryParam' => 'category',
        'tagParam' => 'tag',
        'dayParam' => 'day',
        'monthParam' => 'month',
        'yearParam' => 'year',
    ];

    /**
     * Get a range of years.
     * It can be used with absolute values, eg 2019 and 2022
     * or relative values to the $from value, eg "-2 years" and "+2 years"
     *
     * @param int|string $startRange The initial value of the range, it can be absolute or relative.
     * @param int|string $endRange The initial value of the range, it can be absolute or relative.
     * @param \Cake\I18n\FrozenTime|string $from The start date for relative values.
     * @return array
     */
    public function getYears($startRange, $endRange, $from = 'now'): array
    {
        if (is_int($startRange)) {
            return range($startRange, $endRange);
        }

        $from = new FrozenTime($from);
        $start = $from->modify($startRange)->year;
        $end = $from->modify($endRange)->year;

        $years = range($start, $end);

        return array_combine($years, $years);
    }

    /**
     * An array of i18n months names, useful for building a select input.
     *
     * @return array
     */
    public function getMonths(): array
    {
        $date = FrozenTime::now()->day(1);
        $months = range(1, 12);

        return array_combine($months, array_map(
            fn ($monthNum): string => $date->month($monthNum)->i18nFormat('MMMM'),
            $months
        ));
    }

    /**
     * Get a list of available days in a month for a given year.
     *
     * @param int $year Year
     * @param int $month Month
     * @return array
     */
    public function getDaysInMonth(int $year, int $month): array
    {
        $last = FrozenTime::create($year, $month, 1);
        $days = range(1, $last->lastOfMonth()->day);

        return array_combine($days, $days);
    }

    /**
     * Get the request date, if available.
     *
     * @return \Cake\I18n\FrozenTime
     */
    public function getDate(): FrozenTime
    {
        $dayParam = $this->getConfig('dayParam');
        $monthParam = $this->getConfig('monthParam');
        $yearParam = $this->getConfig('yearParam');
        $request = $this->_View->getRequest();

        if ($request->getQuery($monthParam) === null || $request->getQuery($yearParam) === null) {
            return FrozenTime::now();
        }

        return FrozenTime::create($request->getQuery($yearParam), $request->getQuery($monthParam), $request->getQuery($dayParam) ?? 1, 0, 0, 0);
    }

    /**
     * Get the request search text, if available.
     *
     * @return string|null
     */
    public function getSearchText(): ?string
    {
        $request = $this->_View->getRequest();

        return $request->getQuery($this->getConfig('searchParam'));
    }

    /**
     * Generate an url to a day in the calendar.
     * It accept absolute and relative dates eg "+1 month" "2022-04-25" "-7 days"
     *
     * @param mixed $date The absolute or relative date.
     * @param array $options Link options.
     * @return string The url to the calendar date.
     */
    public function url($date, array $options = [])
    {
        $dayParam = $this->getConfig('dayParam');
        $monthParam = $this->getConfig('monthParam');
        $yearParam = $this->getConfig('yearParam');
        $query = array_merge($options['?'] ?? [], [
            $dayParam => $date->day,
            $monthParam => $date->month,
            $yearParam => $date->year,
        ]);

        return $this->Url->build(['?' => $query] + $options);
    }

    /**
     * Generate a link to a day in the calendar.
     * It accept absolute and relative dates eg "+1 month" "2022-04-25" "-7 days"
     *
     * @param string $title Link title.
     * @param mixed $date The absolute or relative date.
     * @param array $options Link options.
     * @return string The anchor element with a link to the calendar date.
     */
    public function link(string $title, $date, array $options = []): string
    {
        $dayParam = $this->getConfig('dayParam');
        $monthParam = $this->getConfig('monthParam');
        $yearParam = $this->getConfig('yearParam');
        $query = array_merge($options['?'] ?? [], [
            $dayParam => $date->day,
            $monthParam => $date->month,
            $yearParam => $date->year,
        ]);

        return $this->Html->link($title, ['?' => $query] + $options);
    }

    /**
     * Create calendar filters form.
     *
     * @param mixed $context The context for which the form is being defined.
     *   Can be a ContextInterface instance, ORM entity, ORM resultset, or an
     *   array of meta data. You can use null to make a context-less form.
     * @param array $options An array of html attributes and options.
     * @return string An formatted opening FORM tag.
     */
    public function createFiltersForm($context = null, ?array $options = null)
    {
        $options = $options ?? [];

        return $this->Form->create($context, $options + [
            'type' => 'GET',
            'is' => 'calendar-filters',
            'day-param' => $this->getConfig('dayParam'),
            'month-param' => $this->getConfig('monthParam'),
            'year-param' => $this->getConfig('yearParam'),
        ]);
    }

    /**
     * Closes the filters form.
     *
     * @return string A closing FORM tag.
     */
    public function closeFiltersForm()
    {
        return $this->Form->end();
    }

    /**
     * Generate a <input> element for search.
     *
     * @param array|null $options Options for the input element.
     * @return string The <input> element.
     */
    public function searchControl($options = null): string
    {
        $text = $this->getSearchText();
        $options = $options ?? [];

        return $this->Form->text($this->getConfig('searchParam'), [
            'value' => $text,
        ] + $options);
    }

    /**
     * Generate a <select> element for days.
     *
     * @param array|null $options Options for the select element.
     * @return string The <select> element.
     */
    public function daysControl($options = null): string
    {
        $date = $this->getDate();
        $options = $options ?? [];

        return $this->Form->control($this->getConfig('dayParam'), [
            'label' => '',
            'type' => 'select',
            'options' => $this->getDaysInMonth($date->year, $date->month),
            'value' => $date->day,
        ] + $options);
    }

    /**
     * Generate a <select> element for months.
     *
     * @param array|null $options Options for the select element.
     * @return string The <select> element.
     */
    public function monthsControl($options = null): string
    {
        $date = $this->getDate();
        $options = $options ?? [];

        return $this->Form->control($this->getConfig('monthParam'), [
            'label' => '',
            'type' => 'select',
            'options' => $this->getMonths(),
            'value' => $date->month,
        ] + $options);
    }

    /**
     * Generate a <select> element for years.
     *
     * @param array|null $options Options for the select element.
     * @return string The <select> element.
     */
    public function yearsControl($options = null, $start = '-2 years', $end = '+2 years'): string
    {
        $date = $this->getDate();
        $options = $options ?? [];

        return $this->Form->control($this->getConfig('yearParam'), [
            'label' => '',
            'type' => 'select',
            'options' => $this->getYears($start, $end),
            'value' => $date->year,
        ] + $options);
    }

    /**
     * Generate a checkbox control for category filtering.
     *
     * @param \BEdita\Core\Model\Entity\Category $category The category entity.
     * @param array|null $options Options for the checkbox element.
     * @return string The checkbox element.
     */
    public function categoryControl(Category $category, $options = null): string
    {
        $options = $options ?? [];
        $categoryParam = $this->getConfig('categoryParam');

        $request = $this->_View->getRequest();
        $value = $request->getQuery($categoryParam) ?? [];

        return $this->Form->control($categoryParam . '[]', [
            'id' => sprintf('filter-category-%s', $category->name),
            'type' => 'checkbox',
            'label' => $category->label ?? $category->name,
            'value' => $category->name,
            'checked' => in_array($category->name, $value),
            'hiddenField' => false,
        ] + $options);
    }

    /**
     * Generate a checkbox control for tag filtering.
     *
     * @param \BEdita\Core\Model\Entity\Tag $tag The tag entity.
     * @param array|null $options Options for the checkbox element.
     * @return string The checkbox element.
     */
    public function tagControl(Tag $tag, $options = null): string
    {
        $options = $options ?? [];
        $tagParam = $this->getConfig('tagParam');

        $request = $this->_View->getRequest();
        $value = $request->getQuery($tagParam) ?? [];

        return $this->Form->control($tagParam . '[]', [
            'id' => sprintf('filter-tag-%s', $tag->name),
            'type' => 'checkbox',
            'label' => $tag->label ?? $tag->name,
            'value' => $tag->name,
            'checked' => in_array($tag->name, $value),
            'hiddenField' => false,
        ] + $options);
    }

    /**
     * Generate a reset link for the calendar view.
     *
     * @param string $title The title of the link.
     * @param array|null $options Options for the link element.
     * @return string The <a> element.
     */
    public function resetControl(string $title, $options = null): string
    {
        $searchParam = $this->getConfig('searchParam');
        $categoryParam = $this->getConfig('categoryParam');
        $tagParam = $this->getConfig('tagParam');
        $dayParam = $this->getConfig('dayParam');
        $monthParam = $this->getConfig('monthParam');
        $yearParam = $this->getConfig('yearParam');

        $url = $this->_View->getRequest()->url;
        $query = $this->_View->getRequest()->getQueryParams();
        $query = array_diff_key($query, [
            $searchParam => null,
            $categoryParam => null,
            $tagParam => null,
            $dayParam => null,
            $monthParam => null,
            $yearParam => null,
        ]);
        if (!empty($query)) {
            $url = sprintf('%s?%s', $url, http_build_query($query));
        }

        return $this->Html->link($title, $url, $options);
    }

    /**
     * Extract a list of available time filters.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity[] $objects Result set of objects.
     * @return array A list of time filters.
     */
    public function extractTimeFilters($objects): array
    {
        $occurencies = [
            'today' => 0,
            'tomorrow' => 0,
            'this-week' => 0,
            'this-weekend' => 0,
            'this-month' => 0,
        ];

        $filters = [
            'today' => fn (FrozenTime $date) => $date->isToday(),
            'tomorrow' => fn (FrozenTime $date) => $date->isTomorrow(),
            'this-week' => fn (FrozenTime $date) => $date->isThisWeek(),
            'this-weekend' => fn (FrozenTime $date) => $date->isWeekend(),
            'this-month' => fn (FrozenTime $date) => $date->isThisMonth(),
        ];

        $labels = [
            'today' => __('today'),
            'tomorrow' => __('tomorrow'),
            'this-week' => __('this week'),
            'this-weekend' => __('this weekend'),
            'this-month' => __('this month'),
        ];

        foreach ($objects as $object) {
            if (empty($object->date_ranges)) {
                continue;
            }

            foreach ($filters as $key => $filter) {
                foreach ($object->date_ranges as $range) {
                    $startDate = (new FrozenTime($range->start_date))->startOfDay();
                    $endDate = (new FrozenTime($range->end_date ?? $range->start_date))->endOfDay();

                    while ($startDate->lte($endDate)) {
                        if ($filter($startDate)) {
                            $occurencies[$key]++;
                            break 2;
                        }

                        $startDate = $startDate->addDay();
                    }
                }
            }
        }

        $total = count($objects);

        return array_filter(
            $labels,
            fn ($key) => ($occurencies[$key] > 0 && $occurencies[$key] < $total),
            ARRAY_FILTER_USE_KEY
        );
    }
}

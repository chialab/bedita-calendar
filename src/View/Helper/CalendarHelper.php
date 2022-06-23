<?php
declare(strict_types=1);

namespace Chialab\Calendar\View\Helper;

use BEdita\Core\Model\Entity\Category;
use BEdita\Core\Model\Entity\Tag;
use Cake\I18n\FrozenTime;
use Cake\Utility\Hash;
use Chialab\Calendar\RelativeDatesTrait;
use Chialab\FrontendKit\View\Helper\DateRangesHelper;
use DateTimeInterface;

/**
 * Calendar helper
 *
 * @property \Cake\View\Helper\FormHelper $Form
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class CalendarHelper extends DateRangesHelper
{
    use RelativeDatesTrait;

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
        'dateParam' => 'date',
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
        $dateParam = $this->getConfig('dateParam');
        $request = $this->_View->getRequest();
        if ($request->getQuery($dateParam)) {
            return new FrozenTime($request->getQuery($dateParam));
        }

        $dayParam = $this->getConfig('dayParam');
        $monthParam = $this->getConfig('monthParam');
        $yearParam = $this->getConfig('yearParam');
        if ($request->getQuery($monthParam) !== null && $request->getQuery($yearParam) !== null) {
            return FrozenTime::create($request->getQuery($yearParam), $request->getQuery($monthParam), $request->getQuery($dayParam) ?? 1, 0, 0, 0);
        }

        return FrozenTime::now();
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
     * Get the request categories list, if available.
     *
     * @return string|null
     */
    public function getCategories(): array
    {
        $request = $this->_View->getRequest();

        return $request->getQuery($this->getConfig('categoryParam')) ?? [];
    }

    /**
     * Get the request tags list, if available.
     *
     * @return string|null
     */
    public function getTags(): array
    {
        $request = $this->_View->getRequest();

        return $request->getQuery($this->getConfig('tagParam')) ?? [];
    }

    /**
     * Generate url query params for calendar filter.
     *
     * @param mixed $date The absolute or relative date.
     * @return array List of query params.
     */
    public function getFilterParams($date): array
    {
        $categoryParam = $this->getConfig('categoryParam');
        $tagParam = $this->getConfig('tagParam');
        $searchParam = $this->getConfig('searchParamParam');
        $dateParam = $this->getConfig('dateParam');

        $formatDate = function ($date) use (&$formatDate) {
            if (is_array($date)) {
                return array_map($formatDate, $date);
            }

            if ($date instanceof DateTimeInterface) {
                return $date->format('Y-m-d');
            }

            return $date;
        };

        return [
            $dateParam => $formatDate($date),
            $searchParam => $this->getSearchText(),
            $categoryParam => $this->getCategories(),
            $tagParam => $this->getTags(),
        ];
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
            'category-param' => $this->getConfig('categoryParam'),
            'tag-param' => $this->getConfig('tagParam'),
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
            'templates' => [
                'nestingLabel' => '{{input}}<label{{attrs}}>{{text}}</label>',
            ],
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
            'templates' => [
                'nestingLabel' => '{{input}}<label{{attrs}}>{{text}}</label>',
            ],
        ] + $options);
    }

    /**
     * Generate a radio control for time range filtering.
     *
     * @param array $ranges Time ranges.
     * @param array|null $options Options for the radio group.
     * @param array|null $attrs Options for the radio element.
     * @return string The radio element.
     */
    public function rangeControl(array $ranges, $options = null, $attrs = null): string
    {
        $options = $options ?? [];
        $attrs = $attrs ?? [];
        $ranges = Hash::normalize($ranges);

        $dateParam = $this->getConfig('dateParam');
        $request = $this->_View->getRequest();
        $value = $request->getQuery($dateParam);

        return $this->Form->control($dateParam, [
            'type' => 'radio',
            'options' => $ranges,
            'value' => $value,
            'checked' => $value,
            'label' => false,
            'hiddenField' => false,
            'templates' => [
                'nestingLabel' => '{{hidden}}{{input}}<label{{attrs}}>{{text}}</label>',
            ],
        ] + $options, $attrs);
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
        $dateParam = $this->getConfig('dateParam');
        $dayParam = $this->getConfig('dayParam');
        $monthParam = $this->getConfig('monthParam');
        $yearParam = $this->getConfig('yearParam');

        $url = $this->_View->getRequest()->url;
        $query = $this->_View->getRequest()->getQueryParams();
        $query = array_diff_key($query, [
            $searchParam => null,
            $categoryParam => null,
            $tagParam => null,
            $dateParam => null,
            $dayParam => null,
            $monthParam => null,
            $yearParam => null,
        ]);
        if (!empty($query)) {
            $url = sprintf('%s?%s', $url, http_build_query($query));
        }

        return $this->Html->link($title, $url, $options);
    }
}

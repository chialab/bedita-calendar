<?php
declare(strict_types=1);

namespace Chialab\Calendar\View\Helper;

use BEdita\Core\Model\Entity\Category;
use BEdita\Core\Model\Entity\Tag;
use Cake\I18n\FrozenTime;
use Cake\Utility\Hash;
use Chialab\Calendar\Controller\Component\CalendarComponent;
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
     * Get view calendar filter value.
     *
     * @param string $path Filter key.
     * @return mixed
     */
    public function getFilter(string $path)
    {
        $params = $this->_View->get(CalendarComponent::VIEW_PARAMS);

        return Hash::get($params, $path);
    }

    /**
     * Get view calendar filter params.
     *
     * @return array
     */
    public function getFilterParams()
    {
        $params = $this->_View->get(CalendarComponent::VIEW_PARAMS);

        return Hash::get($params, 'params');
    }

    /**
     * Get view calendar filter param name.
     *
     * @param string $name Filter name.
     * @return string The param name.
     */
    public function getFilterParam(string $name): string
    {
        return Hash::get($this->getFilterParams(), $name);
    }

    /**
     * Get the calendar range filter.
     *
     * @return array
     */
    public function getRange(): array
    {
        return $this->getFilter('range');
    }

    /**
     * Get the calendar range start date.
     *
     * @return \Cake\I18n\FrozenTime
     */
    public function getStartDate(): FrozenTime
    {
        return $this->getRange()[0];
    }

    /**
     * Get the calendar range end date.
     *
     * @return \Cake\I18n\FrozenTime|null
     */
    public function getEndDate(): ?FrozenTime
    {
        return $this->getRange()[1];
    }

    /**
     * Get the search text filter.
     *
     * @return string|null
     */
    public function getSearchText(): ?string
    {
        return $this->getFilter('search');
    }

    /**
     * Get the categories list filter.
     *
     * @return string|null
     */
    public function getCategories(): array
    {
        return $this->getFilter('categories');
    }

    /**
     * Get the request tags list filter.
     *
     * @return string|null
     */
    public function getTags(): array
    {
        return $this->getFilter('tags');
    }

    /**
     * Generate url query params for calendar filter.
     *
     * @param mixed $date The absolute or relative date.
     * @return array List of query params.
     */
    public function getFilterQuery($date): array
    {
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
            $this->getFilterParam('date') => $formatDate($date),
            $this->getFilterParam('search') => $this->getSearchText(),
            $this->getFilterParam('categories') => $this->getCategories(),
            $this->getFilterParam('tags') => $this->getTags(),
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
            'date-param' => $this->getFilterParam('date'),
            'day-param' => $this->getFilterParam('day'),
            'month-param' => $this->getFilterParam('month'),
            'year-param' => $this->getFilterParam('year'),
            'categories-param' => $this->getFilterParam('categories'),
            'tags-param' => $this->getFilterParam('tags'),
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
    public function searchControl(?array $options = null): string
    {
        $options = $options ?? [];

        return $this->Form->text($this->getFilterParam('search'), [
            'value' => $this->getSearchText(),
        ] + $options);
    }

    /**
     * Generate a <select> element for days.
     *
     * @param array|null $options Options for the select element.
     * @return string The <select> element.
     */
    public function daysControl(?array $options = null): string
    {
        $options = $options ?? [];
        $date = $this->getStartDate();

        return $this->Form->control($this->getFilterParam('day'), [
            'label' => false,
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
    public function monthsControl(?array $options = null): string
    {
        $options = $options ?? [];

        return $this->Form->control($this->getFilterParam('month'), [
            'label' => '',
            'type' => 'select',
            'options' => $this->getMonths(),
            'value' => $this->getStartDate()->month,
        ] + $options);
    }

    /**
     * Generate a <select> element for years.
     *
     * @param array|null $options Options for the select element.
     * @return string The <select> element.
     */
    public function yearsControl(?array $options = null, $start = '-2 years', $end = '+2 years'): string
    {
        $options = $options ?? [];

        return $this->Form->control($this->getFilterParam('year'), [
            'label' => '',
            'type' => 'select',
            'options' => $this->getYears($start, $end),
            'value' => $this->getStartDate()->year,
        ] + $options);
    }

    /**
     * Generate a checkbox control for category filtering.
     *
     * @param \BEdita\Core\Model\Entity\Category $category The category entity.
     * @param array|null $options Options for the checkbox element.
     * @return string The checkbox element.
     */
    public function categoryControl(Category $category, ?array $options = null): string
    {
        $options = $options ?? [];

        return $this->Form->control($this->getFilterParam('categories') . '[]', [
            'id' => sprintf('filter-category-%s', $category->name),
            'type' => 'checkbox',
            'label' => $category->label ?? $category->name,
            'value' => $category->name,
            'checked' => in_array($category->name, $this->getFilter('categories') ?? []),
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
    public function tagControl(Tag $tag, ?array $options = null): string
    {
        $options = $options ?? [];

        return $this->Form->control($this->getFilterParam('tags') . '[]', [
            'id' => sprintf('filter-tag-%s', $tag->name),
            'type' => 'checkbox',
            'label' => $tag->label ?? $tag->name,
            'value' => $tag->name,
            'checked' => in_array($tag->name, $this->getFilter('tags') ?? []),
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
    public function rangeControl(array $ranges, ?array $options = null, ?array $attrs = null): string
    {
        $options = $options ?? [];
        $attrs = $attrs ?? [];
        $ranges = Hash::normalize($ranges);

        return $this->Form->control($this->getFilterParam('date'), [
            'type' => 'radio',
            'options' => $ranges,
            'value' => $this->getFilter('date'),
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
        $url = $this->_View->getRequest()->url;
        $query = $this->_View->getRequest()->getQueryParams();
        $params = [];
        foreach ($this->getFilterParams() as $param) {
            $params[$param] = null;
        }

        $query = array_diff_key($query, $params);
        if (!empty($query)) {
            $url = sprintf('%s?%s', $url, http_build_query($query));
        }

        return $this->Html->link($title, $url, $options);
    }
}

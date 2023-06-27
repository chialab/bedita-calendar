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
     * @inheritDoc
     */
    public $helpers = ['Form', 'Html', 'Url'];

    /**
     * @inheritDoc
     */
    protected $_defaultConfig = [
        'params' => [
            'day' => 'day',
            'month' => 'month',
            'year' => 'year',
        ],
    ];

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
    public function getFilter(string $path): mixed
    {
        $params = $this->_View->get(CalendarComponent::VIEW_PARAMS);

        return Hash::get($params, $path);
    }

    /**
     * Get view calendar filter params.
     *
     * @return array
     */
    public function getFilterParams(): array
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
     * Get the calendar range start date.
     *
     * @return \Cake\I18n\FrozenTime
     */
    public function getStartDate(): FrozenTime
    {
        return $this->getFilter('computed')[0];
    }

    /**
     * Get the calendar range end date.
     *
     * @return \Cake\I18n\FrozenTime|null
     */
    public function getEndDate(): FrozenTime|null
    {
        return $this->getFilter('computed')[1];
    }

    /**
     * Generate url query params for calendar filter.
     *
     * @param array $filters The filters to set.
     * @param bool|null $keepActive Should preserve active filters.
     * @return array List of query params.
     */
    public function generateFilters(array $filters, bool|null $keepActive = true): array
    {
        if ($keepActive) {
            $filters += array_filter([
                'categories' => $this->getFilter('categories'),
                'tags' => $this->getFilter('tags'),
                'search' => $this->getFilter('search'),
            ]);

            if (empty($filters['range'])) {
                $filters += array_filter([
                    'range' => $this->getFilter('range'),
                ]);
            }
        }

        $formatDate = function ($date) use (&$formatDate) {
            if (is_array($date)) {
                return array_map($formatDate, $date);
            }

            if ($date instanceof DateTimeInterface) {
                return $date->format('Y-m-d');
            }

            return $date;
        };

        $query = [];
        foreach ($filters as $key => $value) {
            switch ($key) {
                case 'range':
                    $value = $formatDate($value);
                    break;
            }
            $query[$this->getFilterParam($key)] = $value;
        }

        return $query;
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
    public function createFiltersForm(mixed $context = null, array|null $options = null): string
    {
        $options = $options ?? [];

        return $this->Form->create($context, $options + [
            'type' => 'GET',
            'is' => 'calendar-filters',
            'range-param' => $this->getFilterParam('range'),
            'categories-param' => $this->getFilterParam('categories'),
            'tags-param' => $this->getFilterParam('tags'),
            'search-param' => $this->getFilterParam('search'),
            'day-param' => $this->getConfig('params.day'),
            'month-param' => $this->getConfig('params.month'),
            'year-param' => $this->getConfig('params.year'),
        ]);
    }

    /**
     * Closes the filters form.
     *
     * @return string A closing FORM tag.
     */
    public function closeFiltersForm(): string
    {
        return $this->Form->end();
    }

    /**
     * Generate a <input> element for search.
     *
     * @param array|null $options Options for the input element.
     * @return string The <input> element.
     */
    public function searchControl(array|null $options = null): string
    {
        $options = $options ?? [];

        return $this->Form->text($this->getFilterParam('search'), [
            'value' => $this->getFilter('search'),
        ] + $options);
    }

    /**
     * Generate a <select> element for days.
     *
     * @param array|null $options Options for the select element.
     * @return string The <select> element.
     */
    public function dayControl(array|null $options = null): string
    {
        $options = $options ?? [];
        $date = $this->getStartDate() ?? FrozenTime::now();

        return $this->Form->control($this->getConfig('params.day'), [
            'label' => false,
            'type' => 'select',
            'form' => '',
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
    public function monthControl(array|null $options = null): string
    {
        $options = $options ?? [];
        $date = $this->getStartDate() ?? FrozenTime::now();

        return $this->Form->control($this->getConfig('params.month'), [
            'label' => '',
            'type' => 'select',
            'form' => '',
            'options' => $this->getMonths(),
            'value' => $date->month,
        ] + $options);
    }

    /**
     * Generate a <select> element for years.
     *
     * @param array|null $options Options for the select element.
     * @param string|int|null $start The range start year.
     * @param string|int|null $end The range end year.
     * @return string The <select> element.
     */
    public function yearControl(array|null $options = null, int|string|null $start = '-2 years', int|string|null $end = '+2 years'): string
    {
        $options = $options ?? [];
        $date = $this->getStartDate() ?? FrozenTime::now();

        if (is_string($start)) {
            $start = FrozenTime::now()->modify($start)->year;
        }
        if (is_string($end)) {
            $end = FrozenTime::now()->modify($end)->year;
        }

        $years = range($start, $end);
        $yearsOptions = array_combine($years, $years);
        if (!in_array($date->year, $years)) {
            $yearsOptions[$date->year] = $date->year;
            ksort($yearsOptions);
        }

        $hidden = $this->Form->control($this->getFilterParam('range') . '[]', [
            'type' => 'hidden',
            'form' => is_array($this->getFilter('range')) ? null : '',
            'value' => is_array($this->getFilter('range')) ? $date->format('Y-m-d') : '',
        ]);

        return $hidden . $this->Form->control($this->getConfig('params.year'), [
            'label' => '',
            'type' => 'select',
            'form' => '',
            'options' => $yearsOptions,
            'value' => $date->year,
        ] + $options);
    }

    /**
     * Generate a checkbox control for category filtering.
     *
     * @param string $label The control label.
     * @param \BEdita\Core\Model\Entity\Category $category The category entity.
     * @param array|null $options Options for the checkbox element.
     * @return string The checkbox element.
     */
    public function categoryControl(string $label, Category $category, array|null $options = null): string
    {
        $options = $options ?? [];

        return $this->Form->control($this->getFilterParam('categories') . '[]', [
            'id' => sprintf('filter-category-%s', $category->name),
            'type' => 'checkbox',
            'label' => $label,
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
     * @param string $label The control label.
     * @param \BEdita\Core\Model\Entity\Tag $tag The tag entity.
     * @param array|null $options Options for the checkbox element.
     * @return string The checkbox element.
     */
    public function tagControl(string $label, Tag $tag, array|null $options = null): string
    {
        $options = $options ?? [];

        return $this->Form->control($this->getFilterParam('tags') . '[]', [
            'id' => sprintf('filter-tag-%s', $tag->name),
            'type' => 'checkbox',
            'label' => $label,
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
    public function rangeControl(array $ranges, array|null $options = null, array|null $attrs = null): string
    {
        $options = $options ?? [];
        $attrs = $attrs ?? [];
        $ranges = Hash::normalize($ranges);
        $range = $this->getFilter('range');

        return $this->Form->control($this->getFilterParam('range'), [
            'type' => 'radio',
            'options' => $ranges,
            'value' => is_string($range) ? $range : null,
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
     * @param string $label The label of the link.
     * @param array|null $options Options for the link element.
     * @return string The <a> element.
     */
    public function resetControl(string $label, array|null $options = null): string
    {
        $url = $this->_View->getRequest()->getPath();
        $query = $this->_View->getRequest()->getQueryParams();
        $params = [];
        foreach ($this->getFilterParams() as $param) {
            $params[$param] = null;
        }

        $query = array_diff_key($query, $params);
        if (!empty($query)) {
            $url = sprintf('%s?%s', $url, http_build_query($query));
        }

        return $this->Html->link($label, $url, $options);
    }
}

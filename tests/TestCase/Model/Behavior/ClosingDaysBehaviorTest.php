<?php
declare(strict_types=1);

namespace Chialab\Calendar\Test\TestCase\Model\Behavior;

use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Collection\Collection;
use Cake\TestSuite\TestCase;
use Chialab\Calendar\Model\Behavior\ClosingDaysBehavior;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ClosingDaysBehavior::class)]
class ClosingDaysBehaviorTest extends TestCase
{
    protected array $fixtures = [
        'plugin.BEdita/Core.ObjectTypes',
        'plugin.BEdita/Core.PropertyTypes',
        'plugin.Chialab/Calendar.Properties',
        'plugin.Chialab/Calendar.Relations',
        'plugin.Chialab/Calendar.RelationTypes',
        'plugin.Chialab/Calendar.Objects',
        'plugin.Chialab/Calendar.DateRanges',
    ];

    protected function format(array $objects): array
    {
        $table = $this->getTableLocator()->get('Events');
        $table->addBehavior('Chialab/Calendar.ClosingDays');
        $query = $table->find('closingDays');

        $results = new Collection($objects);
        foreach ($query->getResultFormatters() as $formatter) {
            $results = $formatter($results, $query);
        }

        return $results->toList();
    }

    // CakePHP 5 `has()` is true for null fields: null values must keep the CakePHP 4 behavior.
    public function testNullFields(): void
    {
        [$nullRanges, $nullFiltered, $emptyRanges] = $this->format([
            new ObjectEntity(['date_ranges' => null]),
            new ObjectEntity(['date_ranges' => [], 'filtered_date_ranges' => null]),
            new ObjectEntity(['date_ranges' => []]),
        ]);

        $this->assertFalse($nullRanges->has('filtered_date_ranges'));
        $this->assertSame([], $nullFiltered->get('filtered_date_ranges')->toList());
        $this->assertSame([], $emptyRanges->get('filtered_date_ranges')->toList());
    }
}

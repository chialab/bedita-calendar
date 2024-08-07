<?php
declare(strict_types=1);

namespace Chialab\Calendar\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;

/**
 * {@see \Chialab\Calendar\Controller\Component\CalendarComponent} Test Case
 *
 * @coversDefaultClass \Chialab\Calendar\Controller\Component\CalendarComponent
 */
class CalendarComponentTest extends TestCase
{
    public $fixtures = [
        'plugin.BEdita/Core.ObjectTypes',
        'plugin.BEdita/Core.PropertyTypes',
        'plugin.Chialab/Calendar.Properties',
        'plugin.Chialab/Calendar.Relations',
        'plugin.Chialab/Calendar.RelationTypes',
        'plugin.Chialab/Calendar.Objects',
        'plugin.Chialab/Calendar.Users',
        'plugin.Chialab/Calendar.Media',
        'plugin.Chialab/Calendar.Streams',
        'plugin.Chialab/Calendar.Profiles',
        'plugin.Chialab/Calendar.Trees',
        'plugin.Chialab/Calendar.ObjectRelations',
        'plugin.Chialab/Calendar.DateRanges',
        'plugin.BEdita/Core.Categories',
        'plugin.BEdita/Core.ObjectCategories',
        'plugin.BEdita/Core.Tags',
        'plugin.BEdita/Core.ObjectTags',
    ];

    /**
     * Objects component
     *
     * @var \Chialab\FrontendKit\Controller\Component\ObjectsComponent
     */
    public $Objects;

    /**
     * Test subject
     *
     * @var \Chialab\Calendar\Controller\Component\CalendarComponent
     */
    public $Calendar;

    /**
     * The request controller.
     *
     * @var \Cake\Controller\Controller
     */
    public $controller;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $request = new ServerRequest();
        $response = new Response();
        /** @var \Cake\Controller\Controller $controller */
        $this->controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setConstructorArgs([$request, $response])
            ->setMethods(null)
            ->getMock();

        $this->controller->viewBuilder()->setTemplatePath('Pages');

        // Add behavior, since the test app does not
        $table = $this->getTableLocator()->get('Events');
        $table->addBehavior('Chialab/Calendar.ClosingDays');

        $registry = new ComponentRegistry($this->controller);
        $this->Objects = $registry->load('Chialab/FrontendKit.Objects');
        $this->Calendar = $registry->load('Chialab/Calendar.Calendar');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Calendar, $this->Objects, $this->controller);

        parent::tearDown();
    }

    /**
     * Data provider for {@see CalendarComponentTest::testFindGroupByDayWithStart()} test case.
     *
     * @return array[]
     */
    public function findGroupByDayWithStartProvider()
    {
        return [
            'test' => [
                [
                    '2022-02-15' => [
                        'event-2',
                    ],
                    '2022-02-16' => [
                        'event-2',
                        'event-3',
                    ],
                    '2022-02-17' => [
                        'event-2',
                        'event-3',
                        'event-1',
                    ],
                    '2022-02-18' => [
                        'event-3',
                    ],
                    '2022-02-21' => [
                        'event-3',
                    ],
                    '2022-02-22' => [
                        'event-3',
                    ],
                ],
                '2022-02-15 00:00:00',
            ],
        ];
    }

    /**
     * Test {@see CalendarComponent::findGroupedByDay()}.
     *
     * @param array $expected Expected objects.
     * @param string $start Start date.
     * @return void
     * @covers ::findGroupedByDay()
     * @dataProvider findGroupByDayWithStartProvider()
     */
    public function testFindGroupByDayWithStart(array $expected, $start)
    {
        $events = $this->Calendar->findGroupedByDay(
            $this->Objects->loadObjects([], 'events'),
            new FrozenTime($start)
        )->toArray();

        $actual = array_map(fn ($items) => array_map(fn ($event) => $event->uname, $items), $events);

        static::assertSame($expected, $actual);
    }

    /**
     * Data provider for {@see CalendarComponentTest::testFindGroupedByDayWithRange()} test case.
     *
     * @return array[]
     */
    public function findGroupedByDayWithRangeProvider()
    {
        return [
            'test' => [
                [
                    '2022-02-15' => [
                        'event-2',
                    ],
                    '2022-02-16' => [
                        'event-2',
                        'event-3',
                    ],
                    '2022-02-17' => [
                        'event-2',
                        'event-3',
                    ],
                ],
                '2022-02-15 00:00:00',
                '2022-02-17 00:00:00',
            ],
        ];
    }

    /**
     * Test {@see CalendarComponent::findGroupedByDay()}.
     *
     * @param array $expected Expected objects.
     * @param string $start Start date.
     * @param string $end End date.
     * @return void
     * @covers ::findGroupedByDay()
     * @dataProvider findGroupedByDayWithRangeProvider()
     */
    public function testFindGroupedByDayWithRange(array $expected, string $start, string $end)
    {
        $events = $this->Calendar->findGroupedByDay(
            $this->Objects->loadObjects([], 'events'),
            new FrozenTime($start),
            new FrozenTime($end)
        )->toArray();

        static::assertSame($expected, array_map(fn ($items) => array_map(fn ($event) => $event->uname, $items), $events));
    }
}

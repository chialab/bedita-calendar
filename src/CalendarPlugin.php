<?php
declare(strict_types=1);

namespace Chialab\Calendar;

use ArrayObject;
use BEdita\Core\Model\Table\ObjectsBaseTable;
use BEdita\Core\Model\Table\ObjectsTable;
use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\ORM\Query\SelectQuery;

/**
 * Plugin for Chialab\Calendar
 */
class CalendarPlugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        EventManager::instance()
            ->on('Model.initialize', function (EventInterface $event): void {
                $table = $event->getSubject();
                if (!$table instanceof ObjectsTable && !$table instanceof ObjectsBaseTable) {
                    return;
                }

                $table->addBehavior('Chialab/Calendar.ClosingDays');
                $table->getEventManager()
                    ->on(
                        'Model.beforeFind',
                        fn(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): SelectQuery => $primary ? $query->find('closingDays') : $query,
                    );
            });
    }
}

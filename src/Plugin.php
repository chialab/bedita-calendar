<?php
declare(strict_types=1);

namespace Chialab\Calendar;

use ArrayObject;
use BEdita\Core\Model\Table\ObjectsBaseTable;
use BEdita\Core\Model\Table\ObjectsTable;
use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\Query;

/**
 * Plugin for Chialab\Calendar
 */
class Plugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function bootstrap(PluginApplicationInterface $app)
    {
        parent::bootstrap($app);

        EventManager::instance()
            ->on('Model.initialize', function (Event $event): void {
                $table = $event->getSubject();
                if (!$table instanceof ObjectsTable && !$table instanceof ObjectsBaseTable) {
                    return;
                }

                $table->addBehavior('Chialab/Calendar.ClosingDays');
                $table->getEventManager()
                    ->on(
                        'Model.beforeFind',
                        fn (Event $event, Query $query, ArrayObject $options, bool $primary): Query => $primary ? $query->find('closingDays') : $query,
                    );
            });
    }
}

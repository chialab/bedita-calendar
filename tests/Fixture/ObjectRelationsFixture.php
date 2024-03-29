<?php
declare(strict_types=1);

namespace Chialab\Calendar\Test\Fixture;

use BEdita\Core\Test\Fixture\ObjectRelationsFixture as BEObjectRelationsFixture;

/**
 * ObjectRelations test fixture.
 */
class ObjectRelationsFixture extends BEObjectRelationsFixture
{
    public $records = [
        // 1
        [
            'left_id' => 10,
            'relation_id' => 1,
            'right_id' => 11,
            'priority' => 1,
            'inv_priority' => 2,
            'params' => '',
        ],
        // 2
        [
            'left_id' => 10,
            'relation_id' => 2,
            'right_id' => 12,
            'priority' => 1,
            'inv_priority' => 2,
            'params' => '',
        ],
    ];
}

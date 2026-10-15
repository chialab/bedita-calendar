<?php
declare(strict_types=1);

namespace Chialab\Calendar\Test\Fixture;

use BEdita\Core\Test\Fixture\TreesFixture as BETreesFixture;

/**
 * Trees test fixture.
 */
class TreesFixture extends BETreesFixture
{
    public function init(): void
    {
        $trees = [
            2 => [
                4 => [
                    8 => [
                        10 => [],
                        12 => [],
                    ],
                    9 => [],
                ],
                5 => [],
            ],
            3 => [
                6 => [],
                7 => [],
            ],
        ];

        $this->transformBranch($trees, $records);
        $this->records = $records;

        parent::init();
    }

    private function transformBranch($branch, &$records = null, $parent = null, &$count = 0)
    {
        if ($records === null) {
            $records = [];
        }

        $children = [];
        $parentIndex = $count;
        $priority = 0;
        foreach ($branch as $id => $child) {
            $index = $count++;
            $entry = [
                'object_id' => $id,
                'parent_id' => $parent ? $parent['object_id'] : null,
                'root_id' => $parent ? $parent['root_id'] : $id,
                'parent_node_id' => $parent ? $parentIndex : null,
                'priority' => ++$priority,
                'menu' => 1,
                'canonical' => 0,
            ];

            $records[] = $entry;

            if (!empty($branch)) {
                $this->transformBranch($child, $records, $entry, $count);
            }

            $children[] = $entry;
        }

        return $children;
    }
}

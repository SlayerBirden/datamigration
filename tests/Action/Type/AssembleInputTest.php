<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;

class AssembleInputTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCode()
    {
        $action = new AssembleInput(
            $this->getUnitBag([$this->getUnit('table1')]),
            $this->getConfig(),
            $this->getFS(),
            $this->getInputResource([]),
            $this->getMap([]),
            $this->getResourceHelper()
        );
        $this->assertEquals('assemble_input', $action->getCode());
    }

    /**
     * @param array $data
     * @return MapInterface
     */
    protected function getMap($data)
    {
        $map = $this->getMockBuilder('\Maketok\DataMigration\MapInterface')
            ->getMock();
        if (count($data)) {
            $map->expects($this->any())
                ->method('dumpState')
                ->willReturnOnConsecutiveCalls($data[0], $data[1], $data[2]);
        }
        return $map;
    }

    /**
     * @return ResourceHelperInterface
     */
    protected function getResourceHelper()
    {
        $rh = $this->getMockBuilder('\Maketok\DataMigration\Storage\Db\ResourceHelperInterface')
            ->getMock();
        return $rh;
    }

    /**
     * @param $name
     * @return AbstractUnit
     */
    protected function getUnit($name)
    {
        /** @var AbstractUnit $unit */
        $unit = $this->getMockBuilder('\Maketok\DataMigration\Unit\AbstractUnit')
            ->getMockForAbstractClass();
        return $unit->setTable($name);
    }

    /**
     * @param AbstractUnit[] $units
     * @return UnitBagInterface
     */
    protected function getUnitBag($units)
    {
        $unitBag = $this->getMockBuilder('\Maketok\DataMigration\Unit\UnitBagInterface')
            ->getMock();
        $unitBag->expects($this->any())->method('add')->willReturnSelf();
        $unitBag->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($units));
        return $unitBag;
    }

    /**
     * @param array $data
     * @return InputResourceInterface
     */
    protected function getInputResource(array $data)
    {
        $input = $this->getMockBuilder('\Maketok\DataMigration\Input\InputResourceInterface')
            ->getMock();
        if (count($data)) {
            $input->expects($this->exactly(2))->method('add')->withConsecutive($data[0], $data[1]);
        }
        return $input;
    }

    /**
     * @return ConfigInterface
     */
    protected function getConfig()
    {
        $config = $this->getMockBuilder('\Maketok\DataMigration\Action\ConfigInterface')
            ->getMock();
        $config->expects($this->any())->method('get')->willReturnMap([
            ['tmp_folder', '/tmp'],
            ['tmp_file_mask', '%1$s.csv'], // fname, date
        ]);
        return $config;
    }

    /**
     * @param bool $expect
     * @return ResourceInterface
     */
    protected function getFS($expect = false)
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        if ($expect) {
            $filesystem->expects($this->exactly(2))
                ->method('open');
            $filesystem->expects($this->exactly(3))
                ->method('readRow')
                ->willReturnOnConsecutiveCalls(
                    ['1', 'otherField', '1'],
                    ['2', 'otherField2', '1'],
                    ['3', 'someField2', '2']
                );
            $filesystem->expects($this->exactly(2))
                ->method('close');
        }
        return $filesystem;
    }

    public function testProcess()
    {
        $expected = [
            ['id' => '1', 'name' => null, 'code' => 'otherField'],
            ['id' => '2', 'name' => 'someField2', 'code' => 'otherField2'],
        ];
        $unit1 = $this->getUnit('entity_table1');
        $unit1->setMapping([
            'entity_id' => 'id',
            'code' => 'code',
            'const' => '1',
        ])->setIsEntityCondition(function () {
            return true;
        })->setReversedMapping([
            'id' => 'entity_id',
            'code' => 'code',
        ]);
        $unit2 = $this->getUnit('data_table1');
        $counter = new \stdClass();
        $counter->count = 2;
        $unit2->setMapping([
            'new_id' => function ($counter) {
                return ++$counter->count;
            },
            'name' => 'name',
            'parent_id' => 'id',
        ])->setIsEntityCondition(function (MapInterface $map, ResourceHelperInterface $rh, array $row) {
            return $row['id'] == 2;
        })->setReversedMapping([
            'name' => 'name',
            'id' => 'parent_id',
        ]);

        $action = new AssembleInput(
            $this->getUnitBag([$unit1, $unit2]),
            $this->getConfig(),
            $this->getFS(true),
            $this->getInputResource($expected),
            $this->getMap([]),
            $this->getResourceHelper()
        );
        $action->process();
    }
}

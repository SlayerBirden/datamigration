<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Storage\Db\ResourceInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface as FsResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;

class ReverseMoveTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCode()
    {
        $action = new ReverseMove(
            $this->getUnitBag([$this->getUnit()]),
            $this->getConfig(),
            $this->getFS(),
            $this->getResource()
        );
        $this->assertEquals('reverse_move', $action->getCode());
    }

    /**
     * @return ConfigInterface
     */
    protected function getConfig()
    {
        $config = $this->getMockBuilder('\Maketok\DataMigration\Action\ConfigInterface')
            ->getMock();
        $config->expects($this->any())->method('get')->willReturnMap([
            ['tmp_table_mask', 'tmp_%1$s%2$s'], // fname, microtime
        ]);
        return $config;
    }

    /**
     * @return AbstractUnit
     */
    protected function getUnit()
    {
        /** @var AbstractUnit $unit */
        $unit = $this->getMockBuilder('\Maketok\DataMigration\Unit\AbstractUnit')
            ->getMockForAbstractClass();
        $unit->setTable('test_table1');
        $unit->setTmpFileName('test_table1.csv');
        $unit->setTmpTable('tmp_test_table1');
        return $unit;
    }

    /**
     * @param array $units
     * @return UnitBagInterface
     */
    protected function getUnitBag(array $units)
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
     * @param bool $expects
     * @return ResourceInterface
     */
    protected function getResource($expects = false)
    {
        $resource = $this->getMockBuilder('\Maketok\DataMigration\Storage\Db\ResourceInterface')
            ->getMock();
        if ($expects) {
            $resource->expects($this->atLeastOnce())
                ->method('move');
        }
        return $resource;
    }

    /**
     * @return FsResourceInterface
     */
    protected function getFS()
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        return $filesystem;
    }

    public function testProcess()
    {
        $unit = $this->getUnit();
        $action = new ReverseMove(
            $this->getUnitBag([$unit]),
            $this->getConfig(),
            $this->getFS(),
            $this->getResource(true)
        );
        $action->process();

        $this->assertNotEmpty($unit->getTmpTable());
    }
}

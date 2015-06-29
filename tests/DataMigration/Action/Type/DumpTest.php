<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Storage\Db\ResourceInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface as FsResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;

class DumpTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCode()
    {
        $action = new Dump(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getFS(),
            $this->getResource()
        );
        $this->assertEquals('dump', $action->getCode());
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
     * @return AbstractUnit
     */
    protected function getUnit()
    {
        /** @var AbstractUnit $unit */
        $unit = $this->getMockBuilder('\Maketok\DataMigration\Unit\AbstractUnit')
            ->getMockForAbstractClass();
        $unit->setTable('test_table1');
        return $unit;
    }

    /**
     * @return UnitBagInterface
     */
    protected function getUnitBag()
    {
        $unitBag = $this->getMockBuilder('\Maketok\DataMigration\Unit\UnitBagInterface')
            ->getMock();
        $unitBag->expects($this->any())->method('add')->willReturnSelf();
        $unitBag->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->getUnit()]));
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
                ->method('dumpData')
                ->willReturn([
                    [1, 'value1'],
                    [2, 'value2'],
                ]);
        }
        return $resource;
    }

    /**
     * @param bool $expects
     * @return FsResourceInterface
     */
    protected function getFS($expects = false)
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        if ($expects) {
            $filesystem->expects($this->exactly(2))
                ->method('writeRow');
        }
        return $filesystem;
    }

    public function testProcess()
    {
        $action = new Dump(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getFS(true),
            $this->getResource(true)
        );
        $action->process();

        //assert name is assigned to unit
        $this->assertEquals('/tmp/test_table1.csv',
            $this->getUnit()->getTmpFileName());
    }
}

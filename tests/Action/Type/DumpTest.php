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
            ['dump_limit', '10000'],
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
        $unit->setTable('test_table1')
            ->setTmpTable('tmp_test_table1')
            ->setMapping([]);
        return $unit;
    }

    /**
     * @param array $units
     * @return UnitBagInterface
     */
    protected function getUnitBag(array $units = [])
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
     * @param array $returns
     * @return ResourceInterface
     */
    protected function getResource($returns = [])
    {
        $resource = $this->getMockBuilder('\Maketok\DataMigration\Storage\Db\ResourceInterface')
            ->getMock();
        $method = $resource->expects($this->atLeastOnce())
            ->method('dumpData');
        call_user_func_array([$method, 'willReturnOnConsecutiveCalls'], $returns);
        return $resource;
    }

    /**
     * @param int $expects
     * @return FsResourceInterface
     */
    protected function getFS($expects = 0)
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        $filesystem->expects($this->exactly($expects))
            ->method('writeRow');
        return $filesystem;
    }

    public function testProcess()
    {
        $unit = $this->getUnit();
        $action = new Dump(
            $this->getUnitBag([$unit]),
            $this->getConfig(),
            $this->getResource([])
        );
        $action->process();

        $this->assertEquals('/tmp/test_table1.csv',
            $unit->getTmpFileName());
    }
}

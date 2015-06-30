<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface as FsResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;

class GenerateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractUnit
     */
    private $unit;

    public function testGetCode()
    {
        $action = new Generate(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getFS(),
            2
        );
        $this->assertEquals('generate', $action->getCode());
    }

    /**
     * @return AbstractUnit
     */
    protected function getUnit()
    {
        if (is_null($this->unit)) {
            /** @var AbstractUnit $unit */
            $this->unit = $this->getMockBuilder('\Maketok\DataMigration\Unit\AbstractUnit')
                ->getMockForAbstractClass();
            $this->unit->setTable('test_table1');
        }
        return $this->unit;
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
     * @return FsResourceInterface
     */
    protected function getFS($expect = false)
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        if ($expect) {
            $filesystem->expects($this->once())
                ->method('open')
                ->with($this->equalTo('/tmp/test_table1.csv'));
            $filesystem->expects($this->exactly(2))->method('writeRow');
            $filesystem->expects($this->once())->method('close');
        }
        return $filesystem;
    }

    public function testProcess()
    {
        $action = new Generate(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getFS(true),
            2
        );
        $action->process();
    }
}

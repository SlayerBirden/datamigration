<?php

namespace Maketok\DataMigration\Action\Type;

use Faker\Generator;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface as FsResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;

class GenerateTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCode()
    {
        $action = new Generate(
            $this->getUnitBag([$this->getUnit()]),
            $this->getConfig(),
            $this->getFS(),
            new Generator(),
            2
        );
        $this->assertEquals('generate', $action->getCode());
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
            ->setMapping([])
            ->setGeneratorMapping([]);
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
        $unit = $this->getUnit();
        $action = new Generate(
            $this->getUnitBag([$unit]),
            $this->getConfig(),
            $this->getFS(true),
            new Generator(),
            2
        );
        $action->process();

        $this->assertEquals('/tmp/test_table1.csv',
            $unit->getTmpFileName());
    }
}
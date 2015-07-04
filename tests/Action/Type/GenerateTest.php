<?php

namespace Maketok\DataMigration\Action\Type;

use Faker\Generator;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;

class GenerateTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCode()
    {
        $action = new Generate(
            $this->getUnitBag(),
            $this->getConfig(),
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

    public function testProcess()
    {
        $unit = $this->getUnit();
        $action = new Generate(
            $this->getUnitBag([$unit]),
            $this->getConfig(),
            new Generator(),
            2
        );
        $action->process();

        $this->assertEquals('/tmp/test_table1.csv',
            $unit->getTmpFileName());
    }

    public function testGetRandom()
    {
        $action = new Generate(
            $this->getUnitBag([$this->getUnit()]),
            $this->getConfig(),
            new Generator(),
            1
        );
        // distribution 1...40 with peak at 10;
        //            o
        //         o      o
        //       o          o
        //     o |          |   o
        //  o    |    50%   |       o
        //       |          |               o
        //0           10          20          30          40
        $numbers = [];
        $count = 100000;
        for ($i = 0; $i < $count; $i++) {
            $rnd = $action->getRandom(40, 10);
            if (isset($numbers[$rnd])) {
                $numbers[$rnd]++;
            } else {
                $numbers[$rnd] = 1;
            }
        }
        $percentage = [];
        foreach ($numbers as $numb => $cnt) {
            $percentage[$numb] = $cnt/$count*100;
        }
        // statistics
        $centerZone = 0;
        foreach (range(5, 15) as $indx) {
            $centerZone += $percentage[$indx];
        }

        $this->assertGreaterThan(45, $centerZone);
    }
}

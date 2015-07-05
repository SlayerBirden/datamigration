<?php

namespace Maketok\DataMigration\Action\Type;

use Faker\Generator;

class GenerateTest extends \PHPUnit_Framework_TestCase
{
    use ServiceGetterTrait;

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

    public function testProcess()
    {
        $unit = $this->getUnit('test_table1');
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
            $this->getUnitBag(),
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

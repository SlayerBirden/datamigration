<?php

namespace Maketok\DataMigration\Action\Type;

use Faker\Generator;
use Maketok\DataMigration\Expression\LanguageAdapter;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\Type\GeneratorUnit;

class GenerateTest extends \PHPUnit_Framework_TestCase
{
    use ServiceGetterTrait;

    /**
     * @var Generate
     */
    protected $action;

    public function setUp()
    {
        $this->action = new Generate(
            $this->getUnitBag(),
            $this->getConfig(),
            new LanguageAdapter(),
            new Generator(),
            2
        );
    }

    /**
     * @param string $code
     * @return GeneratorUnit
     */
    public function getUnit($code)
    {
        return new GeneratorUnit($code);
    }

    public function testGetCode()
    {
        $this->assertEquals('generate', $this->action->getCode());
    }

    /**
     * @param int $expects
     * @return ResourceInterface
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
        $unit = $this->getUnit('test_table1');
        $unit->setFilesystem($this->getFS(2));
        $action = new Generate(
            $this->getUnitBag([$unit]),
            $this->getConfig(),
            new LanguageAdapter(),
            new Generator(),
            2
        );
        $action->process();

        $this->assertEquals(
            '/tmp/test_table1.csv',
            $unit->getTmpFileName()
        );
    }

    public function testGetRandom()
    {
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
            $rnd = $this->action->getRandom(40, 10);
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

        $this->assertGreaterThan(50, $centerZone); // actual value is around 56% (+1 range)
    }
}

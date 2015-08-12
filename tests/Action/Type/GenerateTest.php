<?php

namespace Maketok\DataMigration\Action\Type;

use Faker\Generator;
use Faker\Provider\Base;
use Faker\Provider\Lorem;
use Maketok\DataMigration\ArrayMap;
use Maketok\DataMigration\Expression\LanguageAdapter;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\Type\Unit;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

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
            2,
            new ArrayMap(),
            $this->getResourceHelper()
        );
    }

    /**
     * @param string $code
     * @return Unit
     */
    public function getUnit($code)
    {
        $unit = new Unit($code);
        $arr = \SplFixedArray::fromArray([1,1]);
        $unit->setGenerationSeed($arr);
        return $unit;
    }

    /**
     * @return ResourceHelperInterface
     */
    protected function getResourceHelper()
    {
        $helper = $this->getMockBuilder('\Maketok\DataMigration\Storage\Db\ResourceHelperInterface')
            ->getMock();
        return $helper;
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
        $unit->setGeneratorMapping([
            'id' => 'generator.randomDigit',
            'code' => function (Generator $generator) {
                return $generator->word;
            }
        ]);
        $generator = new Generator();
        $generator->addProvider(new Base($generator));
        $generator->addProvider(new Lorem($generator));
        $action = new Generate(
            $this->getUnitBag([$unit]),
            $this->getConfig(),
            new LanguageAdapter(new ExpressionLanguage()),
            $generator,
            2,
            new ArrayMap(),
            $this->getResourceHelper()
        );
        $action->process($this->getResultMock());

        $this->assertEquals(
            '/tmp/test_table1.csv',
            $unit->getTmpFileName()
        );
    }

    /**
     * @throws \Exception
     */
    public function testComplexProcess()
    {
        $unit = $this->getUnit('test_table1');
        $unit->setFilesystem($this->getFS(10));
        $unit->setGeneratorMapping([
            'id' => 'generator.randomDigit',
            'code' => function (Generator $generator) {
                return $generator->word;
            }
        ]);
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        $counter1 = 0;
        $filesystem->expects($this->any())
            ->method('writeRow')->willReturnCallback(function () use (&$counter1) {
                $counter1++;
            });
        /** @var ResourceInterface $filesystem */
        $childUnit = $this->getUnit('test_table2');
        $childUnit->setFilesystem($filesystem);
        $childUnit->setGeneratorMapping([
            'id' => 'generator.randomDigit',
            'parent_id' => 'map.test_table1_id',
            'field1' => '0'
        ]);
        $childUnit->setParent($unit);
        $seed = new \SplFixedArray(2);
        $seed[0] = 4;
        $seed[1] = 1;
        $childUnit->setGenerationSeed($seed);
        $childDataUnit = $this->getUnit('test_table3');
        $counter2 = 0;
        $filesystem2 = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        $filesystem2->expects($this->any())
            ->method('writeRow')->willReturnCallback(function () use (&$counter2) {
                $counter2++;
            });
        /** @var ResourceInterface $filesystem2 */
        $childDataUnit->setFilesystem($filesystem2);
        $childDataUnit->setGeneratorMapping([
            'id' => 'generator.randomDigit',
            'parent_id' => 'map.test_table2_id',
            'field2' => '2'
        ]);
        $childDataUnit->setGenerationSeed($seed);
        $childUnit->addSibling($childDataUnit);
        $generator = new Generator();
        $generator->addProvider(new Base($generator));
        $generator->addProvider(new Lorem($generator));
        $action = new Generate(
            $this->getUnitBag([$unit, $childUnit, $childDataUnit]),
            $this->getConfig(),
            new LanguageAdapter(new ExpressionLanguage()),
            $generator,
            10,
            new ArrayMap(),
            $this->getResourceHelper()
        );
        $action->process($this->getResultMock());

        $this->assertSame($counter1, $counter2);
    }

    /**
     * @expectedException \Maketok\DataMigration\Action\Exception\WrongContextException
     * @expectedExceptionMessage Can not use generation with unit test_table1
     */
    public function testWrongProcess()
    {
        $unit = $this->getUnit('test_table1');
        $action = new Generate(
            $this->getUnitBag([$unit]),
            $this->getConfig(),
            new LanguageAdapter(),
            new Generator(),
            2,
            new ArrayMap(),
            $this->getResourceHelper()
        );
        $action->process($this->getResultMock());
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

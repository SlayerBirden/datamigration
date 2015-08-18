<?php

namespace Maketok\DataMigration\Unit;

use Maketok\DataMigration\Unit\Type\Unit;

class SimpleBagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SimpleBag
     */
    protected $bag;

    /**
     * set up
     *       1 - 5
     *      / \
     * 3 - 2   4
     *          \
     *           6
     */
    public function setUp()
    {
        $this->bag = new SimpleBag();

        $unit = new Unit('test1');
        $unit2 = new Unit('test2');
        $unit3= new Unit('test3');
        $unit4 = new Unit('test4');
        $unit5 = new Unit('test5');
        $unit6 = new Unit('test6');

        $unit6->setParent($unit4);
        $unit4->setParent($unit);
        $unit->addSibling($unit5);
        $unit3->addSibling($unit2);
        $unit2->setParent($unit);

        $this->bag->addSet([$unit, $unit2, $unit3, $unit4, $unit5, $unit6]);
        $this->bag->compileTree();
    }

    public function testing()
    {
        $this->assertTrue($this->bag->isLowest('test6'));
        $this->assertEquals(3, $this->bag->getLowestLevel());
        $this->assertEquals(2, $this->bag->getUnitLevel('test3'));
        $unit = $this->bag->getUnitByCode('test1');
        $unit2 = $this->bag->getUnitByCode('test2');
        $unit3 = $this->bag->getUnitByCode('test3');
        $unit4 = $this->bag->getUnitByCode('test4');
        $unit5 = $this->bag->getUnitByCode('test5');
        $unit6 = $this->bag->getUnitByCode('test6');
        $this->assertSame([$unit6], $this->bag->getChildren('test4'));
        $this->assertSame(['test2', 'test3', 'test4'], $this->bag->getUnitsFromLevel(2));
        $this->assertEquals([
            ['pc' => ['test4' => $unit4,'test6' => $unit6]],
            ['s' => ['test3' => $unit3, 'test2' => $unit2]],
            ['pc' => ['test2' => $unit2, 'test1' => $unit]],
            ['pc' => ['test4' => $unit4, 'test1' => $unit]],
            ['s' => ['test1' => $unit, 'test5' => $unit5]],
        ], $this->bag->getRelations());
    }

    public function testCompileLevels()
    {
        $bag = new SimpleBag();

        $unit = new Unit('test1');
        $unit2 = new Unit('test2');
        $bag->addSet([$unit, $unit2]);
        $bag->compileTree();
        $this->assertSame(['test1', 'test2'], $bag->getUnitsFromLevel(1));
    }

    public function testIteratorSort()
    {
        $unit1 = new Unit('A');
        $unit2 = new Unit('B');
        $unit2->setParent($unit1);

        $bag = new SimpleBag();
        $bag->addSet([$unit2, $unit1]);

        $i = 0;
        foreach ($bag as $unit) {
            if ($i == 0) {
                $this->assertSame($unit1, $unit);
            } elseif ($i == 1) {
                $this->assertSame($unit2, $unit);
            }
            ++$i;
        }
    }

    public function testIteratorSort2()
    {
        $unit1 = new Unit('A');
        $unit2 = new Unit('B');
        $unit3 = new Unit('C');
        $unit2->setParent($unit1);
        $unit3->setParent($unit2);

        $bag = new SimpleBag();
        $bag->addSet([$unit2, $unit3, $unit1]);

        $i = 0;
        foreach ($bag as $unit) {
            if ($i == 0) {
                $this->assertSame($unit1, $unit);
            } elseif ($i == 1) {
                $this->assertSame($unit2, $unit);
            } elseif ($i == 2) {
                $this->assertSame($unit3, $unit);
            }
            ++$i;
        }
    }
}

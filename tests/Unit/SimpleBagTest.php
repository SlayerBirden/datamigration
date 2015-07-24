<?php

namespace Maketok\DataMigration\Unit;

use Maketok\DataMigration\Unit\Type\Unit;

class SimpleBagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider bagProvider
     * @param SimpleBag $bag
     * @param string $code
     * @param $expected
     */
    public function testIsLeaf(SimpleBag $bag, $code, $expected)
    {
        $this->assertSame($expected, $bag->isLeaf($code));
    }

    /**
     * @return array
     */
    public function bagProvider()
    {
        $bag1 = new SimpleBag();

        $unit = new Unit('test1');
        $unit2 = new Unit('test2');
        $unit3= new Unit('test3');
        $unit4 = new Unit('test4');
        $unit4->setParent($unit);
        $unit3->setParent($unit2);
        $unit2->setParent($unit);

        $bag1->add($unit);
        $bag1->add($unit2);
        $bag1->add($unit3);
        $bag1->add($unit4);
        return array(
            [$bag1, 'test1', false],
            [$bag1, 'test2', false],
            [$bag1, 'test3', true],
            [$bag1, 'test4', true],
        );
    }
}

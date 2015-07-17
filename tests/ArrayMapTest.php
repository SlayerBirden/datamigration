<?php

namespace Maketok\DataMigration;

class ArrayMapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ArrayMap
     */
    private $map;

    public function setUp()
    {
        $this->map = new ArrayMap();
    }

    public function testGet()
    {
        $this->map->somevar = 1;

        $this->assertSame(1, $this->map->somevar);
        $this->assertSame(1, $this->map['somevar']);
        $this->assertNull($this->map['someothervar']);
    }

    public function testHas()
    {
        $this->map->somevar = 1;

        $this->assertTrue(isset($this->map['somevar']));
        $this->assertTrue($this->map->offsetExists('somevar'));
    }

    public function testUnset()
    {
        $this->map->somevar = 1;
        unset($this->map['somevar']);

        // should not produce error
        unset($this->map->someothervar);

        $this->assertFalse(isset($this->map['somevar']));
    }

    public function testFill()
    {
        $this->map->setState([
            'somevar' => 2
        ]);

        $this->assertEquals(2, $this->map->somevar);
        $this->assertEquals([
            'somevar' => 2
        ], $this->map->dumpState());
    }

    public function testIncr()
    {
        $this->map->somevar = 1;
        $this->map->incr('somevar', 50);
        $this->map->incr('someothervar', 50, 50);

        $this->assertSame(2, $this->map->somevar);
        $this->assertSame(50, $this->map->someothervar);
    }

    public function testFrozenIncr()
    {
        $this->map->somevar = 1;
        $this->map->frozenIncr('somevar', 1, 1); // +1
        $this->map->frozenIncr('somevar', 1, 1); // +1
        $this->map->frozenIncr('somevar', 1, 1); // +1
        $this->map->freeze();
        $this->map->frozenIncr('somevar', 1, 1); // do nothing
        $this->map->frozenIncr('somevar', 1, 1); // do nothing
        $this->map->unFreeze();
        $this->map->frozenIncr('somevar', 1, 5); // +5
        $this->map->frozenIncr('somevar', 1, 5); // +5

        $this->assertSame(14, $this->map->somevar);
    }

    public function testIterate()
    {
        $this->map->setState([
            'somevar' => 2,
            'someothervar' => 2,
        ]);

        $i = 0;
        foreach ($this->map as $key => $val) {
            $i++;
        }

        $this->assertEquals(2, $i);
    }

    public function testWithClear()
    {
        $this->map->setState([
            'somevar' => 2,
            'someothervar' => 2,
        ]);
        $this->map->clear();

        $this->assertFalse(isset($this->map['somevar']));
    }
}

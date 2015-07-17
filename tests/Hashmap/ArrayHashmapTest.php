<?php

namespace Maketok\DataMigration\Hashmap;

class ArrayHashmapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ArrayHashmap
     */
    private $map;

    public function setUp()
    {
        $this->map = new ArrayHashmap('test');
    }

    public function testGet()
    {
        $this->map->somevar = 1;
        $this->map->somevar = 1;

        $this->assertSame(1, $this->map->somevar);
        $this->assertSame(1, $this->map['somevar']);
        $this->assertNull($this->map['someothervar']);
    }

    public function testHas()
    {
        $this->map['somevar'] = 1;

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

    public function testLoad()
    {
        $this->map->load([
            'somevar' => 2
        ]);

        $this->assertEquals(2, $this->map->somevar);
    }
}

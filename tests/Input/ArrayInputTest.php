<?php

namespace Maketok\DataMigration\Input;

class ArrayInputTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ArrayInput */
    private $input;

    public function setUp()
    {
        $this->input = new ArrayInput();
    }

    public function testKey()
    {
        $this->assertSame(0, $this->input->key());
        $this->input->next();
        $this->assertSame(1, $this->input->key());
    }

    public function testValid()
    {
        $this->assertFalse($this->input->valid());
        $this->input->add(['1']);
        $this->assertTrue($this->input->valid());
    }

    public function testGet()
    {
        $this->input->add([1,2]);
        $this->input->add([2,3]);

        $this->assertSame([1,2], $this->input->get());
        $this->assertSame([2,3], $this->input->get());
        $this->assertSame(false, $this->input->get());
    }

    public function testReset()
    {
        $this->input->add([1,2]);

        $this->assertSame([1,2], $this->input->get());
        $this->assertSame(false, $this->input->get());
        $this->input->reset();
        $this->assertSame([1,2], $this->input->get());
        $this->assertSame(false, $this->input->get());
    }
}

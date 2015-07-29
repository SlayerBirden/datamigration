<?php

namespace Maketok\DataMigration\Action;

class ArrayConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $config = new ArrayConfig();
        $config->assign(['code' => 'test']);
        $this->assertEquals('test', $config['code']);
        unset($config['code']);
        $this->assertNull($config['code']);
    }

    public function testDump()
    {
        $stash = ['code' => 'test'];
        $config = new ArrayConfig();
        $config->assign($stash);
        $this->assertSame($stash, $config->dump());
    }
}

<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Storage\Db\ResourceInterface;
use Maketok\DataMigration\Unit\Type\ImportDbUnit;

class LoadTest extends \PHPUnit_Framework_TestCase
{
    use ServiceGetterTrait;

    public function testGetCode()
    {
        $action = new Load(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getResource()
        );
        $this->assertEquals('load', $action->getCode());
    }

    /**
     * @param string $code
     * @return ImportDbUnit
     */
    public function getUnit($code)
    {
        $unit = new ImportDbUnit($code);
        $unit->setTable('table');
        $unit->setPk('id');
        return $unit;
    }

    /**
     * @param bool $expects
     * @return ResourceInterface
     */
    protected function getResource($expects = false)
    {
        $resource = $this->getMockBuilder('\Maketok\DataMigration\Storage\Db\ResourceInterface')->getMock();
        if ($expects) {
            $resource->expects($this->atLeastOnce())->method('loadData');
        }
        return $resource;
    }

    public function testProcess()
    {
        $unit = $this->getUnit('tmp_table1');
        $unit->setTmpFileName('tmp_file.csv');
        $action = new Load(
            $this->getUnitBag([$unit]),
            $this->getConfig(),
            $this->getResource(true)
        );
        $action->process($this->getResultMock());

        $this->assertNotEmpty($unit->getTmpTable());
    }

    /**
     * @expectedException \Maketok\DataMigration\Action\Exception\WrongContextException
     */
    public function testWrongProcess()
    {
        $action = new Load(
            $this->getUnitBag([$this->getUnit('tmp_table1')]),
            $this->getConfig(),
            $this->getResource()
        );
        $action->process($this->getResultMock());
    }
}

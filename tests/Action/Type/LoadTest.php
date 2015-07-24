<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Storage\Db\ResourceInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface as FsResourceInterface;
use Maketok\DataMigration\Unit\Type\Unit;

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
     * @return Unit
     */
    public function getUnit($code)
    {
        $unit = new Unit($code);
        $unit->setTable('table');
        $unit->setPk('id');
        /** @var FsResourceInterface $filesystem */
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        $unit->setFilesystem($filesystem);
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

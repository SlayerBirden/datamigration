<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\Storage\Db\ResourceInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface as FsResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class DumpTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    /**
     * setup
     */
    public function setUp()
    {
        $this->root = vfsStream::setup();
    }

    public function testGetCode()
    {
        $action = new Dump(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getFS(),
            $this->getResource(),
            $this->getInputResource()
        );
        $this->assertEquals('dump', $action->getCode());
    }

    /**
     * @return ConfigInterface
     */
    protected function getConfig()
    {
        $config = $this->getMockBuilder('\Maketok\DataMigration\Action\ConfigInterface')
            ->getMock();
        $config->expects($this->any())->method('get')->willReturnMap([
            ['dump_folder', $this->root->url()],
            ['dump_mask', '%1$s.csv'],
        ]);
        return $config;
    }

    /**
     * @param bool $expects
     * @return InputResourceInterface
     */
    protected function getInputResource($expects = false)
    {
        $input = $this->getMockBuilder('\Maketok\DataMigration\Input\InputResourceInterface')
            ->getMock();
        if ($expects) {
            $input->expects($this->exactly(2))->method('add');
        }
        return $input;
    }

    /**
     * @return AbstractUnit
     */
    protected function getUnit()
    {
        /** @var AbstractUnit $unit */
        $unit = $this->getMockBuilder('\Maketok\DataMigration\Unit\AbstractUnit')
            ->getMockForAbstractClass();
        $unit->setTable('test_table1');
        return $unit;
    }

    /**
     * @return UnitBagInterface
     */
    protected function getUnitBag()
    {
        $unitBag = $this->getMockBuilder('\Maketok\DataMigration\Unit\UnitBagInterface')
            ->getMock();
        $unitBag->expects($this->any())->method('add')->willReturnSelf();
        $unitBag->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->getUnit()]));
        return $unitBag;
    }

    /**
     * @param bool $expects
     * @return ResourceInterface
     */
    protected function getResource($expects = false)
    {
        $resource = $this->getMockBuilder('\Maketok\DataMigration\Storage\Db\ResourceInterface')
            ->getMock();
        if ($expects) {
            $resource->expects($this->atLeastOnce())
                ->method('dumpData')
                ->willReturnCallback(function () {
                    vfsStream::newFile('test_table1.csv')
                        ->setContent("1,value1\n2,value2\n")
                        ->at($this->root);
                });
        }
        return $resource;
    }

    /**
     * @param bool $expects
     * @return FsResourceInterface
     */
    protected function getFS($expects = false)
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        if ($expects) {
            $filesystem->expects($this->once())->method('open');
            $filesystem->expects($this->exactly(2))->method('readRow');
            $filesystem->expects($this->once())->method('close');
        }
        return $filesystem;
    }

    public function testProcess()
    {
        $action = new Dump(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getFS(true),
            $this->getResource(true),
            $this->getInputResource(true)
        );
        $action->process();
    }
}

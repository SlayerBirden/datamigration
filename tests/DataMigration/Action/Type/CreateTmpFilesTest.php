<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class CreateTmpFilesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;
    /**
     * @var AbstractUnit
     */
    private $unit;

    /**
     * setup
     */
    public function setUp()
    {
        $this->root = vfsStream::setup();
    }

    public function testGetCode()
    {
        $action = new CreateTmpFiles(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getFS(),
            $this->getInputResource([])
        );
        $this->assertEquals('create_tmp_files', $action->getCode());
    }

    /**
     * @return AbstractUnit
     */
    protected function getUnit()
    {
        if (is_null($this->unit)) {
            /** @var AbstractUnit $unit */
            $this->unit = $this->getMockBuilder('\Maketok\DataMigration\Unit\AbstractUnit')
                ->getMockForAbstractClass();
            $this->unit->setTable('test_table1');
        }
        return $this->unit;
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
     * @param array $data
     * @return InputResourceInterface
     */
    protected function getInputResource(array $data)
    {
        $input = $this->getMockBuilder('\Maketok\DataMigration\Input\InputResourceInterface')
            ->getMock();
        for ($i = 0; $i < count($data); ++$i) {
            $input->expects($this->at($i))->method('get')->willReturn($data[$i]);
        }
        if ($i > 0) {
            $input->expects($this->at($i))->method('get')->willReturn(false);
        }
        return $input;
    }

    /**
     * @return ConfigInterface
     */
    protected function getConfig()
    {
        $config = $this->getMockBuilder('\Maketok\DataMigration\Action\ConfigInterface')
            ->getMock();
        $config->expects($this->any())->method('get')->willReturnMap([
            ['tmp_folder', $this->root->url() . '/tmp'],
            ['tmp_file_mask', '%1$s.csv'], // fname, date
        ]);
        return $config;
    }

    /**
     * @param bool $expect
     * @return ResourceInterface
     */
    protected function getFS($expect = false)
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        if ($expect) {
            $filesystem->expects($this->once())
                ->method('open')
                ->with($this->equalTo($this->root->url() . '/tmp/test_table1.csv'));
            $filesystem->expects($this->exactly(2))->method('writeRow');
            $filesystem->expects($this->once())->method('close');
        }
        return $filesystem;
    }

    public function testProcess()
    {
        $expected = [
            ['1', 'someField', 'otherField'],
            ['2', 'someField2', 'otherField2']
        ];

        $action = new CreateTmpFiles(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getFS(true),
            $this->getInputResource($expected)
        );
        $action->process();

        //assert name is assigned to unit
        $this->assertEquals($this->root->url() . '/tmp/test_table1.csv',
            $this->getUnit()->getTmpFileName());
    }
}

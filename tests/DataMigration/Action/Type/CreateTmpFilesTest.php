<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Unit\AbstractUnit;
use org\bovigo\vfs\vfsStream;

class CreateTmpFilesTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCode()
    {
        $action = new CreateTmpFiles($this->getMockBuilder('\Maketok\DataMigration\Unit\UnitBagInterface')->getMock(),
            $this->getMockBuilder('\Maketok\DataMigration\Action\ConfigInterface')->getMock(),
            $this->getMockBuilder('\Maketok\DataMigration\Input\InputResourceInterface')->getMock());
        $this->assertEquals('create_tmp_files', $action->getCode());
    }

    public function testProcess()
    {
        // set up Unit mock
        /** @var AbstractUnit $unit */
        $unit = $this->getMockBuilder('\Maketok\DataMigration\Unit\AbstractUnit')
            ->getMockForAbstractClass();
        $unit->setTable('test_table1');
        // set up bag
        $unitBag = $this->getMockBuilder('\Maketok\DataMigration\Unit\UnitBagInterface')->getMock();
        $unitBag->expects($this->any())->method('add')->willReturnSelf();
        $unitBag->expects($this->any())->method('getIterator')->willReturn(new \ArrayIterator([$unit]));
        // set up input mock
        // 2 entities returned
        $expected = [
            ['1', 'someField', 'otherField'],
            ['2', 'someField2', 'otherField2']
        ];
        $input = $this->getMockBuilder('\Maketok\DataMigration\Input\InputResourceInterface')->getMock();
        $input->expects($this->at(0))->method('get')->willReturn($expected[0]);
        $input->expects($this->at(1))->method('get')->willReturn($expected[1]);
        $input->expects($this->at(2))->method('get')->willReturn(false);
        // set up config
        $root = vfsStream::setup();
        $config = $this->getMockBuilder('\Maketok\DataMigration\Action\ConfigInterface')->getMock();
        $config->expects($this->any())->method('get')->willReturnMap([
            ['tmp_folder', $root->url() . '/tmp'],
            ['mask', '%1$s.csv'], // fname, date
        ]);

        $action = new CreateTmpFiles($unitBag, $config, $input);
        $action->process();

        $this->assertTrue(file_exists($root->url() . '/tmp/test_table1.csv'));

        $actual = [];
        $readFile = new \SplFileObject($root->url() . '/tmp/test_table1.csv');
        while (($row = $readFile->fgetcsv()) !== false) {
            $actual[] = $row;
        }

        $this->assertEquals($expected, $actual);
    }
}

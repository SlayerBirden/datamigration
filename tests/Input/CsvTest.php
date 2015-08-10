<?php

namespace Maketok\DataMigration\Input;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class CsvTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    protected $root;

    public function setUp()
    {
        $this->root = vfsStream::setup('root');
    }

    public function testRead()
    {
        $content = <<<CSV
id,name
1,John
2,Peter
CSV;
        vfsStream::newFile('test.csv')->setContent($content)->at($this->root);
        $csv = new Csv(vfsStream::url('root/test.csv'));
        $this->assertSame([
            'id' => '1',
            'name' => 'John',
        ], $csv->get());
        $this->assertSame([
            'id' => '2',
            'name' => 'Peter',
        ], $csv->get());
        $this->assertSame(false, $csv->get());
    }

    /**
     * @expectedException \Maketok\DataMigration\Storage\Exception\ParsingException
     * @expectedExceptionMessage Row contains wrong number of columns compared to header
     */
    public function testWrongRead()
    {
        $content = <<<CSV
id,name
1,John,Smith
2,Peter
CSV;
        vfsStream::newFile('test.csv')->setContent($content)->at($this->root);
        $csv = new Csv(vfsStream::url('root/test.csv'));
        $csv->get();
    }

    /**
     * @depends testRead
     */
    public function testReset()
    {
        $content = <<<CSV
id,name
1,John
2,Peter
CSV;
        vfsStream::newFile('test.csv')->setContent($content)->at($this->root);
        $csv = new Csv(vfsStream::url('root/test.csv'));
        $this->assertSame([
            'id' => '1',
            'name' => 'John',
        ], $csv->get());
        $csv->reset();
        $this->assertSame([
            'id' => '1',
            'name' => 'John',
        ], $csv->get());
    }

    public function testWrite()
    {
        $file = vfsStream::newFile('test.csv');
        $file->at($this->root);
        $csv = new Csv(vfsStream::url('root/test.csv'), 'w');
        $csv->add([
            'id' => '1',
            'name' => 'John',
        ]);
        $csv->add([
            'id' => '2',
            'name' => 'Peter',
        ]);

        $expected = <<<CSV
id,name
1,John
2,Peter

CSV;
        $this->assertEquals($expected, $file->getContent());
    }
}

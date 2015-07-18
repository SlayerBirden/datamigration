<?php

namespace Maketok\DataMigration\Storage\Filesystem;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    public function setUp()
    {
        $this->root = vfsStream::setup('root');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage failed to open stream
     */
    public function testOpenClose()
    {
        $filesystem = new Resource();
        $filesystem->open(vfsStream::url('root/file1'), 'w');
        $filesystem->close();
        $this->assertFileExists(vfsStream::url('root/file1'));

        // should fail
        $filesystem->open(vfsStream::url('root/file2'), 'r');
    }

    /**
     * @depends testOpenClose
     */
    public function testWrite()
    {
        $filesystem = new Resource();
        $filesystem->open(vfsStream::url('root/file1'), 'w');
        $filesystem->writeRow(['name', 'age', 'address']);
        $filesystem->close();

        $fileObject = new \SplFileObject(vfsStream::url('root/file1'), 'r');
        $this->assertSame(['name', 'age', 'address'], $fileObject->fgetcsv());
    }

    /**
     * @depends testOpenClose
     */
    public function testRead()
    {
        $fileObject = new \SplFileObject(vfsStream::url('root/file1'), 'w');
        $fileObject->fputcsv(['name', 'age', 'address']);

        $filesystem = new Resource();
        $filesystem->open(vfsStream::url('root/file1'), 'r');
        $this->assertSame(['name', 'age', 'address'], $filesystem->readRow());
        $filesystem->close();
    }

    /**
     * @depends testRead
     */
    public function testRewind()
    {
        $fileObject = new \SplFileObject(vfsStream::url('root/file1'), 'w');
        $fileObject->fputcsv(['name', 'age', 'address']);
        $fileObject->fputcsv(['Oleg', '3', 'Universe']);

        $filesystem = new Resource();
        $filesystem->open(vfsStream::url('root/file1'), 'r');
        $this->assertSame(['name', 'age', 'address'], $filesystem->readRow());
        $this->assertSame(['Oleg', '3', 'Universe'], $filesystem->readRow());
        $this->assertEmpty($filesystem->readRow());
        $filesystem->rewind();
        $this->assertSame(['name', 'age', 'address'], $filesystem->readRow());
        $filesystem->close();
    }

    /**
     * @depends testOpenClose
     */
    public function testIsActive()
    {
        $filesystem = new Resource();
        $filesystem->open(vfsStream::url('root/file1'), 'w');
        $this->assertTrue($filesystem->isActive());
        $filesystem->close();
        $this->assertFalse($filesystem->isActive());
        $filesystem->open(vfsStream::url('root/file1'), 'w');
        $this->assertTrue($filesystem->isActive());
        $filesystem->close();
    }
}

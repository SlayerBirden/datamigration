<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Expression\LanguageAdapter;
use Maketok\DataMigration\Storage\Db\ResourceInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface as FsResourceInterface;
use Maketok\DataMigration\Unit\Type\ExportFileUnit;

class DumpTest extends \PHPUnit_Framework_TestCase
{
    use ServiceGetterTrait;

    public function testGetCode()
    {
        $action = new Dump(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getResource()
        );
        $this->assertEquals('dump', $action->getCode());
    }

    /**
     * @param array $returns
     * @return ResourceInterface
     */
    protected function getResource($returns = [])
    {
        $resource = $this->getMockBuilder('\Maketok\DataMigration\Storage\Db\ResourceInterface')
            ->getMock();
        $method = $resource->expects($this->exactly(count($returns)))
            ->method('dumpData');
        call_user_func_array([$method, 'willReturnOnConsecutiveCalls'], $returns);
        return $resource;
    }

    /**
     * @param string $code
     * @return ExportFileUnit
     */
    public function getUnit($code)
    {
        return new ExportFileUnit($code);
    }

    /**
     * @param int $expects
     * @return FsResourceInterface
     */
    protected function getFS($expects = 0)
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        $filesystem->expects($this->exactly($expects))
            ->method('writeRow');
        return $filesystem;
    }

    public function testProcess()
    {
        $unit = $this->getUnit('test_table1')
            ->setTmpTable('tmp_test_table1')
            ->setFilesystem($this->getFS(2));
        $action = new Dump(
            $this->getUnitBag([$unit]),
            $this->getConfig(),
            $this->getResource([
                [["1", "somedata"]],
                [["2", "somedata2"]],
                false
            ])
        );
        $action->process();

        $this->assertEquals('/tmp/test_table1.csv',
            $unit->getTmpFileName());
    }
}

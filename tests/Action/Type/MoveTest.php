<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Expression\LanguageAdapter;
use Maketok\DataMigration\Storage\Db\ResourceInterface;
use Maketok\DataMigration\Unit\Type\ImportDbUnit;

class MoveTest extends \PHPUnit_Framework_TestCase
{
    use ServiceGetterTrait;

    public function testGetCode()
    {
        $action = new Move(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getResource()
        );
        $this->assertEquals('move', $action->getCode());
    }

    /**
     * @param string $code
     * @return ImportDbUnit
     */
    public function getUnit($code)
    {
        return new ImportDbUnit($code);
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
                ->method('move');
        }
        return $resource;
    }

    public function testProcess()
    {
        $action = new Move(
            $this->getUnitBag([$this->getUnit('test')->setTmpTable('tmp')]),
            $this->getConfig(),
            $this->getResource(true)
        );
        $action->process();
    }
}

<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Expression\LanguageAdapter;
use Maketok\DataMigration\Storage\Db\ResourceInterface;

class DeleteTest extends \PHPUnit_Framework_TestCase
{
    use ServiceGetterTrait;

    public function testGetCode()
    {
        $action = new Delete(
            $this->getUnitBag(),
            $this->getConfig(),
            new LanguageAdapter(),
            $this->getResource()
        );
        $this->assertEquals('delete', $action->getCode());
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
            $resource->expects($this->atLeastOnce())->method('deleteUsingTempPK');
        }
        return $resource;
    }

    public function testProcess()
    {
        $action = new Delete(
            $this->getUnitBag([$this->getUnit('tmp')->setTmpTable('tmp1')]),
            $this->getConfig(),
            new LanguageAdapter(),
            $this->getResource(true)
        );
        $action->process();
    }

    /**
     * @expectedException \Maketok\DataMigration\Action\Exception\WrongContextException
     */
    public function testWrongProcess()
    {
        $action = new Delete(
            $this->getUnitBag([$this->getUnit('tmp')]),
            $this->getConfig(),
            new LanguageAdapter(),
            $this->getResource()
        );
        $action->process();
    }
}

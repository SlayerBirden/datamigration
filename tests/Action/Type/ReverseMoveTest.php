<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Expression\LanguageAdapter;
use Maketok\DataMigration\Storage\Db\ResourceInterface;

class ReverseMoveTest extends \PHPUnit_Framework_TestCase
{
    use ServiceGetterTrait;

    public function testGetCode()
    {
        $action = new ReverseMove(
            $this->getUnitBag(),
            $this->getConfig(),
            new LanguageAdapter(),
            $this->getResource()
        );
        $this->assertEquals('reverse_move', $action->getCode());
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
        $unit = $this->getUnit('tmp');
        $action = new ReverseMove(
            $this->getUnitBag([$unit]),
            $this->getConfig(),
            new LanguageAdapter(),
            $this->getResource(true)
        );
        $action->process();

        $this->assertNotEmpty($unit->getTmpTable());
    }
}

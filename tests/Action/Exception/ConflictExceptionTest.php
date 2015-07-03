<?php

namespace Maketok\DataMigration\Action\Exception;

class ConflictExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testThrowException()
    {
        $conflictedUnits = ['unit1', 'unit2'];
        $conflictedKey = 'column1';
        try {
            throw new ConflictException("Message", $conflictedUnits, $conflictedKey);
        } catch (ConflictException $e) {
            $this->assertEquals('Message', $e->getMessage());
            $this->assertSame($conflictedUnits, $e->getUnitsInConflict());
            $this->assertSame($conflictedKey, $e->getConflictedKey());
        }
    }
}

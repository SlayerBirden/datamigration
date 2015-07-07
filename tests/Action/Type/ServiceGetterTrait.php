<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;

trait ServiceGetterTrait
{
    /**
     * @param AbstractUnit[] $units
     * @return UnitBagInterface
     */
    protected function getUnitBag($units = [])
    {
        /** @var \PHPUnit_Framework_TestCase $this */
        $unitBag = $this->getMockBuilder('\Maketok\DataMigration\Unit\UnitBagInterface')
            ->getMock();
        $unitBag->expects($this->any())->method('add')->willReturnSelf();
        $unitBag->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($units));
        $unitBag->expects($this->any())
            ->method('count')
            ->willReturn(count($units));
        $map = [];
        foreach ($units as $unit) {
            $map[] = [$unit->getCode(), $unit];
        }
        $unitBag->expects($this->any())
            ->method('getUnitByCode')
            ->willReturnMap($map);
        $unitBag->expects($this->any())
            ->method('count')
            ->willReturn(count($units));
        return $unitBag;
    }

    /**
     * @return ConfigInterface
     */
    protected function getConfig()
    {
        /** @var \PHPUnit_Framework_TestCase $this */
        $config = $this->getMockBuilder('\Maketok\DataMigration\Action\ConfigInterface')
            ->getMock();
        $config->expects($this->any())->method('offsetGet')->willReturnMap([
            ['tmp_folder', '/tmp'],
            ['tmp_file_mask', '%1$s.csv'], // fname, date
            ['dump_limit', '10000'],
            ['tmp_table_mask', 'tmp_%1$s%2$s'], // fname, stamp
        ]);
        return $config;
    }
}

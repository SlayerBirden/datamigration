<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\SimpleBag;
use Maketok\DataMigration\Unit\Type\Unit;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Workflow\ResultInterface;

trait ServiceGetterTrait
{
    /**
     * @param AbstractUnit[] $units
     * @return UnitBagInterface
     */
    protected function getUnitBag($units = [])
    {
        /** @var \PHPUnit_Framework_TestCase $this */
        $unitBag = new SimpleBag();
        foreach ($units as $unit) {
            $unitBag->add($unit);
        }
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

    /**
     * @return ResultInterface
     */
    protected function getResultMock()
    {
        /** @var \PHPUnit_Framework_TestCase $this */
        return $this->getMockBuilder('\Maketok\DataMigration\Workflow\ResultInterface')->getMock();
    }

    /**
     * @param string $code
     * @return Unit
     */
    public function getUnit($code)
    {
        return new Unit($code);
    }
}

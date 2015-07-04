<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;

class CreateTmpFilesTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCode()
    {
        $action = new CreateTmpFiles(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getInputResource(),
            $this->getMap(),
            $this->getResourceHelper()
        );
        $this->assertEquals('create_tmp_files', $action->getCode());
    }

    /**
     * @param $name
     * @return AbstractUnit
     */
    protected function getUnit($name)
    {
        /** @var AbstractUnit $unit */
        $unit = $this->getMockBuilder('\Maketok\DataMigration\Unit\AbstractUnit')
            ->getMockForAbstractClass();
        return $unit->setTable($name);
    }

    /**
     * @param AbstractUnit[] $units
     * @return UnitBagInterface
     */
    protected function getUnitBag($units = [])
    {
        $unitBag = $this->getMockBuilder('\Maketok\DataMigration\Unit\UnitBagInterface')
            ->getMock();
        $unitBag->expects($this->any())->method('add')->willReturnSelf();
        $unitBag->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($units));
        return $unitBag;
    }

    /**
     * @param array $doMappingReturn
     * @param array $getMap
     * @return MapInterface
     */
    protected function getMap($doMappingReturn = [], $getMap = [])
    {
        $map = $this->getMockBuilder('\Maketok\DataMigration\MapInterface')
            ->getMock();
        $method = $map->expects($this->any())
            ->method('doMapping');
        call_user_func_array([$method, 'willReturnOnConsecutiveCalls'], $doMappingReturn);
        $map->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);
        return $map;
    }

    /**
     * @return ResourceHelperInterface
     */
    protected function getResourceHelper()
    {
        $rh = $this->getMockBuilder('\Maketok\DataMigration\Storage\Db\ResourceHelperInterface')
            ->getMock();
        return $rh;
    }

    /**
     * @param array $toReturn
     * @return InputResourceInterface
     */
    protected function getInputResource(array $toReturn = [])
    {
        $input = $this->getMockBuilder('\Maketok\DataMigration\Input\InputResourceInterface')
            ->getMock();
        $method = $input->expects($this->any())
            ->method('get');
        call_user_func_array([$method, 'willReturnOnConsecutiveCalls'], $toReturn);
        return $input;
    }

    /**
     * @param array $map
     * @return ConfigInterface
     */
    protected function getConfig($map = [])
    {
        $config = $this->getMockBuilder('\Maketok\DataMigration\Action\ConfigInterface')
            ->getMock();
        $config->expects($this->any())
            ->method('get')
            ->willReturnMap($map);
        return $config;
    }

    /**
     * @param int $expect
     * @param int $number
     * @return ResourceInterface
     */
    protected function getFS($expect = 0, $number = 0)
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        $filesystem->expects($this->exactly($expect))
            ->method('open');
        $filesystem->expects($this->exactly($number))->method('writeRow');
        $filesystem->expects($this->exactly($expect))->method('close');
        return $filesystem;
    }

    /**
     * input type
     * - customer,address1,address2
     * - customer,address1,address2
     * ...
     */
    public function testProcess()
    {
        $inputs = [
            ['email' => 'tst1@example.com', 'name' => 'Olaf'],
        ];
        $dumpStates = [

        ];
        $unit1 = $this->getUnit('customer');
        $unit1->setMapping([
            'entity_id' => 'id',
            'code' => 'code',
            'const' => '1',
        ])->setIsEntityCondition(function () {
            return true;
        });
        $unit2 = $this->getUnit('address');
        $counter = new \stdClass();
        $counter->count = 2;
        $unit2->setMapping([
            'new_id' => function ($counter) {
                return ++$counter->count;
            },
            'name' => 'name',
            'parent_id' => 'id',
        ])->setIsEntityCondition(function (MapInterface $map) {
            return $map->get('id') == 2;
        });

        $action = new CreateTmpFiles(
            $this->getUnitBag([$unit1, $unit2]),
            $this->getConfig(),
            $this->getInputResource($inputs),
            $this->getMap($dumpStates),
            $this->getResourceHelper()
        );
        $action->process();

        $this->assertEquals('/tmp/entity_table1.csv',
            $unit1->getTmpFileName());
        $this->assertEquals('/tmp/data_table1.csv',
            $unit2->getTmpFileName());
    }

    public function testProcessWriteCond()
    {
    }

    public function testProcessInvalid()
    {
    }
}

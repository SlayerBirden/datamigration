<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Action\Exception\ConflictException;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;

class AssembleInputTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AssembleInput
     */
    private $action;

    public function setUp()
    {
        $this->action = new AssembleInput(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getInputResource(),
            $this->getMap(),
            $this->getResourceHelper()
        );
    }

    public function testGetCode()
    {
        $this->assertEquals('assemble_input', $this->action->getCode());
    }

    /**
     * @param array $data
     * @return MapInterface
     */
    protected function getMap($data = [])
    {
        $map = $this->getMockBuilder('\Maketok\DataMigration\MapInterface')
            ->getMock();
        if (count($data)) {
            // todo
        }
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
     * @param array $data
     * @return InputResourceInterface
     */
    protected function getInputResource(array $data = [])
    {
        $input = $this->getMockBuilder('\Maketok\DataMigration\Input\InputResourceInterface')
            ->getMock();
        $count = count($data);
        $method = $input->expects($this->exactly($count))->method('add');
        call_user_func_array([$method, 'withConsecutive'], $data);
        return $input;
    }

    /**
     * @return ConfigInterface
     */
    protected function getConfig()
    {
        $config = $this->getMockBuilder('\Maketok\DataMigration\Action\ConfigInterface')
            ->getMock();
        $config->expects($this->any())->method('get')->willReturnMap([
            ['tmp_folder', '/tmp'],
            ['tmp_file_mask', '%1$s.csv'], // fname, date
        ]);
        return $config;
    }

    /**
     * @param array $returns
     * @param int $cntUnits
     * @return ResourceInterface
     */
    protected function getFS($returns = [], $cntUnits = 0)
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        $filesystem->expects($this->exactly($cntUnits))
            ->method('open');
        $filesystem->expects($this->exactly($cntUnits))
            ->method('close');
        $cntReturns = count($returns);
        $method = $filesystem->expects($this->exactly($cntReturns))
            ->method('readRow');
        call_user_func_array([$method, 'willReturnOnConsecutiveCalls'], $returns);
        return $filesystem;
    }

    public function testProcess()
    {
        // orders and items

    }

    /**
     * @param $data
     * @param $expectedRow
     * @dataProvider tmpUnitsProvider
     */
    public function testAssemble($data, $expectedRow)
    {
        $this->assertEquals($expectedRow, $this->action->assemble($data));
    }

    /**
     * @expectedException \Maketok\DataMigration\Action\Exception\ConflictException
     */
    public function testAssembleConflict()
    {
        $data = [
            'unit1' => [
                'id' => 1,
                'name'=> 'u1'
            ],
            'unit2' => [
                'id' => 1,
                'name' => 'u2'
            ],
        ];
        try {
            $this->action->assemble($data);
        } catch (ConflictException $e) {
            $this->assertSame(['unit1', 'unit2'], $e->getUnitsInConflict());
            $this->assertSame('name', $e->getConflictedKey());
            throw $e;
        }
        $this->fail("Failed asserting that ConflictException was thrown");
    }

    /**
     * @return array
     */
    public function tmpUnitsProvider()
    {
        return [
            // simple merge
            [
                [
                    'unit1' => [
                        'id' => 1,
                        'name' => 'tmp1',
                    ],
                    'unit2' => [
                        'code' => 't1',
                    ],
                ],
                [
                    'id' => 1,
                    'name' => 'tmp1',
                    'code' => 't1'
                ],
            ],
            // merge with equal keys
            [
                [
                    'unit1' => [
                        'id' => 1,
                        'name' => 'tmp1',
                    ],
                    'unit2' => [
                        'id' => 1,
                        'code' => 't1',
                    ],
                ],
                [
                    'id' => 1,
                    'name' => 'tmp1',
                    'code' => 't1'
                ],
            ],
        ];
    }

    /**
     * @param array $data
     * @param bool $expected
     * @dataProvider isEmptyDataProvider
     */
    public function testIsEmptyData(array $data, $expected)
    {
        $this->assertEquals($expected, $this->action->isEmptyData($data));
    }

    /**
     * @return array
     */
    public function isEmptyDataProvider()
    {
        return [
            [[], true],
            [
                [
                    [],
                ],
                true,
            ],
            [
                [
                    ['some' => null],
                ],
                true,
            ],
            [
                [
                    ['some'],
                ],
                false,
            ],
        ];
    }
}

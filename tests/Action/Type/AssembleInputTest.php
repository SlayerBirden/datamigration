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
    public function testGetCode()
    {
        $action = new AssembleInput(
            $this->getUnitBag([$this->getUnit('table1')]),
            $this->getConfig(),
            $this->getFS(),
            $this->getInputResource([]),
            $this->getMap([]),
            $this->getResourceHelper()
        );
        $this->assertEquals('assemble_input', $action->getCode());
    }

    /**
     * @param array $data
     * @return MapInterface
     */
    protected function getMap($data)
    {
        $map = $this->getMockBuilder('\Maketok\DataMigration\MapInterface')
            ->getMock();
        if (count($data)) {
            $map->expects($this->any())
                ->method('dumpState')
                ->willReturnOnConsecutiveCalls($data[0], $data[1], $data[2]);
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
    protected function getUnitBag($units)
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
    protected function getInputResource(array $data)
    {
        $input = $this->getMockBuilder('\Maketok\DataMigration\Input\InputResourceInterface')
            ->getMock();
        if (($count = count($data)) == 2) {
            $input->expects($this->exactly($count))->method('add')->withConsecutive([$data[0]], [$data[1]]);
        } elseif ($count == 3) {
            $input->expects($this->exactly($count))->method('add')->withConsecutive([$data[0]], [$data[1]], [$data[2]]);
        }
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
     * @param bool $expect
     * @return ResourceInterface
     */
    protected function getFS($expect = null)
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        switch ($expect) {
            case 'forward':
                $filesystem->expects($this->exactly(2))
                    ->method('open');
                $filesystem->expects($this->exactly(5))
                    ->method('readRow')
                    ->willReturnOnConsecutiveCalls(
                        ['1', 'otherField', '1'],
                        ['3', 'someField2', '2'],
                        ['2', 'otherField2', '1'],
                        false,
                        false
                    );
                $filesystem->expects($this->exactly(2))
                    ->method('close');
                break;
            case 'reversed':
                $filesystem->expects($this->exactly(2))
                    ->method('open');
                $filesystem->expects($this->exactly(7))
                    ->method('readRow')
                    ->willReturnOnConsecutiveCalls(
                        ['3', 'someField2', '2'],
                        ['1', 'otherField', '1'],
                        false,
                        false,
                        ['2', 'otherField2', '1'],
                        false,
                        false
                    );
                $filesystem->expects($this->exactly(2))
                    ->method('close');
                break;
        }
        return $filesystem;
    }

    public function testProcess()
    {
        $expected = [
            ['id' => '1', 'name' => null, 'code' => 'otherField'],
            ['id' => '2', 'name' => 'someField2', 'code' => 'otherField2'],
        ];
        $unit1 = $this->getUnit('entity_table1');
        $unit1->setMapping([
            'entity_id' => 'id',
            'code' => 'code',
            'const' => '1',
        ])->setIsEntityCondition(function () {
            return true;
        })->setReversedMapping([
            'id' => 'entity_id',
            'code' => 'code',
        ])->setTmpFileName('entity_table1.csv');
        $unit2 = $this->getUnit('data_table1');
        $counter = new \stdClass();
        $counter->count = 2;
        $unit2->setMapping([
            'new_id' => function ($counter) {
                return ++$counter->count;
            },
            'name' => 'name',
            'parent_id' => 'id',
        ])->setIsEntityCondition(function (array $row) {
            return $row['id'] == 2;
        })->setReversedMapping([
            'name' => 'name',
            'id' => 'parent_id',
        ])->setTmpFileName('data_table1.csv');

        $action = new AssembleInput(
            $this->getUnitBag([$unit1, $unit2]),
            $this->getConfig(),
            $this->getFS('forward'),
            $this->getInputResource($expected),
            $this->getMap([]),
            $this->getResourceHelper()
        );
        $action->process();
    }

    public function testProcessReversedOrder()
    {
        // REVERSED
        $expected = [
            ['id' => '2', 'name' => 'someField2', 'code' => null],
            ['id' => '1', 'name' => null, 'code' => 'otherField'],
            ['id' => '2', 'name' => null, 'code' => 'otherField2'],
        ];
        $unit1 = $this->getUnit('entity_table1');
        $unit1->setMapping([
            'entity_id' => 'id',
            'code' => 'code',
            'const' => '1',
        ])->setIsEntityCondition(function () {
            return true;
        })->setReversedMapping([
            'id' => 'entity_id',
            'code' => 'code',
        ])->setTmpFileName('entity_table1.csv');
        $unit2 = $this->getUnit('data_table1');
        $counter = new \stdClass();
        $counter->count = 2;
        $unit2->setMapping([
            'new_id' => function ($counter) {
                return ++$counter->count;
            },
            'name' => 'name',
            'parent_id' => 'id',
        ])->setIsEntityCondition(function (array $row) {
            return $row['id'] == 2;
        })->setReversedMapping([
            'name' => 'name',
            'id' => 'parent_id',
        ])->setTmpFileName('data_table1.csv');

        $action = new AssembleInput(
            $this->getUnitBag([$unit2, $unit1]), // REVERSED
            $this->getConfig(),
            $this->getFS('reversed'),
            $this->getInputResource($expected),
            $this->getMap([]),
            $this->getResourceHelper()
        );
        $action->process();
    }

    /**
     * @param $data
     * @param $expectedRow
     * @dataProvider tmpUnitsProvider
     */
    public function testAssemble($data, $expectedRow)
    {
        $action = new AssembleInput(
            $this->getUnitBag([$this->getUnit('table1')]),
            $this->getConfig(),
            $this->getFS(),
            $this->getInputResource([]),
            $this->getMap([]),
            $this->getResourceHelper()
        );
        $this->assertEquals($expectedRow, $action->assemble($data));
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
        $action = new AssembleInput(
            $this->getUnitBag([$this->getUnit('table1')]),
            $this->getConfig(),
            $this->getFS(),
            $this->getInputResource([]),
            $this->getMap([]),
            $this->getResourceHelper()
        );
        try {
            $action->assemble($data);
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
        $action = new AssembleInput(
            $this->getUnitBag([$this->getUnit('table1')]),
            $this->getConfig(),
            $this->getFS(),
            $this->getInputResource([]),
            $this->getMap([]),
            $this->getResourceHelper()
        );
        $this->assertEquals($expected, $action->isEmptyData($data));
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

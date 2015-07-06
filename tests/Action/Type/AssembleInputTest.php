<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\Exception\ConflictException;
use Maketok\DataMigration\ArrayMap;
use Maketok\DataMigration\Expression\LanguageAdapter;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;

class AssembleInputTest extends \PHPUnit_Framework_TestCase
{
    use ServiceGetterTrait;

    /**
     * @var AssembleInput
     */
    private $action;

    public function setUp()
    {
        $this->action = new AssembleInput(
            $this->getUnitBag(),
            $this->getConfig(),
            new LanguageAdapter(),
            $this->getInputResource(),
            new ArrayMap(),
            $this->getResourceHelper()
        );
    }

    public function testGetCode()
    {
        $this->assertEquals('assemble_input', $this->action->getCode());
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

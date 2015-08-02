<?php

namespace Maketok\DataMigration;

class ArrayUtilsTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ArrayUtilsTrait
     */
    protected $trait;

    /**
     * set up trait
     */
    public function setUp()
    {
        $this->trait = $this->getMockBuilder('Maketok\DataMigration\ArrayUtilsTrait')->getMockForTrait();
    }
    /**
     * @param array $data
     * @param bool $expected
     * @dataProvider isEmptyDataProvider
     */
    public function testIsEmptyData(array $data, $expected)
    {
        $this->assertEquals($expected, $this->trait->isEmptyData($data));
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

    /**
     * @dataProvider rowProvider
     * @param array $row
     * @param $expected
     */
    public function normalize(array $row, $expected)
    {
        $this->assertSame($expected, $this->trait->normalize($row));
    }

    /**
     * @expectedException \Maketok\DataMigration\Action\Exception\NormalizationException
     * @expectedExceptionMessage Can not extract values: uneven data for row
     */
    public function normalizeError()
    {
        $row = ['str', [1,2], 'boo'];
        $this->trait->normalize($row);
    }

    /**
     * @return array
     */
    public function rowProvider()
    {
        return array(
            array(
                ['str', 1, 'boo'], [['str', 1, 'boo']]
            ),
            array(
                [['str', 'baz'], [1,2], ['boo', 'bar']], [['str', 1, 'boo'], ['baz', 2, 'bar']]
            ),
            array(
                [], []
            ),
        );
    }

    /**
     * @param $data
     * @param $expectedRow
     * @dataProvider tmpUnitsProvider
     */
    public function testAssemble($data, $expectedRow)
    {
        $this->assertEquals($expectedRow, $this->trait->assemble($data));
    }

    /**
     * @expectedException \Maketok\DataMigration\Action\Exception\ConflictException
     */
    public function testAssembleConflict()
    {
        $data = [
            'unit1' => [
                'id' => 1,
                'name' => 'u1',
            ],
            'unit2' => [
                'id' => 1,
                'name' => 'u2',
            ],
        ];
        $this->trait->assemble($data);
    }

    /**
     * @expectedException \Maketok\DataMigration\Action\Exception\ConflictException
     */
    public function testAssembleConflict2()
    {
        $data = [
            'unit2' => [
                'id' => 1,
                'name' => 'u2',
            ],
            'unit3' => [
                'id' => 2,
            ],
        ];
        $this->trait->assemble($data);
    }

    /**
     * @expectedException \Maketok\DataMigration\Action\Exception\ConflictException
     */
    public function testAssembleConflict3()
    {
        $data = [
            'unit1' => [
                'name' => 'u1',
                'id' => 3,
            ],
            'unit2' => [
                'id' => 1,
                'name' => 'u2',
            ],
        ];
        $this->trait->assemble($data);
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
                    'code' => 't1',
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
                    'code' => 't1',
                ],
            ],
            // merge one unit
            [
                [
                    'unit1' => [
                        'id' => 1,
                        'name' => 'tmp1',
                    ],
                ],
                [
                    'id' => 1,
                    'name' => 'tmp1',
                ],
            ],
        ];
    }
}

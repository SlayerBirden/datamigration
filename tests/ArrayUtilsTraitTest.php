<?php

namespace Maketok\DataMigration;

class ArrayUtilsTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $data
     * @param bool $expected
     * @dataProvider isEmptyDataProvider
     */
    public function testIsEmptyData(array $data, $expected)
    {
        /** @var ArrayUtilsTrait $trait */
        $trait = $this->getMockBuilder('Maketok\DataMigration\ArrayUtilsTrait')->getMockForTrait();
        $this->assertEquals($expected, $trait->isEmptyData($data));
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
        /** @var ArrayUtilsTrait $trait */
        $trait = $this->getMockBuilder('Maketok\DataMigration\ArrayUtilsTrait')->getMockForTrait();
        $this->assertSame($expected, $trait->normalize($row));
    }

    /**
     * @expectedException \Maketok\DataMigration\Action\Exception\NormalizationException
     * @expectedExceptionMessage Can not extract values: uneven data for row
     */
    public function normalizeError()
    {
        /** @var ArrayUtilsTrait $trait */
        $trait = $this->getMockBuilder('Maketok\DataMigration\ArrayUtilsTrait')->getMockForTrait();
        $row = ['str', [1,2], 'boo'];
        $trait->normalize($row);
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
}

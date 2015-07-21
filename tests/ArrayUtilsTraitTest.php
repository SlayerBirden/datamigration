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
}

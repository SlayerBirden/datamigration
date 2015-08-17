<?php

namespace Maketok\DataMigration\Expression;

use Maketok\DataMigration\ArrayMap;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class LanguageAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testSetters()
    {
        $lang1 = new ExpressionLanguage();
        $lang2 = new ExpressionLanguage();
        $adapter = new LanguageAdapter($lang1);
        $this->assertSame($lang1, $adapter->getLanguage());
        $adapter->setLanguage($lang2);
        $this->assertNotSame($lang1, $adapter->getLanguage());
        $this->assertSame($lang2, $adapter->getLanguage());
    }

    /**
     * @param mixed $expression
     * @param array $values
     * @param mixed $expected
     * @dataProvider evalProvider
     */
    public function testEval($expression, array $values, $expected)
    {
        $adapter = new LanguageAdapter(new ExpressionLanguage());
        $this->assertSame($expected, $adapter->evaluate($expression, $values));
    }

    /**
     * @return array
     */
    public function evalProvider()
    {
        $map =  new ArrayMap();
        $map->setState([
            'somevar' => 'Bingo!'
        ]);
        return [
            ['"somevar"', ['map' => $map], 'somevar'],
            ['map.somevar', ['map' => $map], 'Bingo!'],
            ['"Bingo!"', ['map' => $map], 'Bingo!'],
            [10, ['map' => $map], 10],
            ['false', ['map' => $map], false],
            [function ($map) {
                return $map['somevar'];
            }, ['map' => $map], 'Bingo!'],
        ];
    }

    /**
     * @param mixed $expression
     * @expectedException \InvalidArgumentException
     * @dataProvider wrongEvalProvider
     */
    public function testEvalWrongExpression($expression)
    {
        $adapter = new LanguageAdapter(new ExpressionLanguage());
        $adapter->evaluate($expression, []);
    }

    /**
     * @return array
     */
    public function wrongEvalProvider()
    {
        return [
            [false],
            [new \stdClass()],
            [[]],
        ];
    }
}

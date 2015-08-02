<?php

namespace Maketok\DataMigration\Input\Shaper;

use Maketok\DataMigration\ArrayMap;
use Maketok\DataMigration\Expression\LanguageAdapter;
use Maketok\DataMigration\Unit\SimpleBag;
use Maketok\DataMigration\Unit\Type\Unit;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param UnitBagInterface $bag
     * @return Processor
     */
    public function getShaper(UnitBagInterface $bag)
    {
        return new Processor($bag, new ArrayMap(), new LanguageAdapter(new ExpressionLanguage()));
    }

    public function testSimpleBag()
    {
        $unit = new Unit('test');
        $bag = new SimpleBag();
        $bag->add($unit);

        $rows = [
            ['code' => 'test', 'id' => 1, 'name' => 'bar'],
            ['code' => 'test2', 'id' => 11, 'name' => 'baz'],
        ];

        $shaper = $this->getShaper($bag);

        foreach ($rows as $row) {
            $this->assertSame($row, $shaper->feed($row));
        }
        $this->assertFalse($shaper->feed([]));
    }

    public function testTwoLevelBag()
    {
        $unit1 = new Unit('customer');
        $unit1->setIsEntityCondition(function ($map) {
            return !empty($map['email']);
        });
        $unit2 = new Unit('address');
        $unit2->setParent($unit1);

        $unit3 = new Unit('address_data');
        $unit3->addSibling($unit2);

        $bag = new SimpleBag();
        $bag->addSet([$unit1, $unit2, $unit3]);

        $shaper = $this->getShaper($bag);

        $rows = [
            ['email' => 'bob@example.com', 'name' => 'bob', 'street' => 'charity str.'],
            ['email' => 'paul@example.com', 'name' => 'paul', 'street' => 'buckingham ave.'],
            ['email' => null, 'name' => null, 'street' => 'mirabelle str.'],
        ];
        $this->assertFalse($shaper->feed($rows[0]));
        $this->assertSame([
            'email' => 'bob@example.com',
            'name' => 'bob',
            'street' => 'charity str.',
            'address' => [
                [
                    'email' => 'bob@example.com',
                    'name' => 'bob',
                    'street' => 'charity str.',
                ],
            ]
        ], $shaper->feed($rows[1]));
        $this->assertFalse($shaper->feed($rows[2]));
        $this->assertSame([
            'email' => 'paul@example.com',
            'name' => 'paul',
            'street' => 'buckingham ave.',
            'address' => [
                [
                    'email' => 'paul@example.com',
                    'name' => 'paul',
                    'street' => 'buckingham ave.',
                ],
                [
                    'email' => null,
                    'name' => null,
                    'street' => 'mirabelle str.',
                ],
            ]
        ], $shaper->feed([]));
        $this->assertFalse($shaper->feed([]));
    }
}

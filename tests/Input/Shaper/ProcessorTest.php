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
        return $this->getMockBuilder('Maketok\DataMigration\Input\Shaper\Processor')->setConstructorArgs([
            $bag,
            new ArrayMap(),
            new LanguageAdapter(new ExpressionLanguage())
        ])->getMockForAbstractClass();
    }

    public function testSimpleBagFeed()
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

    public function testTwoLevelFeed()
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

    public function testThreeLevelFeed()
    {
        $unit1 = new Unit('customer');
        $unit1->setIsEntityCondition(function ($map) {
            return !empty($map['email']);
        });
        $unit2 = new Unit('address');
        $unit2->setParent($unit1);
        $unit2->setIsEntityCondition(function ($map, $oldmap) {
            return $map['street'] != $oldmap['street'];
        });

        $unit3 = new Unit('address_data');
        $unit3->setParent($unit2);

        $bag = new SimpleBag();
        $bag->addSet([$unit1, $unit2, $unit3]);

        $shaper = $this->getShaper($bag);

        $rows = [
            ['email' => 'bob@example.com', 'name' => 'bob', 'street' => 'charity str.', 'phone' => '123'],
            ['email' => null, 'name' => null, 'street' => 'charity str.', 'phone' => '432'],
            ['email' => 'paul@example.com', 'name' => 'paul', 'street' => 'buckingham ave.', 'phone' => '222'],
            ['email' => null, 'name' => null, 'street' => 'mirabelle str.', 'phone' => '323'],
        ];
        $this->assertFalse($shaper->feed($rows[0]));
        $this->assertFalse($shaper->feed($rows[1]));
        $this->assertSame([
            'email' => 'bob@example.com',
            'name' => 'bob',
            'street' => 'charity str.',
            'phone' => '123',
            'address' => [
                [
                    'email' => 'bob@example.com',
                    'name' => 'bob',
                    'street' => 'charity str.',
                    'phone' => '123',
                    'address_data' => [
                        [
                            'email' => 'bob@example.com',
                            'name' => 'bob',
                            'street' => 'charity str.',
                            'phone' => '123',
                        ],
                        [
                            'email' => null,
                            'name' => null,
                            'street' => 'charity str.',
                            'phone' => '432',
                        ]
                    ],
                ],
            ]
        ], $shaper->feed($rows[2]));
        $this->assertFalse($shaper->feed($rows[3]));
        $this->assertSame([
            'email' => 'paul@example.com',
            'name' => 'paul',
            'street' => 'buckingham ave.',
            'phone' => '222',
            'address' => [
                [
                    'email' => 'paul@example.com',
                    'name' => 'paul',
                    'street' => 'buckingham ave.',
                    'phone' => '222',
                    'address_data' => [
                        [
                            'email' => 'paul@example.com',
                            'name' => 'paul',
                            'street' => 'buckingham ave.',
                            'phone' => '222',
                        ]
                    ],
                ],
                [
                    'email' => null,
                    'name' => null,
                    'street' => 'mirabelle str.',
                    'phone' => '323',
                    'address_data' => [
                        [
                            'email' => null,
                            'name' => null,
                            'street' => 'mirabelle str.',
                            'phone' => '323',
                        ]
                    ],
                ],
            ]
        ], $shaper->feed([]));
        $this->assertFalse($shaper->feed([]));
    }
}

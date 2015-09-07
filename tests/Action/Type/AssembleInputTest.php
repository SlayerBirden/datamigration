<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\ArrayMap;
use Maketok\DataMigration\Expression\LanguageAdapter;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class AssembleInputTest extends \PHPUnit_Framework_TestCase
{
    use ServiceGetterTrait;

    /**
     * @var AssembleInput
     */
    private $action;

    public function setUp()
    {
        $this->action = $this->getAction();
    }

    /**
     * @param array $units
     * @param array $inputExpectations
     * @return AssembleInput
     */
    public function getAction($units = [], $inputExpectations = [])
    {
        return new AssembleInput(
            $this->getUnitBag($units),
            $this->getConfig(),
            new LanguageAdapter(new ExpressionLanguage()),
            $this->getInputResource($inputExpectations),
            new ArrayMap()
        );
    }

    public function testGetCode()
    {
        $this->assertEquals('assemble_input', $this->action->getCode());
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
     * @return ResourceInterface
     */
    protected function getFS($returns = [])
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        $cntReturns = count($returns);
        $method = $filesystem->expects($this->exactly($cntReturns))
            ->method('readRow');
        call_user_func_array([$method, 'willReturnOnConsecutiveCalls'], $returns);
        return $filesystem;
    }

    /**
     * standard process
     * 2 branches 1 leaf
     */
    public function testProcess()
    {
        $unit1 = $this->getUnit('customer');
        $unit1->setReversedMapping([
            'email' => 'map.email',
            'age' => 'map.age',
        ]);
        $unit1->setReversedConnection([
            'customer_id' => 'id',
        ]);
        $unit1->setMapping([
            'id' => 'map.id',
            'email' => 'map.email',
            'age' => 'map.age',
        ]);
        $unit1->setFilesystem($this->getFS(
            [
                [1, 'tst1@example.com', 30],
                [2, 'pete111@eol.com', 33],
                [3, 'bm@gmail.com', 55],
                [4, 'pp@gmail.com', 11],
                false,
            ]
        ));
        $unit1->setIsEntityCondition(function (
            MapInterface $map,
            MapInterface $oldmap
        ) {
            return $oldmap->offsetGet('email') != $map->offsetGet('email');
        });
        $unit1->setTmpFileName('customer_tmp.csv');

        $unit2 = $this->getUnit('customer_data');
        $unit2->setReversedMapping([
            'name' => function ($map) {
                return $map['fname'] . ' ' . $map['lname'];
            },
        ]);
        $unit2->setReversedConnection([
            'customer_id' => 'parent_id',
        ]);
        $unit2->setMapping([
            'id' => 'map.data_id',
            'parent_id' => 'map.id',
            'fname' => function ($map) {
                list($fname) = explode(" ", $map['name']);
                return $fname;
            },
            'lname' => function ($map) {
                list(, $lname) = explode(" ", $map['name']);
                return $lname;
            },
        ]);
        $unit2->setTmpFileName('customer_data_tmp.csv');
        $unit2->setFilesystem($this->getFS(
            [
                [1, 1, 'Olaf', 'Stone'],
                [2, 2,  'Peter', 'Ostridge'],
                [3, 3, 'Bill', 'Murray'],
                [4, 4, 'Peter', 'Pan'],
                false,
            ]
        ));
        $unit2->addSibling($unit1);

        $unit3 = $this->getUnit('address');
        $unit3->setReversedMapping([
            'addr_city' => 'map.city',
            'addr_street' => 'map.street',
        ]);
        $unit3->setReversedConnection([
            'customer_id' => 'parent_id',
        ]);
        $unit3->setMapping([
            'id' => 'map.addr_id',
            'street' => 'map.addr_street',
            'city' => 'map.addr_city',
            'parent_id' => 'map.id',
        ]);
        $unit3->addContribution(function (MapInterface $map) {
            $map->incr('addr_id', 1);
        });
        $unit3->setFilesystem($this->getFS(
            [
                [1, '4100 Marine dr. App. 54', 'Chicago', 1],
                [2, '3300 St. George, Suite 300', 'New York', 1],
                [3, '111 W Jackson', 'Chicago', 2],
                [4, '111 W Jackson-2', 'Chicago', 3],
                [5, '111 W Jackson-3', 'Chicago', 3],
                [6, 'Hollywood', 'LA', 3],
                [7, 'fake', 'LA', 3],
                [8, 'Fairy Tale', 'NY', 4],
                false,
            ]
        ));
        $unit3->setTmpFileName('address_tmp.csv');
        $unit3->setParent($unit1);

        $expected = [
            [[
                'email' => 'tst1@example.com',
                'name' => 'Olaf Stone',
                'age' => 30,
                'address' => [
                    [
                        'addr_city' => 'Chicago',
                        'addr_street' => '4100 Marine dr. App. 54',
                    ],
                    [
                        'addr_city' => 'New York',
                        'addr_street' => '3300 St. George, Suite 300',
                    ],
                ]
            ]],
            [[
                'email' => 'pete111@eol.com',
                'name' => 'Peter Ostridge',
                'age' => 33,
                'address' => [
                    [
                        'addr_city' => 'Chicago',
                        'addr_street' => '111 W Jackson',
                    ]
                ]
            ]],
            [[
                'email' => 'bm@gmail.com',
                'name' => 'Bill Murray',
                'age' => 55,
                'address' => [
                    [
                        'addr_city' => 'Chicago',
                        'addr_street' => '111 W Jackson-2',
                    ],
                    [
                        'addr_city' => 'Chicago',
                        'addr_street' => '111 W Jackson-3',
                    ],
                    [
                        'addr_city' => 'LA',
                        'addr_street' => 'Hollywood',
                    ],
                    [
                        'addr_city' => 'LA',
                        'addr_street' => 'fake',
                    ]
                ]
            ]],
            [[
                'email' => 'pp@gmail.com',
                'name' => 'Peter Pan',
                'age' => 11,
                'address' => [
                    [
                        'addr_city' => 'NY',
                        'addr_street' => 'Fairy Tale',
                    ]
                ]
            ]],
        ];

        $action = $this->getAction([$unit1, $unit2, $unit3], $expected);
        $action->process($this->getResultMock());
    }

    /**
     * standard process
     * 1 branch 2 leaf
     */
    public function testProcess1b2l()
    {
        $unit1 = $this->getUnit('customer');
        $unit1->setReversedMapping([
            'email' => 'map.email',
            'name' => function ($map) {
                return $map['fname'] . ' ' . $map['lname'];
            },
            'age' => 'map.age',
        ]);
        $unit1->setReversedConnection([
            'customer_id' => 'id',
        ]);
        $unit1->setMapping([
            'id' => 'map.id',
            'email' => 'map.email',
            'age' => 'map.age',
            'fname' => function ($map) {
                list($fname) = explode(" ", $map['name']);
                return $fname;
            },
            'lname' => function ($map) {
                list(, $lname) = explode(" ", $map['name']);
                return $lname;
            },
        ]);
        $unit1->setFilesystem($this->getFS(
            [
                [1, 'tst1@example.com', 30, 'Olaf', 'Stone'],
                [2, 'pete111@eol.com', 33, 'Peter', 'Ostridge'],
                [3, 'bm@gmail.com', 55, 'Bill', 'Murray'],
                [4, 'pp@gmail.com', 11, 'Peter', 'Pan'],
                false,
            ]
        ));
        $unit1->setIsEntityCondition(function (
            MapInterface $map,
            MapInterface $oldmap
        ) {
            return $oldmap->offsetGet('email') != $map->offsetGet('email');
        });
        $unit1->setTmpFileName('customer_tmp.csv');

        $unit3 = $this->getUnit('address');
        $unit3->setReversedMapping([
            'addr_city' => 'map.city',
        ]);
        $unit3->setReversedConnection([
            'customer_id' => 'parent_id',
            'address_id' => 'id',
        ]);
        $unit3->setMapping([
            'id' => 'map.addr_id',
            'city' => 'map.addr_city',
            'parent_id' => 'map.id',
        ]);
        $unit3->addContribution(function (MapInterface $map) {
            $map->incr('addr_id', 1);
        });
        $unit3->setFilesystem($this->getFS(
            [
                [1, 'Chicago', 1],
                [2, 'New York', 1],
                [3, 'Chicago', 2],
                [4, 'Chicago', 3],
                [5, 'Chicago', 3],
                [6, 'LA', 3],
                [7, 'LA', 3],
                [8, 'NY', 4],
                false,
            ]
        ));
        $unit3->setTmpFileName('address_tmp.csv');
        $unit3->setParent($unit1);

        $unit2 = $this->getUnit('address_data');
        $unit2->setReversedMapping([
            'addr_street' => 'map.street',
        ]);
        $unit2->setReversedConnection([
            'address_id' => 'parent_id',
        ]);
        $unit2->setMapping([
            'id' => 'map.addr_data_id',
            'street' => 'map.addr_street',
            'parent_id' => 'map.address_id',
        ]);
        $unit2->setTmpFileName('customer_data_tmp.csv');
        $unit2->setFilesystem($this->getFS(
            [
                [1, '4100 Marine dr. App. 54', 1],
                [2, '3300 St. George, Suite 300', 2],
                [3, '111 W Jackson', 3],
                [4, '111 W Jackson-2', 4],
                [5, '111 W Jackson-3', 5],
                [6, 'Hollywood', 6],
                [7, 'fake', 7],
                [8, 'Fairy Tale', 8],
                false,
            ]
        ));
        $unit2->addSibling($unit3);

        $expected = [
            [[
                'email' => 'tst1@example.com',
                'name' => 'Olaf Stone',
                'age' => 30,
                'address' => [
                    [
                        'addr_city' => 'Chicago',
                        'addr_street' => '4100 Marine dr. App. 54',
                    ],
                    [
                        'addr_city' => 'New York',
                        'addr_street' => '3300 St. George, Suite 300',
                    ],
                ]
            ]],
            [[
                'email' => 'pete111@eol.com',
                'name' => 'Peter Ostridge',
                'age' => 33,
                'address' => [
                    [
                        'addr_city' => 'Chicago',
                        'addr_street' => '111 W Jackson',
                    ]
                ]
            ]],
            [[
                'email' => 'bm@gmail.com',
                'name' => 'Bill Murray',
                'age' => 55,
                'address' => [
                    [
                        'addr_city' => 'Chicago',
                        'addr_street' => '111 W Jackson-2',
                    ],
                    [
                        'addr_city' => 'Chicago',
                        'addr_street' => '111 W Jackson-3',
                    ],
                    [
                        'addr_city' => 'LA',
                        'addr_street' => 'Hollywood',
                    ],
                    [
                        'addr_city' => 'LA',
                        'addr_street' => 'fake',
                    ]
                ]
            ]],
            [[
                'email' => 'pp@gmail.com',
                'name' => 'Peter Pan',
                'age' => 11,
                'address' => [
                    [
                        'addr_city' => 'NY',
                        'addr_street' => 'Fairy Tale',
                    ]
                ]
            ]],
        ];

        $action = $this->getAction([$unit1, $unit2, $unit3], $expected);
        $action->process($this->getResultMock());
    }

    /**
     * test process with incorrect connection (addresses do not match customers)
     * @expectedException \LogicException
     * @expectedExceptionMessage Orphaned rows in some of the units
     */
    public function testProcess2()
    {
        $unit1 = $this->getUnit('customer');
        $unit1->setReversedMapping([
            'email' => 'map.email',
            'name' => function ($map) {
                return $map['fname'] . ' ' . $map['lname'];
            },
            'age' => 'map.age',
        ]);
        $unit1->setReversedConnection([
            'customer_id' => 'id',
        ]);
        $unit1->setMapping([
            'id' => 'map.id',
            'fname' => function ($map) {
                list($fname) = explode(" ", $map['name']);
                return $fname;
            },
            'lname' => function ($map) {
                list(, $lname) = explode(" ", $map['name']);
                return $lname;
            },
            'email' => 'map.email',
            'age' => 'map.age',
        ]);
        $unit1->setFilesystem($this->getFS(
            [
                [1, 'Olaf', 'Stone', 'tst1@example.com', 30],
                [2, 'Peter', 'Ostridge', 'pete111@eol.com', 33],
                false,
            ]
        ));
        $unit1->setTmpFileName('customer_tmp.csv');

        $unit2 = $this->getUnit('address');
        $unit2->setReversedMapping([
            'addr_city' => 'map.city',
            'addr_street' => 'map.street',
        ]);
        $unit2->setReversedConnection([
            'customer_id' => 'parent_id',
        ]);
        $unit2->setMapping([
            'id' => 'map.addr_id',
            'street' => 'map.addr_street',
            'city' => 'map.addr_city',
            'parent_id' => 'map.id',
        ]);
        $unit2->addContribution(function (MapInterface $map) {
            $map->incr('addr_id', 1);
        });
        $unit2->setFilesystem($this->getFS(
            [
                [1, '4100 Marine dr. App. 54', 'Chicago', 1],
                [2, '3300 St. George, Suite 300', 'New York', 1],
                [3, '111 W Jackson', 'Chicago', 4],
                [4, '111 W Jackson-2', 'Chicago', 4],
                // next rows will not be read due to LogicException
                // [5, 'Hollywood', 'LA', 4],
                // false,
            ]
        ));
        $unit2->setTmpFileName('address_tmp.csv');
        $unit2->setParent($unit1);

        $expected = [
            [[
                'email' => 'tst1@example.com',
                'name' => 'Olaf Stone',
                'age' => 30,
                'address' => [
                    [
                        'addr_city' => 'Chicago',
                        'addr_street' => '4100 Marine dr. App. 54',
                    ],
                    [
                        'addr_city' => 'New York',
                        'addr_street' => '3300 St. George, Suite 300',
                    ]
                ]
            ]],
        ];

        $action = $this->getAction([$unit1, $unit2], $expected);
        $action->process($this->getResultMock());
    }

    /**
     * process unit with incorrect map
     * @expectedException \LogicException
     * @expectedExceptionMessage Wrong reversed connection key given.
     */
    public function testProcessException1()
    {
        $unit1 = $this->getUnit('test');
        $unit1->setReversedMapping([
            'name' => 'field3',
        ]);
        $unit1->setReversedConnection([
            'id' => 'field4',
        ]);
        $unit1->setMapping([
            'field1' => 'name',
            'field2' => 'code',
        ]);
        $unit1->setFilesystem($this->getFS(
            [
                ['Pete', 'tst1']
            ]
        ));
        $unit1->setTmpFileName('customer_tmp.csv');

        $action = $this->getAction([$unit1]);
        $action->process($this->getResultMock());
    }

    /**
     * try to process units that do not match
     * no additions should be done
     */
    public function testProcessWeirdCase1()
    {
        $unit1 = $this->getUnit('test');
        $unit1->setReversedMapping([
            'name' => 'map.field1',
        ]);
        $unit1->setReversedConnection([
            'tid' => 'id',
        ]);
        $unit1->setMapping([
            'field1' => 'name',
            'field2' => 'code',
            'id' => 'id',
        ]);
        $unit1->setFilesystem($this->getFS(
            [
                ['Pete', 'tst1', '1'],
                false,
            ]
        ));
        $unit1->setTmpFileName('customer_tmp.csv');

        $unit2 = $this->getUnit('test2');
        $unit2->setReversedMapping([
            'name' => 'map.field1',
        ]);
        $unit2->setReversedConnection([
            'tid' => 'parent_id',
        ]);
        $unit2->setMapping([
            'field1' => 'name',
            'field2' => 'code',
            'parent_id' => 'id',
        ]);
        $unit2->setFilesystem($this->getFS(
            [
                ['Pete', 'tst1', '2'],
                false,
            ]
        ));
        $unit2->setTmpFileName('customer_tmp.csv');
        $unit2->setParent($unit1);

        $action = $this->getAction([$unit1, $unit2]);
        $action->process($this->getResultMock());
    }

    /**
     * try to process 2 units one of which is empty
     * nothing should happen here (not single addition nor exceptions)
     */
    public function testProcessWeirdCase2()
    {
        $unit1 = $this->getUnit('test');
        $unit1->setReversedMapping([
            'name' => 'map.field1',
        ]);
        $unit1->setReversedConnection([
            'tid' => 'id',
        ]);
        $unit1->setMapping([
            'field1' => 'name',
            'field2' => 'code',
            'id' => 'id',
        ]);
        $unit1->setFilesystem($this->getFS(
            [
                ['Pete', 'tst1', '1'],
                 false,
            ]
        ));
        $unit1->setTmpFileName('customer_tmp.csv');

        $unit2 = $this->getUnit('test2');
        $unit2->setReversedMapping([
            'name' => 'map.field1',
        ]);
        $unit2->setReversedConnection([
            'tid' => 'parent_id',
        ]);
        $unit2->setMapping([
            'field1' => 'name',
            'field2' => 'code',
            'parent_id' => 'id',
        ]);
        $unit2->setFilesystem($this->getFS(
            [
                false
            ]
        ));
        $unit2->setTmpFileName('customer_tmp.csv');
        $unit2->setParent($unit1);

        $action = $this->getAction([$unit1, $unit2]);
        $action->process($this->getResultMock());
    }

    /**
     * @expectedException \Maketok\DataMigration\Action\Exception\WrongContextException
     * @expectedExceptionMessage Action can not be used for current unit test121312
     */
    public function testWrongProcess()
    {
        $action = $this->getAction([$this->getUnit('test121312')]);
        $action->process($this->getResultMock());
    }

    /**
     * Test that units which have same keys for data (field1)
     * append unit code as prefix for it
     * @throws \Exception
     * @test
     */
    public function testSameCodes()
    {
        $unit1 = $this->getUnit('test');
        $unit1->setReversedMapping([
            'name' => 'map.test_field1',
        ]);
        $unit1->setReversedConnection([
            'tid' => 'id',
        ]);
        $unit1->setMapping([
            'field1' => 'name',
            'field2' => 'code',
            'id' => 'id',
        ]);
        $unit1->setFilesystem($this->getFS(
            [
                ['Pete', 'tst1', '1'],
                false,
            ]
        ));
        $unit1->setTmpFileName('test_tmp.csv');

        $unit2 = $this->getUnit('test2');
        $unit2->setReversedMapping([
            'secondName' => 'map.test2_field1',
        ]);
        $unit2->setReversedConnection([
            'tid' => 'parent_id',
        ]);
        $unit2->setMapping([
            'field1' => 'name',
            'field2' => 'code',
            'parent_id' => 'id',
        ]);
        $unit2->setFilesystem($this->getFS(
            [
                ['George', 'tst2', '1'],
                false,
            ]
        ));
        $unit2->setTmpFileName('test2_tmp.csv');
        $unit2->addSibling($unit1);

        $expected = [
            [[
                'name' => 'Pete',
                'secondName' => 'George',
            ]],
        ];

        $action = $this->getAction([$unit1, $unit2], $expected);
        $action->process($this->getResultMock());
    }

    /**
     * test inconsistent siblings process
     * when some of the sibling can be absent for current row
     */
    public function testInconsistentSiblings()
    {
        $unit1 = $this->getUnit('shipment');
        $unit1->setReversedMapping([
            'total' => 'map.total'
        ]);
        $unit1->setReversedConnection([
            'shipment_id' => 'shipment_id',
        ]);
        $unit1->setMapping([
            'shipment_id' => 'map.shipment_id',
            'total' => 'map.total',
        ]);
        $unit1->setFilesystem($this->getFS(
            [
                [1, 30],
                [2, 33],
                [3, 55],
                [4, 11],
                false,
            ]
        ));
        $unit1->setIsEntityCondition(function (MapInterface $map) {
            return !empty($map['shipment_id']);
        });
        $unit1->setTmpFileName('shipment_tmp.csv');

        $unit3 = $this->getUnit('tracking');
        $unit3->setReversedMapping([
            'tracking_number' => 'map.tracking_number',
        ]);
        $unit3->setReversedConnection([
            'shipment_id' => 'shipment_id',
        ]);
        $unit3->setMapping([
            'id' => 'map.incr("track_id", 1)',
            'tracking_number' => 'map.tracking_number',
            'shipment_id' => 'map.shipment_id',
        ]);
        $unit3->setFilesystem($this->getFS(
            [
                [1, '56465454', 1],
                [2, '13122333', 3],
                [3, '13343443', 4],
                false,
            ]
        ));
        $unit3->setTmpFileName('tracking_tmp.csv');
        $unit3->addSibling($unit1);

        $expected = [
            [[
                'total' => 30,
                'tracking_number' => '56465454',
            ]],
            [[
                'total' => 33,
                'tracking_number' => null,
            ]],
            [[
                'total' => 55,
                'tracking_number' => '13122333',
            ]],
            [[
                'total' => 11,
                'tracking_number' => '13343443',
            ]],
        ];

        $action = $this->getAction([$unit1, $unit3], $expected);
        $action->process($this->getResultMock());
    }

    /**
     * test inconsistent siblings process
     * when some of the sibling can be absent for current row
     */
    public function testInconsistentSiblings2Levels()
    {
        $unit1 = $this->getUnit('shipment');
        $unit1->setReversedMapping([
            'total' => 'map.total'
        ]);
        $unit1->setReversedConnection([
            'shipment_id' => 'shipment_id',
        ]);
        $unit1->setMapping([
            'shipment_id' => 'map.shipment_id',
            'total' => 'map.total',
        ]);
        $unit1->setFilesystem($this->getFS(
            [
                [1, 30],
                [2, 33],
                [3, 55],
                [4, 11],
                false,
            ]
        ));
        $unit1->setIsEntityCondition(function (MapInterface $map) {
            return !empty($map['shipment_id']);
        });
        $unit1->setTmpFileName('shipment_tmp.csv');

        $unit2 = $this->getUnit('item');
        $unit2->setReversedMapping([
            'item_name' => 'map.name',
        ]);
        $unit2->setReversedConnection([
            'shipment_id' => 'shipment_id',
        ]);
        $unit2->setMapping([
            'id' => 'map.incr("item_id", 1)',
            'name' => 'map.item_name',
            'shipment_id' => 'map.shipment_id',
        ]);
        $unit2->setFilesystem($this->getFS(
            [
                [1, 'cool item', 1],
                [2, 'another super item', 1],
                [3, 'Coca Cola', 2],
                [4, 'Pepsi', 3],
                [5, 'pants', 4],
                false,
            ]
        ));
        $unit2->setTmpFileName('item_tmp.csv');
        $unit2->setParent($unit1);

        $unit3 = $this->getUnit('tracking');
        $unit3->setReversedMapping([
            'tracking_number' => 'map.tracking_number',
        ]);
        $unit3->setReversedConnection([
            'shipment_id' => 'shipment_id',
        ]);
        $unit3->setMapping([
            'id' => 'map.incr("track_id", 1)',
            'tracking_number' => 'map.tracking_number',
            'shipment_id' => 'map.shipment_id',
        ]);
        $unit3->setFilesystem($this->getFS(
            [
                [1, '56465454', 1],
                [2, '13122333', 3],
                [3, '13343443', 4],
                false,
            ]
        ));
        $unit3->setTmpFileName('tracking_tmp.csv');
        $unit3->addSibling($unit1);

        $expected = [
            [[
                'total' => 30,
                'tracking_number' => '56465454',
                'item' => [
                    [
                        'item_name' => 'cool item'
                    ],
                    [
                        'item_name' => 'another super item'
                    ],
                ],
            ]],
            [[
                'total' => 33,
                'tracking_number' => null,
                'item' => [
                    [
                        'item_name' => 'Coca Cola'
                    ],
                ],
            ]],
            [[
                'total' => 55,
                'tracking_number' => '13122333',
                'item' => [
                    [
                        'item_name' => 'Pepsi'
                    ],
                ],
            ]],
            [[
                'total' => 11,
                'tracking_number' => '13343443',
                'item' => [
                    [
                        'item_name' => 'pants'
                    ],
                ],
            ]],
        ];

        $action = $this->getAction([$unit1, $unit2, $unit3], $expected);
        $action->process($this->getResultMock());
    }
}

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
                [4, '111 W Jackson-2', 'Chicago', 2],
                [5, 'Hollywood', 'LA', 3],
                [6, 'fake', 'LA', 3],
                [7, 'Fairy Tale', 'NY', 4],
                false,
            ]
        ));
        $unit3->setTmpFileName('address_tmp.csv');

        $expected = [
            [['email' => 'tst1@example.com', 'name' => 'Olaf Stone', 'age' => 30, 'addr_city' => 'Chicago',
                'addr_street' => '4100 Marine dr. App. 54']],
            [['email' => 'tst1@example.com', 'name' => 'Olaf Stone', 'age' => 30, 'addr_city' => 'New York',
                'addr_street' => '3300 St. George, Suite 300']],
            [['email' => 'pete111@eol.com', 'name' => 'Peter Ostridge', 'age' => 33, 'addr_city' => 'Chicago',
                'addr_street' => '111 W Jackson']],
            [['email' => 'pete111@eol.com', 'name' => 'Peter Ostridge', 'age' => 33, 'addr_city' => 'Chicago',
                'addr_street' => '111 W Jackson-2']],
            [['email' => 'bm@gmail.com', 'name' => 'Bill Murray', 'age' => 55, 'addr_city' => 'LA',
                'addr_street' => 'Hollywood']],
            [['email' => 'bm@gmail.com', 'name' => 'Bill Murray', 'age' => 55, 'addr_city' => 'LA',
                'addr_street' => 'fake']],
            [['email' => 'pp@gmail.com', 'name' => 'Peter Pan', 'age' => 11, 'addr_city' => 'NY',
                'addr_street' => 'Fairy Tale']],
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

        $expected = [
            [['email' => 'tst1@example.com', 'name' => 'Olaf Stone', 'age' => 30, 'addr_city' => 'Chicago',
                'addr_street' => '4100 Marine dr. App. 54']],
            [['email' => 'tst1@example.com', 'name' => 'Olaf Stone', 'age' => 30, 'addr_city' => 'New York',
                'addr_street' => '3300 St. George, Suite 300']],
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
     * @expectedException \LogicException
     * @expectedExceptionMessage Conflict is in the first row of given units. Will not process further.
     */
    public function testProcessWeirdCase1()
    {
        $unit1 = $this->getUnit('test');
        $unit1->setReversedMapping([
            'name' => 'field1',
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
                ['Pete', 'tst1', '1']
            ]
        ));
        $unit1->setTmpFileName('customer_tmp.csv');

        $unit2 = $this->getUnit('test2');
        $unit2->setReversedMapping([
            'name' => 'field1',
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
                ['Pete', 'tst1', '2']
            ]
        ));
        $unit2->setTmpFileName('customer_tmp.csv');

        $action = $this->getAction([$unit1, $unit2]);
        $action->process($this->getResultMock());
    }

    /**
     * try to process 2 units one of which is empty
     * nothing should happen here (not single addition nor exceptions)
     * @expectedException \LogicException
     * @expectedExceptionMessage Orphaned rows in some of the units
     */
    public function testProcessWeirdCase2()
    {
        $unit1 = $this->getUnit('test');
        $unit1->setReversedMapping([
            'name' => 'field1',
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
                // next rows will not be read due to LogicException
                // false,
            ]
        ));
        $unit1->setTmpFileName('customer_tmp.csv');

        $unit2 = $this->getUnit('test2');
        $unit2->setReversedMapping([
            'name' => 'field1',
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
}

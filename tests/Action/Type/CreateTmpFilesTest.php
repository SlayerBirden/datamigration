<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\ArrayMap;
use Maketok\DataMigration\Expression\LanguageAdapter;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;

class CreateTmpFilesTest extends \PHPUnit_Framework_TestCase
{
    use ServiceGetterTrait;

    public function testGetCode()
    {
        $action = new CreateTmpFiles(
            $this->getUnitBag(),
            $this->getConfig(),
            new LanguageAdapter(),
            $this->getInputResource(),
            new ArrayMap(),
            $this->getResourceHelper()
        );
        $this->assertEquals('create_tmp_files', $action->getCode());
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
     * @param array $toReturn
     * @return InputResourceInterface
     */
    protected function getInputResource(array $toReturn = [])
    {
        $input = $this->getMockBuilder('\Maketok\DataMigration\Input\InputResourceInterface')
            ->getMock();
        $method = $input->expects($this->any())
            ->method('get');
        call_user_func_array([$method, 'willReturnOnConsecutiveCalls'], $toReturn);
        return $input;
    }

    /**
     * @param array $with
     * @return ResourceInterface
     */
    protected function getFS($with = [])
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        $method = $filesystem->expects($this->exactly(count($with)))->method('writeRow');
        call_user_func_array([$method, 'withConsecutive'], $with);
        return $filesystem;
    }

    /**
     * input type
     * - customer,address1,address2
     * - customer,address1,address2
     * ...
     */
    public function testProcess()
    {
        $inputs = [
            ['email' => 'tst1@example.com', 'name' => 'Olaf Stone', 'age' => 30, 'addr1_city' => 'Chicago',
            'addr1_street' => '4100 Marine dr. App. 54', 'addr2_city' => 'New York',
            'addr2_street' => '3300 St. George, Suite 300'],
            ['email' => 'pete111@eol.com', 'name' => 'Peter Ostridge', 'age' => 33, 'addr1_city' => 'Chicago',
            'addr1_street' => '5011 Sunnyside ave', 'addr2_city' => 'Chicago',
            'addr2_street' => '111 W Jackson'],
            false
        ];
        $unit1 = $this->getUnit('customer')
            ->setMapping([
                'id' => 'id',
                'fname' => function ($row) {
                    list($fname) = explode(" ", $row['name']);
                    return $fname;
                },
                'lname' => function ($row) {
                    list(, $lname) = explode(" ", $row['name']);
                    return $lname;
                },
                'email' => 'email',
                'age' => 'age',
            ])
            ->addContribution(function (MapInterface $map) {
                $map->incr('id', 1);
            })
            ->setFilesystem($this->getFS(
                [
                    [[1, 'Olaf', 'Stone', 'tst1@example.com', 30]],
                    [[2, 'Peter', 'Ostridge', 'pete111@eol.com', 33]],
                ]
            ));
        $unit2 = $this->getUnit('address1')
            ->setMapping([
                'id' => 'addr_id',
                'street' => 'addr1_street',
                'city' => 'addr1_city',
                'parent_id' => 'id',
            ])
            ->addContribution(function (MapInterface $map) {
                $map->incr('addr_id', 1);
            })
            ->setFilesystem($this->getFS(
                [
                    [[1, '4100 Marine dr. App. 54', 'Chicago', 1]],
                    [[3, '5011 Sunnyside ave', 'Chicago', 2]],
                ]
            ));
        $unit3 = $this->getUnit('address2')
            ->setMapping([
                'id' => 'addr_id',
                'street' => 'addr2_street',
                'city' => 'addr2_city',
                'parent_id' => 'id',
            ])
            ->addContribution(function (MapInterface $map) {
                $map->incr('addr_id', 1);
            })
            ->setFilesystem($this->getFS(
                [
                    [[2, '3300 St. George, Suite 300', 'New York', 1]],
                    [[4, '111 W Jackson', 'Chicago', 2]],
                ]
            ));

        $action = new CreateTmpFiles(
            $this->getUnitBag([$unit1, $unit2, $unit3]),
            $this->getConfig(),
            new LanguageAdapter(),
            $this->getInputResource($inputs),
            new ArrayMap(),
            $this->getResourceHelper()
        );
        $action->process();

        $this->assertEquals('/tmp/customer.csv',
            $unit1->getTmpFileName());
        $this->assertEquals('/tmp/address1.csv',
            $unit2->getTmpFileName());
        $this->assertEquals('/tmp/address2.csv',
            $unit3->getTmpFileName());
    }

    /**
     * input type
     * - customer1,address1
     * - customer1,address2
     * - customer2,address1
     * ...
     */
    public function testProcess2()
    {
        $inputs = [
            ['email' => 'tst1@example.com', 'name' => 'Olaf Stone', 'age' => 30, 'addr_city' => 'Chicago',
                'addr_street' => '4100 Marine dr. App. 54'],
            ['email' => 'tst1@example.com', 'name' => 'Olaf Stone', 'age' => 30, 'addr_city' => 'New York',
                'addr_street' => '3300 St. George, Suite 300'],
            ['email' => 'pete111@eol.com', 'name' => 'Peter Ostridge', 'age' => 33, 'addr_city' => 'Chicago',
                'addr_street' => '111 W Jackson'],
            false
        ];
        $unit1 = $this->getUnit('customer')
            ->setMapping([
                'id' => 'id',
                'fname' => function ($map) {
                    list($fname) = explode(" ", $map['name']);
                    return $fname;
                },
                'lname' => function ($map) {
                    list(, $lname) = explode(" ", $map['name']);
                    return $lname;
                },
                'email' => 'email',
                'age' => 'age',
            ])
            ->addContribution(function (MapInterface $map) {
                $map->frozenIncr('id', 1);
            })
            ->setFilesystem($this->getFS(
                [
                    [[1, 'Olaf', 'Stone', 'tst1@example.com', 30]],
                    [[2, 'Peter', 'Ostridge', 'pete111@eol.com', 33]],
                ]
            ))->setIsEntityCondition(function (
                MapInterface $map,
                MapInterface $oldmap
            ) {
                return $oldmap->offsetGet('email') != $map->offsetGet('email');
            });
        $unit2 = $this->getUnit('address')
            ->setMapping([
                'id' => 'addr_id',
                'street' => 'addr_street',
                'city' => 'addr_city',
                'parent_id' => 'id',
            ])
            ->addContribution(function (MapInterface $map) {
                $map->incr('addr_id', 1);
            })
            ->setFilesystem($this->getFS(
                [
                    [[1, '4100 Marine dr. App. 54', 'Chicago', 1]],
                    [[2, '3300 St. George, Suite 300', 'New York', 1]],
                    [[3, '111 W Jackson', 'Chicago', 2]],
                ]
            ));

        $action = new CreateTmpFiles(
            $this->getUnitBag([$unit1, $unit2]),
            $this->getConfig(),
            new LanguageAdapter(),
            $this->getInputResource($inputs),
            new ArrayMap(),
            $this->getResourceHelper()
        );
        $action->process();

        $this->assertEquals('/tmp/customer.csv',
            $unit1->getTmpFileName());
        $this->assertEquals('/tmp/address.csv',
            $unit2->getTmpFileName());
    }
}

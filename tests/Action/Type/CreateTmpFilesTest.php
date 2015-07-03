<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;

class CreateTmpFilesTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCode()
    {
        $action = new CreateTmpFiles(
            $this->getUnitBag([$this->getUnit(['table1'])]),
            $this->getConfig(),
            $this->getFS(),
            $this->getInputResource([]),
            $this->getMap([]),
            $this->getResourceHelper()
        );
        $this->assertEquals('create_tmp_files', $action->getCode());
    }

    /**
     * @param $name
     * @return AbstractUnit
     */
    protected function getUnit($name)
    {
        /** @var AbstractUnit $unit */
        $unit = $this->getMockBuilder('\Maketok\DataMigration\Unit\AbstractUnit')
            ->getMockForAbstractClass();
        return $unit->setTable($name);
    }

    /**
     * @param AbstractUnit[] $units
     * @return UnitBagInterface
     */
    protected function getUnitBag($units)
    {
        $unitBag = $this->getMockBuilder('\Maketok\DataMigration\Unit\UnitBagInterface')
            ->getMock();
        $unitBag->expects($this->any())->method('add')->willReturnSelf();
        $unitBag->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($units));
        return $unitBag;
    }

    /**
     * @param array $data
     * @return MapInterface
     */
    protected function getMap($data)
    {
        $map = $this->getMockBuilder('\Maketok\DataMigration\MapInterface')
            ->getMock();
        if (count($data)) {
            $map->expects($this->any())
                ->method('doMapping')
                ->willReturnOnConsecutiveCalls($data[0], $data[1], $data[2], $data[3]);
            $map->expects($this->any())
                ->method('get')
                ->willReturnMap([
                    ['id', 2]
                ]);
        }
        return $map;
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
    protected function getInputResource(array $data)
    {
        $input = $this->getMockBuilder('\Maketok\DataMigration\Input\InputResourceInterface')
            ->getMock();
        if (count($data)) {
            $input->expects($this->any())
                ->method('get')
                ->willReturnOnConsecutiveCalls($data[0], $data[1], false, $data[0], $data[1], false);
        }
        return $input;
    }

    /**
     * @return ConfigInterface
     */
    protected function getConfig()
    {
        $config = $this->getMockBuilder('\Maketok\DataMigration\Action\ConfigInterface')
            ->getMock();
        $config->expects($this->any())->method('get')->willReturnMap([
            ['tmp_folder', '/tmp'],
            ['tmp_file_mask', '%1$s.csv'], // fname, date
        ]);
        return $config;
    }

    /**
     * @param bool $expect
     * @param int $number
     * @return ResourceInterface
     */
    protected function getFS($expect = false, $number = 4)
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        if ($expect) {
            $filesystem->expects($this->exactly(2))
                ->method('open');
            $filesystem->expects($this->exactly($number))->method('writeRow');
            $filesystem->expects($this->exactly(2))->method('close');
        }
        return $filesystem;
    }

    public function testProcess()
    {
        $inputs = [
            ['id' => '1', 'name' => 'someField', 'code' => 'otherField'],
            ['id' => '2', 'name' => 'someField2', 'code' => 'otherField2'],
        ];
        $dumpStates = [
            ['1', 'otherField', '1'],
            ['3', 'someField', '1'],
            ['2', 'otherField2', '1'],
            ['4', 'someField2', '2'],
        ];
        $unit1 = $this->getUnit('entity_table1');
        $unit1->setMapping([
            'entity_id' => 'id',
            'code' => 'code',
            'const' => '1',
        ])->setIsEntityCondition(function () {
            return true;
        });
        $unit2 = $this->getUnit('data_table1');
        $counter = new \stdClass();
        $counter->count = 2;
        $unit2->setMapping([
            'new_id' => function ($counter) {
                return ++$counter->count;
            },
            'name' => 'name',
            'parent_id' => 'id',
        ])->setIsEntityCondition(function (MapInterface $map) {
            return $map->get('id') == 2;
        });

        $action = new CreateTmpFiles(
            $this->getUnitBag([$unit1, $unit2]),
            $this->getConfig(),
            $this->getFS(true),
            $this->getInputResource($inputs),
            $this->getMap($dumpStates),
            $this->getResourceHelper()
        );
        $action->process();

        $this->assertEquals('/tmp/entity_table1.csv',
            $unit1->getTmpFileName());
        $this->assertEquals('/tmp/data_table1.csv',
            $unit2->getTmpFileName());
    }

    public function testProcessWriteCond()
    {
        $inputs = [
            ['id' => '1', 'name' => 'someField', 'code' => 'otherField'],
            ['id' => '2', 'name' => 'someField2', 'code' => 'otherField2'],
        ];
        $dumpStates = [
            ['1', 'otherField', '1'],
            ['3', 'someField', '1'],
            ['4', 'someField2', '2'],
            false,
        ];
        $unit1 = $this->getUnit('entity_table1');
        $unit1->setMapping([
            'entity_id' => 'id',
            'code' => 'code',
            'const' => '1',
        ])->setIsEntityCondition(function () {
            return true;
        });
        $unit2 = $this->getUnit('data_table1');
        $counter = new \stdClass();
        $counter->count = 2;
        $unit2->setMapping([
            'new_id' => function ($counter) {
                return ++$counter->count;
            },
            'name' => 'name',
            'parent_id' => 'id',
        ])->setIsEntityCondition(function (MapInterface $map) {
            return $map->get('id') == 2;
        })->addWriteCondition(function (array $row) {
            return $row['id'] == 2;
        });

        $action = new CreateTmpFiles(
            $this->getUnitBag([$unit1, $unit2]),
            $this->getConfig(),
            $this->getFS(true, 3), // CALLED 3 TIMES!!!
            $this->getInputResource($inputs),
            $this->getMap($dumpStates),
            $this->getResourceHelper()
        );
        $action->process();

        $this->assertEquals('/tmp/entity_table1.csv',
            $unit1->getTmpFileName());
        $this->assertEquals('/tmp/data_table1.csv',
            $unit2->getTmpFileName());
    }

    public function testProcessInvalid()
    {
        $inputs = [
            ['id' => '1', 'name' => 'someField', 'code' => 'otherField'],
            ['id' => '2', 'name' => 'someField2', 'code' => 'otherField2'],
        ];
        $dumpStates = [
            ['1', 'otherField', '1'],
            ['3', 'someField', '1'],
            ['2', 'otherField2', '1'],
            ['4', 'someField2', '2'],
        ];
        $unit1 = $this->getUnit('entity_table1');
        $unit1->setMapping([
            'entity_id' => 'id',
            'code' => 'code',
            'const' => '1',
        ])->setIsEntityCondition(function () {
            return true;
        })->addValidationRule(function (array $row) {
            return $row['id'] != 1;
        });
        $unit2 = $this->getUnit('data_table1');
        $counter = new \stdClass();
        $counter->count = 2;
        $unit2->setMapping([
            'new_id' => function ($counter) {
                return ++$counter->count;
            },
            'name' => 'name',
            'parent_id' => 'id',
        ])->setIsEntityCondition(function (MapInterface $map) {
            return $map->get('id') == 2;
        });

        $action = new CreateTmpFiles(
            $this->getUnitBag([$unit1, $unit2]),
            $this->getConfig(),
            $this->getFS(true, 2), // CALLED 2 TIMES!!!
            $this->getInputResource($inputs),
            $this->getMap($dumpStates),
            $this->getResourceHelper()
        );
        $action->process();

        $this->assertEquals('/tmp/entity_table1.csv',
            $unit1->getTmpFileName());
        $this->assertEquals('/tmp/data_table1.csv',
            $unit2->getTmpFileName());
    }
}

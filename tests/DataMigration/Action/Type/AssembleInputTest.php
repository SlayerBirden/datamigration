<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;

class AssembleInputTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCode()
    {
        $action = new AssembleInput(
            $this->getUnitBag([$this->getUnit('table1')]),
            $this->getConfig(),
            $this->getFS(),
            $this->getInputResource([])
        );
        $this->assertEquals('assemble_input', $action->getCode());
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
     * @return InputResourceInterface
     */
    protected function getInputResource(array $data)
    {
        $input = $this->getMockBuilder('\Maketok\DataMigration\Input\InputResourceInterface')
            ->getMock();
        for ($i = 0; $i < count($data); ++$i) {
            $input->expects($this->at($i))->method('add')->with($data[$i]);
        }
        if ($i > 0) {
            $input->expects($this->once())->method('assemble');
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
     * @return ResourceInterface
     */
    protected function getFS($expect = false)
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        if ($expect) {
            $filesystem->expects($this->exactly(2))
                ->method('open');
            // open 0
            $filesystem->expects($this->at(1))
                ->method('readRow')
                ->willReturn(['1', 'otherField', '1']);
            $filesystem->expects($this->at(2))
                ->method('readRow')
                ->willReturn(['2', 'otherField2', '1']);
            // close 3
            // open 4
            $filesystem->expects($this->at(5))
                ->method('readRow')
                ->willReturn(['3', 'someField2', '2']);
            // close 6
            $filesystem->expects($this->exactly(3))->method('readRow');
            $filesystem->expects($this->exactly(2))->method('close');
        }
        return $filesystem;
    }

    public function testProcess()
    {
        $expected = [
            ['id' => '1', 'name' => null, 'code' => 'otherField'],
            ['id' => '2', 'name' => 'someField2', 'code' => 'otherField2'],
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
        ])->setIsEntityCondition(function (array $row) {
            return $row['id'] == 2;
        });

        $action = new AssembleInput(
            $this->getUnitBag([$unit1, $unit2]),
            $this->getConfig(),
            $this->getFS(true),
            $this->getInputResource($expected)
        );
        $action->process();
    }
}

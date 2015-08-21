<?php

namespace Maketok\DataMigration\Unit;

class AbstractUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $code
     * @return AbstractUnit
     */
    public function getUnitMock($code)
    {
        return $this->getMockBuilder('\Maketok\DataMigration\Unit\AbstractUnit')
            ->setConstructorArgs([$code])
            ->getMockForAbstractClass();
    }

    public function testSiblings()
    {
        $uni1 = $this->getUnitMock('tst1');
        $uni2 = $this->getUnitMock('tst2');
        $uni3 = $this->getUnitMock('tst3');
        $uni4 = $this->getUnitMock('tst4');
        $uni5 = $this->getUnitMock('tst5');
        $uni6 = $this->getUnitMock('tst6');
        $uni7 = $this->getUnitMock('tst7');
        $uni8 = $this->getUnitMock('tst8'); // orphan on purpose :)
        $uni9 = $this->getUnitMock('tst9');

        $uni1->addSibling($uni2);
        $uni1->addSibling($uni3);
        $uni1->addSibling($uni4);
        $uni1->addSibling($uni5);
        $uni5->addSibling($uni6);
        $uni6->addSibling($uni7);
        $uni7->addSibling($uni9);

        $siblings = [
            'tst1' => $uni1,
            'tst2' => $uni2,
            'tst3' => $uni3,
            'tst4' => $uni4,
            'tst5' => $uni5,
            'tst6' => $uni6,
            'tst7' => $uni7,
            'tst9' => $uni9,
        ];

        /** @var AbstractUnit $unit */
        foreach ($siblings as $unit) {
            $toCompare = $siblings;
            unset($toCompare[$unit->getCode()]);
            $this->assertEquals($toCompare, $unit->getSiblings());
        }
    }
}

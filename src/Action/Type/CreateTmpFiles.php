<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;

/**
 * Disperse base input stream into separate units (tmp csv files) for further processing
 */
class CreateTmpFiles extends AbstractAction implements ActionInterface
{
    /**
     * @var InputResourceInterface
     */
    private $input;
    /**
     * @var MapInterface
     */
    private $map;
    /**
     * @var MapInterface
     */
    private $oldmap;
    /**
     * @var ResourceHelperInterface
     */
    private $helperResource;
    /**
     * @var ResourceInterface[]
     */
    private $handlers = [];
    /**
     * @var array
     */
    private $buffer = [];
    /**
     * @var bool
     */
    private $ignoreValidation = false;

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param ResourceInterface $filesystem
     * @param InputResourceInterface $input
     * @param MapInterface $map
     * @param ResourceHelperInterface $helperResource
     */
    public function __construct(
        UnitBagInterface $bag,
        ConfigInterface $config,
        ResourceInterface $filesystem,
        InputResourceInterface $input,
        MapInterface $map,
        ResourceHelperInterface $helperResource
    ) {
        parent::__construct($bag, $config, $filesystem);
        $this->input = $input;
        $this->map = $map;
        $this->helperResource = $helperResource;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        // TODO add hashtables
        $this->start();
        $valid = true;
        while (($row = $this->input->get()) !== false) {
            if ($this->map->isFresh($row)) {
                $this->map->feed($row);
            }
            foreach ($this->bag as $unit) {
                if (call_user_func_array($unit->getIsEntityCondition(), [
                    'map' => $this->map,
                    'resource' => $this->helperResource,
                    'oldmap' => $this->oldmap,
                ])) {
                    $this->dumpBuffer($valid, $unit);
                }
            }
            $valid = true;
            foreach ($this->bag as $unit) {
                $this->processAdditions($unit, $row);
                $valid &= $this->validate($unit, $row);
                $this->writeRowBuffered($unit, $row);
            }
        }
        $this->dumpBuffer($valid);
        $this->close();
    }
    /**
     * open handlers
     */
    private function start()
    {
        foreach ($this->bag as $unit) {
            $unit->setTmpFileName($this->getTmpFileName($unit));
            $handler = clone $this->filesystem;
            $handler->open($unit->getTmpFileName(), 'w');
            $this->handlers[$unit->getTable()] = $handler;
        }
    }

    /**
     * close all handlers
     */
    private function close()
    {
        foreach ($this->bag as $unit) {
            $handler = $this->handlers[$unit->getTable()];
            $handler->close();
        }
    }

    /**
     * @param AbstractUnit $unit
     * @param $row
     */
    private function processAdditions(AbstractUnit $unit, $row)
    {
        foreach ($unit->getContributions() as $contribution) {
            call_user_func_array($contribution, [
                'row' => $row,
                'map' => $this->map,
                'resource' => $this->helperResource,
            ]);
        }
    }

    /**
     * @param AbstractUnit $unit
     * @param $row
     */
    private function writeRowBuffered(AbstractUnit $unit, $row)
    {
        $shouldAdd = true;
        foreach ($unit->getWriteConditions() as $condition) {
            $shouldAdd = call_user_func_array($condition, [
                'row' => $row,
                'map' => $this->map,
                'resource' => $this->helperResource,
            ]);
            if (!$shouldAdd) {
                break 1;
            }
        }
        if ($shouldAdd) {
            $this->buffer[$unit->getTable()] = $this->map->doMapping(
                $row,
                $unit->getMapping(),
                $this->helperResource
            );
        }
    }

    /**
     * @param $unit
     * @param $row
     * @return bool
     */
    private function validate(AbstractUnit $unit, $row)
    {
        $valid = true;
        foreach ($unit->getValidationRules() as $validationRule) {
            $valid = call_user_func_array($validationRule, [
                'row' => $row,
                'map' => $this->map,
                'resource' => $this->helperResource,
            ]);
            if (!$valid) {
                break 1;
            }
        }
        return (bool) $valid;
    }

    /**
     * @param AbstractUnit $unit
     * @param bool $valid
     */
    private function dumpBuffer($valid, AbstractUnit $unit = null)
    {
        if ($valid || $this->ignoreValidation) {
            foreach ($this->buffer as $key => $dataArray) {
                if ($unit && $key != $unit->getTable()) {
                    continue;
                }
                if (!isset($this->handlers[$key])) {
                    throw new \LogicException(sprintf("No file handlers available for unit %s", $key));
                }
                $handler = $this->handlers[$key];
                $handler->writeRow($dataArray);
                unset($this->buffer[$key]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'create_tmp_files';
    }

    /**
     * @return boolean
     */
    public function isIgnoreValidation()
    {
        return $this->ignoreValidation;
    }

    /**
     * @param boolean $ignoreValidation
     * @return $this
     */
    public function setIgnoreValidation($ignoreValidation)
    {
        $this->ignoreValidation = $ignoreValidation;
        return $this;
    }
}

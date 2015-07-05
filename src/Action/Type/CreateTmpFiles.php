<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
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
     * @var array
     */
    private $buffer = [];

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param InputResourceInterface $input
     * @param MapInterface $map
     * @param ResourceHelperInterface $helperResource
     */
    public function __construct(
        UnitBagInterface $bag,
        ConfigInterface $config,
        InputResourceInterface $input,
        MapInterface $map,
        ResourceHelperInterface $helperResource
    ) {
        parent::__construct($bag, $config);
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
                $shouldDump = false;
                if (is_callable($unit->getIsEntityCondition())) {
                    $shouldDump = call_user_func_array($unit->getIsEntityCondition(), [
                        'map' => $this->map,
                        'resource' => $this->helperResource,
                        'oldmap' => $this->oldmap,
                    ]);
                } elseif (empty($unit)) {
                    $shouldDump = true;
                } else {
                    // todo expression
                }
                if ($shouldDump) {
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
            $unit->getFilesystem()->open($unit->getTmpFileName(), 'w');
        }
    }

    /**
     * close all handlers
     */
    private function close()
    {
        foreach ($this->bag as $unit) {
            $unit->getFilesystem()->close();
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
                'map' => $this->map,
                'row' => $row,
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
                'map' => $this->map,
                'row' => $row,
                'resource' => $this->helperResource,
            ]);
            if (!$shouldAdd) {
                break 1;
            }
        }
        if ($shouldAdd) {
            $this->buffer[$unit->getCode()] = array_map(function ($var) use ($row) {
                if (is_callable($var)) {
                    return call_user_func_array($var, [
                        'map' => $this->map,
                        'row' => $row,
                        'resource' => $this->helperResource,
                    ]);
                } else {
                    return $this->map->offsetGet($var);
                }
            }, $unit->getMapping());
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
            if (is_callable($validationRule)) {
                $valid = call_user_func_array($validationRule, [
                    'map' => $this->map,
                    'row' => $row,
                    'resource' => $this->helperResource,
                ]);
            } else {
                // todo expression
            }
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
        if ($valid || $this->config->offsetGet('ignore_validation')) {
            foreach ($this->buffer as $key => $dataArray) {
                if ($unit && $key != $unit->getCode()) {
                    continue;
                }
                $handler = false;
                if ($unit) {
                    $handler = $unit->getFilesystem();
                } else {
                    foreach ($this->bag as $u) {
                        if ($key == $u->getCode()) {
                            $handler = $u->getFilesystem();
                            break 1;
                        }
                    }
                }
                if ($handler) {
                    $handler->writeRow(array_values($dataArray));
                }
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
}

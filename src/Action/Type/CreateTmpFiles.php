<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Expression\LanguageInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Unit\ImportFileUnitInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;

/**
 * Disperse base input stream into separate units (tmp csv files) for further processing
 */
class CreateTmpFiles extends AbstractAction implements ActionInterface
{
    /**
     * @var UnitBagInterface|ImportFileUnitInterface[]
     */
    protected $bag;
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
     * is last processed entity valid
     * @var bool
     */
    private $isValid = true;
    /**
     * @var LanguageInterface
     */
    protected $language;

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param LanguageInterface $language
     * @param InputResourceInterface $input
     * @param MapInterface $map
     * @param ResourceHelperInterface $helperResource
     */
    public function __construct(
        UnitBagInterface $bag,
        ConfigInterface $config,
        LanguageInterface $language,
        InputResourceInterface $input,
        MapInterface $map,
        ResourceHelperInterface $helperResource
    ) {
        parent::__construct($bag, $config, $language);
        $this->input = $input;
        $this->map = $map;
        $this->helperResource = $helperResource;
        $this->language = $language;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        // TODO add hashtables
        $this->start();
        while (($row = $this->input->get()) !== false) {
            if ($this->map->isFresh($row)) {
                $this->map->feed($row);
            }
            $this->processDump();
            $this->processWrite();
        }
        $this->dumpBuffer();
        $this->close();
    }

    /**
     * dump buffered row (if needed)
     */
    private function processDump()
    {
        $shouldUnfreeze = true;
        foreach ($this->bag as $unit) {
            $isEntity = $unit->getIsEntityCondition();
            if (!isset($this->oldmap) || empty($unit) || empty($isEntity)) {
                $shouldDump = true;
            } elseif (is_callable($isEntity) || is_string($isEntity)) {
                $shouldDump = $this->language->evaluate($isEntity, [
                    'map' => $this->map,
                    'oldmap' => $this->oldmap,
                    'resource' => $this->helperResource,
                ]);
            } else {
                throw new \LogicException(
                    sprintf("Can not understand is Entity Condition for %s unit.", $unit->getCode())
                );
            }
            if ($shouldDump) {
                $this->dumpBuffer($unit);
            }
            $shouldUnfreeze &= $shouldDump;
        }
        // if all Units were dumped this row
        if ($shouldUnfreeze) {
            $this->map->unFreeze();
        }
    }

    /**
     * write row to buffer
     */
    private function processWrite()
    {
        $this->isValid = true;
        foreach ($this->bag as $unit) {
            $this->processAdditions($unit);
            $this->isValid &= $this->validate($unit);
            $this->writeRowBuffered($unit);
            $this->oldmap = clone $this->map;
        }
        $this->map->freeze();
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
     * @param ImportFileUnitInterface $unit
     */
    private function processAdditions(ImportFileUnitInterface $unit)
    {
        foreach ($unit->getContributions() as $contribution) {
            $this->language->evaluate($contribution, [
                'map' => $this->map,
                'resource' => $this->helperResource,
            ]);
        }
    }

    /**
     * @param ImportFileUnitInterface $unit
     */
    private function writeRowBuffered(ImportFileUnitInterface $unit)
    {
        $shouldAdd = true;
        foreach ($unit->getWriteConditions() as $condition) {
            $shouldAdd = $this->language->evaluate($condition, [
                'map' => $this->map,
                'resource' => $this->helperResource,
            ]);
            if (!$shouldAdd) {
                break 1;
            }
        }
        if ($shouldAdd) {
            $this->buffer[$unit->getCode()] = array_map(function ($var) {
                return $this->language->evaluate($var, [
                    'map' => $this->map,
                    'resource' => $this->helperResource,
                ]);
            }, $unit->getMapping());
        }
    }

    /**
     * @param ImportFileUnitInterface $unit
     * @return bool
     */
    private function validate(ImportFileUnitInterface $unit)
    {
        $valid = true;
        foreach ($unit->getValidationRules() as $validationRule) {
            $valid = $this->language->evaluate($validationRule, [
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
     * @param ImportFileUnitInterface $unit
     */
    private function dumpBuffer(ImportFileUnitInterface $unit = null)
    {
        if (!$this->isValid && !$this->config->offsetGet('ignore_validation')) {
            return;
        }
        foreach ($this->buffer as $key => $dataArray) {
            if ($unit && $key != $unit->getCode()) {
                continue;
            }
            $handler = false;
            if ($unit) {
                $handler = $unit->getFilesystem();
            } elseif (($tmpUnit = $this->bag->getUnitByCode($key)) !== false) {
                /** @var ImportFileUnitInterface $tmpUnit */
                $handler = $tmpUnit->getFilesystem();
            }
            if (is_object($handler)) {
                $handler->writeRow(array_values($dataArray));
            }
            unset($this->buffer[$key]);
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

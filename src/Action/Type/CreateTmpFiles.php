<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Action\Exception\NormalizationException;
use Maketok\DataMigration\ArrayUtilsTrait;
use Maketok\DataMigration\Expression\LanguageInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Storage\Exception\ParsingException;
use Maketok\DataMigration\Unit\ImportFileUnitInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Workflow\ResultInterface;

/**
 * Disperse base input stream into separate units (tmp csv files) for further processing
 */
class CreateTmpFiles extends AbstractAction implements ActionInterface
{
    use ArrayUtilsTrait;

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
     * @var ResultInterface
     */
    protected $result;

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
        parent::__construct($bag, $config);
        $this->input = $input;
        $this->map = $map;
        $this->helperResource = $helperResource;
        $this->language = $language;
    }

    /**
     * {@inheritdoc}
     * @throws \LogicException
     */
    public function process(ResultInterface $result)
    {
        $this->result = $result;
        $this->start();
        while (true) {
            try {
                $row = $this->input->get();
                if ($row === false) {
                    break 1;
                }
                if ($this->map->isFresh($row)) {
                    $this->map->feed($row);
                }
                $this->processDump();
                $this->processWrite();
            } catch (ParsingException $e) {
                if ($this->config['continue_on_error']) {
                    $this->result->addActionException($this->getCode(), $e);
                } else {
                    $this->close();
                    throw $e;
                }
            } catch (\Exception $e) {
                $this->close();
                throw $e;
            }
        }
        $this->dumpBuffer();
        $this->close();
    }

    /**
     * dump buffered row (if needed)
     * @throws \LogicException
     */
    private function processDump()
    {
        $shouldUnfreeze = true;
        foreach ($this->bag as $unit) {
            $isEntity = $unit->getIsEntityCondition();
            if (!isset($this->oldmap) || empty($isEntity)) {
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
            if (!$this->validate($unit)) {
                $this->isValid = false;
            }
            $this->writeRowBuffered($unit);
        }
        $this->oldmap = clone $this->map;
        $this->map->freeze();
    }

    /**
     * open handlers
     */
    private function start()
    {
        $this->result->setActionStartTime($this->getCode(), new \DateTime());
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
        $this->input->reset();
        $this->result->setActionEndTime($this->getCode(), new \DateTime());
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
                'hashmaps' => $unit->getHashmaps(),
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
                'hashmaps' => $unit->getHashmaps(),
            ]);
            if (!$shouldAdd) {
                break 1;
            }
        }
        if ($shouldAdd) {
            $row = array_map(function ($var) use ($unit) {
                return $this->language->evaluate($var, [
                    'map' => $this->map,
                    'resource' => $this->helperResource,
                    'hashmaps' => $unit->getHashmaps(),
                ]);
            }, $unit->getMapping());
            /**
             * Each unit can return rows multiple times in case it needs
             * but each mapped part should be returned equal times
             */
            try {
                $this->buffer[$unit->getCode()] = $this->normalize($row);
            } catch (NormalizationException $e) {
                $this->result->addActionException($this->getCode(), $e);
            }
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
                'hashmaps' => $unit->getHashmaps(),
            ]);
            if (!$valid) {
                $this->result->addActionError(
                    $this->getCode(),
                    sprintf(
                        "Invalid row %s for unit %s.",
                        json_encode($this->map->dumpState()),
                        $unit->getCode()
                    )
                );
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
        if (!$this->isValid && !$this->config['ignore_validation']) {
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
                foreach ($dataArray as $row) {
                    $written = $handler->writeRow(array_values($row));
                    if (false === $written) {
                        $this->result->addActionError(
                            $this->getCode(),
                            sprintf("Could not write row %s to file.", json_encode($row))
                        );
                    } else {
                        $this->result->incrementActionProcessed($this->getCode());
                    }
                }
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

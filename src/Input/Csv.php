<?php

namespace Maketok\DataMigration\Input;

use Maketok\DataMigration\ArrayUtilsTrait;
use Maketok\DataMigration\Storage\Exception\ParsingException;

class Csv extends AbstractFile
{
    use ArrayUtilsTrait;

    /**
     * @var array
     */
    protected $header;
    /**
     * @var ShaperInterface
     */
    private $shaper;

    /**
     * @param string $fileName
     * @param string $mode
     * @param ShaperInterface $shaper
     */
    public function __construct($fileName, $mode = 'r', ShaperInterface $shaper = null)
    {
        parent::__construct($fileName, $mode);
        $this->shaper = $shaper;
    }

    /**
     * @return array - hashmap of current entity
     * @throws ParsingException
     */
    public function get()
    {
        $shaper = $this->getShaper();
        do {
            $row = $this->descriptor->fgetcsv();
            if (!isset($this->header) || !is_array($this->header)) {
                $this->header = $row;
                $row = $this->descriptor->fgetcsv();
            }
            if ($row === false || $row === null || $this->isEmptyData($row)) {
                if ($shaper) {
                    return $shaper->feed([]);
                } else {
                    return false;
                }
            }
            if (count($this->header) != count($row)) {
                throw new ParsingException(
                    sprintf(
                        "Row contains wrong number of columns compared to header: %s",
                        json_encode($row)
                    )
                );
            }
            $combinedRow = array_combine($this->header, $row);
            if ($shaper) {
                $entity = $shaper->feed($combinedRow);
            } else {
                $entity = $combinedRow;
            }
        } while ($entity === false);
        return $entity;
    }

    /**
     * add row to input resource
     * @param array $entity
     * @return void
     */
    public function add(array $entity)
    {
        if ($shaper = $this->getShaper()) {
            $rows = $shaper->parse($entity);
        } else {
            $rows = [$entity];
        }
        if (!isset($this->header) && count($rows)) {
            $currentRow = current($row);
            $this->header = array_keys($currentRow);
            $this->descriptor->fputcsv($this->header);
        }
        foreach ($rows as $row) {
            $this->descriptor->fputcsv($row);
        }
    }

    /**
     * reset internal counter
     * @return mixed
     */
    public function reset()
    {
        $this->header = null;
        $this->descriptor->rewind();
        if ($shaper = $this->getShaper()) {
            $shaper->clear();
        }
    }

    /**
     * @return ShaperInterface
     */
    public function getShaper()
    {
        return $this->shaper;
    }

    /**
     * @param ShaperInterface $shaper
     */
    public function setShaper($shaper)
    {
        $this->shaper = $shaper;
    }
}

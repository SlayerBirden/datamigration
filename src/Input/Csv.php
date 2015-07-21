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
     * @return array - hashmap of current entity
     * @throws ParsingException
     */
    public function get()
    {
        $row = $this->descriptor->fgetcsv();
        if (!isset($this->header) || !is_array($this->header)) {
            $this->header = $row;
            $row = $this->descriptor->fgetcsv();
        }
        if ($row === false || $row === null || $this->isEmptyData($row)) {
            return false;
        }
        if (count($this->header) != count($row)) {
            throw new ParsingException(
                sprintf("Row contains wrong number of rows compared to header: %s", json_encode($row))
            );
        }
        return array_combine($this->header, $row);
    }

    /**
     * add row to input resource
     * @param array $entity
     * @return void
     */
    public function add(array $entity)
    {
        if (!isset($this->header)) {
            $this->header = array_keys($entity);
            $this->descriptor->fputcsv(array_keys($entity));
        }
        $this->descriptor->fputcsv($entity);
    }

    /**
     * reset internal counter
     * @return mixed
     */
    public function reset()
    {
        $this->header = null;
        $this->descriptor->rewind();
    }
}

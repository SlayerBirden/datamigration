<?php

namespace Maketok\DataMigration;

use Maketok\DataMigration\Action\Exception\ConflictException;
use Maketok\DataMigration\Action\Exception\NormalizationException;

trait ArrayUtilsTrait
{
    /**
     * recursive array_filter
     * @param array $data
     * @return bool
     */
    public function isEmptyData(array $data)
    {
        $filteredData = array_filter($data, function ($var) {
            if (is_array($var)) {
                return !$this->isEmptyData($var);
            }
            return !empty($var);
        });
        return empty($filteredData);
    }

    /**
     * Accepts array of strings or arrays and tries to extract array of string[] from it
     * @param array $row
     * @return array
     * @throws NormalizationException
     */
    public function normalize(array $row)
    {
        $arrayRow = array_map(function ($var) {
            if (!is_array($var)) {
                return [$var];
            }
            return $var;
        }, $row);
        $count = 0;
        foreach ($arrayRow as $el) {
            $count = count($el);
            if (isset($oldCount) && $count != $oldCount) {
                throw new NormalizationException(
                    sprintf("Can not extract values: uneven data for row %s", json_encode($row))
                );
            }
            $oldCount = $count;
        }
        array_map('array_values', $arrayRow);
        $extracted = [];
        for ($i = 0; $i < $count; ++ $i) {
            $extracted[] = array_map(function ($var) use ($i) {
                return $var[$i];
            }, $arrayRow);
        }
        return $extracted;
    }

    /**
     * @param array $data
     * @param bool $force
     * @return array
     * @throws ConflictException
     */
    public function assemble(array $data, $force = false)
    {
        if (count($data) > 1) {
            $byKeys = call_user_func_array('array_intersect_key', $data);
            $byKeysAndValues = call_user_func_array('array_intersect_assoc', $data);
            if ($byKeys != $byKeysAndValues && !$force) {
                throw new ConflictException(
                    sprintf("Conflict with data %s", json_encode($data))
                );
            }
        }
        return call_user_func_array('array_replace', $data);
    }
}

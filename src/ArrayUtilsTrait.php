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
     * @param bool $resolve
     * @return array
     * @throws ConflictException
     */
    public function assemble(array $data, $force = false, $resolve = false)
    {
        if (count($data) > 1) {
            $byKeys = $this->intersectKeyMultiple($data);
            $byKeysAndValues = $this->intersectAssocMultiple($data);
            if ($byKeys != $byKeysAndValues) {
                if ($resolve) {
                    $diff = array_diff($byKeys, $byKeysAndValues);
                    array_walk($data, function (&$unitData, $code) use ($diff) {
                        foreach ($unitData as $key => $val) {
                            if (isset($diff[$key])) {
                                unset($unitData[$key]);
                                $unitData[$code . '_' . $key] = $val;
                            }
                        }
                    });
                } elseif (!$force) {
                    throw new ConflictException(
                        sprintf("Conflict with data %s", json_encode($data))
                    );
                }
            }
        }
        return call_user_func_array('array_replace', $data);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function intersectKeyMultiple(array $data)
    {
        $res = [];
        foreach ($data as $outerArray) {
            foreach ($data as $innerArray) {
                if ($innerArray !== $outerArray) {
                    $res[] = array_intersect_key($outerArray, $innerArray);
                }
            }
        }
        if (!empty($res)) {
            return call_user_func_array('array_replace', $res);
        }
        return [];
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function intersectAssocMultiple(array $data)
    {
        $res = [];
        foreach ($data as $outerArray) {
            foreach ($data as $innerArray) {
                if ($innerArray !== $outerArray) {
                    $res[] = array_intersect_assoc($outerArray, $innerArray);
                }
            }
        }
        if (!empty($res)) {
            return call_user_func_array('array_replace', $res);
        }
        return [];
    }

    /**
     * Same as assemble, but will not raise exception when values don't match
     * instead will adjust keys by pre-pending unit code
     * @param array $data
     * @return array
     */
    public function assembleResolve(array $data)
    {
        return $this->assemble($data, false, true);
    }

    /**
     * Convert all values to nulls persisting keys
     * @param array $data
     * @return array
     */
    public function getNullsData(array $data)
    {
        return array_map(function () {
            return null;
        }, $data);
    }
}

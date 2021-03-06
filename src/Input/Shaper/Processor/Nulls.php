<?php

namespace Maketok\DataMigration\Input\Shaper\Processor;

use Maketok\DataMigration\Input\Shaper\Processor;

class Nulls extends Processor
{
    /**
     * {@inheritdoc}
     */
    public function parse(array $entity)
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($entity));
        $current = [];
        $res = [];
        $depthMap = [];
        $prevDepth = 0;
        foreach ($iterator as $key => $value) {
            $depth = $iterator->getDepth();
            if ($depth < $prevDepth) {
                $res[] = $current;
                $current = array_map(function () {
                    return null;
                }, $current);
                $depthMap = [];
            }
            if (isset($depthMap[$depth]) && in_array($key, $depthMap[$depth])) {
                $res[] = $current;
                $current = array_map(function ($val) use ($current, $depthMap, $depth) {
                    $key = array_search($val, $current);
                    if (in_array($key, $depthMap[$depth])) {
                        return $val;
                    }
                    return null;
                }, $current);
                $depthMap = [];
                $depthMap[$depth][] = $key;
            } else {
                $depthMap[$depth][] = $key;
            }
            $current[$key] = $value;
            $prevDepth = $depth;
        }
        $res[] = $current;
        return $res;
    }
}

<?php

namespace Maketok\DataMigration\Input\Shaper\Processor;

use Maketok\DataMigration\Input\Shaper\Processor;

class Duplicates extends Processor
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
                $depthMap = [];
            }
            if (isset($depthMap[$depth]) && in_array($key, $depthMap[$depth])) {
                $res[] = $current;
                $depthMap = [];
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

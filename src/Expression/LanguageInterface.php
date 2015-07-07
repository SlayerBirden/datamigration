<?php

namespace Maketok\DataMigration\Expression;

interface LanguageInterface
{
    /**
     * @param mixed $expression
     * @param array $values
     * @return mixed
     */
    public function evaluate($expression,  array $values = []);
}

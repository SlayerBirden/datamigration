<?php

namespace Maketok\DataMigration\Expression;

interface LanguageInterface
{
    /**
     * @param mixed $expression
     * @return mixed
     */
    public function evaluate($expression);
}

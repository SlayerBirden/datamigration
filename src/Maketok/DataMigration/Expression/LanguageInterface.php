<?php

namespace Maketok\DataMigration\Expression;

interface LanguageInterface
{
    /**
     * @param string $expression
     * @return mixed
     */
    public function evaluate($expression);
}

<?php

namespace Maketok\DataMigration\Expression;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class LanguageAdapter implements LanguageInterface
{
    /** @var  ExpressionLanguage */
    protected $language;

    /**
     * @param string $expression
     * @param array $values
     * @return mixed|string
     */
    public function evaluate($expression, array $values = array())
    {
        return $this->language->evaluate($expression, $values);
    }
}

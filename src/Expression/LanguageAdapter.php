<?php

namespace Maketok\DataMigration\Expression;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class LanguageAdapter implements LanguageInterface
{
    /** @var  ExpressionLanguage */
    protected $language;

    /**
     * @param ExpressionLanguage $language
     */
    public function __construct($language = null)
    {
        if ($language) {
            $this->language = $language;
        }
    }

    /**
     * @param string $expression
     * @param array $values
     * @return mixed|string
     */
    public function evaluate($expression, array $values = [])
    {
        return $this->language->evaluate($expression, $values);
    }

    /**
     * @return ExpressionLanguage
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param ExpressionLanguage $language
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }
}

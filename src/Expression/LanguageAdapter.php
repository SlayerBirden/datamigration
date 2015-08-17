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
     * @param mixed $expression
     * @param array $values
     * @return mixed
     */
    public function evaluate($expression, array $values = [])
    {
        if (is_callable($expression)) {
            return call_user_func_array($expression, $values);
        } elseif ((is_string($expression) || is_int($expression)) && $this->language) {
            return $this->language->evaluate($expression, $values);
        }
        throw new \InvalidArgumentException(
            sprintf("Wrong type of expression given: %s", gettype($expression))
        );
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
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }
}

<?php

namespace Maketok\DataMigration\Worker\Operation\Expression;

class TokenBag implements \IteratorAggregate
{
    /**
     * @var Token[]
     */
    protected $tokens;

    /**
     * @return Token[]
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * @param Token[] $tokens
     */
    public function setTokens($tokens)
    {
        $this->tokens = $tokens;
    }

    public function addToken(Token $token)
    {
        $this->tokens[] = $token;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->tokens);
    }
}

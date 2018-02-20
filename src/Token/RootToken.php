<?php

namespace AeriesGuard\Token;

use AeriesGuard\StringLexer;

class RootToken extends StringToken
{
    public function __construct($text)
    {
        $this->lexer = new StringLexer($text);
        $this->parse();
    }

    public function getType()
    {
        return TOKEN_ROOT;
    }
}

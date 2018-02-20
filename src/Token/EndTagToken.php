<?php

namespace AeriesGuard\Token;

class EndTagToken extends StringToken
{
    private $name;

    protected function parse()
    {
        $this->lexer->read(2);
        $this->name = $this->lexer->readUntil(']');
        $this->lexer->read(1);
    }

    public function getType()
    {
        return TOKEN_ENDTAG;
    }

    public function getName()
    {
        return $this->name;
    }
}

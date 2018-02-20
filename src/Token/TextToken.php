<?php

namespace AeriesGuard\Token;

class TextToken extends StringToken
{
    private $text;
    public $newPara = FALSE;

    function __construct(\AeriesGuard\StringLexer $lexer)
    {
        $this->lexer = $lexer;
        $this->parse();
    }

    protected function parse()
    {
        if ($this->lexer->peek(2) == "\n\n") {
            $this->lexer->read(2);
            $this->newPara = TRUE;
        } else if ($this->lexer->peek(1) == "\n" || $this->lexer->peek(1) == "[") {
            $this->text .= $this->lexer->read(1);
        }

        $this->text .= $this->lexer->readUntil("[", "\n");
    }

    public function getType()
    {
        return TOKEN_TEXT;
    }

    public function getText()
    {
        return $this->text;
    }
}

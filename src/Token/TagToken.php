<?php

namespace AeriesGuard\Token;

class TagToken extends StringToken
{
    private $name;
    public $attributes = [];

    protected function parse()
    {
        $this->lexer->read(1);
        $this->name = $this->lexer->readUntil(" ", ']', "=");
        $this->parseAttributes();
        $this->lexer->read(1);

        parent::parse();
    }

    private function parseAttributes()
    {
        while (!$this->lexer->eof() && $this->lexer->peek(1) != ']') {
            if ($this->lexer->peek(1) == ' ') {
                $this->lexer->read(1);
            } else {
                $key = $this->lexer->readUntil('=', ']', ' ');
                if ($key == "") {
                    $key = "_default";
                }

                $value = NULL;
                if ($this->lexer->peek(1) == '=') {
                    $this->lexer->read(1);
                    if ($this->lexer->peek(1) == '"') {
                        $this->lexer->read(1);
                        $value = $this->lexer->readUntil('"');
                        $this->lexer->read(1);
                    } else {
                        $value = $this->lexer->readUntil(' ', ']');
                    }
                }

                if (is_null($value) && isset($this->attributes['_default'])) {
                    $this->attributes['_default'] .= ' ' . $key;
                } else {
                    $this->attributes[$key] = $value;
                }
            }
        }
    }

    public function getType()
    {
        return TOKEN_TAG;
    }

    public function getName()
    {
        return $this->name;
    }
}

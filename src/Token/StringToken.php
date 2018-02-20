<?php

namespace AeriesGuard\Token;

use ArrayAccess;
use Countable;
use Iterator;
use AeriesGuard\StringLexer;

const TOKEN_ROOT = 0;
const TOKEN_TAG = 1;
const TOKEN_TEXT = 2;
const TOKEN_ENDTAG = 3;

abstract class StringToken implements Iterator, ArrayAccess, Countable
{
    protected $lexer;
    protected $tokens = [];

    function __construct(StringLexer $lexer)
    {
        $this->lexer = $lexer;
        $this->parse();
    }

    protected function parse()
    {
        $previousToken = NULL;
        $continue = TRUE;
        while (!$this->lexer->eof() && $continue) {
            switch ($this->getNextTokenType()) {
                case TOKEN_TAG:
                    $token = new TagToken($this->lexer);
                    //echo "<blockquote>TAG => ".$token->GetName()."</blockquote>";
                    array_push($this->tokens, $token);
                    $previousToken = $token;
                    break;
                case TOKEN_ENDTAG:
                    $token = new EndTagToken($this->lexer);
                    //echo "<blockquote>ENDTAG => ".$token->GetName()."</blockquote>";
                    array_push($this->tokens, $token);
                    $previousToken = $token;
                    $continue = FALSE;
                    break;
                case TOKEN_TEXT:
                    if ($previousToken == NULL || $previousToken->getType() != TOKEN_TEXT) {
                        $token = new TextToken($this->lexer);
                        //echo "<blockquote>TEXT => ".$token->GetText()."</blockquote>";
                        array_push($this->tokens, $token);
                        $previousToken = $token;
                    } else if ($this->lexer->peek(2) == "\n\n") {
                        $token = new TextToken($this->lexer);
                        array_push($this->tokens, $token);
                        $previousToken = $token;
                    } else {
                        $previousToken->parse();
                    }

                    break;
            }
        }
    }

    private function getNextTokenType()
    {
        $result = TOKEN_TEXT;
        if ($this->lexer->peek(1) == '[') {
            if ($this->lexer->peek(2) == '[/') {
                if ($this->getType() == TOKEN_TAG) {
                    $name = trim($this->lexer->peekUntil("]"));
                    $name = mb_substr($name, 2, mb_strlen($name), 'UTF-8');
                    if (mb_strlen($name) > 0 && $name == $this->GetName()) {
                        $result = TOKEN_ENDTAG;
                    }
                }
            } else {
                $name = trim($this->lexer->peekUntil(" ", "\n", "]", "="));
                $name = mb_substr($name, 1, mb_strlen($name), 'UTF-8');
                if (mb_strlen($name) > 0 && $this->lexer->hasWord('[/' . $name . ']')) {
                    $result = TOKEN_TAG;
                }
            }
        }

        return $result;
    }

    abstract function getType();

    /* Iterator */

    public function rewind()
    {
        reset($this->tokens);
    }

    public function current()
    {
        return current($this->tokens);
    }

    public function key()
    {
        return key($this->tokens);
    }

    public function next()
    {
        next($this->tokens);
    }

    public function valid()
    {
        $key = key($this->tokens);
        return ($key !== NULL && $key !== FALSE);
    }

    /* ArrayAccess */

    public function offsetExists($offset)
    {
        return isset($this->tokens[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->tokens[$offset] : "";
    }

    public function offsetSet($offset, $value)
    {

    }

    public function offsetUnset($offset)
    {

    }

    /* Countable */

    public function count()
    {
        return count($this->tokens);
    }
}

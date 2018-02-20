<?php

namespace AeriesGuard;

class StringLexer
{
    private $text;
    private $count;
    private $pos;

    public function __construct($text)
    {
        $this->text = $text;
        $this->count = mb_strlen($text, 'UTF-8');
        $this->pos = 0;
    }

    public function debug()
    {
        if (func_num_args() == 0) {
            echo "<p>Count: " . $this->count . "\tPos: " . $this->pos . "</p>";
        } else {
            echo "<p>Pos: " . $this->pos . "\t" . func_get_arg(0) . "</p>";
        }
    }

    /**
     * Read $count characters from the current position, move the cursor and return the string read.
     */
    public function read($count = 1)
    {
        $result = $this->peek($count);
        $this->pos += $count;
        return $result;
    }

    public function readUntil(...$stoppers)
    {
        if (count($stoppers) <= 0) {
            throw new \BadMethodCallException('At least one character must be provided');
        }

        $result = "";
        $continue = TRUE;
        while (!$this->eof() && $continue) {
            $char = $this->peek(1);
            foreach ($stoppers as $stopper) {
                $continue = $continue && ($stopper != $char);
            }

            if ($continue) {
                $result = $result . $char;
                $this->pos++;
            }
        }

        return $result;
    }

    public function readUntilWord($word)
    {
        if (!$this->eof()) {
            $index = mb_strpos($this->text, $word, $this->pos, 'UTF-8');
            //echo "<p><strong>".$this->pos.",".$index."</strong></p>";
            if ($index !== FALSE) {
                return $this->read($index - $this->pos);
            } else {
                return "";
            }
        } else {
            return "";
        }
    }

    public function hasWord($word)
    {
        if (!$this->eof()) {
            $index = mb_strpos($this->text, $word, $this->pos, 'UTF-8');
            return ($index === FALSE) ? FALSE : TRUE;
        } else {
            return FALSE;
        }
    }

    public function peek($count = 1)
    {
        if ($count > 0) {
            return mb_substr($this->text, $this->pos, $count, 'UTF-8');
        } else {
            return "";
        }
    }

    function peekUntil(...$stoppers)
    {
        if (count($stoppers) <= 0) {
            throw new \BadMethodCallException('At least one character must be provided');
        }

        $result = "";
        $continue = TRUE;
        $pos = $this->pos;
        while (!$this->eof($pos) && $continue) {
            $char = mb_substr($this->text, $pos, 1, 'UTF-8');
            foreach($stoppers as $stopper) {
                $continue = $continue && ($stopper != $char);
            }

            if ($continue) {
                $result = $result . $char;
                $pos++;
            }
        }

        return $result;
    }

    /**
     * Returns TRUE if the cursor has reached the end of the text, FALSE otherwise.
     */
    function eof($pos = null)
    {
        if ($pos === null) {
            $pos = $this->pos;
        }

        return $pos >= $this->count;
    }
}

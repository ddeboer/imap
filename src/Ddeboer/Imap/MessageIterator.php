<?php

namespace Ddeboer\Imap;

class MessageIterator implements \Iterator
{
    protected $key;
    protected $stream;

    public function __construct($stream)
    {
        $this->stream = $stream;
        $this->count = \imap_num_msg($stream);
    }

    /**
     * Get message
     * 
     * @return Message
     */
    public function current()
    {
        return new Message($this->stream, $this->key);
    }

    public function key()
    {
        return $this->key;
    }

    public function next()
    {
        $this->key++;
    }

    public function rewind()
    {
        $this->key = 1;
    }

    public function valid()
    {
        return $this->key <= $this->count;
    }
}
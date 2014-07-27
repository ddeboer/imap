<?php

namespace Ddeboer\Imap;

class MessageIterator extends \ArrayIterator
{
    protected $stream;

    /**
     * Constructor
     *
     * @param \resource $stream         IMAP stream
     * @param array     $messageNumbers Array of message numbers
     */
    public function __construct($stream, array $messageNumbers)
    {
        $this->stream = $stream;
        
        parent::__construct($messageNumbers);
    }

    /**
     * Get current message
     *
     * @return Message
     */
    public function current()
    {
        return new Message($this->stream, parent::current());
    }

    /**
     * Get message on index
     * @param int $index
     * @return Message
     */
    public function offsetGet($index)
    {
        parent::offsetGet($index);
        return new Message($this->stream, parent::offsetGet($index));
    }

}

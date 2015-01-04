<?php

namespace Ddeboer\Imap;

class MessageIterator extends \ArrayIterator
{
    private $stream;

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
}

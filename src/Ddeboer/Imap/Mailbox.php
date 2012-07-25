<?php

namespace Ddeboer\Imap;

class Mailbox implements \IteratorAggregate
{
    protected $name;
    protected $stream;
    protected $messageIds;
    protected $key = 0;

    public function __construct($name, $stream)
    {
        $this->name = $name;
        $this->stream = $stream;
    }

    public function count()
    {
        return \imap_num_msg($this->stream);
    }

    public function getMessages()
    {
        if (null === $this->messageIds) {
            $this->messageIds = \imap_search($this->stream, 'ALL');
        }

        return $this->messageIds;
    }

    /**
     *
     * @return MessageIterator
     */
    public function getIterator()
    {
        return new MessageIterator($this->stream);
    }
}
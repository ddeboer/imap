<?php

namespace Ddeboer\Imap;

/**
 * An IMAP mailbox
 */
class Mailbox implements \IteratorAggregate
{
    protected $name;
    protected $stream;
    protected $messageIds;

    /**
     * Constructor
     *
     * @param string   $name   Mailbox name
     * @param resource $stream PHP IMAP resource
     */
    public function __construct($name, $stream)
    {
        $this->name = $name;
        $this->stream = $stream;
    }

    /**
     * Get number of messages in this mailbox
     *
     * @return int
     */
    public function count()
    {
        return \imap_num_msg($this->stream);
    }

    /**
     * Get message ids
     *
     * @return array
     */
    public function getMessages()
    {
        if (null === $this->messageIds) {
            $this->messageIds = \imap_search($this->stream, 'ALL');
        }

        return $this->messageIds;
    }

    /**
     * Get a message by message number
     *
     * @param int $number Message number
     *
     * @return Message
     */
    public function getMessage($number)
    {
        return new Message($this->stream, $number);
    }

    /**
     * Get messages in this mailbox
     *
     * @return MessageIterator
     */
    public function getIterator()
    {
        return new MessageIterator($this->stream);
    }
}
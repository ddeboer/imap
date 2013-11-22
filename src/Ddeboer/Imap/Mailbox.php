<?php

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\Exception;

/**
 * An IMAP mailbox (commonly referred to as a ‘folder’)
 *
 */
class Mailbox implements \IteratorAggregate
{
    protected $mailbox;
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
        $this->mailbox = $name;
        $this->stream = $stream;
        $this->name = substr($name, strpos($name, '}')+1);
    }

    /**
     * Get mailbox name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get number of messages in this mailbox
     *
     * @return int
     */
    public function count()
    {
        $this->init();

        return \imap_num_msg($this->stream);
    }

    /**
     * Get message ids
     *
     * @return MessageIterator
     */
    public function getMessages(SearchExpression $search = null)
    {
        $this->init();
        $query = ($search ? (string) $search : 'ALL');

        $messageNumbers = \imap_search($this->stream, $query);
        if (false == $messageNumbers) {
            // \imap_search can also return false
            $messageNumbers = array();
        }

        return new MessageIterator($this->stream, $messageNumbers);
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
        $this->init();

        return new Message($this->stream, $number);
    }

    /**
     * Get messages in this mailbox
     *
     * @return MessageIterator
     */
    public function getIterator()
    {
        $this->init();

        return $this->getMessages();
    }

    /**
     * Delete this mailbox
     *
     */
    public function delete()
    {
        if (false === \imap_deletemailbox($this->stream, $this->mailbox)) {
            throw new Exception('Mailbox ' . $this->name . ' could not be deleted');
        }
    }

    /**
     * Delete all messages marked for deletion
     *
     * @return boolean
     */
    public function expunge()
    {
        $this->init();

        return \imap_expunge($this->stream);
    }

    /**
     * If connection is not currently in this mailbox, switch it to this mailbox
     */
    protected function init()
    {
        $check = \imap_check($this->stream);
        if ($check->Mailbox != $this->mailbox) {
            \imap_reopen($this->stream, $this->mailbox);
        }
    }
}


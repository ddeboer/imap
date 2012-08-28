<?php

namespace Ddeboer\Imap;

use Ddeboer\Imap\Search\DateRange;

/**
 * An IMAP mailbox (commonly referred to as a ‘folder’)
 *
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
    public function __construct($mailbox, $stream)
    {
        $this->mailbox = $mailbox;
        $this->stream = $stream;
        $this->name = substr($mailbox, strpos($mailbox, '}')+1);
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
    public function getMessages(DateRange $dateRange = null)
    {
        $this->init();

        $query = array();
        if ($dateRange) {
            if ($dateRange->getFrom()) {
                $query[] = 'SINCE ' . $dateRange->getFrom()->format('Y-m-d');
            }

            if ($dateRange->getUntil()) {
                $query[] = 'BEFORE ' . $dateRange->getUntil()->format('Y-m-d');
            }
        }

        if (count($query) === 0) {
            $query[] = 'ALL';
        }

        $queryString = implode(' ', $query);
        $messageNumbers = \imap_search($this->stream, $queryString);

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

    protected function init()
    {
        $check = \imap_check($this->stream);
        if ($check->Mailbox != $this->mailbox) {
            \imap_reopen($this->stream, $this->mailbox);
        }
    }
}


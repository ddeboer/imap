<?php

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\Exception;
use Ddeboer\Imap\Exception\ServerDisallowCriterionException;

/**
 * An IMAP mailbox (commonly referred to as a ‘folder’)
 *
 */
class Mailbox implements \Countable, \IteratorAggregate
{
    private $mailbox;
    private $name;
    private $connection;

    /**
     * Constructor
     *
     * @param string     $name       Mailbox name
     * @param Connection $connection IMAP connection
     */
    public function __construct($name, Connection $connection)
    {
        $this->mailbox = $name;
        $this->connection = $connection;

        $name = substr($name, strpos($name, '}')+1);

        if (function_exists('mb_convert_encoding')) {
            $this->name = mb_convert_encoding($name, "UTF-8", "UTF7-IMAP");
        } else {
            $this->name = imap_utf7_decode($name);
        }
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

        return imap_num_msg($this->connection->getResource());
    }

    /**
     * Get message ids
     *
     * @param SearchExpression $search Search expression (optional)
     *
     * @return MessageIterator|Message[]
     */
    public function getMessages(SearchExpression $search = null)
    {
        $this->init();

        $query = ($search ? (string) $search : 'ALL');

        $messageNumbers = imap_search($this->connection->getResource(), $query, \SE_UID);

        $lastError = imap_last_error();
        if ($lastError !== false) {
            if (1 === preg_match('/Unknown search criterion: ([A-Z]+)/', $lastError, $matches)) {
                // drop php notices.
                imap_errors();
                throw new ServerDisallowCriterionException($matches[1]);
            } else {
                throw new Exception($lastError);
            }
        }

        if (false == $messageNumbers) {
            // imap_search can also return false
            $messageNumbers = array();
        }

        return new MessageIterator($this->connection->getResource(), $messageNumbers);
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

        return new Message($this->connection->getResource(), $number);
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
        $this->connection->deleteMailbox($this);
    }

    /**
     * Delete all messages marked for deletion
     *
     * @return Mailbox
     */
    public function expunge()
    {
        $this->init();

        imap_expunge($this->connection->getResource());

        return $this;
    }

    /**
     * Add a message to the mailbox
     *
     * @param string $message
     *
     * @return boolean
     */
    public function addMessage($message)
    {
        return imap_append($this->connection->getResource(), $this->mailbox, $message);
    }

    /**
     * If connection is not currently in this mailbox, switch it to this mailbox
     */
    private function init()
    {
        $check = imap_check($this->connection->getResource());
        if ($check === false || $check->Mailbox != $this->mailbox) {
            imap_reopen($this->connection->getResource(), $this->mailbox);
        }
    }
}

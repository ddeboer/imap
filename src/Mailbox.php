<?php

namespace Ddeboer\Imap;

/**
 * An IMAP mailbox (commonly referred to as a ‘folder’)
 *
 */
class Mailbox implements \IteratorAggregate
{
    private $mailbox;
    private $name;
    private $connection;

    /**
     * Constructor
     *
     * @param string     $name       Mailbox object from imap_getmailboxes
     * @param Connection $connection IMAP connection
     */
    public function __construct($mboxInfo, Connection $connection)
    {
        $this->mailbox =  $mboxInfo;
        $name = $mboxInfo->name;
        $this->connection = $connection;
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

        return imap_num_msg($this->connection->getResource());
    }

    public function getAttributes()
    {
        $attributes = array();
        $mask = $this->mailbox->attributes;
        if(($mask & LATT_NOINFERIORS) == LATT_NOINFERIORS){
            $attributes[] = 'noinferiors';
        }
        if(($mask & LATT_NOSELECT) == LATT_NOSELECT){
            $attributes[] = 'noselect';
        }
        if(($mask & LATT_MARKED) == LATT_MARKED){
            $attributes[] = 'marked';
        }
        if(($mask & LATT_UNMARKED) == LATT_UNMARKED){
            $attributes[] = 'unmarked';
        }
        return $attributes;
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
        if ($check === false || $check->Mailbox != $this->mailbox->name) {
            imap_reopen($this->connection->getResource(), $this->mailbox->name);
        }
    }
}

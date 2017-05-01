<?php

namespace openWebX\Imap;

/**
 * An IMAP mailbox (commonly referred to as a ‘folder’)
 *
 */
class Mailbox implements \Countable, \IteratorAggregate {
    /**
     * @var string
     */
    private $mailbox;
    /**
     * @var bool|string
     */
    private $name;
    /**
     * @var \openWebX\Imap\Connection
     */
    private $connection;

    /**
     * Constructor
     *
     * @param string     $name       Mailbox name
     * @param Connection $connection IMAP connection
     */
    public function __construct($name, Connection $connection) {
        $this->mailbox = $name;
        $this->connection = $connection;
        $this->name = substr($name, strpos($name, '}') + 1);
    }

    /**
     * Get mailbox name
     *
     * @return string
     */
    public function getName():string {
        return $this->name;
    }


    /**
     * @return object
     */
    public function getInfos():\stdClass {
        $this->init();
        //dbg($this);
        return imap_status($this->connection->getResource(), $this->mailbox, SA_ALL);
    }

    /**
     * If connection is not currently in this mailbox, switch it to this mailbox
     */
    private function init() {
        $check = imap_check($this->connection->getResource());
        if ($check === FALSE || $check->Mailbox != $this->mailbox) {
            imap_reopen($this->connection->getResource(), $this->mailbox);
        }
    }

    /**
     * @return object
     */
    public function getExtendedInfos():\stdClass {
        $this->init();
        return imap_mailboxmsginfo($this->connection->getResource());
    }

    /**
     * Get number of messages in this mailbox
     *
     * @return int
     */
    public function count():int {
        $this->init();
        return imap_num_msg($this->connection->getResource());
    }

    /**
     * Get a message by message number
     *
     * @param int $number Message number
     *
     * @return Message
     */
    public function getMessage(int $number) {
        $this->init();
        return new Message($this->connection->getResource(), $number);
    }

    /**
     * Get messages in this mailbox
     *
     * @return MessageIterator
     */
    public function getIterator() {
        $this->init();
        return $this->getMessages();
    }

    /**
     * Get message ids
     *
     * @param SearchExpression $search Search expression (optional)
     *
     * @return MessageIterator|Message[]
     */
    public function getMessages(SearchExpression $search = NULL) {
        $this->init();
        $query = ($search ? (string) $search : 'ALL');
        $messageNumbers = imap_search($this->connection->getResource(), $query, \SE_UID);
        if (FALSE == $messageNumbers) {
            // imap_search can also return false
            $messageNumbers = [];
        }

        return new MessageIterator($this->connection->getResource(), $messageNumbers);
    }

    /**
     * Delete this mailbox
     *
     */
    public function delete() {
        $this->connection->deleteMailbox($this);
    }

    /**
     * Delete all messages marked for deletion
     *
     * @return Mailbox
     */
    public function expunge() {
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
    public function addMessage($message):bool {
        return imap_append($this->connection->getResource(), $this->mailbox, $message);
    }
}

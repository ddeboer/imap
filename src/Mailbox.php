<?php

declare(strict_types=1);

namespace Ddeboer\Imap;

/**
 * An IMAP mailbox (commonly referred to as a ‘folder’)
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
    public function __construct(string $name, Connection $connection)
    {
        $this->mailbox = $name;
        $this->connection = $connection;
        $this->name = substr($name, strpos($name, '}') + 1);
    }

    /**
     * Get mailbox name
     *
     * @return string
     */
    public function getName(): string
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
     * @return Message[]|MessageIterator
     */
    public function getMessages(SearchExpression $search = null): MessageIterator
    {
        $this->init();

        $query = ($search ? (string) $search : 'ALL');

        $messageNumbers = imap_search($this->connection->getResource(), $query, \SE_UID);
        if (false == $messageNumbers) {
            // imap_search can also return false
            $messageNumbers = [];
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
    public function getMessage(int $number): Message
    {
        $this->init();

        return new Message($this->connection->getResource(), $number);
    }

    /**
     * Get messages in this mailbox
     *
     * @return MessageIterator
     */
    public function getIterator(): MessageIterator
    {
        return $this->getMessages();
    }

    /**
     * Delete this mailbox
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
    public function expunge(): self
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
     * @return bool
     */
    public function addMessage($message): bool
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

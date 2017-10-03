<?php

declare(strict_types=1);

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\InvalidSearchCriteriaException;
use Ddeboer\Imap\Exception\ReopenMailboxException;
use Ddeboer\Imap\Search\ConditionInterface;
use Ddeboer\Imap\Search\LogicalOperator\All;

/**
 * An IMAP mailbox (commonly referred to as a 'folder').
 */
final class Mailbox implements MailboxInterface
{
    private $resource;
    private $name;
    private $info;

    /**
     * Constructor.
     *
     * @param ImapResourceInterface $resource IMAP resource
     * @param string                $name     Mailbox decoded name
     * @param stdClass              $info     Mailbox info
     */
    public function __construct(ImapResourceInterface $resource, string $name, \stdClass $info)
    {
        $this->resource = $resource;
        $this->name = $name;
        $this->info = $info;
    }

    /**
     * Get mailbox decoded name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get mailbox encoded path.
     *
     * @return string
     */
    public function getEncodedName(): string
    {
        return \preg_replace('/^{.+}/', '', $this->info->name);
    }

    /**
     * Get mailbox encoded full name.
     *
     * @return string
     */
    public function getFullEncodedName(): string
    {
        return $this->info->name;
    }

    /**
     * Get mailbox attributes.
     *
     * @return int
     */
    public function getAttributes(): int
    {
        return $this->info->attributes;
    }

    /**
     * Get mailbox delimiter.
     *
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->info->delimiter;
    }

    /**
     * Get number of messages in this mailbox.
     *
     * @return int
     */
    public function count()
    {
        $this->init();

        return \imap_num_msg($this->resource->getStream());
    }

    /**
     * Get Mailbox status.
     *
     * @param int $flag
     *
     * @return \stdClass
     */
    public function getStatus(int $flags = null): \stdClass
    {
        $this->init();

        return \imap_status($this->resource->getStream(), $this->getFullEncodedName(), $flags ?? \SA_ALL);
    }

    /**
     * Get message ids.
     *
     * @param ConditionInterface $search Search expression (optional)
     *
     * @return MessageIteratorInterface
     */
    public function getMessages(ConditionInterface $search = null, int $sortCriteria = null, bool $descending = false): MessageIteratorInterface
    {
        $this->init();

        if (null === $search) {
            $search = new All();
        }
        $query = $search->toString();

        // We need to clear the stack to know whether imap_last_error()
        // is related to this imap_search
        \imap_errors();

        if (null !== $sortCriteria) {
            $messageNumbers = \imap_sort($this->resource->getStream(), $sortCriteria, $descending ? 1 : 0, \SE_UID, $query);
        } else {
            $messageNumbers = \imap_search($this->resource->getStream(), $query, \SE_UID);
        }
        if (false == $messageNumbers) {
            if (false !== \imap_last_error()) {
                throw new InvalidSearchCriteriaException(\sprintf('Invalid search criteria [%s]', $query));
            }

            // imap_search can also return false
            $messageNumbers = [];
        }

        return new MessageIterator($this->resource, $messageNumbers);
    }

    /**
     * Get a message by message number.
     *
     * @param int $number Message number
     *
     * @return MessageInterface
     */
    public function getMessage(int $number): MessageInterface
    {
        $this->init();

        return new Message($this->resource, $number);
    }

    /**
     * Get messages in this mailbox.
     *
     * @return MessageIteratorInterface
     */
    public function getIterator(): MessageIteratorInterface
    {
        return $this->getMessages();
    }

    /**
     * Add a message to the mailbox.
     *
     * @param string $message
     *
     * @return bool
     */
    public function addMessage(string $message): bool
    {
        return \imap_append($this->resource->getStream(), $this->getFullEncodedName(), $message);
    }

    /**
     * If connection is not currently in this mailbox, switch it to this mailbox.
     */
    private function init()
    {
        if ($this->isMailboxOpen()) {
            return;
        }

        \imap_reopen($this->resource->getStream(), $this->getFullEncodedName());

        if ($this->isMailboxOpen()) {
            return;
        }

        throw new ReopenMailboxException(\sprintf('Cannot reopen mailbox "%s"', $this->getName()));
    }

    private function isMailboxOpen(): bool
    {
        $check = \imap_check($this->resource->getStream());

        return false !== $check && $check->Mailbox === $this->getFullEncodedName();
    }
}

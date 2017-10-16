<?php

declare(strict_types=1);

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\InvalidResourceException;
use Ddeboer\Imap\Exception\ReopenMailboxException;

/**
 * An imap resource stream.
 */
final class ImapResource implements ImapResourceInterface
{
    /**
     * @var resource
     */
    private $resource;

    /**
     * @var null|MailboxInterface
     */
    private $mailbox;

    /**
     * Constructor.
     *
     * @param resource $resource
     */
    public function __construct($resource, MailboxInterface $mailbox = null)
    {
        $this->resource = $resource;
        $this->mailbox = $mailbox;
    }

    /**
     * Get IMAP resource stream.
     *
     * @throws InvalidResourceException
     *
     * @return resource
     */
    public function getStream()
    {
        if (false === \is_resource($this->resource) || 'imap' !== \get_resource_type($this->resource)) {
            throw new InvalidResourceException('Supplied resource is not a valid imap resource');
        }

        $this->initMailbox();

        return $this->resource;
    }

    /**
     * If connection is not currently in this mailbox, switch it to this mailbox.
     */
    private function initMailbox()
    {
        if (null === $this->mailbox || $this->isMailboxOpen()) {
            return;
        }

        \imap_reopen($this->resource, $this->mailbox->getFullEncodedName());

        if ($this->isMailboxOpen()) {
            return;
        }

        throw new ReopenMailboxException(\sprintf('Cannot reopen mailbox "%s"', $this->mailbox->getName()));
    }

    /**
     * Check whether the current mailbox is open.
     *
     * @return bool
     */
    private function isMailboxOpen(): bool
    {
        $check = \imap_check($this->resource);

        return false !== $check && $check->Mailbox === $this->mailbox->getFullEncodedName();
    }
}

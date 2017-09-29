<?php

declare(strict_types=1);

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\CreateMailboxException;
use Ddeboer\Imap\Exception\DeleteMailboxException;
use Ddeboer\Imap\Exception\InvalidResourceException;
use Ddeboer\Imap\Exception\MailboxDoesNotExistException;

/**
 * A connection to an IMAP server that is authenticated for a user
 */
class Connection implements \Countable
{
    private $server;
    private $resource;
    private $mailboxes;
    private $mailboxNames;

    /**
     * Constructor
     *
     * @param resource $resource
     * @param string   $server
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($resource, string $server)
    {
        $this->resource = $resource;
        $this->server = $server;

        // Performs resource check
        $this->getResource();
    }

    /**
     * Get IMAP resource
     *
     * @return resource
     */
    public function getResource()
    {
        if (false === is_resource($this->resource) || 'imap' !== get_resource_type($this->resource)) {
            throw new InvalidResourceException('Supplied resource is not a valid imap resource');
        }

        return $this->resource;
    }

    /**
     * Delete all messages marked for deletion
     *
     * @return Mailbox
     */
    public function expunge()
    {
        imap_expunge($this->getResource());
    }

    /**
     * Close connection
     *
     * @param int $flag
     *
     * @return bool
     */
    public function close(int $flag = 0): bool
    {
        return imap_close($this->getResource(), $flag);
    }

    /**
     * Get a list of mailboxes (also known as folders)
     *
     * @return Mailbox[]
     */
    public function getMailboxes(): array
    {
        $this->initMailboxNames();

        if (null === $this->mailboxes) {
            $this->mailboxes = [];
            foreach ($this->mailboxNames as $mailboxName => $mailboxInfo) {
                $this->mailboxes[$mailboxName] = $this->getMailbox($mailboxName);
            }
        }

        return $this->mailboxes;
    }

    /**
     * Check that a mailbox with the given name exists
     *
     * @param string $name Mailbox name
     *
     * @return bool
     */
    public function hasMailbox(string $name): bool
    {
        $this->initMailboxNames();

        return isset($this->mailboxNames[$name]);
    }

    /**
     * Get a mailbox by its name
     *
     * @param string $name Mailbox name
     *
     * @throws MailboxDoesNotExistException If mailbox does not exist
     *
     * @return Mailbox
     */
    public function getMailbox(string $name): Mailbox
    {
        if (false === $this->hasMailbox($name)) {
            throw new MailboxDoesNotExistException(sprintf(
                'Mailbox name "%s" does not exist',
                $name
            ));
        }

        return new Mailbox($this, $name, $this->mailboxNames[$name]);
    }

    /**
     * Count number of messages not in any mailbox
     *
     * @return int
     */
    public function count()
    {
        return imap_num_msg($this->getResource());
    }

    /**
     * Create mailbox
     *
     * @param $name
     *
     * @throws CreateMailboxException
     *
     * @return Mailbox
     */
    public function createMailbox(string $name): Mailbox
    {
        if (false === imap_createmailbox($this->getResource(), $this->server . mb_convert_encoding($name, 'UTF7-IMAP', 'UTF-8'))) {
            throw new CreateMailboxException(sprintf(
                'Can not create "%s" mailbox at "%s"',
                $name,
                $this->server
            ));
        }

        $this->mailboxNames = $this->mailboxes = null;

        return $this->getMailbox($name);
    }

    /**
     * Create mailbox
     *
     * @param Mailbox
     *
     * @throws DeleteMailboxException
     */
    public function deleteMailbox(Mailbox $mailbox)
    {
        if (false === imap_deletemailbox($this->getResource(), $mailbox->getFullEncodedName())) {
            throw new DeleteMailboxException(sprintf(
                'Mailbox "%s" could not be deleted',
                $mailbox->getName()
            ));
        }

        $this->mailboxes = $this->mailboxNames = null;
    }

    /**
     * Get mailbox names
     *
     * @return array
     */
    private function initMailboxNames()
    {
        if (null !== $this->mailboxNames) {
            return;
        }

        $this->mailboxNames = [];
        $mailboxesInfo = imap_getmailboxes($this->getResource(), $this->server, '*');
        foreach ($mailboxesInfo as $mailboxInfo) {
            $name = mb_convert_encoding(str_replace($this->server, '', $mailboxInfo->name), 'UTF-8', 'UTF7-IMAP');
            $this->mailboxNames[$name] = $mailboxInfo;
        }
    }
}

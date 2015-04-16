<?php

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\Exception;
use Ddeboer\Imap\Exception\MailboxDoesNotExistException;

/**
 * A connection to an IMAP server that is authenticated for a user
 */
class Connection
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
    public function __construct($resource, $server)
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('$resource must be a resource');
        }

        $this->resource = $resource;
        $this->server = $server;
    }

    /**
     * Get a list of mailboxes (also known as folders)
     *
     * @return Mailbox[]
     */
    public function getMailboxes()
    {
        if (null === $this->mailboxes) {
            foreach ($this->getMailboxNames() as $mailboxName) {
                $this->mailboxes[] = $this->getMailbox($mailboxName);
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
    public function hasMailbox($name)
    {
        return in_array($name, $this->getMailboxNames());
    }

    /**
     * Get a mailbox by its name
     *
     * @param string $name Mailbox name
     *
     * @return Mailbox
     * @throws MailboxDoesNotExistException If mailbox does not exist
     */
    public function getMailbox($name)
    {
        if (!$this->hasMailbox($name)) {
            throw new MailboxDoesNotExistException($name);
        }

        return new Mailbox($this->server . imap_utf7_encode($name), $this);
    }

    /**
     * Count number of messages not in any mailbox
     *
     * @return int
     */
    public function count()
    {
        return imap_num_msg($this->resource);
    }

    /**
     * Create mailbox
     *
     * @param $name
     *
     * @return Mailbox
     * @throws Exception
     */
    public function createMailbox($name)
    {
        if (imap_createmailbox($this->resource, $this->server . $name)) {
            $this->mailboxNames = $this->mailboxes = null;

            return $this->getMailbox($name);
        }

        throw new Exception("Can not create '{$name}' mailbox at '{$this->server}'");
    }

    /**
     * Close connection
     *
     * @param int $flag
     *
     * @return bool
     */
    public function close($flag = 0)
    {
        return imap_close($this->resource, $flag);
    }

    public function deleteMailbox(Mailbox $mailbox)
    {
        if (false === imap_deletemailbox(
            $this->resource,
            $this->server . $mailbox->getName()
        )) {
            throw new Exception('Mailbox ' . $mailbox->getName() . ' could not be deleted');
        }

        $this->mailboxes = $this->mailboxNames = null;
    }

    /**
     * Get IMAP resource
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Get mailbox names
     * 
     * @return array
     */
    private function getMailboxNames()
    {
        if (null === $this->mailboxNames) {
            $mailboxes = imap_getmailboxes($this->resource, $this->server, '*');
            foreach ($mailboxes as $mailbox) {
                $this->mailboxNames[] = imap_utf7_decode(str_replace($this->server, '', $mailbox->name));
            }
        }

        return $this->mailboxNames;
    }
}

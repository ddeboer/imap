<?php

namespace Ddeboer\Imap;

/**
 * A connection to an IMAP server that is authenticated for a user
 */
class Connection
{
    protected $server;
    protected $resource;
    protected $mailboxes;
    protected $mailboxNames;

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
     * @return array
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

    public function getMailbox($name)
    {
        return new Mailbox($this->server . $name, $this->resource);
    }

    /**
     * Count number of messages not in any mailbox
     *
     * @return int
     */
    public function count()
    {
        return \imap_num_msg($this->resource);
    }

    protected function getMailboxNames()
    {
        if (null === $this->mailboxNames) {
            $mailboxes = \imap_getmailboxes($this->resource, $this->server, '*');
            foreach ($mailboxes as $mailbox) {
                $this->mailboxNames[] = str_replace($this->server, '', $mailbox->name);
            }
        }

        return $this->mailboxNames;
    }
}
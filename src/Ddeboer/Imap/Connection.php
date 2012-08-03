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

    public function __construct($resource, $server)
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('$resource must be a resource');
        }

        $this->resource = $resource;
        $this->server = $server;
    }

    /**
     * Get a list of mailboxes
     *
     * @return array
     */
    public function getMailboxes()
    {
        if (null === $this->mailboxes) {
            $mailboxes = \imap_getmailboxes($this->resource, $this->server, '*');
            foreach ($mailboxes as $mailbox) {
                $this->mailboxes[] = str_replace($this->server, '', $mailbox->name);
            }
        }

        return $this->mailboxes;
    }

    public function getMailbox($name)
    {
        foreach ($this->getMailboxes() as $mailbox) {
            if (strcasecmp($name, $mailbox) === 0) {
                if (false === \imap_reopen($this->resource, $this->server . $mailbox)) {
                    throw new \Exception('Could not open mailbox ' . $mailbox);
                }

                return new Mailbox($mailbox, $this->resource);
            }
        }

        throw new \InvalidArgumentException('Mailbox ' . $name . ' not found');
    }

    public function count()
    {
        return \imap_num_msg($this->resource);
    }
}
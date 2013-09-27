<?php

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\Exception;

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

    /**
     * Create mailbox
     *
     * @param $name
     * @return Mailbox
     * @throws Exception
     */
    public function createMailbox($name)
    {
        if (\imap_createmailbox($this->resource, $this->server . $name)) {

            $mailbox = $this->getMailbox($name);

            if ($this->mailboxNames) {
                $this->mailboxNames[] = $name;
            }
            if ($this->mailboxes) {
                $this->mailboxes[] = $mailbox;
            }

            return $mailbox;
        }

        throw new Exception("Can not create '{$name}' mailbox at '{$this->server}'");
    }
}

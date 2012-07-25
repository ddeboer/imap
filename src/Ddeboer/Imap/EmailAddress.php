<?php

namespace Ddeboer\Imap;

class EmailAddress
{
    protected $mailbox;
    protected $host;
    protected $name;
    protected $address;

    public function __construct($mailbox, $host, $name = null)
    {
        $this->mailbox = $mailbox;
        $this->host = $host;
        $this->name = $name;
        $this->address = $mailbox . '@' . $host;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getMailbox()
    {
        return $this->mailbox;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getName()
    {
        return $this->name;
    }

    public function __toString()
    {
        return $this->getAddress();
    }
}
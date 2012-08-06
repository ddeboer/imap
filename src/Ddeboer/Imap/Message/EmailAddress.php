<?php

namespace Ddeboer\Imap\Message;

class EmailAddress
{
    protected $mailbox;
    protected $hostname;
    protected $name;
    protected $address;

    public function __construct($mailbox, $hostname, $name = null)
    {
        $this->mailbox = $mailbox;
        $this->hostname = $hostname;
        $this->name = $name;
        $this->address = $mailbox . '@' . $hostname;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getMailbox()
    {
        return $this->mailbox;
    }

    public function getHostname()
    {
        return $this->hostname;
    }

    public function getName()
    {
        return $this->name;
    }

    public function __toString()
    {
        return $this->getAddress();
    }

    public static function fromString($address)
    {
        $parts = explode('@', $address);

        return new self($parts[0], $parts[1]);
    }
}
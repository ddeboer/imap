<?php

namespace Ddeboer\Imap\Message;

/**
 * An e-mail address
 */
class EmailAddress
{
    private $mailbox;
    private $hostname;
    private $name;
    private $address;

    public function __construct($mailbox, $hostname = null, $name = null)
    {
        $this->mailbox = $mailbox;
        $this->hostname = $hostname;
        $this->name = $name;
        
        if ($hostname) {
            $this->address = $mailbox . '@' . $hostname;
        }
    }

    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Returns address with person name
     *
     * @return string
     */
    public function getFullAddress()
    {
        if ($this->name) {
            $address = sprintf("%s <%s@%s>", $this->name, $this->mailbox, $this->hostname);
        } else {
            $address = sprintf("%s@%s", $this->mailbox, $this->hostname);
        }

        return $address;
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
}

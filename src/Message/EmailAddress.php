<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Message;

/**
 * An e-mail address.
 */
final class EmailAddress
{
    private $mailbox;
    private $hostname;
    private $name;
    private $address;

    public function __construct(string $mailbox, string $hostname = null, string $name = null)
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
     * Returns address with person name.
     *
     * @return string
     */
    public function getFullAddress(): string
    {
        $address = \sprintf('%s@%s', $this->mailbox, $this->hostname);
        if ($this->name) {
            $address = \sprintf('"%s" <%s>', \addcslashes($this->name, '"'), $address);
        }

        return $address;
    }

    public function getMailbox(): string
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
}

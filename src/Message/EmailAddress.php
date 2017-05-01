<?php

namespace openWebX\Imap\Message;

/**
 * Class EmailAddress
 *
 * @package openWebX\Imap\Message
 */
class EmailAddress {
    /**
     * @var
     */
    private $mailbox;
    /**
     * @var null
     */
    private $hostname;
    /**
     * @var null
     */
    private $name;
    /**
     * @var string
     */
    private $address;

    /**
     * EmailAddress constructor.
     *
     * @param      $mailbox
     * @param null $hostname
     * @param null $name
     */
    public function __construct($mailbox, $hostname = NULL, $name = NULL) {
        $this->mailbox = $mailbox;
        $this->hostname = $hostname;
        $this->name = $name;

        if ($hostname) {
            $this->address = $mailbox . '@' . $hostname;
        }
    }

    /**
     * @return string
     */
    public function getAddress() {
        return $this->address;
    }

    /**
     * Returns address with person name
     *
     * @return string
     */
    public function getFullAddress() {
        if ($this->name) {
            $address = sprintf("%s <%s@%s>", $this->name, $this->mailbox, $this->hostname);
        }
        else {
            $address = sprintf("%s@%s", $this->mailbox, $this->hostname);
        }

        return $address;
    }

    /**
     * @return mixed
     */
    public function getMailbox() {
        return $this->mailbox;
    }

    /**
     * @return null
     */
    public function getHostname() {
        return $this->hostname;
    }

    /**
     * @return null
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->getAddress();
    }
}

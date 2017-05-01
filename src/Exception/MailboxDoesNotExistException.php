<?php

namespace openWebX\Imap\Exception;

/**
 * Class MailboxDoesNotExistException
 *
 * @package openWebX\Imap\Exception
 */
class MailboxDoesNotExistException extends Exception {
    /**
     * MailboxDoesNotExistException constructor.
     *
     * @param string $mailbox
     */
    public function __construct($mailbox) {
        parent::__construct('Mailbox ' . $mailbox . ' does not exist');
    }
}

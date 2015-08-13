<?php

namespace Ddeboer\Imap\Exception;

class MailboxOpenException extends Exception
{
    public function __construct($mailbox)
    {
        parent::__construct('Error opening/reopening mailbox' . $mailbox);
    }
}

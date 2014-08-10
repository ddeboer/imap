<?php

namespace Ddeboer\Imap\Exception;

class MessageMoveException extends Exception
{
    public function __construct($messageNumber, $mailbox)
    {
        parent::__construct(
            sprintf(
                'Message %s cannot be moved to %s',
                $messageNumber,
                $mailbox
            )
        );
    }
}

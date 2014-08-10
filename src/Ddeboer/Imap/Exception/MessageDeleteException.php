<?php

namespace Ddeboer\Imap\Exception;

class MessageDeleteException extends Exception
{
    public function __construct($messageNumber)
    {
        parent::__construct(sprintf('Message %s cannot be deleted', $messageNumber));
    }
}

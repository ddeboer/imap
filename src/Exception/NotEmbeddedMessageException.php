<?php

namespace Ddeboer\Imap\Exception;

class NotEmbeddedMessageException extends Exception
{
    public function __construct()
    {
        parent::__construct("Attachment %s in message %s is not embedded message");
    }
}

<?php

namespace Ddeboer\Imap\Exception;

class UnknownCharsetException extends Exception
{
    public function __construct($messageNumber)
    {
        parent::__construct(
            sprintf(
                'Message %s has no charset specified',
                $messageNumber
            )
        );
    }
}

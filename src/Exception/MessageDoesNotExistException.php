<?php

namespace Ddeboer\Imap\Exception;

class MessageDoesNotExistException extends Exception
{
    public function __construct($number)
    {
        parent::__construct(
            sprintf(
                'Message %s does not exist',
                $number
            )
        );
    }
}

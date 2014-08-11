<?php

namespace Ddeboer\Imap\Exception;

class HeadersNotFoundException extends Exception
{
    public function __construct($message)
    {
        parent::__construct('Headers for message ' . $message->getNumber() . ' does not exist');
    }
}

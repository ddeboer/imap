<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Exception;

class MessageDoesNotExistException extends Exception
{
    public function __construct($number, $error)
    {
        parent::__construct(
            sprintf(
                'Message %s does not exist: %s',
                $number,
                $error
            )
        );
    }
}

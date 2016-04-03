<?php
/**
 * Created by PhpStorm.
 * User: holdmann
 * Date: 23.03.16
 * Time: 23:22
 */

namespace Ddeboer\Imap\Exception;


class MessageUnsupportedEncodeException extends Exception
{
    public function __construct($messageNumber, $partNumber)
    {
        parent::__construct(
            sprintf(
                'Part number %s of message %s has unsupported encode.',
                $partNumber,
                $messageNumber
            )
        );
    }
}
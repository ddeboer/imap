<?php
namespace Ddeboer\Imap\Exception;
class UnknownEncodingException extends Exception
{
    public function __construct($messageNumber,$encoding)
    {
        parent::__construct(
            sprintf(
                'Cannot decode message uid: %s with encoding "%s"',
                $messageNumber,
                $encoding
            )
        );
    }
}

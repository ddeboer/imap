<?php

namespace openWebX\Imap\Exception;

/**
 * Class MessageMoveException
 *
 * @package openWebX\Imap\Exception
 */
class MessageMoveException extends Exception {
    /**
     * MessageMoveException constructor.
     *
     * @param string $messageNumber
     * @param null   $mailbox
     */
    public function __construct($messageNumber, $mailbox) {
        parent::__construct(
            sprintf(
                'Message %s cannot be moved to %s',
                $messageNumber,
                $mailbox
            )
        );
    }
}

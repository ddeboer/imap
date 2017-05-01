<?php

namespace openWebX\Imap\Exception;

/**
 * Class MessageDeleteException
 *
 * @package openWebX\Imap\Exception
 */
class MessageDeleteException extends Exception {
    /**
     * MessageDeleteException constructor.
     *
     * @param string $messageNumber
     */
    public function __construct($messageNumber) {
        parent::__construct(sprintf('Message %s cannot be deleted', $messageNumber));
    }
}

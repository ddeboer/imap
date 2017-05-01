<?php

namespace openWebX\Imap\Exception;

/**
 * Class MessageDoesNotExistException
 *
 * @package openWebX\Imap\Exception
 */
class MessageDoesNotExistException extends Exception {
    /**
     * MessageDoesNotExistException constructor.
     *
     * @param string $number
     * @param null   $error
     */
    public function __construct($number, $error) {
        parent::__construct(
            sprintf(
                'Message %s does not exist: %s',
                $number,
                $error
            )
        );
    }
}

<?php

namespace openWebX\Imap\Exception;

/**
 * Class AuthenticationFailedException
 *
 * @package openWebX\Imap\Exception
 */
class AuthenticationFailedException extends Exception {
    /**
     * AuthenticationFailedException constructor.
     *
     * @param string $user
     * @param null   $error
     */
    public function __construct($user, $error = NULL) {
        parent::__construct(
            sprintf(
                'Authentication failed for user %s with error %s',
                $user,
                $error
            )
        );
    }
}

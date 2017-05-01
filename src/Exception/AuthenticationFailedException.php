<?php

namespace openWebX\Imap\Exception;

class AuthenticationFailedException extends Exception
{
    public function __construct($user, $error = null)
    {
        parent::__construct(
            sprintf(
                'Authentication failed for user %s with error %s',
                $user,
                $error
            )
        );
    }
}

<?php

namespace Ddeboer\Imap\Exception;

class AuthenticationFailedException extends Exception
{
    public function __construct($user)
    {
        parent::__construct('Authentication failed for user ' . $user);
    }
}

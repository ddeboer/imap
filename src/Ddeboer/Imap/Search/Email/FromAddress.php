<?php

namespace Ddeboer\Imap\Search\Email;

use Ddeboer\Imap\Search\Email;

/**
 * Represents a "From" email address condition. Messages must have been sent
 * from the specified email address in order to match the condition.
 */
class FromAddress extends Email
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'FROM';
    }
}

<?php

namespace Ddeboer\Imap\Search\Email;

use Ddeboer\Imap\Search\Email;

/**
 * Represents a "To" email address condition. Messages must have been addressed
 * to the specified recipient (along with any others) in order to match the
 * condition.
 */
class To extends Email
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'TO';
    }
}

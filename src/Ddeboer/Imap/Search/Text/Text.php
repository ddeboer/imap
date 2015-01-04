<?php

namespace Ddeboer\Imap\Search\Text;

use Ddeboer\Imap\Search\Text as SearchText;

/**
 * Represents a message text contains condition. Messages must contain the
 * specified text in order to match the condition.
 */
class Text extends SearchText
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'TEXT';
    }
}

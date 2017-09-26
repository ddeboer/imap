<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Search\Text;

/**
 * Represents a message text contains condition. Messages must contain the
 * specified text in order to match the condition.
 */
class Text extends AbstractText
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword(): string
    {
        return 'TEXT';
    }
}

<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Search\Email;

/**
 * Represents a "From" email address condition. Messages must have been sent
 * from the specified email address in order to match the condition.
 */
final class FromAddress extends AbstractEmail
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    protected function getKeyword(): string
    {
        return 'FROM';
    }
}

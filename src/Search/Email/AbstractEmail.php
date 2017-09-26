<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Search\Email;

use Ddeboer\Imap\Search\AbstractCondition;

/**
 * Represents an email condition.
 */
abstract class AbstractEmail extends AbstractCondition
{
    /**
     * Email address for the condition.
     *
     * @var string
     */
    private $email;

    /**
     * Constructor
     *
     * @param string $email optional email address for the condition
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * Converts the condition to a string that can be sent to the IMAP server.
     *
     * @return string
     */
    final public function toString(): string
    {
        return sprintf('%s "%s"', $this->getKeyword(), $this->email);
    }
}

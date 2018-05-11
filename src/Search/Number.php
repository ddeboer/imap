<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Search;

/**
 * Represents a number range condition. Number is formally known as UID.
 */
final class Number implements ConditionInterface
{
    /**
     * The minimum accepted message number (null means unlimited).
     *
     * @var null|int
     */
    private $minNumber;

    /**
     * The maximum accepted message number (null means unlimited).
     *
     * @var null|int
     */
    private $maxNumber;

    /**
     * Constructor.
     *
     * @param null|int $minNumber the minimum accepted message number (null means unlimited)
     * @param null|int $maxNumber the maximum accepted message number (null means unlimited)
     */
    final public function __construct(int $minNumber = null, int $maxNumber = null)
    {
        $this->minNumber = $minNumber;
        $this->maxNumber = $maxNumber;
    }

    /**
     * Converts the condition to a string that can be sent to the IMAP server.
     *
     * @return string
     */
    final public function toString(): string
    {
        return \sprintf('UID "%s:%s"', $this->minNumber ?? '*', $this->maxNumber ?? '*');
    }
}

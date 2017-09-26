<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Search\Date;

use DateTimeInterface;
use Ddeboer\Imap\Search\AbstractCondition;

/**
 * Represents a date condition.
 */
abstract class AbstractDate extends AbstractCondition
{
    /**
     * Format for dates to be sent to the IMAP server.
     *
     * @var string
     */
    const DATE_FORMAT = 'Y-m-d';

    /**
     * The date to be used for the condition.
     *
     * @var DateTimeInterface
     */
    private $date;

    /**
     * Constructor.
     *
     * @param DateTimeInterface $date optional date for the condition
     */
    public function __construct(DateTimeInterface $date)
    {
        $this->date = $date;
    }

    /**
     * Converts the condition to a string that can be sent to the IMAP server.
     *
     * @return string
     */
    final public function toString(): string
    {
        return sprintf('%s "%s"', $this->getKeyword(), $this->date->format(self::DATE_FORMAT));
    }
}

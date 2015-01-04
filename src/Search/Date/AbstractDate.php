<?php

namespace Ddeboer\Imap\Search\Date;

use DateTime;
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
     * @var DateTime
     */
    protected $date;

    /**
     * Constructor.
     *
     * @param DateTime $date Optional date for the condition.
     */
    public function __construct(DateTime $date = null)
    {
        if ($date) {
            $this->setDate($date);
        }
    }

    /**
     * Sets the date for the condition.
     *
     * @param DateTime $date
     */
    public function setDate(DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Converts the condition to a string that can be sent to the IMAP server.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getKeyword() . ' "' . $this->date->format(self::DATE_FORMAT) .'"';
    }
}

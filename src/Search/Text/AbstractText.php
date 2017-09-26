<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Search\Text;

use Ddeboer\Imap\Search\AbstractCondition;

/**
 * Represents a text based condition. Text based conditions use a contains
 * restriction.
 */
abstract class AbstractText extends AbstractCondition
{
    /**
     * Text to be used for the condition.
     *
     * @var string
     */
    private $text;

    /**
     * Constructor.
     *
     * @param string $text optional text for the condition
     */
    public function __construct(string $text)
    {
        $this->text = $text;
    }

    /**
     * Converts the condition to a string that can be sent to the IMAP server.
     *
     * @return string
     */
    final public function toString(): string
    {
        return sprintf('%s "%s"', $this->getKeyword(), str_replace('"', '\\"', $this->text));
    }
}

<?php

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
    protected $text;

    /**
     * Constructor.
     *
     * @param string $text Optional text for the condition.
     */
    public function __construct($text = null)
    {
        if (!is_null($text) && strlen($text) > 0) {
            $this->setText($text);
        }
    }

    /**
     * Sets the text for the condition.
     *
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Converts the condition to a string that can be sent to the IMAP server.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getKeyword() . ' "' . str_replace('"', '\\"', $this->text) . '"';
    }
}

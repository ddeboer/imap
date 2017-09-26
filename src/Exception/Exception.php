<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Exception;

class Exception extends \RuntimeException
{
    protected $errors = [];

    public function __construct($message, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = imap_errors();
    }

    /**
     * Get IMAP errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}

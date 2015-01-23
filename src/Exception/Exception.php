<?php

namespace Ddeboer\Imap\Exception;

class Exception extends \RuntimeException
{
    protected $errors = array();

    public function __construct($message, $code = null, $previous = null)
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

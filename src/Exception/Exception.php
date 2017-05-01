<?php

namespace openWebX\Imap\Exception;

/**
 * Class Exception
 *
 * @package openWebX\Imap\Exception
 */
class Exception extends \RuntimeException {
    /**
     * @var array
     */
    protected $errors = [];

    /**
     * Exception constructor.
     *
     * @param string $message
     * @param null   $code
     * @param null   $previous
     */
    public function __construct($message, $code = NULL, $previous = NULL) {
        parent::__construct($message, $code, $previous);
        $this->errors = imap_errors();
    }

    /**
     * Get IMAP errors
     *
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
}

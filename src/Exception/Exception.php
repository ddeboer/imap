<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Exception;

class Exception extends \RuntimeException
{
    private static $errorLabels = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];

    final public function __construct($message, $code = 0, $previous = null)
    {
        $errorType = '';
        if (is_int($code) && isset(self::$errorLabels[$code])) {
            $errorType = sprintf('[%s] ', self::$errorLabels[$code]);
        }

        $joinString = "\n- ";
        $alerts = imap_alerts();
        $errors = imap_errors();
        $completeMessage = sprintf(
            "%s%s\nimap_alerts (%s):%s\nimap_errors (%s):%s",
            $errorType,
            $message,
            $alerts ? count($alerts) : 0,
            $alerts ? $joinString . implode($joinString, $alerts) : '',
            $errors ? count($errors) : 0,
            $errors ? $joinString . implode($joinString, $errors) : ''
        );

        parent::__construct($completeMessage, $code, $previous);
    }
}

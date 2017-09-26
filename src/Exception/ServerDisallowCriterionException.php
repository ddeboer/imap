<?php
/**
 * Created by PhpStorm.
 * User: holdmann
 * Date: 23.03.16
 * Time: 0:54
 */

namespace Ddeboer\Imap\Exception;


class ServerDisallowCriterionException extends \RuntimeException
{
    public function __construct($criterion)
    {
        parent::__construct('Server disallow criterion "' . $criterion . '"');
    }
}
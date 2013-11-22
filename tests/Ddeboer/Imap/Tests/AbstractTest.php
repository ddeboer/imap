<?php
namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Server;
use Ddeboer\Imap\Connection;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Connection
     */
    protected function getConnection()
    {
        $server = new Server('imap.gmail.com');

        return $server->authenticate(\getenv('EMAIL_USERNAME'), \getenv('EMAIL_PASSWORD'));
    }
}
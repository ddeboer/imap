<?php

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Server;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    public function testAuthorize()
    {
        $password = $_ENV['GMAIL_PASSWORD'];

        $server = new Server('gmail.com');
        $result = $server->authenticate('ddeboerimap', $password);

        $this->assertInstanceOf('\Ddeboer\Imap\Connection', $result);
    }
}
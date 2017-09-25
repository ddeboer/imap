<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Exception;
use Ddeboer\Imap\Server;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    public function testFailedAuthenticate()
    {
        $server = new Server(\getenv('IMAP_SERVER_NAME'), \getenv('IMAP_SERVER_PORT'));

        $this->expectException(Exception\AuthenticationFailedException::class);
        $this->expectExceptionMessageRegExp('/E_WARNING.+AUTHENTICATIONFAILED/s');

        $server->authenticate(uniqid('fake_username_'), uniqid('fake_password_'));
    }
}

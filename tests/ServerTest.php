<?php

namespace openWebX\Imap\Tests;

use openWebX\Imap\Server;

class ServerTest extends \PHPUnit_Framework_TestCase {
    /**
     * @expectedException \openWebX\Imap\Exception\AuthenticationFailedException
     */
    public function testFailedAuthenticate() {
        $server = new Server('imap.gmail.com');
        $server->authenticate('fake_username', 'fake_password');
    }
}

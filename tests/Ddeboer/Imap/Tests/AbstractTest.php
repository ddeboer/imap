<?php
namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Mailbox;
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

    protected function createTestMessage(
        Mailbox $mailbox,
        $subject = 'Don\t panic!',
        $from = 'someone@there.com',
        $to = 'me@here.com',
        $contents = 'Don\'t forget your towel'
    ) {
        $message = "From: $from\r\n"
            . "To: $to\r\n"
            . "Subject: $subject\r\n"
            . "\r\n"
            . "$contents\r\n";

        $mailbox->addMessage($message);
    }
}
<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Connection;
use Ddeboer\Imap\Exception\AuthenticationFailedException;
use Ddeboer\Imap\Server;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Server::class)]
final class ServerTest extends AbstractTestCase
{
    public function testValidConnection(): void
    {
        $connection = $this->getConnection();

        $check = \imap_check($connection->getResource()->getStream());

        self::assertInstanceOf(\stdClass::class, $check);
    }

    public function testFailedAuthenticate(): void
    {
        $server = new Server((string) \getenv('IMAP_SERVER_NAME'), (string) \getenv('IMAP_SERVER_PORT'), self::IMAP_FLAGS);

        $this->expectException(AuthenticationFailedException::class);
        $this->expectExceptionMessageMatches('/E_WARNING.+AUTHENTICATIONFAILED/s');

        $server->authenticate(\uniqid('fake_username_'), \uniqid('fake_password_'));
    }

    public function testEmptyPort(): void
    {
        if ('993' !== (string) \getenv('IMAP_SERVER_PORT')) {
            self::markTestSkipped('Active IMAP test server must have 993 port for this test');
        }

        $server = new Server((string) \getenv('IMAP_SERVER_NAME'), '', self::IMAP_FLAGS);

        self::assertInstanceOf(Connection::class, $server->authenticate((string) \getenv('IMAP_USERNAME'), (string) \getenv('IMAP_PASSWORD')));
    }

    public function testCustomOptions(): void
    {
        $server = new Server((string) \getenv('IMAP_SERVER_NAME'), (string) \getenv('IMAP_SERVER_PORT'), self::IMAP_FLAGS, [], \OP_HALFOPEN);

        $connection = $server->authenticate((string) \getenv('IMAP_USERNAME'), (string) \getenv('IMAP_PASSWORD'));

        $check = \imap_check($connection->getResource()->getStream());

        self::assertNotFalse($check);

        $mailbox = \strtolower($check->Mailbox);

        self::assertStringContainsString((string) \getenv('IMAP_USERNAME'), $mailbox);
        self::assertStringNotContainsString('inbox', $mailbox);
        self::assertStringContainsString('no_mailbox', $mailbox);
    }
}

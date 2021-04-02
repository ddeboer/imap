<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Connection;
use Ddeboer\Imap\Exception\AuthenticationFailedException;
use Ddeboer\Imap\Server;

/**
 * @covers \Ddeboer\Imap\Server
 */
final class ServerTest extends AbstractTest
{
    public function testValidConnection(): void
    {
        $connection = $this->getConnection();

        $check = \imap_check($connection->getResource()->getStream());

        static::assertInstanceOf(\stdClass::class, $check);
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
            static::markTestSkipped('Active IMAP test server must have 993 port for this test');
        }

        $server = new Server((string) \getenv('IMAP_SERVER_NAME'), '', self::IMAP_FLAGS);

        static::assertInstanceOf(Connection::class, $server->authenticate((string) \getenv('IMAP_USERNAME'), (string) \getenv('IMAP_PASSWORD')));
    }

    public function testCustomOptions(): void
    {
        $server = new Server((string) \getenv('IMAP_SERVER_NAME'), (string) \getenv('IMAP_SERVER_PORT'), self::IMAP_FLAGS, [], \OP_HALFOPEN);

        $connection = $server->authenticate((string) \getenv('IMAP_USERNAME'), (string) \getenv('IMAP_PASSWORD'));

        $check = \imap_check($connection->getResource()->getStream());

        static::assertNotFalse($check);

        $mailbox = \strtolower($check->Mailbox);

        static::assertStringContainsString((string) \getenv('IMAP_USERNAME'), $mailbox);
        static::assertStringNotContainsString('inbox', $mailbox);
        static::assertStringContainsString('no_mailbox', $mailbox);
    }
}

<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Exception\CreateMailboxException;
use Ddeboer\Imap\Exception\DeleteMailboxException;
use Ddeboer\Imap\Exception\InvalidResourceException;
use Ddeboer\Imap\Exception\MailboxDoesNotExistException;
use Ddeboer\Imap\ImapResource;
use Ddeboer\Imap\Mailbox;

/**
 * @covers \Ddeboer\Imap\Connection
 * @covers \Ddeboer\Imap\ImapResource
 */
final class ConnectionTest extends AbstractTest
{
    public function testValidResourceStream(): void
    {
        $connection = $this->getConnection();

        $check = \imap_check($connection->getResource()->getStream());

        static::assertInstanceOf(\stdClass::class, $check);
    }

    public function testCannotInstantiateArbitraryConnections(): void
    {
        $resource = new ImapResource(\uniqid());

        $this->expectException(InvalidResourceException::class);

        $resource->getStream();
    }

    public function testCloseConnection(): void
    {
        $connection = $this->createConnection();
        $connection->close();

        $this->expectException(InvalidResourceException::class);

        $connection->close();
    }

    public function testCount(): void
    {
        static::assertIsInt($this->getConnection()->count());
    }

    public function testPing(): void
    {
        $connection = $this->createConnection();

        static::assertTrue($connection->ping());

        $connection->close();

        $this->expectException(InvalidResourceException::class);

        $connection->ping();
    }

    public function testQuota(): void
    {
        if (false === \getenv('IMAP_QUOTAROOT_SUPPORTED')) {
            static::markTestSkipped('IMAP quota root support is disabled.');
        }

        $quota = $this->getConnection()->getQuota();

        static::assertArrayHasKey('usage', $quota);
        static::assertIsInt($quota['usage']);

        static::assertArrayHasKey('limit', $quota);
        // @see quota_rule in .travis/dovecot_install.sh
        static::assertSame(1048576, $quota['limit']);
    }

    public function testGetMailboxes(): void
    {
        $mailboxes = $this->getConnection()->getMailboxes();
        foreach ($mailboxes as $mailbox) {
            static::assertInstanceOf(Mailbox::class, $mailbox);
        }
    }

    public function testGetMailbox(): void
    {
        $mailbox = $this->getConnection()->getMailbox('INBOX');
        static::assertInstanceOf(Mailbox::class, $mailbox);
    }

    public function testCreateMailbox(): void
    {
        $connection = $this->getConnection();

        $name    = \uniqid('test_');
        $mailbox = $connection->createMailbox($name);
        static::assertSame($name, $mailbox->getName());
        static::assertSame($name, $connection->getMailbox($name)->getName());

        $connection->deleteMailbox($mailbox);

        $this->expectException(MailboxDoesNotExistException::class);

        $connection->getMailbox($name);
    }

    public function testCannotDeleteInvalidMailbox(): void
    {
        $connection = $this->getConnection();
        $mailbox    = $this->createMailbox();

        $connection->deleteMailbox($mailbox);

        $this->expectException(DeleteMailboxException::class);
        $this->expectExceptionMessageRegExp('/NONEXISTENT/');

        $connection->deleteMailbox($mailbox);
    }

    public function testCannotCreateMailboxesOnReadonly(): void
    {
        $this->expectException(CreateMailboxException::class);
        $this->expectExceptionMessageRegExp('/(SERVERBUG|ALREADYEXISTS)/');

        $this->getConnection()->createMailbox('INBOX');
    }

    public function testEscapesMailboxNames(): void
    {
        static::assertInstanceOf(Mailbox::class, $this->getConnection()->createMailbox(\uniqid(self::SPECIAL_CHARS)));
    }

    public function testCustomExceptionOnInvalidMailboxName(): void
    {
        $this->expectException(CreateMailboxException::class);
        $this->expectExceptionMessageRegExp('/CANNOT/');

        static::assertInstanceOf(Mailbox::class, $this->getConnection()->createMailbox(\uniqid("\t")));
    }

    public function testGetInvalidMailbox(): void
    {
        $this->expectException(MailboxDoesNotExistException::class);
        $this->getConnection()->getMailbox('does-not-exist');
    }

    public function testNumericMailbox(): void
    {
        $number  = (string) \mt_rand(100, 999);
        $conn    = $this->getConnection();
        $mailbox = $conn->createMailbox($number);

        $mailboxes = $conn->getMailboxes();

        static::assertArrayHasKey($number, $mailboxes);
    }

    public function testMailboxSelectionAfterReconnect(): void
    {
        $connection  = $this->createConnection();
        $mailbox     = $this->createMailbox($connection);
        $mailboxName = $mailbox->getName();
        $this->createTestMessage($mailbox, 'Reconnect test');

        $connection->close();

        $connection = $this->createConnection();
        $mailbox    = $connection->getMailbox($mailboxName);

        // This fails if we haven't properly reselected the mailbox
        static::assertSame('Reconnect test', $mailbox->getMessages()->current()->getSubject());

        $connection->close();
    }
}

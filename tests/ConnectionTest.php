<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Connection;
use Ddeboer\Imap\Exception\CreateMailboxException;
use Ddeboer\Imap\Exception\DeleteMailboxException;
use Ddeboer\Imap\Exception\MailboxDoesNotExistException;
use Ddeboer\Imap\Exception\SubscribeMailboxException;
use Ddeboer\Imap\ImapResource;
use Ddeboer\Imap\Mailbox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[CoversClass(Connection::class)]
#[CoversClass(ImapResource::class)]
#[RunTestsInSeparateProcesses]
final class ConnectionTest extends AbstractTestCase
{
    public function testValidResourceStream(): void
    {
        $connection = $this->getConnection();

        $check = \imap_check($connection->getResource()->getStream());

        self::assertInstanceOf(\stdClass::class, $check);
    }

    public function testCount(): void
    {
        self::assertSame(0, $this->getConnection()->count());
    }

    public function testPing(): void
    {
        $connection = $this->createConnection();

        self::assertTrue($connection->ping());

        $connection->close();
    }

    public function testQuota(): void
    {
        if (false === \getenv('IMAP_QUOTAROOT_SUPPORTED')) {
            self::markTestSkipped('IMAP quota root support is disabled.');
        }

        $quota = $this->getConnection()->getQuota();

        self::assertArrayHasKey('usage', $quota);
        self::assertSame(1, $quota['usage']);

        self::assertArrayHasKey('limit', $quota);
        // @see quota_rule in .travis/dovecot_install.sh
        self::assertSame(1048576, $quota['limit']);
    }

    public function testGetMailboxes(): void
    {
        $mailboxes = $this->getConnection()->getMailboxes();
        foreach ($mailboxes as $mailbox) {
            self::assertInstanceOf(Mailbox::class, $mailbox);
        }
    }

    public function testGetMailbox(): void
    {
        $mailbox = $this->getConnection()->getMailbox('INBOX');
        self::assertInstanceOf(Mailbox::class, $mailbox);
    }

    public function testCreateMailbox(): void
    {
        $connection = $this->getConnection();

        $name    = \uniqid('test_');
        $mailbox = $connection->createMailbox($name);
        self::assertSame($name, $mailbox->getName());
        self::assertSame($name, $connection->getMailbox($name)->getName());

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
        $this->expectExceptionMessageMatches('/NONEXISTENT/');

        $connection->deleteMailbox($mailbox);
    }

    public function testCannotCreateMailboxesOnReadonly(): void
    {
        $this->expectException(CreateMailboxException::class);
        $this->expectExceptionMessageMatches('/(SERVERBUG|ALREADYEXISTS)/');

        $this->getConnection()->createMailbox('INBOX');
    }

    public function testEscapesMailboxNames(): void
    {
        self::assertInstanceOf(Mailbox::class, $this->getConnection()->createMailbox(\uniqid(self::SPECIAL_CHARS)));
    }

    public function testCustomExceptionOnInvalidMailboxName(): void
    {
        $this->expectException(CreateMailboxException::class);
        $this->expectExceptionMessageMatches('/CANNOT/');

        self::assertInstanceOf(Mailbox::class, $this->getConnection()->createMailbox(\uniqid("\t")));
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
        $conn->createMailbox($number);

        $mailboxes = $conn->getMailboxes();

        self::assertArrayHasKey($number, $mailboxes);
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
        self::assertSame('Reconnect test', $mailbox->getMessages()->current()->getSubject());

        $connection->close();
    }

    public function testSubscribeMailbox(): void
    {
        $connection = $this->getConnection();

        $name    = \uniqid('test_');
        $mailbox = $connection->createMailbox($name);
        $connection->subscribeMailbox($name);

        $connection->deleteMailbox($mailbox);

        $this->expectException(SubscribeMailboxException::class);
        $connection->subscribeMailbox($name);
    }
}

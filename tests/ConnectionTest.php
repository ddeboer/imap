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
    public function testValidResourceStream()
    {
        $connection = $this->getConnection();

        $check = \imap_check($connection->getResource()->getStream());

        static::assertInstanceOf(\stdClass::class, $check);
    }

    public function testCannotInstantiateArbitraryConnections()
    {
        $resource = new ImapResource(\uniqid());

        $this->expectException(InvalidResourceException::class);

        $resource->getStream();
    }

    public function testCloseConnection()
    {
        $connection = $this->createConnection();
        $connection->close();

        $this->expectException(InvalidResourceException::class);

        $connection->close();
    }

    public function testCount()
    {
        static::assertIsInt($this->getConnection()->count());
    }

    public function testPing()
    {
        $connection = $this->createConnection();

        static::assertTrue($connection->ping());

        $connection->close();

        $this->expectException(InvalidResourceException::class);

        $connection->ping();
    }

    public function testQuota()
    {
        if (false === \getenv('IMAP_QUOTAROOT_SUPPORTED')) {
            $this->markTestSkipped('IMAP quota root support is disabled.');
        }

        $quota = $this->getConnection()->getQuota();

        static::assertArrayHasKey('usage', $quota);
        static::assertArrayHasKey('limit', $quota);
    }

    public function testGetMailboxes()
    {
        $mailboxes = $this->getConnection()->getMailboxes();
        foreach ($mailboxes as $mailbox) {
            static::assertInstanceOf(Mailbox::class, $mailbox);
        }
    }

    public function testGetMailbox()
    {
        $mailbox = $this->getConnection()->getMailbox('INBOX');
        static::assertInstanceOf(Mailbox::class, $mailbox);
    }

    public function testCreateMailbox()
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

    public function testCannotDeleteInvalidMailbox()
    {
        $connection = $this->getConnection();
        $mailbox    = $this->createMailbox();

        $connection->deleteMailbox($mailbox);

        $this->expectException(DeleteMailboxException::class);
        $this->expectExceptionMessageRegExp('/NONEXISTENT/');

        $connection->deleteMailbox($mailbox);
    }

    public function testCannotCreateMailboxesOnReadonly()
    {
        $this->expectException(CreateMailboxException::class);
        $this->expectExceptionMessageRegExp('/(SERVERBUG|ALREADYEXISTS)/');

        $this->getConnection()->createMailbox('INBOX');
    }

    public function testEscapesMailboxNames()
    {
        static::assertInstanceOf(Mailbox::class, $this->getConnection()->createMailbox(\uniqid(self::SPECIAL_CHARS)));
    }

    public function testCustomExceptionOnInvalidMailboxName()
    {
        $this->expectException(CreateMailboxException::class);
        $this->expectExceptionMessageRegExp('/CANNOT/');

        static::assertInstanceOf(Mailbox::class, $this->getConnection()->createMailbox(\uniqid("\t")));
    }

    public function testGetInvalidMailbox()
    {
        $this->expectException(MailboxDoesNotExistException::class);
        $this->getConnection()->getMailbox('does-not-exist');
    }

    public function testNumericMailbox()
    {
        $number  = (string) \mt_rand(100, 999);
        $conn    = $this->getConnection();
        $mailbox = $conn->createMailbox($number);

        $mailboxes = $conn->getMailboxes();

        static::assertArrayHasKey($number, $mailboxes);
    }
}

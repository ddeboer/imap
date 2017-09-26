<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Connection;
use Ddeboer\Imap\Exception\Exception;
use Ddeboer\Imap\Exception\MailboxDoesNotExistException;
use Ddeboer\Imap\Mailbox;

/**
 * @covers \Ddeboer\Imap\Connection
 */
class ConnectionTest extends AbstractTest
{
    public function testCannotInstantiateArbitraryConnections()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Connection(uniqid(), uniqid());
    }

    public function testCount()
    {
        $this->assertInternalType('int', $this->getConnection()->count());
    }

    public function testGetMailboxes()
    {
        $mailboxes = $this->getConnection()->getMailboxes();
        $this->assertInternalType('array', $mailboxes);

        foreach ($mailboxes as $mailbox) {
            $this->assertInstanceOf(Mailbox::class, $mailbox);
        }
    }

    public function testGetMailbox()
    {
        $mailbox = $this->getConnection()->getMailbox('INBOX');
        $this->assertInstanceOf(Mailbox::class, $mailbox);
    }

    public function testCreateMailbox()
    {
        $connection = $this->getConnection();

        $name = uniqid('test_');
        $mailbox = $connection->createMailbox($name);
        $this->assertSame($name, $mailbox->getName());
        $this->assertSame($name, $connection->getMailbox($name)->getName());

        $mailbox->delete();

        $this->expectException(MailboxDoesNotExistException::class);

        $connection->getMailbox($name);
    }

    public function testCannotCreateMailboxesOnReadonly()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/ALREADYEXISTS/');

        $this->getConnection()->createMailbox('INBOX');
    }

    public function testEscapesMailboxNames()
    {
        $this->assertInstanceOf(Mailbox::class, $this->getConnection()->createMailbox(uniqid(self::NON_PRINTABLE_ASCII)));
    }

    public function testCustomExceptionOnInvalidMailboxName()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/Mailbox name is not valid mUTF-7/');

        $this->assertInstanceOf(Mailbox::class, $this->getConnection()->createMailbox(uniqid('A_€_')));
    }

    public function testGetInvalidMailbox()
    {
        $this->expectException(MailboxDoesNotExistException::class);
        $this->getConnection()->getMailbox('does-not-exist');
    }
}

<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use DateTimeImmutable;
use Ddeboer\Imap\Exception\MessageDoesNotExistException;
use Ddeboer\Imap\Exception\OutOfBoundsException;
use Ddeboer\Imap\Exception\ReopenMailboxException;
use Ddeboer\Imap\Mailbox;

/**
 * @covers \Ddeboer\Imap\Exception\AbstractException
 * @covers \Ddeboer\Imap\ImapResource
 * @covers \Ddeboer\Imap\Mailbox
 */
final class MailboxTest extends AbstractTest
{
    protected $mailbox;

    protected function setUp()
    {
        $this->mailbox = $this->createMailbox();

        $this->createTestMessage($this->mailbox, 'Message 1');
        $this->createTestMessage($this->mailbox, 'Message 2');
        $this->createTestMessage($this->mailbox, 'Message 3');
    }

    public function testGetName()
    {
        $this->assertSame($this->mailboxName, $this->mailbox->getName());
    }

    public function testGetFullEncodedName()
    {
        $this->assertContains(\getenv('IMAP_SERVER_PORT'), $this->mailbox->getFullEncodedName());
        $this->assertNotContains($this->mailboxName, $this->mailbox->getFullEncodedName());
        $this->assertContains(\mb_convert_encoding($this->mailboxName, 'UTF7-IMAP', 'UTF-8'), $this->mailbox->getFullEncodedName());
        $this->assertNotContains(':' . \getenv('IMAP_SERVER_PORT'), $this->mailbox->getEncodedName());
    }

    public function testGetAttributes()
    {
        $this->assertInternalType('integer', $this->mailbox->getAttributes());
    }

    public function testGetDelimiter()
    {
        $this->assertInternalType('string', $this->mailbox->getDelimiter());
    }

    public function testGetMessages()
    {
        $directMethodInc = 0;
        foreach ($this->mailbox->getMessages() as $message) {
            ++$directMethodInc;
        }

        $this->assertSame(3, $directMethodInc);

        $aggregateIteratorMethodInc = 0;
        foreach ($this->mailbox as $message) {
            ++$aggregateIteratorMethodInc;
        }

        $this->assertSame(3, $aggregateIteratorMethodInc);
    }

    public function testGetMessageThrowsException()
    {
        $this->expectException(MessageDoesNotExistException::class);
        $this->expectExceptionMessageRegExp('/E_WARNING.+Message "999" does not exist.+Bad message number/s');

        $this->mailbox->getMessage(999);
    }

    public function testCount()
    {
        $this->assertSame(3, $this->mailbox->count());
    }

    public function testDelete()
    {
        $connection = $this->getConnection();
        $connection->deleteMailbox($this->mailbox);

        $this->expectException(ReopenMailboxException::class);

        $this->mailbox->count();
    }

    public function testDefaultStatus()
    {
        $status = $this->mailbox->getStatus();

        $this->assertSame(\SA_ALL, $status->flags);
        $this->assertSame(3, $status->messages);
        $this->assertSame(4, $status->uidnext);
    }

    public function testCustomStatusFlag()
    {
        $status = $this->mailbox->getStatus(\SA_MESSAGES);

        $this->assertSame(\SA_MESSAGES, $status->flags);
        $this->assertSame(3, $status->messages);
        $this->assertFalse(isset($status->uidnext), 'uidnext shouldn\'t be set');
    }

    public function testBulkSetFlags()
    {
        // prepare second mailbox with 3 messages
        $anotherMailbox = $this->createMailbox();
        $this->createTestMessage($anotherMailbox, 'Message 1');
        $this->createTestMessage($anotherMailbox, 'Message 2');
        $this->createTestMessage($anotherMailbox, 'Message 3');

        // Message UIDs created in setUp method
        $messages = [1, 2, 3];

        foreach ($messages as $uid) {
            $message = $this->mailbox->getMessage($uid);
            $this->assertFalse($message->isFlagged());
        }

        $this->mailbox->setFlag('\\Flagged', $messages);

        foreach ($messages as $uid) {
            $message = $this->mailbox->getMessage($uid);
            $this->assertTrue($message->isFlagged());
        }

        $this->mailbox->clearFlag('\\Flagged', $messages);

        foreach ($messages as $uid) {
            $message = $this->mailbox->getMessage($uid);
            $this->assertFalse($message->isFlagged());
        }

        // Set flag for messages from another mailbox
        $anotherMailbox->setFlag('\\Flagged', [1, 2, 3]);

        $this->assertTrue($anotherMailbox->getMessage(2)->isFlagged());
    }

    public function testBulkSetFlagsNumbersParameter()
    {
        $mailbox = $this->createMailbox();

        $uids = \range(1, 10);

        foreach ($uids as $uid) {
            $this->createTestMessage($mailbox, 'Message ' . $uid);
        }

        $mailbox->setFlag('\\Seen', [
            '1,2',
            '3',
            '4:6',
        ]);
        $mailbox->setFlag('\\Seen', '7,8:10');

        foreach ($uids as $uid) {
            $message = $mailbox->getMessage($uid);
            $this->assertTrue($message->isSeen());
        }

        $mailbox->clearFlag('\\Seen', '1,2,3,4:6');
        $mailbox->clearFlag('\\Seen', [
          '7:9',
          '10',
        ]);

        foreach ($uids as $uid) {
            $message = $mailbox->getMessage($uid);
            $this->assertFalse($message->isSeen());
        }
    }

    public function testThread()
    {
        $mailboxOne = $this->createMailbox();
        $mailboxOne->addMessage($this->getFixture('thread/my_topic'));
        $mailboxOne->addMessage($this->getFixture('thread/unrelated'));
        $mailboxOne->addMessage($this->getFixture('thread/re_my_topic'));

        $expected = [
            '0.num' => 1,
            '0.next' => 1,
            '1.num' => 3,
            '1.next' => 0,
            '1.branch' => 0,
            '0.branch' => 2,
            '2.num' => 2,
            '2.next' => 0,
            '2.branch' => 0,
        ];

        $this->assertSame($expected, $mailboxOne->getThread());

        $emptyMailbox = $this->createMailbox();

        $this->assertEmpty($emptyMailbox->getThread());
    }

    public function testAppendOptionalArguments()
    {
        $mailbox = $this->createMailbox();

        $mailbox->addMessage($this->getFixture('thread/unrelated'), '\\Seen', new DateTimeImmutable('2012-01-03T10:30:03+01:00'));

        $message = $mailbox->getMessage(1);

        $this->assertTrue($message->isSeen());
        $this->assertSame(' 3-Jan-2012 09:30:03 +0000', $message->getHeaders()->get('maildate'));
    }

    public function testEmptyMailbox()
    {
        $mailbox = $this->createMailbox();

        $messages = $mailbox->getMessages();
        $this->assertCount(0, $messages);
        $this->assertFalse(\current($messages));

        $this->expectException(OutOfBoundsException::class);

        $messages->current();
    }
}

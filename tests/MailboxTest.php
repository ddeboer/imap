<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use DateTimeImmutable;
use Ddeboer\Imap\Exception\InvalidSearchCriteriaException;
use Ddeboer\Imap\Exception\MessageCopyException;
use Ddeboer\Imap\Exception\MessageDoesNotExistException;
use Ddeboer\Imap\Exception\MessageMoveException;
use Ddeboer\Imap\Exception\ReopenMailboxException;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\MessageIterator;
use Ddeboer\Imap\MessageIteratorInterface;
use Ddeboer\Imap\Search;

/**
 * @covers \Ddeboer\Imap\Exception\AbstractException
 * @covers \Ddeboer\Imap\ImapResource
 * @covers \Ddeboer\Imap\Mailbox
 */
final class MailboxTest extends AbstractTest
{
    /** @var MailboxInterface */
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
        static::assertSame($this->mailboxName, $this->mailbox->getName());
    }

    public function testGetFullEncodedName()
    {
        static::assertIsString($this->mailboxName);

        static::assertContains(\getenv('IMAP_SERVER_PORT'), $this->mailbox->getFullEncodedName());
        static::assertNotContains($this->mailboxName, $this->mailbox->getFullEncodedName());
        static::assertContains(\mb_convert_encoding($this->mailboxName, 'UTF7-IMAP', 'UTF-8'), $this->mailbox->getFullEncodedName());
        static::assertNotContains(':' . \getenv('IMAP_SERVER_PORT'), $this->mailbox->getEncodedName());
    }

    public function testGetAttributes()
    {
        static::assertGreaterThan(0, $this->mailbox->getAttributes());
    }

    public function testGetDelimiter()
    {
        static::assertNotEmpty($this->mailbox->getDelimiter());
    }

    public function testGetMessages()
    {
        $directMethodInc = 0;
        foreach ($this->mailbox->getMessages() as $message) {
            ++$directMethodInc;
        }

        static::assertSame(3, $directMethodInc);

        $aggregateIteratorMethodInc = 0;
        foreach ($this->mailbox as $message) {
            ++$aggregateIteratorMethodInc;
        }

        static::assertSame(3, $aggregateIteratorMethodInc);
    }

    public function testGetMessageSequence()
    {
        $inc = 0;
        foreach ($this->mailbox->getMessageSequence('1:*') as $message) {
            ++$inc;
        }
        static::assertSame(3, $inc);

        $inc = 0;
        foreach ($this->mailbox->getMessageSequence('1:2') as $message) {
            ++$inc;
        }

        static::assertSame(2, $inc);
        $inc = 0;
        foreach ($this->mailbox->getMessageSequence('99998:99999') as $message) {
            ++$inc;
        }
        static::assertSame(0, $inc);
    }

    public function testGetMessageSequenceThrowsException()
    {
        $this->expectException(InvalidSearchCriteriaException::class);
        $this->mailbox->getMessageSequence('-1:x');
    }

    public function testGetMessageThrowsException()
    {
        $message = $this->mailbox->getMessage(999);

        $this->expectException(MessageDoesNotExistException::class);
        $this->expectExceptionMessageRegExp('/Message "999" does not exist/');

        $message->isRecent();
    }

    public function testCount()
    {
        static::assertSame(3, $this->mailbox->count());
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

        static::assertSame(\SA_ALL, $status->flags);
        static::assertSame(3, $status->messages);
        static::assertSame(4, $status->uidnext);
    }

    public function testCustomStatusFlag()
    {
        $status = $this->mailbox->getStatus(\SA_MESSAGES);

        static::assertSame(\SA_MESSAGES, $status->flags);
        static::assertSame(3, $status->messages);
        static::assertFalse(isset($status->uidnext), 'uidnext shouldn\'t be set');
    }

    public function testQuota()
    {
        static::markTestIncomplete('imap_get_quotaroot isn\'t supported by the current c-client library');

        $quota = $this->mailbox->getQuota();

        static::assertArrayHasKey('usage', $quota);
        static::assertArrayHasKey('limit', $quota);
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
            static::assertFalse($message->isFlagged());
        }

        $this->mailbox->setFlag('\\Flagged', $messages);

        foreach ($messages as $uid) {
            $message = $this->mailbox->getMessage($uid);
            static::assertTrue($message->isFlagged());
        }

        $this->mailbox->clearFlag('\\Flagged', $messages);

        foreach ($messages as $uid) {
            $message = $this->mailbox->getMessage($uid);
            static::assertFalse($message->isFlagged());
        }

        // Set flag for messages from another mailbox
        $anotherMailbox->setFlag('\\Flagged', [1, 2, 3]);

        static::assertTrue($anotherMailbox->getMessage(2)->isFlagged());
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
            static::assertTrue($message->isSeen());
        }

        $mailbox->clearFlag('\\Seen', '1,2,3,4:6');
        $mailbox->clearFlag('\\Seen', [
            '7:9',
            '10',
        ]);

        foreach ($uids as $uid) {
            $message = $mailbox->getMessage($uid);
            static::assertFalse($message->isSeen());
        }
    }

    public function testThread()
    {
        $mailboxOne = $this->createMailbox();
        $mailboxOne->addMessage($this->getFixture('thread/my_topic'));
        $mailboxOne->addMessage($this->getFixture('thread/unrelated'));
        $mailboxOne->addMessage($this->getFixture('thread/re_my_topic'));

        $expected = [
            '0.num'    => 1,
            '0.next'   => 1,
            '1.num'    => 3,
            '1.next'   => 0,
            '1.branch' => 0,
            '0.branch' => 2,
            '2.num'    => 2,
            '2.next'   => 0,
            '2.branch' => 0,
        ];

        static::assertSame($expected, $mailboxOne->getThread());

        $emptyMailbox = $this->createMailbox();

        static::assertEmpty($emptyMailbox->getThread());
    }

    public function testAppendOptionalArguments()
    {
        $mailbox = $this->createMailbox();

        $mailbox->addMessage($this->getFixture('thread/unrelated'), '\\Seen', new DateTimeImmutable('2012-01-03T10:30:03+01:00'));

        $message = $mailbox->getMessage(1);

        static::assertTrue($message->isSeen());
        static::assertSame(' 3-Jan-2012 09:30:03 +0000', $message->getHeaders()->get('maildate'));
    }

    public function testBulkMove()
    {
        $anotherMailbox = $this->createMailbox();

        // Test move by id
        $messages = [1, 2, 3];

        static::assertSame(0, $anotherMailbox->count());
        $this->mailbox->move($messages, $anotherMailbox);
        $this->getConnection()->expunge();

        static::assertSame(3, $anotherMailbox->count());
        static::assertSame(0, $this->mailbox->count());

        // move back by iterator
        /** @var MessageIterator $messages */
        $messages = $anotherMailbox->getMessages();
        $anotherMailbox->move($messages, $this->mailbox);
        $this->getConnection()->expunge();

        static::assertSame(0, $anotherMailbox->count());
        static::assertSame(3, $this->mailbox->count());

        // test failing bulk move - try to move to a non-existent mailbox
        $this->getConnection()->deleteMailbox($anotherMailbox);
        $this->expectException(MessageMoveException::class);
        $this->mailbox->move($messages, $anotherMailbox);
    }

    public function testBulkCopy()
    {
        $anotherMailbox = $this->createMailbox();
        $messages       = [1, 2, 3];

        static::assertSame(0, $anotherMailbox->count());
        static::assertSame(3, $this->mailbox->count());
        $this->mailbox->copy($messages, $anotherMailbox);

        static::assertSame(3, $anotherMailbox->count());
        static::assertSame(3, $this->mailbox->count());

        // test failing bulk copy - try to move to a non-existent mailbox
        $this->getConnection()->deleteMailbox($anotherMailbox);
        $this->expectException(MessageCopyException::class);
        $this->mailbox->copy($messages, $anotherMailbox);
    }

    public function testSort()
    {
        $anotherMailbox = $this->createMailbox();
        $this->createTestMessage($anotherMailbox, 'B');
        $this->createTestMessage($anotherMailbox, 'A');
        $this->createTestMessage($anotherMailbox, 'C');

        $concatSubjects = static function (MessageIteratorInterface $it) {
            $subject = '';
            foreach ($it as $message) {
                $subject .= $message->getSubject();
            }

            return $subject;
        };

        static::assertSame('BAC', $concatSubjects($anotherMailbox->getMessages()));
        static::assertSame('ABC', $concatSubjects($anotherMailbox->getMessages(null, \SORTSUBJECT)));
        static::assertSame('CBA', $concatSubjects($anotherMailbox->getMessages(null, \SORTSUBJECT, true)));
        static::assertSame('B', $concatSubjects($anotherMailbox->getMessages(new Search\Text\Subject('B'), \SORTSUBJECT, true)));
    }

    public function testGetMessagesWithUtf8Subject()
    {
        $anotherMailbox = $this->createMailbox();
        $this->createTestMessage($anotherMailbox, '1', 'Ж П');
        $this->createTestMessage($anotherMailbox, '2', 'Ж б');
        $this->createTestMessage($anotherMailbox, '3', 'б П');

        $messagesFound = '';
        foreach ($anotherMailbox->getMessages(new Search\Text\Body(\mb_convert_encoding('б', 'Windows-1251', 'UTF-8')), null, false, 'Windows-1251') as $message) {
            $subject = $message->getSubject();
            static::assertIsString($subject);

            $messagesFound .= \substr($subject, 0, 1);
        }

        static::assertSame('23', $messagesFound);

        $messagesFound = '';
        foreach ($anotherMailbox->getMessages(new Search\Text\Body(\mb_convert_encoding('П', 'Windows-1251', 'UTF-8')), \SORTSUBJECT, true, 'Windows-1251') as $message) {
            $subject = $message->getSubject();
            static::assertIsString($subject);

            $messagesFound .= \substr($subject, 0, 1);
        }

        static::assertSame('31', $messagesFound);
    }
}

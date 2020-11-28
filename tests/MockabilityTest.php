<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\ConnectionInterface;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\Message\AttachmentInterface;
use Ddeboer\Imap\MessageInterface;
use Ddeboer\Imap\ServerInterface;
use Ddeboer\Imap\Test\RawMessageIterator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ddeboer\Imap\Test\RawMessageIterator
 */
final class MockabilityTest extends TestCase
{
    public function testFullMockedBehaviour(): void
    {
        // Setup
        $username           = \uniqid('username_');
        $password           = \uniqid('password_');
        $inboxName          = \uniqid('INBOX_');
        $attachmentFilename = \uniqid('filename_');

        $attachmentMock = $this->createMock(AttachmentInterface::class);
        $attachmentMock
            ->expects(static::once())
            ->method('getFilename')
            ->willReturn($attachmentFilename)
        ;

        $messageMock = $this->createMock(MessageInterface::class);
        $messageMock
            ->expects(static::once())
            ->method('getAttachments')
            ->willReturn([$attachmentMock])
        ;

        $mailboxMock = $this->createMock(MailboxInterface::class);
        $mailboxMock
            ->expects(static::once())
            ->method('getMessages')
            ->willReturn(new RawMessageIterator([$messageMock]))
        ;

        $connectionMock = $this->createMock(ConnectionInterface::class);
        $connectionMock
            ->expects(static::once())
            ->method('getMailbox')
            ->with(static::identicalTo($inboxName))
            ->willReturn($mailboxMock)
        ;

        $serverMock = $this->createMock(ServerInterface::class);
        $serverMock
            ->expects(static::once())
            ->method('authenticate')
            ->with(
                static::identicalTo($username),
                static::identicalTo($password)
            )
            ->willReturn($connectionMock)
        ;

        // Run
        $connection = $serverMock->authenticate($username, $password);
        $mailbox    = $connection->getMailbox($inboxName);
        $messages   = $mailbox->getMessages();

        static::assertCount(1, $messages);

        // This foreach has the solely purpose to trigger code-coverage for
        // RawMessageIterator::current() and prove RawMessageIterator is
        // iterable. There is no need to do this in your app test suite
        $loopedMessages = [];
        foreach ($messages as $message) {
            $loopedMessages[] = $message;
        }

        static::assertCount(1, $loopedMessages);
        $foundMessage = \current($loopedMessages);
        static::assertInstanceOf(MessageInterface::class, $foundMessage);
        $attachments = $foundMessage->getAttachments();

        static::assertCount(1, $attachments);

        $attachment = \current($attachments);
        static::assertNotFalse($attachment);
        static::assertSame($attachmentFilename, $attachment->getFilename());
    }
}

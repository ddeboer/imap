<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\ConnectionInterface;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\Message\AttachmentInterface;
use Ddeboer\Imap\MessageInterface;
use Ddeboer\Imap\ServerInterface;
use Ddeboer\Imap\Test\RawMessageIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RawMessageIterator::class)]
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
            ->expects(self::once())
            ->method('getFilename')
            ->willReturn($attachmentFilename)
        ;

        $messageMock = $this->createMock(MessageInterface::class);
        $messageMock
            ->expects(self::once())
            ->method('getAttachments')
            ->willReturn([$attachmentMock])
        ;

        $mailboxMock = $this->createMock(MailboxInterface::class);
        $mailboxMock
            ->expects(self::once())
            ->method('getMessages')
            ->willReturn(new RawMessageIterator([$messageMock]))
        ;

        $connectionMock = $this->createMock(ConnectionInterface::class);
        $connectionMock
            ->expects(self::once())
            ->method('getMailbox')
            ->with(self::identicalTo($inboxName))
            ->willReturn($mailboxMock)
        ;

        $serverMock = $this->createMock(ServerInterface::class);
        $serverMock
            ->expects(self::once())
            ->method('authenticate')
            ->with(
                self::identicalTo($username),
                self::identicalTo($password)
            )
            ->willReturn($connectionMock)
        ;

        // Run
        $connection = $serverMock->authenticate($username, $password);
        $mailbox    = $connection->getMailbox($inboxName);
        $messages   = $mailbox->getMessages();

        self::assertCount(1, $messages);

        // This foreach has the solely purpose to trigger code-coverage for
        // RawMessageIterator::current() and prove RawMessageIterator is
        // iterable. There is no need to do this in your app test suite
        $loopedMessages = [];
        foreach ($messages as $message) {
            $loopedMessages[] = $message;
        }

        self::assertCount(1, $loopedMessages);
        $foundMessage = \current($loopedMessages);
        $attachments  = $foundMessage->getAttachments();

        self::assertCount(1, $attachments);

        $attachment = \current($attachments);
        self::assertSame($attachmentFilename, $attachment->getFilename());
    }
}

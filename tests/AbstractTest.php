<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Mailbox;
use Ddeboer\Imap\Server;
use PHPUnit_Framework_TestCase;
use Zend\Mail;
use Zend\Mime;

abstract class AbstractTest extends PHPUnit_Framework_TestCase
{
    const IMAP_FLAGS = '/imap/ssl/novalidate-cert';

    const SPECIAL_CHARS = 'A_\\|!"£$%&()=?àèìòùÀÈÌÒÙ<>-@#[]{}_ß_б_π_€_✔_你_يد_Z_';

    final protected function getConnection()
    {
        static $connection;
        if (null === $connection) {
            $connection = $this->createConnection();
        }

        return $connection;
    }

    final protected function createConnection()
    {
        $server = new Server(\getenv('IMAP_SERVER_NAME'), \getenv('IMAP_SERVER_PORT'), self::IMAP_FLAGS);

        return $server->authenticate(\getenv('IMAP_USERNAME'), \getenv('IMAP_PASSWORD'));
    }

    final protected function createMailbox()
    {
        $this->mailboxName = uniqid('mailbox_' . self::SPECIAL_CHARS);

        return $this->getConnection()->createMailbox($this->mailboxName);
    }

    final protected function createTestMessage(
        Mailbox $mailbox,
        string $subject,
        string $contents = null,
        string $encoding = null,
        string $charset = null,
        string $overwriteCharset = null
    ) {
        $bodyPart = new Mime\Part($contents ?? uniqid($subject));
        $bodyPart->setType(Mime\Mime::TYPE_TEXT);
        if ($encoding) {
            $bodyPart->setEncoding($encoding);
        }
        if ($charset) {
            $bodyPart->setCharset($charset);
        }

        $bodyMessage = new Mime\Message();
        $bodyMessage->addPart($bodyPart);

        $message = new Mail\Message();
        $message->addFrom('from@here.com');
        $message->addTo('to@there.com');
        $message->setSubject($subject);
        $message->setBody($bodyMessage);

        $messageString = $message->toString();
        if ($overwriteCharset) {
            $messageString = preg_replace(
                sprintf('/charset="%s"/', preg_quote($charset)),
                sprintf('charset="%s"', $overwriteCharset),
                $messageString
            );
        }

        $mailbox->addMessage($messageString);
    }

    final protected function getFixture($fixture)
    {
        return file_get_contents(sprintf('%s/fixtures/%s.eml', __DIR__, $fixture));
    }
}

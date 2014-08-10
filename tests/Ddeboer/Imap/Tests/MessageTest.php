<?php

namespace Ddeboer\Imap\Tests;

class MessageTest extends AbstractTest
{
    /**
     * @var \Ddeboer\Imap\Mailbox
     */
    protected $mailbox;

    public function setUp()
    {
        $this->mailbox = $this->createMailbox('test-message');
    }

    public function tearDown()
    {
        $this->deleteMailbox($this->mailbox);
    }

    public function testKeepUnseen()
    {
        $this->createTestMessage($this->mailbox, 'Message A');
        $this->createTestMessage($this->mailbox, 'Message B');
        $this->createTestMessage($this->mailbox, 'Message C');

        $message = $this->mailbox->getMessage(1);
        $this->assertFalse($message->isSeen());

        $message->getBodyText();
        $this->assertTrue($message->isSeen());

        $message = $this->mailbox->getMessage(2);
        $this->assertFalse($message->isSeen());

        $message->keepUnseen()->getBodyText();
        $this->assertFalse($message->isSeen());
    }

    public function testEncoding7Bit()
    {
        $this->createTestMessage($this->mailbox, 'lietuviškos raidės', 'lietuviškos raidės');

        $message = $this->mailbox->getMessage(1);
        $this->assertEquals('lietuviškos raidės', $message->getSubject());
        $this->assertEquals('lietuviškos raidės', $message->getBodyText());
    }

    public function testEncodingQuotedPrintable()
    {
        $boundary = 'Mailer=123';
        $raw = "Subject: ESPAÑA\r\n"
            . "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n"
            . "--$boundary\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n"
            . "Content-Type: text/html\r\n\tcharset=\"windows-1252\"\r\n"
            . "\r\n"
            . "<html><body>Espa=F1a</body></html>\r\n\r\n"
            . "--$boundary--\r\n\r\n";

        $this->mailbox->addMessage($raw);

        $message = $this->mailbox->getMessage(1);
        $this->assertEquals('ESPAÑA', $message->getSubject());
        $this->assertEquals("<html><body>España</body></html>\r\n", $message->getBodyHtml());
    }

    public function testBcc()
    {
        $raw = "Subject: Undisclosed recipients\r\n";
        $this->mailbox->addMessage($raw);

        $message = $this->mailbox->getMessage(1);

        $this->assertEquals('Undisclosed recipients', $message->getSubject());
        $this->assertCount(0, $message->getTo());
    }
}

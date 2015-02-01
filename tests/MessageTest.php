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
            . "Date: =?ISO-8859-2?Q?Fri,_13_Jun_2014_17:18:44_+020?= =?ISO-8859-2?Q?0_(St=F8edn=ED_Evropa_(letn=ED_=E8as))?=\r\n"
            . "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n"
            . "--$boundary\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n"
            . "Content-Type: text/html; charset=\"windows-1252\"\r\n"
            . "\r\n"
            . "<html><body>Espa=F1a</body></html>\r\n\r\n"
            . "--$boundary--\r\n\r\n";

        $this->mailbox->addMessage($raw);

        $message = $this->mailbox->getMessage(1);
        $this->assertEquals('ESPAÑA', $message->getSubject());
        $this->assertEquals("<html><body>España</body></html>\r\n", $message->getBodyHtml());
        $this->assertEquals(new \DateTime('2014-06-13 17:18:44+0200'), $message->getDate());
    }
    
    public function testEmailAddress()
    {
        $this->mailbox->addMessage($this->getFixture('email_address'));
        $message = $this->mailbox->getMessage(1);
        
        $from = $message->getFrom();
        $this->assertInstanceOf('\Ddeboer\Imap\Message\EmailAddress', $from);
        $this->assertEquals('no_host', $from->getMailbox());

        $cc = $message->getCc();
        $this->assertCount(2, $cc);
        $this->assertInstanceOf('\Ddeboer\Imap\Message\EmailAddress', $cc[0]);
        $this->assertEquals('This one is right', $cc[0]->getName());
        $this->assertEquals('ding@dong.com', $cc[0]->getAddress());
        
        $this->assertInstanceOf('\Ddeboer\Imap\Message\EmailAddress', $cc[1]);
        $this->assertEquals('No-address', $cc[1]->getMailbox());
    }

    public function testBcc()
    {
        $raw = "Subject: Undisclosed recipients\r\n";
        $this->mailbox->addMessage($raw);

        $message = $this->mailbox->getMessage(1);

        $this->assertEquals('Undisclosed recipients', $message->getSubject());
        $this->assertCount(0, $message->getTo());
    }
    
    public function testDelete()
    {
        $this->createTestMessage($this->mailbox, 'Message A');
        $this->createTestMessage($this->mailbox, 'Message B');
        $this->createTestMessage($this->mailbox, 'Message C');

        $message = $this->mailbox->getMessage(3);
        $message->delete();

        $this->assertCount(2, $this->mailbox);
        foreach ($this->mailbox->getMessages() as $message) {
            $this->assertNotEquals('Message C', $message->getSubject());
        }
    }

    /**
     * @dataProvider getAttachmentFixture
     */
    public function testGetAttachments()
    {
        $this->mailbox->addMessage(
            $this->getFixture('attachment_encoded_filename')
        );
        
        $message = $this->mailbox->getMessage(1);
        $this->assertCount(1, $message->getAttachments());
        $attachment = $message->getAttachments()[0];
        $this->assertEquals(
            'Prostřeno_2014_poslední volné termíny.xls',
            $attachment->getFilename()
        );
    }
    
    public function getAttachmentFixture()
    {
        return [
            [ 'attachment_no_disposition' ],
            [ 'attachment_encoded_filename' ]
        ];
    }
}

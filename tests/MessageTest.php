<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Exception\UnsupportedEncodingException;
use Zend\Mime\Mime;

/**
 * @covers \Ddeboer\Imap\Connection::expunge
 * @covers \Ddeboer\Imap\Mailbox::expunge
 * @covers \Ddeboer\Imap\Message
 * @covers \Ddeboer\Imap\Message\Transcoder
 * @covers \Ddeboer\Imap\Message\Attachment
 * @covers \Ddeboer\Imap\Message\EmailAddress
 * @covers \Ddeboer\Imap\Message\Headers
 * @covers \Ddeboer\Imap\Message\Part
 */
class MessageTest extends AbstractTest
{
    /**
     * @var \Ddeboer\Imap\Mailbox
     */
    protected $mailbox;

    protected function setUp()
    {
        $this->mailbox = $this->createMailbox();
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

    /**
     * @dataProvider provideCharsets
     */
    public function testBodyCharsets(string $charset = null, string $charList, string $encoding = null)
    {
        $subject = sprintf('[%s:%s]', $charset, $encoding);
        $this->createTestMessage(
            $this->mailbox,
            $subject,
            mb_convert_encoding($charList, $charset ?? 'ASCII', 'UTF-8'),
            $encoding,
            $charset
        );

        $message = $this->mailbox->getMessage(1);

        $this->assertSame($subject, $message->getSubject());
        $this->assertSame($charList, rtrim($message->getBodyText()));
    }

    public function provideCharsets(): array
    {
        $charsets = [
            'ASCII' => '! "#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~',
            'GB18030' => "　、。〃々〆〇〈〉《》「」『』【】〒〓〔〕〖〗〝〞〡〢〣〤〥〦〧〨〩〾一\u{200b}丁\u{200b}丂踰\u{200b}踱\u{200b}踲\u{200b}",
            'ISO-8859-6' => 'ءآأؤإئابةتثجحخدذرزسشصضطظعغـفقكلمنهوىي',
            'ISO-8859-7' => 'ΆΈΉΊ»Ό½ΎΏΐΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟ2ΠΡΣΤΥΦΧΨΩΪΫάέήίΰαβγδεζηθικλμνξοπρςστυφχψωϊϋόύώ',
            'SJIS' => '｡｢｣､･ｦｧｨｩｪｫｬｭｮｯBｰｱｲｳｴｵｶｷｸｹｺｻｼｽｾｿCﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏDﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜﾝﾞﾟ',
            'UTF-8' => '€✔',
            'Windows-1251' => 'ЂЃѓЉЊЌЋЏђљњќћџЎўЈҐЁЄЇІіґёєјЅѕїАБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыьэюя',
            'Windows-1252' => 'ƒŠŒŽšœžŸªºÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ',
        ];

        $provider = [];

        // This first data set mimics "us-ascii" imap server default settings
        $provider[] = [null, $charsets['ASCII'], null];

        $encodings = [
            Mime::ENCODING_7BIT,
            Mime::ENCODING_8BIT,
            Mime::ENCODING_QUOTEDPRINTABLE,
            Mime::ENCODING_BASE64,
        ];

        foreach ($charsets as $charset => $charList) {
            foreach ($encodings as $encoding) {
                $provider[] = [$charset, $charList, $encoding];
            }
        }

        return $provider;
    }

    public function testCharsetAlias()
    {
        $charset = 'ks_c_5601-1987';
        $charsetAlias = 'EUC-KR';
        $text = '사진';

        $this->createTestMessage(
            $this->mailbox,
            $charset,
            mb_convert_encoding($text, $charsetAlias, 'UTF-8'),
            null,
            $charsetAlias,
            $charset
        );

        $message = $this->mailbox->getMessage(1);

        $this->assertSame($text, rtrim($message->getBodyText()));
    }

    public function testUnsupportedCharset()
    {
        $charset = uniqid('NAN_CHARSET_');
        $this->createTestMessage(
            $this->mailbox,
            'Unsupported',
            null,
            null,
            $charset
        );

        $message = $this->mailbox->getMessage(1);

        $this->expectException(UnsupportedEncodingException::class);
        $this->expectExceptionMessageRegexp(sprintf('/%s/', preg_quote($charset)));

        $message->getBodyText();
    }

    public function testUndefinedContentCharset()
    {
        $this->mailbox->addMessage($this->getFixture('null_content_charset'));

        $message = $this->mailbox->getMessage(1);

        $this->assertSame('Hi!', rtrim($message->getBodyText()));
    }

    public function testSpecialCharsetOnHeaders()
    {
        $this->mailbox->addMessage($this->getFixture('ks_c_5601-1987_headers'));

        $message = $this->mailbox->getMessage(1);

        $this->assertEquals('RE: 회원님께 Ersi님이 메시지를 보냈습니다.', $message->getSubject());

        $from = $message->getFrom();
        $this->assertEquals('김 현진', $from->getName());
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
        $this->mailbox->expunge();

        $this->assertCount(2, $this->mailbox);
        foreach ($this->mailbox->getMessages() as $message) {
            $this->assertNotEquals('Message C', $message->getSubject());
        }
    }

    /**
     * @dataProvider getAttachmentFixture
     */
    public function testGetAttachments(string $fixture)
    {
        $this->mailbox->addMessage(
            $this->getFixture($fixture)
        );

        $message = $this->mailbox->getMessage(1);
        $this->assertCount(1, $message->getAttachments());
        $attachment = $message->getAttachments()[0];
        $this->assertEquals(
            'Prostřeno_2014_poslední volné termíny.xls',
            $attachment->getFilename()
        );
    }

    public function getAttachmentFixture(): array
    {
        return [
            ['attachment_no_disposition'],
            ['attachment_encoded_filename'],
        ];
    }
}

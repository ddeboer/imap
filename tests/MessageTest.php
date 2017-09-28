<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Exception\UnsupportedCharsetException;
use Ddeboer\Imap\Message\EmailAddress;
use Zend\Mime\Mime;

/**
 * @covers \Ddeboer\Imap\Connection::expunge
 * @covers \Ddeboer\Imap\Mailbox::expunge
 * @covers \Ddeboer\Imap\Message
 * @covers \Ddeboer\Imap\MessageIterator
 * @covers \Ddeboer\Imap\Message\Attachment
 * @covers \Ddeboer\Imap\Message\EmailAddress
 * @covers \Ddeboer\Imap\Message\Headers
 * @covers \Ddeboer\Imap\Message\Part
 * @covers \Ddeboer\Imap\Message\Transcoder
 * @covers \Ddeboer\Imap\Parameters
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

        $this->expectException(UnsupportedCharsetException::class);
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
        $this->assertInstanceOf(EmailAddress::class, $from);
        $this->assertEquals('no_host', $from->getMailbox());

        $cc = $message->getCc();
        $this->assertCount(2, $cc);
        $this->assertInstanceOf(EmailAddress::class, $cc[0]);
        $this->assertEquals('This one: is "right"', $cc[0]->getName());
        $this->assertEquals('dong.com', $cc[0]->getHostname());
        $this->assertEquals('ding@dong.com', $cc[0]->getAddress());
        $this->assertEquals('"This one: is \\"right\\"" <ding@dong.com>', $cc[0]->getFullAddress());

        $this->assertInstanceOf(EmailAddress::class, $cc[1]);
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

    /**
     * @dataProvider provideUndisclosedRecipientsCases
     */
    public function testUndiscloredRecipients(string $fixture)
    {
        $this->mailbox->addMessage($this->getFixture($fixture));

        $message = $this->mailbox->getMessage(1);

        $this->assertCount(1, $message->getTo());
    }

    public function provideUndisclosedRecipientsCases(): array
    {
        return [
            ['undisclosed-recipients/minus'],
            ['undisclosed-recipients/space'],
        ];
    }

    public function testAdditionalAddresses()
    {
        $this->mailbox->addMessage($this->getFixture('bcc'));

        $message = $this->mailbox->getMessage(1);

        $types = [
            'Bcc',
            'Reply-To',
            'Sender',
            // 'Return-Path', // Can't get Dovecot return the Return-Path
        ];
        foreach ($types as $type) {
            $method = 'get' . str_replace('-', '', $type);
            $emails = $message->{$method}();

            $this->assertCount(1, $emails, $type);

            $email = current($emails);

            $this->assertSame(sprintf('%s@here.com', strtolower($type)), $email->getAddress(), $type);
        }
    }

    /**
     * @dataProvider provideDateCases
     */
    public function testDates(string $output, string $dateRawHeader)
    {
        $template = $this->getFixture('date-template');
        $message = str_replace('%date_raw_header%', $dateRawHeader, $template);
        $this->mailbox->addMessage($message);

        $message = $this->mailbox->getMessage(1);
        $date = $message->getDate();

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertSame($output, $date->format(\DATE_ISO8601), sprintf('RAW: %s', $dateRawHeader));
    }

    /**
     * @see https://gist.github.com/mikesart/b33762363153e2b8c7c7
     */
    public function provideDateCases(): array
    {
        return [
            ['2017-09-28T09:24:01+0000', 'Thu, 28 Sep 2017 09:24:01 +0000 (UTC)'],
            ['2014-06-13T17:18:44+0200', '=?ISO-8859-2?Q?Fri,_13_Jun_2014_17:18:44_+020?=' . "\r\n" . ' =?ISO-8859-2?Q?0_(St=F8edn=ED_Evropa_(letn=ED_=E8as))?='],
            ['2008-02-13T02:15:46+0000', '13 Feb 08 02:15:46'],
            ['2008-04-03T12:36:15-0700', '03 Apr 2008 12:36:15 PDT'],
            ['2004-08-12T23:38:38-0700', 'Thu, 12 Aug 2004 11:38:38 PM -0700 (PDT)'],
            ['2006-01-04T21:47:28+0000', 'WED 04, JAN 2006 21:47:28'],
        ];
    }
}

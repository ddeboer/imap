<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Exception\InvalidDateHeaderException;
use Ddeboer\Imap\Exception\MessageDoesNotExistException;
use Ddeboer\Imap\Exception\OutOfBoundsException;
use Ddeboer\Imap\Exception\UnexpectedEncodingException;
use Ddeboer\Imap\Exception\UnsupportedCharsetException;
use Ddeboer\Imap\Message;
use Ddeboer\Imap\Message\EmailAddress;
use Ddeboer\Imap\Message\Parameters;
use Ddeboer\Imap\Message\PartInterface;
use Ddeboer\Imap\Message\Transcoder;
use Ddeboer\Imap\MessageInterface;
use Ddeboer\Imap\MessageIterator;
use Ddeboer\Imap\Search;
use PHPUnit\Framework\Error\Deprecated;
use ReflectionClass;
use Zend\Mail;
use Zend\Mime;

/**
 * @covers \Ddeboer\Imap\Connection::expunge
 * @covers \Ddeboer\Imap\Message
 * @covers \Ddeboer\Imap\MessageIterator
 * @covers \Ddeboer\Imap\Message\AbstractMessage
 * @covers \Ddeboer\Imap\Message\AbstractPart
 * @covers \Ddeboer\Imap\Message\Attachment
 * @covers \Ddeboer\Imap\Message\EmailAddress
 * @covers \Ddeboer\Imap\Message\Headers
 * @covers \Ddeboer\Imap\Message\Parameters
 * @covers \Ddeboer\Imap\Message\SimplePart
 * @covers \Ddeboer\Imap\Message\Transcoder
 */
final class MessageTest extends AbstractTest
{
    /**
     * @var \Ddeboer\Imap\MailboxInterface
     */
    private $mailbox;

    private static $encodings = [
        Mime\Mime::ENCODING_7BIT,
        Mime\Mime::ENCODING_8BIT,
        Mime\Mime::ENCODING_QUOTEDPRINTABLE,
        Mime\Mime::ENCODING_BASE64,
    ];

    private static $charsets = [
        'ASCII' => '! "#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~',
        'GB18030' => "　、。〃々〆〇〈〉《》「」『』【】〒〓〔〕〖〗〝〞〡〢〣〤〥〦〧〨〩〾一\u{200b}丁\u{200b}丂踰\u{200b}踱\u{200b}踲\u{200b}",
        'ISO-8859-6' => 'ءآأؤإئابةتثجحخدذرزسشصضطظعغـفقكلمنهوىي',
        'ISO-8859-7' => 'ΆΈΉΊ»Ό½ΎΏΐΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟ2ΠΡΣΤΥΦΧΨΩΪΫάέήίΰαβγδεζηθικλμνξοπρςστυφχψωϊϋόύώ',
        'SJIS' => '｡｢｣､･ｦｧｨｩｪｫｬｭｮｯBｰｱｲｳｴｵｶｷｸｹｺｻｼｽｾｿCﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏDﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜﾝﾞﾟ',
        'UTF-8' => '€✔',
        'Windows-1251' => 'ЂЃѓЉЊЌЋЏђљњќћџЎўЈҐЁЄЇІіґёєјЅѕїАБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыьэюя',
        'Windows-1252' => 'ƒŠŒŽšœžŸªºÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ',
    ];

    private static $iconvOnlyCharsets = [
        'macintosh' => '†°¢£§•¶ß®©™´¨≠ÆØ∞±≤≥¥µ∂∑∏π∫ªºΩæø¿¡¬√ƒ≈«»…ÀÃÕŒœ–—“”‘’÷◊ÿŸ⁄€‹›ﬁﬂ‡·‚„‰ÂÊÁËÈÍÎÏÌÓÔ',
        'Windows-1250' => 'ŚŤŹśťźˇ˘ŁĄŞŻ˛łąşĽ˝ľż',
    ];

    protected function setUp()
    {
        $this->mailbox = $this->createMailbox();
    }

    public function testCustomNonExistentMessageFetch()
    {
        $connection = $this->getConnection();
        $messageNumber = 98765;

        $message = new Message($connection->getResource(), $messageNumber);

        $this->expectException(MessageDoesNotExistException::class);
        $this->expectExceptionMessageRegExp(\sprintf('/%s/', \preg_quote((string) $messageNumber)));

        $message->hasAttachments();
    }

    public function testDeprecateMaskAsSeen()
    {
        $this->createTestMessage($this->mailbox, 'Message A');
        $message = $this->mailbox->getMessage(1);

        $this->expectException(Deprecated::class);

        $message->maskAsSeen();
    }

    public function testAlwaysKeepUnseen()
    {
        $this->createTestMessage($this->mailbox, 'Message A');

        $message = $this->mailbox->getMessage(1);
        $this->assertFalse($message->isSeen());

        $message->getBodyText();
        $this->assertFalse($message->isSeen());

        $message->markAsSeen();
        $this->assertTrue($message->isSeen());
    }

    public function testFlags()
    {
        $this->createTestMessage($this->mailbox, 'Message A');

        $message = $this->mailbox->getMessage(1);

        $this->assertSame('N', $message->isRecent());
        $this->assertFalse($message->isUnseen());
        $this->assertFalse($message->isFlagged());
        $this->assertFalse($message->isAnswered());
        $this->assertFalse($message->isDeleted());
        $this->assertFalse($message->isDraft());
        $this->assertFalse($message->isSeen());
    }

    public function testLowercaseCharsetAliases()
    {
        $refClass = new ReflectionClass(Transcoder::class);
        $properties = $refClass->getStaticProperties();
        $aliases = $properties['charsetAliases'];

        $keys = \array_map('strval', \array_keys($aliases));
        $loweredKeys = \array_map(function ($charset) {
            return \strtolower($charset);
        }, $keys);

        $this->assertSame($loweredKeys, $keys, 'Charset aliases key must be lowercase');

        $sameAliases = \array_filter($aliases, function ($value, $key) {
            return \strtolower((string) $value) === \strtolower((string) $key);
        }, \ARRAY_FILTER_USE_BOTH);

        $this->assertSame([], $sameAliases, 'There must not be self-referencing aliases');

        foreach ($aliases as $finalAlias) {
            $this->assertArrayNotHasKey($finalAlias, $aliases, 'All aliases must refer to final alias');
        }

        $sortedKeys = $keys;
        \sort($sortedKeys, \SORT_STRING);

        $this->assertSame($sortedKeys, $keys, 'Aliases must be sorted');
    }

    /**
     * @dataProvider provideCharsets
     */
    public function testBodyCharsets(string $charset = null, string $charList, string $encoding = null)
    {
        $subject = \sprintf('[%s:%s]', $charset, $encoding);
        $this->createTestMessage(
            $this->mailbox,
            $subject,
            \mb_convert_encoding($charList, $charset ?? 'ASCII', 'UTF-8'),
            $encoding,
            $charset
        );

        $message = $this->mailbox->getMessage(1);

        $this->assertSame($subject, $message->getSubject());
        $this->assertSame($charList, \rtrim($message->getBodyText()));
    }

    public function provideCharsets(): array
    {
        $provider = [];

        // This first data set mimics "us-ascii" imap server default settings
        $provider[] = [null, self::$charsets['ASCII'], null];
        foreach (self::$charsets as $charset => $charList) {
            foreach (self::$encodings as $encoding) {
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
            \mb_convert_encoding($text, $charsetAlias, 'UTF-8'),
            null,
            $charsetAlias,
            $charset
        );

        $message = $this->mailbox->getMessage(1);

        $this->assertSame($text, \rtrim($message->getBodyText()));
    }

    public function testMicrosoftCharsetAlias()
    {
        $charset = '134';
        $charsetAlias = 'GB2312';
        $text = '电佛';

        $this->createTestMessage(
            $this->mailbox,
            $charset,
            \mb_convert_encoding($text, $charsetAlias, 'UTF-8'),
            null,
            $charsetAlias,
            $charset
        );

        $message = $this->mailbox->getMessage(1);

        $this->assertSame($text, \rtrim($message->getBodyText()));
    }

    public function testUnsupportedCharset()
    {
        $charset = \uniqid('NAN_CHARSET_');
        $this->createTestMessage(
            $this->mailbox,
            'Unsupported',
            null,
            null,
            $charset
        );

        $message = $this->mailbox->getMessage(1);

        $this->expectException(UnsupportedCharsetException::class);
        $this->expectExceptionMessageRegExp(\sprintf('/%s/', \preg_quote($charset)));

        $message->getBodyText();
    }

    public function testUndefinedContentCharset()
    {
        $this->mailbox->addMessage($this->getFixture('null_content_charset'));

        $message = $this->mailbox->getMessage(1);

        $this->assertSame('Hi!', \rtrim($message->getBodyText()));
    }

    public function testSpecialCharsetOnHeaders()
    {
        $this->mailbox->addMessage($this->getFixture('ks_c_5601-1987_headers'));

        $message = $this->mailbox->getMessage(1);

        $this->assertSame('RE: 회원님께 Ersi님이 메시지를 보냈습니다.', $message->getSubject());

        $from = $message->getFrom();
        $this->assertSame('김 현진', $from->getName());
    }

    /**
     * @dataProvider provideIconvCharsets
     */
    public function testIconvFallback(string $charset, string $charList, string $encoding)
    {
        $subject = \sprintf('[%s:%s]', $charset, $encoding);
        $this->createTestMessage(
            $this->mailbox,
            $subject,
            \iconv('UTF-8', $charset, $charList),
            $encoding,
            $charset
        );

        $message = $this->mailbox->getMessage(1);

        $this->assertSame($subject, $message->getSubject());
        $this->assertSame($charList, \rtrim($message->getBodyText()));
    }

    public function provideIconvCharsets(): array
    {
        $provider = [];
        foreach (self::$iconvOnlyCharsets as $charset => $charList) {
            foreach (self::$encodings as $encoding) {
                $provider[] = [$charset, $charList, $encoding];
            }
        }

        return $provider;
    }

    public function testEmailAddress()
    {
        $this->mailbox->addMessage($this->getFixture('email_address'));
        $message = $this->mailbox->getMessage(1);

        $this->assertSame('<123@example.com>', $message->getId());
        $this->assertGreaterThan(0, $message->getNumber());
        $this->assertGreaterThan(0, $message->getSize());
        $this->assertGreaterThan(0, $message->getBytes());
        $this->assertInstanceOf(Parameters::class, $message->getParameters());
        $this->assertNull($message->getLines());
        $this->assertNull($message->getDisposition());
        $this->assertNotEmpty($message->getStructure());

        $from = $message->getFrom();
        $this->assertInstanceOf(EmailAddress::class, $from);
        $this->assertSame('no_host', $from->getMailbox());

        $cc = $message->getCc();
        $this->assertCount(2, $cc);
        $this->assertInstanceOf(EmailAddress::class, $cc[0]);
        $this->assertSame('This one: is "right"', $cc[0]->getName());
        $this->assertSame('dong.com', $cc[0]->getHostname());
        $this->assertSame('ding@dong.com', $cc[0]->getAddress());
        $this->assertSame('"This one: is \\"right\\"" <ding@dong.com>', $cc[0]->getFullAddress());

        $this->assertInstanceOf(EmailAddress::class, $cc[1]);
        $this->assertSame('No-address', $cc[1]->getMailbox());

        $this->assertCount(0, $message->getReturnPath());

        $this->assertFalse($message->isSeen());
    }

    public function testBcc()
    {
        $raw = "Subject: Undisclosed recipients\r\n";
        $this->mailbox->addMessage($raw);

        $message = $this->mailbox->getMessage(1);

        $this->assertSame('Undisclosed recipients', $message->getSubject());
        $this->assertCount(0, $message->getTo());
    }

    public function testDelete()
    {
        $this->createTestMessage($this->mailbox, 'Message A');
        $this->createTestMessage($this->mailbox, 'Message B');
        $this->createTestMessage($this->mailbox, 'Message C');

        $message = $this->mailbox->getMessage(3);
        $message->delete();
        $this->getConnection()->expunge();

        $this->assertCount(2, $this->mailbox);
        foreach ($this->mailbox->getMessages() as $message) {
            $this->assertNotSame('Message C', $message->getSubject());
        }
    }

    public function testUndelete()
    {
        $this->createTestMessage($this->mailbox, 'Message A');
        $this->createTestMessage($this->mailbox, 'Message B');
        $this->createTestMessage($this->mailbox, 'Message C');

        $message = $this->mailbox->getMessage(3);
        $message->delete();
        $message->undelete();
        $this->assertFalse($message->isDeleted());
        $this->getConnection()->expunge();

        $this->assertCount(3, $this->mailbox);
        $this->assertSame('Message A', $this->mailbox->getMessage(1)->getSubject());
        $this->assertSame('Message B', $this->mailbox->getMessage(2)->getSubject());
        $this->assertSame('Message C', $this->mailbox->getMessage(3)->getSubject());
    }

    public function testMove()
    {
        $mailboxOne = $this->createMailbox();
        $mailboxTwo = $this->createMailbox();
        $this->createTestMessage($mailboxOne, 'Message A');

        $this->assertCount(1, $mailboxOne);
        $this->assertCount(0, $mailboxTwo);

        $message = $mailboxOne->getMessage(1);
        $message->move($mailboxTwo);
        $this->getConnection()->expunge();

        $this->assertCount(0, $mailboxOne);
        $this->assertCount(1, $mailboxTwo);
    }

    public function testResourceMemoryReuse()
    {
        $mailboxOne = $this->createMailbox();
        $this->createTestMessage($mailboxOne, 'Message A');
        $mailboxTwo = $this->createMailbox();

        $message = $mailboxOne->getMessage(1);

        // Mailbox::count triggers Mailbox::init
        // Reinitializing the imap resource to the mailbox 2
        $this->assertCount(0, $mailboxTwo);

        $message->move($mailboxTwo);
        $this->getConnection()->expunge();

        $this->assertCount(0, $mailboxOne);
        $this->assertCount(1, $mailboxTwo);
    }

    public function testCopy()
    {
        $mailboxOne = $this->createMailbox();
        $mailboxTwo = $this->createMailbox();
        $this->createTestMessage($mailboxOne, 'Message A');

        $this->assertCount(1, $mailboxOne);
        $this->assertCount(0, $mailboxTwo);

        $message = $mailboxOne->getMessage(1);
        $message->copy($mailboxTwo);

        $this->assertCount(1, $mailboxOne);
        $this->assertCount(1, $mailboxTwo);

        $this->assertFalse($message->isSeen());
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
        $this->assertTrue($message->hasAttachments());
        $this->assertCount(1, $message->getAttachments());
        $attachment = $message->getAttachments()[0];

        $this->assertSame('application', \strtolower($attachment->getType()));
        $this->assertSame('vnd.ms-excel', \strtolower($attachment->getSubtype()));
        $this->assertSame(
            'Prostřeno_2014_poslední volné termíny.xls',
            $attachment->getFilename()
        );
        $this->assertNull($attachment->getSize());

        $this->assertFalse($message->isSeen());
    }

    public function getAttachmentFixture(): array
    {
        return [
            ['attachment_no_disposition'],
            ['attachment_encoded_filename'],
        ];
    }

    public function testAttachmentLongFilename()
    {
        $this->mailbox->addMessage($this->getFixture('attachment_long_filename'));

        $message = $this->mailbox->getMessage(1);
        $this->assertTrue($message->hasAttachments());
        $this->assertCount(3, $message->getAttachments());

        $actual = [];
        foreach ($message->getAttachments() as $attachment) {
            $parameters = $attachment->getParameters();

            $actual[] = [
                'filename' => $parameters->get('filename'),
                'name' => $parameters->get('name'),
            ];
        }

        $expected = [
            [
                'filename' => 'Buchungsbestätigung- Rechnung-Geschäftsbedingungen-Nr.B123-45 - XXXXX xxxxxxxxxxxxxxxxx XxxX, Lüxxxxxxxxxx - VM Klaus XXXXXX - xxxxxxxx.pdf',
                'name' => 'Buchungsbestätigung- Rechnung-Geschäftsbedingungen-Nr.B123-45 - XXXX xxxxxxxxxxxxxxxxx XxxX, Lüdxxxxxxxx - VM Klaus XXXXXX - xxxxxxxx.pdf',
            ],
            [
                'filename' => '01_A€àä????@Z-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz.txt',
                'name' => '01_A€àäąбيد@Z-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz.txt',
            ],
            [
                'filename' => '02_A€àäąбيد@Z-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz.txt',
                'name' => '02_A€àäąбيد@Z-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz.txt',
            ],
        ];

        $this->assertSame($expected, $actual);
    }

    public function testPlainTextAttachment()
    {
        $this->mailbox->addMessage($this->getFixture('plain_text_attachment'));

        $message = $this->mailbox->getMessage(1);

        $this->assertSame('Test', $message->getBodyText());
        $this->assertNull($message->getBodyHtml());

        $this->assertTrue($message->hasAttachments());

        $attachments = $message->getAttachments();
        $this->assertCount(1, $attachments);

        $attachment = \current($attachments);

        $this->assertSame('Hi!', $attachment->getDecodedContent());
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
            $method = 'get' . \str_replace('-', '', $type);
            $emails = $message->{$method}();

            $this->assertCount(1, $emails, $type);

            $email = \current($emails);

            $this->assertSame(\sprintf('%s@here.com', \strtolower($type)), $email->getAddress(), $type);
        }
    }

    /**
     * @dataProvider provideDateCases
     */
    public function testDates(string $output, string $dateRawHeader)
    {
        $template = $this->getFixture('date-template');
        $message = \str_replace('%date_raw_header%', $dateRawHeader, $template);
        $this->mailbox->addMessage($message);

        $message = $this->mailbox->getMessage(1);
        $date = $message->getDate();

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertSame($output, $date->format(\DATE_ISO8601), \sprintf('RAW: %s', $dateRawHeader));
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
            ['2018-01-04T06:44:23+0400', 'Thur, 04 Jan 2018 06:44:23 +0400'],
            ['2007-04-06T12:37:39+0000', 'Fri Apr 06 12:37:39 2007'],
            ['2008-02-12T06:35:05+0000', '12 Feb 2008 06:35:05 UT +0000'],
            ['2010-07-09T21:40:33+0000', 'Fri, 9 Jul 2010 21:40:33 UT'],
        ];
    }

    public function testInvalidDate()
    {
        $template = $this->getFixture('date-template');
        $message = \str_replace('%date_raw_header%', 'Fri!', $template);
        $this->mailbox->addMessage($message);

        $message = $this->mailbox->getMessage(1);
        $this->expectException(InvalidDateHeaderException::class);

        $message->getDate();
    }

    public function testRawHeaders()
    {
        $headers = 'From: from@there.com' . "\r\n"
            . 'To: to@here.com' . "\n"
             . "\r\n"
        ;
        $originalMessage = $headers . 'Content' . "\n";

        $this->mailbox->addMessage($originalMessage);
        $message = $this->mailbox->getMessage(1);

        $expectedHeaders = \preg_split('/\R/u', $headers);
        $expectedHeaders = \implode("\r\n", $expectedHeaders);

        $this->assertSame($expectedHeaders, $message->getRawHeaders());

        $this->assertFalse($message->isSeen());
    }

    /**
     * @see https://github.com/ddeboer/imap/issues/200
     */
    public function testGetAllHeaders()
    {
        $this->mailbox->addMessage($this->getFixture('bcc'));

        $message = $this->mailbox->getMessage(1);
        $headers = $message->getHeaders();

        $this->assertGreaterThan(9, \count($headers));

        $this->assertArrayHasKey('from', $headers);
        $this->assertArrayHasKey('date', $headers);
        $this->assertArrayHasKey('recent', $headers);

        $this->assertSame('Wed, 27 Sep 2017 12:48:51 +0200', $headers['date']);
        $this->assertSame('A_€@{è_Z', $headers['bcc'][0]->personal);

        $this->assertFalse($message->isSeen());
    }

    public function testSetFlags()
    {
        $this->createTestMessage($this->mailbox, 'Message A');

        $message = $this->mailbox->getMessage(1);

        $this->assertFalse($message->isFlagged());

        $message->setFlag('\\Flagged');

        $this->assertTrue($message->isFlagged());

        $message->clearFlag('\\Flagged');

        $this->assertFalse($message->isFlagged());

        $message->setFlag('\\Seen');
        $this->assertSame('R', $message->isRecent());
        $this->assertTrue($message->isSeen());
    }

    /**
     * @see https://github.com/ddeboer/imap/pull/143
     */
    public function testUnstructuredMessage()
    {
        $this->markTestIncomplete('Missing test case that gets imap_fetchstructure() to return false;');
    }

    public function testPlainOnlyMessage()
    {
        $this->mailbox->addMessage($this->getFixture('plain_only'));

        $message = $this->mailbox->getMessage(1);

        $this->assertSame('Hi', \rtrim($message->getBodyText()));
        $this->assertNull($message->getBodyHtml());
    }

    public function testHtmlOnlyMessage()
    {
        $this->mailbox->addMessage($this->getFixture('html_only'));

        $message = $this->mailbox->getMessage(1);

        $this->assertSame('<html><body>Hi</body></html>', \rtrim($message->getBodyHtml()));
        $this->assertNull($message->getBodyText());
    }

    public function testSimpleMultipart()
    {
        $this->mailbox->addMessage($this->getFixture('simple_multipart'));

        $message = $this->mailbox->getMessage(1);

        $this->assertSame('MyPlain', \rtrim($message->getBodyText()));
        $this->assertSame('MyHtml', \rtrim($message->getBodyHtml()));

        $parts = [];
        foreach ($message as $key => $part) {
            $parts[$key] = $part;
        }

        $this->assertCount(2, $parts);

        $this->assertFalse($message->isSeen());
    }

    public function testGetRawMessage()
    {
        $fixture = $this->getFixture('structured_with_attachment');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(1);

        $this->assertSame($fixture, $message->getRawMessage());
    }

    public function testAttachmentOnlyEmail()
    {
        $fixture = $this->getFixture('mail_that_is_attachment');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(1);

        $this->assertCount(1, $message->getAttachments());
    }

    /**
     * @see https://github.com/ddeboer/imap/issues/142
     */
    public function testIssue142()
    {
        $fixture = $this->getFixture('issue_142');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(1);

        $this->assertCount(1, $message->getAttachments());
    }

    public function testSort()
    {
        $this->createTestMessage($this->mailbox, 'B');
        $this->createTestMessage($this->mailbox, 'A');
        $this->createTestMessage($this->mailbox, 'C');

        $concatSubjects = function (MessageIterator $it) {
            $subject = '';
            foreach ($it as $message) {
                $subject .= $message->getSubject();
            }

            return $subject;
        };

        $this->assertSame('BAC', $concatSubjects($this->mailbox->getMessages()));
        $this->assertSame('ABC', $concatSubjects($this->mailbox->getMessages(null, \SORTSUBJECT)));
        $this->assertSame('CBA', $concatSubjects($this->mailbox->getMessages(null, \SORTSUBJECT, true)));
        $this->assertSame('B', $concatSubjects($this->mailbox->getMessages(new Search\Text\Subject('B'), \SORTSUBJECT, true)));
    }

    public function testSignedMessage()
    {
        $fixture = $this->getFixture('pec');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(1);
        $attachments = $message->getAttachments();

        $this->assertCount(3, $attachments);

        $expected = [
            'data.xml' => 'PHhtbC8+',
            'postacert.eml' => 'test-content',
            'smime.p7s' => 'MQ==',
        ];

        foreach ($attachments as $attachment) {
            $expectedContains = $expected[$attachment->getFilename()];
            $this->assertContains($expectedContains, \rtrim($attachment->getContent()), \sprintf('Attachment filename: %s', $attachment->getFilename()));
        }
    }

    public function testSimpleMessageWithoutCharset()
    {
        $this->mailbox->addMessage($this->getFixture('without_charset_plain_only'));

        $message = $this->mailbox->getMessage(1);

        $this->assertSame('Hi', \rtrim($message->getBodyText()));
    }

    public function testMultipartMessageWithoutCharset()
    {
        $this->mailbox->addMessage($this->getFixture('without_charset_simple_multipart'));

        $message = $this->mailbox->getMessage(1);

        $this->assertSame('MyPlain', \rtrim($message->getBodyText()));
        $this->assertSame('MyHtml', \rtrim($message->getBodyHtml()));
    }

    public function testGetInReplyTo()
    {
        $fixture = $this->getFixture('references');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(1);

        $this->assertCount(1, $message->getInReplyTo());
        $this->assertContains('<b9e87bd5e661a645ed6e3b832828fcc5@example.com>', $message->getInReplyTo());

        $fixture = $this->getFixture('plain_only');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(2);

        $this->assertCount(0, $message->getInReplyTo());
    }

    public function testGetReferences()
    {
        $fixture = $this->getFixture('references');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(1);

        $this->assertCount(2, $message->getReferences());
        $this->assertContains('<08F04024-A5B3-4FDE-BF2C-6710DE97D8D9@example.com>', $message->getReferences());

        $fixture = $this->getFixture('plain_only');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(2);

        $this->assertCount(0, $message->getReferences());
    }

    public function testInlineAttachment()
    {
        $this->mailbox->addMessage($this->getFixture('inline_attachment'));
        $message = $this->mailbox->getMessage(1);

        $inline = $message->getAttachments()[0];

        $this->assertNull($inline->getFilename());
    }

    public function testMissingFromHeader()
    {
        $this->mailbox->addMessage($this->getFixture('missing_from'));
        $message = $this->mailbox->getMessage(1);

        $this->assertNull($message->getFrom());
    }

    public function testMissingDateHeader()
    {
        $this->mailbox->addMessage($this->getFixture('missing_date'));
        $message = $this->mailbox->getMessage(1);

        $this->assertNull($message->getDate());
    }

    public function testAttachmentMustNotBeCharsetDecoded()
    {
        $parts = [];
        foreach (self::$charsets as $charset => $charList) {
            $part = new Mime\Part(\mb_convert_encoding($charList, $charset, 'UTF-8'));
            $part->setType('text/xml');
            $part->setEncoding(Mime\Mime::ENCODING_BASE64);
            $part->setCharset($charset);
            $part->setDisposition(Mime\Mime::DISPOSITION_ATTACHMENT);
            $part->setFileName(\sprintf('%s.xml', $charset));
            $parts[] = $part;
        }

        $mimeMessage = new Mime\Message();
        $mimeMessage->setParts($parts);

        $message = new Mail\Message();
        $message->addFrom('from@here.com');
        $message->addTo('to@there.com');
        $message->setSubject('Charsets');
        $message->setBody($mimeMessage);

        $messageString = $message->toString();
        $messageString = \preg_replace('/; charset=.+/', '', $messageString);

        $this->mailbox->addMessage($messageString);

        $message = $this->mailbox->getMessage(1);

        $this->resetAttachmentCharset($message);
        $this->assertTrue($message->hasAttachments());
        $attachments = $message->getAttachments();
        $this->assertCount(\count(self::$charsets), $attachments);

        foreach ($attachments as $attachment) {
            $charset = \str_replace('.xml', '', $attachment->getFilename());
            $this->assertSame(\mb_convert_encoding(self::$charsets[$charset], $charset, 'UTF-8'), $attachment->getDecodedContent());
        }
    }

    public function testNoMessageId()
    {
        $this->mailbox->addMessage($this->getFixture('plain_only'));

        $message = $this->mailbox->getMessage(1);

        $this->assertNull($message->getId());
    }

    public function testUnknownEncodingIsManageable()
    {
        $this->mailbox->addMessage($this->getFixture('unknown_encoding'));

        $message = $this->mailbox->getMessage(1);

        $parts = [];
        foreach ($message->getParts() as $part) {
            $parts[$part->getSubtype()] = $part;
        }

        $this->assertArrayHasKey(PartInterface::SUBTYPE_PLAIN, $parts);

        $plain = $parts[PartInterface::SUBTYPE_PLAIN];

        $this->assertSame(PartInterface::ENCODING_UNKNOWN, $plain->getEncoding());

        $this->expectException(UnexpectedEncodingException::class);

        $plain->getDecodedContent();
    }

    public function testMultipleAttachments()
    {
        $this->mailbox->addMessage($this->getFixture('multiple_nested_attachments'));

        $message = $this->mailbox->getMessage(1);

        $this->assertCount(2, $message->getAttachments());
    }

    public function testMixedInlineDisposition()
    {
        $this->mailbox->addMessage($this->getFixture('mixed_filename'));

        $message = $this->mailbox->getMessage(1);

        $attachments = $message->getAttachments();
        $this->assertCount(1, $attachments);

        $attachment = \current($attachments);
        $this->assertSame('Price4VladDaKar.xlsx', $attachment->getFilename());
    }

    public function test_nestes_embedded_with_attachment()
    {
        $this->mailbox->addMessage($this->getFixture('nestes_embedded_with_attachment'));

        $message = $this->mailbox->getMessage(1);

        $expected = [
            'first.eml' => 'Subject: FIRST',
            'chrome.png' => 'ZFM4jELaoSdLtElJrUj1xxP6zwzfqSU4i0HYnydMtUlIqUfywxb60AxZqEXaoifgMCXptR9MtklH',
            'second.eml' => 'Subject: SECOND',
        ];
        $attachments = $message->getAttachments();
        $this->assertCount(3, $attachments);
        foreach ($attachments as $attachment) {
            $this->assertContains($expected[$attachment->getFilename()], $attachment->getContent());
        }
    }

    public function test_imap_mime_header_decode_returns_false()
    {
        $this->mailbox->addMessage($this->getFixture('imap_mime_header_decode_returns_false'));

        $message = $this->mailbox->getMessage(1);

        $this->assertSame('=?UTF-8?B?nnDusSNdG92w6Fuw61fMjAxOF8wMy0xMzMyNTMzMTkzLnBkZg==?=', $message->getSubject());
    }

    private function resetAttachmentCharset(MessageInterface $message)
    {
        // Mimic GMAIL behaviour that correctly doesn't report charset
        // of attachments that don't have it
        $refMessage = new \ReflectionClass($message);
        $refAbstractMessage = $refMessage->getParentClass();
        $refAbstractPart = $refAbstractMessage->getParentClass();

        $refLazyLoadStructure = $refMessage->getMethod('lazyLoadStructure');
        $refLazyLoadStructure->setAccessible(true);
        $refLazyLoadStructure->invoke($message);
        $refLazyLoadStructure->setAccessible(false);

        $refParts = $refAbstractPart->getProperty('parts');
        $refParts->setAccessible(true);
        $refParts->setValue($message, []);
        $refParts->setAccessible(false);

        $refStructure = $refAbstractPart->getProperty('structure');
        $refStructure->setAccessible(true);
        $structure = $refStructure->getValue($message);
        foreach ($structure->parts as $partIndex => $part) {
            if ($part->ifdisposition && 'attachment' === $part->disposition) {
                foreach ($part->parameters as $parameterIndex => $parameter) {
                    if ('charset' === $parameter->attribute) {
                        unset($structure->parts[$partIndex]->parameters[$parameterIndex]);
                    }
                }
                if (0 === \count($part->parameters)) {
                    $part->ifparameters = 0;
                }
            }
        }
        $refStructure->setValue($message, $structure);
        $refStructure->setAccessible(false);

        $refParseStructure = $refAbstractPart->getMethod('lazyParseStructure');
        $refParseStructure->setAccessible(true);
        $refParseStructure->invoke($message);
        $refParseStructure->setAccessible(false);
    }

    public function testEmptyMessageIterator()
    {
        $mailbox = $this->createMailbox();

        $messages = $mailbox->getMessages();
        $this->assertCount(0, $messages);
        $this->assertFalse(\current($messages));

        $this->expectException(OutOfBoundsException::class);

        $messages->current();
    }
}

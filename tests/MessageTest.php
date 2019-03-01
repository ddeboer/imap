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
use PHPUnit\Framework\Error\Deprecated;
use ReflectionClass;
use Zend\Mail;
use Zend\Mime;

/**
 * @covers \Ddeboer\Imap\Connection::expunge
 * @covers \Ddeboer\Imap\Message
 * @covers \Ddeboer\Imap\Message\AbstractMessage
 * @covers \Ddeboer\Imap\Message\AbstractPart
 * @covers \Ddeboer\Imap\Message\Attachment
 * @covers \Ddeboer\Imap\Message\EmailAddress
 * @covers \Ddeboer\Imap\Message\Headers
 * @covers \Ddeboer\Imap\Message\Parameters
 * @covers \Ddeboer\Imap\Message\SimplePart
 * @covers \Ddeboer\Imap\Message\Transcoder
 * @covers \Ddeboer\Imap\MessageIterator
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
        'ASCII'        => '! "#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~',
        'GB18030'      => "　、。〃々〆〇〈〉《》「」『』【】〒〓〔〕〖〗〝〞〡〢〣〤〥〦〧〨〩〾一\u{200b}丁\u{200b}丂踰\u{200b}踱\u{200b}踲\u{200b}",
        'ISO-8859-6'   => 'ءآأؤإئابةتثجحخدذرزسشصضطظعغـفقكلمنهوىي',
        'ISO-8859-7'   => 'ΆΈΉΊ»Ό½ΎΏΐΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟ2ΠΡΣΤΥΦΧΨΩΪΫάέήίΰαβγδεζηθικλμνξοπρςστυφχψωϊϋόύώ',
        'SJIS'         => '｡｢｣､･ｦｧｨｩｪｫｬｭｮｯBｰｱｲｳｴｵｶｷｸｹｺｻｼｽｾｿCﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏDﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜﾝﾞﾟ',
        'UTF-8'        => '€✔',
        'Windows-1251' => 'ЂЃѓЉЊЌЋЏђљњќћџЎўЈҐЁЄЇІіґёєјЅѕїАБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыьэюя',
        'Windows-1252' => 'ƒŠŒŽšœžŸªºÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ',
    ];

    private static $iconvOnlyCharsets = [
        'macintosh'    => '†°¢£§•¶ß®©™´¨≠ÆØ∞±≤≥¥µ∂∑∏π∫ªºΩæø¿¡¬√ƒ≈«»…ÀÃÕŒœ–—“”‘’÷◊ÿŸ⁄€‹›ﬁﬂ‡·‚„‰ÂÊÁËÈÍÎÏÌÓÔ',
        'Windows-1250' => 'ŚŤŹśťźˇ˘ŁĄŞŻ˛łąşĽ˝ľż',
    ];

    protected function setUp()
    {
        $this->mailbox = $this->createMailbox();
    }

    public function testCustomNonExistentMessageFetch()
    {
        $connection    = $this->getConnection();
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
        static::assertFalse($message->isSeen());

        $message->getBodyText();
        static::assertFalse($message->isSeen());

        $message->markAsSeen();
        static::assertTrue($message->isSeen());
    }

    public function testFlags()
    {
        $this->createTestMessage($this->mailbox, 'Message A');

        $message = $this->mailbox->getMessage(1);

        static::assertSame('N', $message->isRecent());
        static::assertFalse($message->isUnseen());
        static::assertFalse($message->isFlagged());
        static::assertFalse($message->isAnswered());
        static::assertFalse($message->isDeleted());
        static::assertFalse($message->isDraft());
        static::assertFalse($message->isSeen());
    }

    public function testLowercaseCharsetAliases()
    {
        $refClass   = new ReflectionClass(Transcoder::class);
        $properties = $refClass->getStaticProperties();
        /** @var array $aliases */
        $aliases = $properties['charsetAliases'];

        $keys        = \array_map('strval', \array_keys($aliases));
        $loweredKeys = \array_map(static function ($charset) {
            return \strtolower($charset);
        }, $keys);

        static::assertSame($loweredKeys, $keys, 'Charset aliases key must be lowercase');

        $sameAliases = \array_filter($aliases, static function ($value, $key) {
            return \strtolower((string) $value) === \strtolower((string) $key);
        }, \ARRAY_FILTER_USE_BOTH);

        static::assertSame([], $sameAliases, 'There must not be self-referencing aliases');

        foreach ($aliases as $finalAlias) {
            static::assertArrayNotHasKey($finalAlias, $aliases, 'All aliases must refer to final alias');
        }

        $sortedKeys = $keys;
        \sort($sortedKeys, \SORT_STRING);

        static::assertSame($sortedKeys, $keys, 'Aliases must be sorted');
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

        static::assertSame($subject, $message->getSubject());
        static::assertSame($charList, $message->getBodyText());
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
        $charset      = 'ks_c_5601-1987';
        $charsetAlias = 'EUC-KR';
        $text         = '사진';

        $this->createTestMessage(
            $this->mailbox,
            $charset,
            \mb_convert_encoding($text, $charsetAlias, 'UTF-8'),
            null,
            $charsetAlias,
            $charset
        );

        $message = $this->mailbox->getMessage(1);

        static::assertSame($text, $message->getBodyText());
    }

    public function testMicrosoftCharsetAlias()
    {
        $charset      = '134';
        $charsetAlias = 'GB2312';
        $text         = '电佛';

        $this->createTestMessage(
            $this->mailbox,
            $charset,
            \mb_convert_encoding($text, $charsetAlias, 'UTF-8'),
            null,
            $charsetAlias,
            $charset
        );

        $message = $this->mailbox->getMessage(1);

        static::assertSame($text, $message->getBodyText());
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

        static::assertSame('Hi!', $message->getBodyText());
    }

    public function testSpecialCharsetOnHeaders()
    {
        $this->mailbox->addMessage($this->getFixture('ks_c_5601-1987_headers'));

        $message = $this->mailbox->getMessage(1);

        static::assertSame('RE: 회원님께 Ersi님이 메시지를 보냈습니다.', $message->getSubject());

        $from = $message->getFrom();
        static::assertNotNull($from);
        static::assertSame('김 현진', $from->getName());
    }

    /**
     * @dataProvider provideIconvCharsets
     */
    public function testIconvFallback(string $charset, string $charList, string $encoding)
    {
        $subject  = \sprintf('[%s:%s]', $charset, $encoding);
        $contents = \iconv('UTF-8', $charset, $charList);

        static::assertIsString($contents);

        $this->createTestMessage(
            $this->mailbox,
            $subject,
            $contents,
            $encoding,
            $charset
        );

        $message = $this->mailbox->getMessage(1);

        static::assertSame($subject, $message->getSubject());
        static::assertSame($charList, $message->getBodyText());
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

        static::assertSame('<123@example.com>', $message->getId());
        static::assertGreaterThan(0, $message->getNumber());
        static::assertGreaterThan(0, $message->getSize());
        static::assertGreaterThan(0, $message->getBytes());
        static::assertInstanceOf(Parameters::class, $message->getParameters());
        static::assertNull($message->getLines());
        static::assertNull($message->getDisposition());
        static::assertNotEmpty($message->getStructure());

        $from = $message->getFrom();
        static::assertInstanceOf(EmailAddress::class, $from);
        static::assertSame('no_host', $from->getMailbox());

        $cc = $message->getCc();
        static::assertCount(2, $cc);
        static::assertInstanceOf(EmailAddress::class, $cc[0]);
        static::assertSame('This one: is "right"', $cc[0]->getName());
        static::assertSame('dong.com', $cc[0]->getHostname());
        static::assertSame('ding@dong.com', $cc[0]->getAddress());
        static::assertSame('"This one: is \\"right\\"" <ding@dong.com>', $cc[0]->getFullAddress());

        static::assertInstanceOf(EmailAddress::class, $cc[1]);
        static::assertSame('No-address', $cc[1]->getMailbox());

        static::assertCount(0, $message->getReturnPath());

        static::assertFalse($message->isSeen());
    }

    public function testBcc()
    {
        $raw = "Subject: Undisclosed recipients\r\n";
        $this->mailbox->addMessage($raw);

        $message = $this->mailbox->getMessage(1);

        static::assertSame('Undisclosed recipients', $message->getSubject());
        static::assertCount(0, $message->getTo());
    }

    public function testDelete()
    {
        $this->createTestMessage($this->mailbox, 'Message A');
        $this->createTestMessage($this->mailbox, 'Message B');
        $this->createTestMessage($this->mailbox, 'Message C');

        $message = $this->mailbox->getMessage(3);
        $message->delete();
        $this->getConnection()->expunge();

        static::assertCount(2, $this->mailbox);
        foreach ($this->mailbox->getMessages() as $message) {
            static::assertNotSame('Message C', $message->getSubject());
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
        static::assertFalse($message->isDeleted());
        $this->getConnection()->expunge();

        static::assertCount(3, $this->mailbox);
        static::assertSame('Message A', $this->mailbox->getMessage(1)->getSubject());
        static::assertSame('Message B', $this->mailbox->getMessage(2)->getSubject());
        static::assertSame('Message C', $this->mailbox->getMessage(3)->getSubject());
    }

    public function testMove()
    {
        $mailboxOne = $this->createMailbox();
        $mailboxTwo = $this->createMailbox();
        $this->createTestMessage($mailboxOne, 'Message A');

        static::assertCount(1, $mailboxOne);
        static::assertCount(0, $mailboxTwo);

        $message = $mailboxOne->getMessage(1);
        $message->move($mailboxTwo);
        $this->getConnection()->expunge();

        static::assertCount(0, $mailboxOne);
        static::assertCount(1, $mailboxTwo);
    }

    public function testResourceMemoryReuse()
    {
        $mailboxOne = $this->createMailbox();
        $this->createTestMessage($mailboxOne, 'Message A');
        $mailboxTwo = $this->createMailbox();

        $message = $mailboxOne->getMessage(1);

        // Mailbox::count triggers Mailbox::init
        // Reinitializing the imap resource to the mailbox 2
        static::assertCount(0, $mailboxTwo);

        $message->move($mailboxTwo);
        $this->getConnection()->expunge();

        static::assertCount(0, $mailboxOne);
        static::assertCount(1, $mailboxTwo);
    }

    public function testCopy()
    {
        $mailboxOne = $this->createMailbox();
        $mailboxTwo = $this->createMailbox();
        $this->createTestMessage($mailboxOne, 'Message A');

        static::assertCount(1, $mailboxOne);
        static::assertCount(0, $mailboxTwo);

        $message = $mailboxOne->getMessage(1);
        $message->copy($mailboxTwo);

        static::assertCount(1, $mailboxOne);
        static::assertCount(1, $mailboxTwo);

        static::assertFalse($message->isSeen());
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
        static::assertTrue($message->hasAttachments());
        static::assertCount(1, $message->getAttachments());
        $attachment = $message->getAttachments()[0];

        static::assertSame('application', \strtolower($attachment->getType() ?: ''));
        static::assertSame('vnd.ms-excel', \strtolower($attachment->getSubtype() ?: ''));
        static::assertSame(
            'Prostřeno_2014_poslední volné termíny.xls',
            $attachment->getFilename()
        );
        static::assertNull($attachment->getSize());

        static::assertFalse($message->isSeen());
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
        static::assertTrue($message->hasAttachments());
        static::assertCount(3, $message->getAttachments());

        $actual = [];
        foreach ($message->getAttachments() as $attachment) {
            $parameters = $attachment->getParameters();

            $actual[] = [
                'filename' => $parameters->get('filename'),
                'name'     => $parameters->get('name'),
            ];
        }

        $expected = [
            [
                'filename' => 'Buchungsbestätigung- Rechnung-Geschäftsbedingungen-Nr.B123-45 - XXXXX xxxxxxxxxxxxxxxxx XxxX, Lüxxxxxxxxxx - VM Klaus XXXXXX - xxxxxxxx.pdf',
                'name'     => 'Buchungsbestätigung- Rechnung-Geschäftsbedingungen-Nr.B123-45 - XXXX xxxxxxxxxxxxxxxxx XxxX, Lüdxxxxxxxx - VM Klaus XXXXXX - xxxxxxxx.pdf',
            ],
            [
                'filename' => '01_A€àä????@Z-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz.txt',
                'name'     => '01_A€àäąбيد@Z-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz.txt',
            ],
            [
                'filename' => '02_A€àäąбيد@Z-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz.txt',
                'name'     => '02_A€àäąбيد@Z-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz.txt',
            ],
        ];

        static::assertSame($expected, $actual);
    }

    public function testPlainTextAttachment()
    {
        $this->mailbox->addMessage($this->getFixture('plain_text_attachment'));

        $message = $this->mailbox->getMessage(1);

        static::assertSame('Test', $message->getBodyText());
        static::assertNull($message->getBodyHtml());

        static::assertTrue($message->hasAttachments());

        $attachments = $message->getAttachments();
        static::assertCount(1, $attachments);

        $attachment = \current($attachments);

        static::assertSame('Hi!', $attachment->getDecodedContent());
    }

    /**
     * @dataProvider provideUndisclosedRecipientsCases
     */
    public function testUndiscloredRecipients(string $fixture)
    {
        $this->mailbox->addMessage($this->getFixture($fixture));

        $message = $this->mailbox->getMessage(1);

        static::assertCount(1, $message->getTo());
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

            static::assertCount(1, $emails, $type);

            $email = \current($emails);

            static::assertSame(\sprintf('%s@here.com', \strtolower($type)), $email->getAddress(), $type);
        }
    }

    /**
     * @dataProvider provideDateCases
     */
    public function testDates(string $output, string $dateRawHeader)
    {
        $template = $this->getFixture('date-template');
        $message  = \str_replace('%date_raw_header%', $dateRawHeader, $template);
        $this->mailbox->addMessage($message);

        $message = $this->mailbox->getMessage(1);
        $date    = $message->getDate();

        static::assertInstanceOf(\DateTimeImmutable::class, $date);
        static::assertSame($output, $date->format(\DATE_ISO8601), \sprintf('RAW: %s', $dateRawHeader));
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
        $message  = \str_replace('%date_raw_header%', 'Fri!', $template);
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
        static::assertIsArray($expectedHeaders);
        $expectedHeaders = \implode("\r\n", $expectedHeaders);

        static::assertSame($expectedHeaders, $message->getRawHeaders());

        static::assertFalse($message->isSeen());
    }

    /**
     * @see https://github.com/ddeboer/imap/issues/200
     */
    public function testGetAllHeaders()
    {
        $this->mailbox->addMessage($this->getFixture('bcc'));

        $message = $this->mailbox->getMessage(1);
        $headers = $message->getHeaders();

        static::assertGreaterThan(9, \count($headers));

        static::assertArrayHasKey('from', $headers);
        static::assertArrayHasKey('date', $headers);
        static::assertArrayHasKey('recent', $headers);

        static::assertSame('Wed, 27 Sep 2017 12:48:51 +0200', $headers['date']);
        static::assertSame('A_€@{è_Z', $headers['bcc'][0]->personal);

        static::assertFalse($message->isSeen());
    }

    public function testSetFlags()
    {
        $this->createTestMessage($this->mailbox, 'Message A');

        $message = $this->mailbox->getMessage(1);

        static::assertFalse($message->isFlagged());

        $message->setFlag('\\Flagged');

        static::assertTrue($message->isFlagged());

        $message->clearFlag('\\Flagged');

        static::assertFalse($message->isFlagged());

        $message->setFlag('\\Seen');
        static::assertSame('R', $message->isRecent());
        static::assertTrue($message->isSeen());
    }

    /**
     * @see https://github.com/ddeboer/imap/pull/143
     */
    public function testUnstructuredMessage()
    {
        static::markTestIncomplete('Missing test case that gets imap_fetchstructure() to return false;');
    }

    public function testPlainOnlyMessage()
    {
        $this->mailbox->addMessage($this->getFixture('plain_only'));

        $message = $this->mailbox->getMessage(1);

        static::assertSame('Hi', $message->getBodyText());
        static::assertNull($message->getBodyHtml());
    }

    public function testHtmlOnlyMessage()
    {
        $this->mailbox->addMessage($this->getFixture('html_only'));

        $message = $this->mailbox->getMessage(1);

        static::assertSame('<html><body>Hi</body></html>', $message->getBodyHtml());
        static::assertNull($message->getBodyText());
    }

    public function testSimpleMultipart()
    {
        $this->mailbox->addMessage($this->getFixture('simple_multipart'));

        $message = $this->mailbox->getMessage(1);

        static::assertSame('MyPlain', $message->getBodyText());
        static::assertSame('MyHtml', $message->getBodyHtml());

        $parts = [];
        foreach ($message as $key => $part) {
            $parts[$key] = $part;
        }

        static::assertCount(2, $parts);

        static::assertFalse($message->isSeen());
    }

    public function testGetRawMessage()
    {
        $fixture = $this->getFixture('structured_with_attachment');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(1);

        static::assertSame($fixture, $message->getRawMessage());
    }

    public function testAttachmentOnlyEmail()
    {
        $fixture = $this->getFixture('mail_that_is_attachment');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(1);

        static::assertCount(1, $message->getAttachments());
    }

    /**
     * @see https://github.com/ddeboer/imap/issues/142
     */
    public function testIssue142()
    {
        $fixture = $this->getFixture('issue_142');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(1);

        static::assertCount(1, $message->getAttachments());
    }

    public function testSignedMessage()
    {
        $fixture = $this->getFixture('pec');
        $this->mailbox->addMessage($fixture);

        $message     = $this->mailbox->getMessage(1);
        $attachments = $message->getAttachments();

        static::assertCount(3, $attachments);

        $expected = [
            'data.xml'      => 'PHhtbC8+',
            'postacert.eml' => 'test-content',
            'smime.p7s'     => 'MQ==',
        ];

        foreach ($attachments as $attachment) {
            $expectedContains = $expected[$attachment->getFilename()];
            static::assertContains($expectedContains, $attachment->getContent(), \sprintf('Attachment filename: %s', $attachment->getFilename()));
        }
    }

    public function testSimpleMessageWithoutCharset()
    {
        $this->mailbox->addMessage($this->getFixture('without_charset_plain_only'));

        $message = $this->mailbox->getMessage(1);

        static::assertSame('Hi', $message->getBodyText());
    }

    public function testMultipartMessageWithoutCharset()
    {
        $this->mailbox->addMessage($this->getFixture('without_charset_simple_multipart'));

        $message = $this->mailbox->getMessage(1);

        static::assertSame('MyPlain', $message->getBodyText());
        static::assertSame('MyHtml', $message->getBodyHtml());
    }

    public function testGetInReplyTo()
    {
        $fixture = $this->getFixture('references');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(1);

        static::assertCount(1, $message->getInReplyTo());
        static::assertContains('<b9e87bd5e661a645ed6e3b832828fcc5@example.com>', $message->getInReplyTo());

        $fixture = $this->getFixture('plain_only');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(2);

        static::assertCount(0, $message->getInReplyTo());
    }

    public function testGetReferences()
    {
        $fixture = $this->getFixture('references');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(1);

        static::assertCount(2, $message->getReferences());
        static::assertContains('<08F04024-A5B3-4FDE-BF2C-6710DE97D8D9@example.com>', $message->getReferences());

        $fixture = $this->getFixture('plain_only');
        $this->mailbox->addMessage($fixture);

        $message = $this->mailbox->getMessage(2);

        static::assertCount(0, $message->getReferences());
    }

    public function testInlineAttachment()
    {
        $this->mailbox->addMessage($this->getFixture('inline_attachment'));
        $message = $this->mailbox->getMessage(1);

        $inline = $message->getAttachments()[0];

        static::assertNull($inline->getFilename());
    }

    public function testMissingFromHeader()
    {
        $this->mailbox->addMessage($this->getFixture('missing_from'));
        $message = $this->mailbox->getMessage(1);

        static::assertNull($message->getFrom());
    }

    public function testMissingDateHeader()
    {
        $this->mailbox->addMessage($this->getFixture('missing_date'));
        $message = $this->mailbox->getMessage(1);

        static::assertNull($message->getDate());
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

        static::assertIsString($messageString);

        $this->mailbox->addMessage($messageString);

        $message = $this->mailbox->getMessage(1);

        $this->resetAttachmentCharset($message);
        static::assertTrue($message->hasAttachments());
        $attachments = $message->getAttachments();
        static::assertCount(\count(self::$charsets), $attachments);

        foreach ($attachments as $attachment) {
            $filename = $attachment->getFilename();
            static::assertNotNull($filename);
            $charset = \str_replace('.xml', '', $filename);
            static::assertSame(\mb_convert_encoding(self::$charsets[$charset], $charset, 'UTF-8'), $attachment->getDecodedContent());
        }
    }

    public function testNoMessageId()
    {
        $this->mailbox->addMessage($this->getFixture('plain_only'));

        $message = $this->mailbox->getMessage(1);

        static::assertNull($message->getId());
    }

    public function testUnknownEncodingIsManageable()
    {
        $this->mailbox->addMessage($this->getFixture('unknown_encoding'));

        $message = $this->mailbox->getMessage(1);

        $parts = [];
        foreach ($message->getParts() as $part) {
            $parts[$part->getSubtype()] = $part;
        }

        static::assertArrayHasKey(PartInterface::SUBTYPE_PLAIN, $parts);

        $plain = $parts[PartInterface::SUBTYPE_PLAIN];

        static::assertSame(PartInterface::ENCODING_UNKNOWN, $plain->getEncoding());

        $this->expectException(UnexpectedEncodingException::class);

        $plain->getDecodedContent();
    }

    public function testMultipleAttachments()
    {
        $this->mailbox->addMessage($this->getFixture('multiple_nested_attachments'));

        $message = $this->mailbox->getMessage(1);

        static::assertCount(2, $message->getAttachments());
    }

    public function testMixedInlineDisposition()
    {
        $this->mailbox->addMessage($this->getFixture('mixed_filename'));

        $message = $this->mailbox->getMessage(1);

        $attachments = $message->getAttachments();
        static::assertCount(1, $attachments);

        $attachment = \current($attachments);
        static::assertSame('Price4VladDaKar.xlsx', $attachment->getFilename());
    }

    public function testNestesEmbeddedWithAttachment()
    {
        $this->mailbox->addMessage($this->getFixture('nestes_embedded_with_attachment'));

        $message = $this->mailbox->getMessage(1);

        $expected = [
            'first.eml'  => 'Subject: FIRST',
            'chrome.png' => 'ZFM4jELaoSdLtElJrUj1xxP6zwzfqSU4i0HYnydMtUlIqUfywxb60AxZqEXaoifgMCXptR9MtklH',
            'second.eml' => 'Subject: SECOND',
        ];
        $attachments = $message->getAttachments();
        static::assertCount(3, $attachments);
        foreach ($attachments as $attachment) {
            static::assertContains($expected[$attachment->getFilename()], $attachment->getContent());
        }
    }

    public function testImapMimeHeaderDecodeReturnsFalse()
    {
        $this->mailbox->addMessage($this->getFixture('imap_mime_header_decode_returns_false'));

        $message = $this->mailbox->getMessage(1);

        static::assertSame('=?UTF-8?B?nnDusSNdG92w6Fuw61fMjAxOF8wMy0xMzMyNTMzMTkzLnBkZg==?=', $message->getSubject());
    }

    private function resetAttachmentCharset(MessageInterface $message)
    {
        // Mimic GMAIL behaviour that correctly doesn't report charset
        // of attachments that don't have it
        $refMessage         = new \ReflectionClass($message);
        $refAbstractMessage = $refMessage->getParentClass();
        static::assertInstanceOf(ReflectionClass::class, $refAbstractMessage);
        $refAbstractPart = $refAbstractMessage->getParentClass();
        static::assertInstanceOf(ReflectionClass::class, $refAbstractPart);

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
        static::assertCount(0, $messages);
        static::assertFalse(\current($messages));

        $this->expectException(OutOfBoundsException::class);

        $messages->current();
    }

    public function testGbkCharsetDecoding()
    {
        $this->mailbox->addMessage($this->getFixture('gbk_charset'));

        $message = $this->mailbox->getMessage(1);

        static::assertSame('Hi', \trim($message->getDecodedContent()));
    }
}

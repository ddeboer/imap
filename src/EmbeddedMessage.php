<?php
/**
 * Created by PhpStorm.
 * User: Artem Evsin
 * Company: Cofis CZ
 * Date: 4.12.15
 * Time: 16:06
 */

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\Exception;
use Ddeboer\Imap\Message\EmailAddress;
use Ddeboer\Imap\Message\EmbeddedAttachment;

class EmbeddedMessage extends Message\Part
{
    /**
     * @var string
     */
    private $rawMimeHeader;
    private $parsedHeader;

    private $id;
    private $from;
    private $to;
    private $cc;
    private $date;
    private $subject;

    private $bodyHtml;
    private $bodyText;
    private $attachments;
    /**
     * @param resource $stream
     * @param int $messageNumber
     * @param int$partNumber
     */
    public function __construct($stream, $messageNumber, $partNumber)
    {
        $this->stream = $stream;
        $this->messageNumber = $messageNumber;
        $this->partNumber = $partNumber;
        $this->loadStructure();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Get message sender
     *
     * @return EmailAddress
     */
    public function getFrom()
    {
        return $this->from;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function getCc()
    {
        return $this->cc;
    }

    public function getNumber()
    {
        throw new Exception("Embedded message does not have number");
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getContent()
    {
        return $this->doGetContent();
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return mixed
     */
    public function getBodyHtml()
    {
        return $this->bodyHtml;
    }

    /**
     * @return mixed
     */
    public function getBodyText()
    {
        return $this->bodyText;
    }

    /**
     * @return mixed
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    private function loadStructure()
    {
        $this->rawMimeHeader = imap_fetchmime($this->stream, $this->messageNumber, $this->partNumber, FT_UID);
        $this->parsedHeader = imap_rfc822_parse_headers($this->rawMimeHeader);

        //Message ID
        $this->id = $this->parsedHeader->message_id;

        //Email from
        $emailFrom = $this->parsedHeader->from[0];
        $this->from = new EmailAddress($emailFrom->mailbox, $emailFrom->host, $emailFrom->personal);

        //EmailsTo
        $emailsTo = [];
        $parsedEmails = $this->parsedHeader->to;
        foreach ($parsedEmails as $email) {
            $emailsTo[] = new EmailAddress($email->mailbox, $email->host, $email->personal);
        }
        $this->to = $emailsTo;

        //EmailsCC
        $emailsCc = [];
        $parsedEmails = $this->parsedHeader->cc;
        foreach ($parsedEmails as $email) {
            $emailsCc[] = new EmailAddress($email->mailbox, $email->host, $email->personal);
        }
        $this->cc = $emailsCc;

        //Date
        $this->date = new \DateTime($this->parsedHeader->date);

        //Subject
        $this->subject = imap_utf8($this->parsedHeader->subject);

        $boundary = $this->getBoundary($this->rawMimeHeader);

        $this->bodyHtml = $this->parseHtml($boundary) ?: $this->parsePlaintext($boundary);

        $this->bodyText = $this->parsePlaintext($boundary);

        $this->attachments = $this->parseAttachments($this->rawMimeHeader);
    }

    /**
     */
    private function parsePlaintext($boundary)
    {
        return $this->getPart($boundary, "text/plain");
    }

    private function parseHtml($boundary)
    {
        return $this->getPart($boundary, "text/html");
    }

    /**
     * @param $mimeHeader
     * @return
     */
    private function getBoundary($mimeHeader)
    {
        $content = $this->getContent();
        preg_match('/boundary="(.*?)"/m', $content, $boundary);
        if (empty($boundary)) {
            preg_match('/boundary="(.*?)"/m', $mimeHeader, $boundary);
        }

        return $boundary[1];
    }

    private function getPart($boundary, $contentType)
    {
        $pattern = $boundary . '\s\s(Content-Type: '.preg_quote($contentType, "/").'.*?).{4}'.$boundary;
        $content = $this->getContent();

        preg_match('/'.$pattern.'/s', $content, $plaintext);

        if (empty($plaintext)) {
            return false;
        }
        preg_match("/Content-Transfer-Encoding: (.*)/m", trim($plaintext[1]), $encodingMatch);
        $encoding = trim($encodingMatch[1]);

        switch ($encoding) {
            case "quoted-printable":
                return $plaintext[1];
                break;
            case "8bit":
                return imap_8bit($plaintext[1]);
                break;
            default:
                throw new \UnexpectedValueException('Cannot decode ' . $encoding);
        }
    }

    private function parseAttachments($rawMimeHeader)
    {
        preg_match('/boundary="(.*?)"/m', $rawMimeHeader, $b);
        $messageBoundary = $b[1];

        $messageParts = explode("--".$messageBoundary, $this->getContent());

        $attachments = [];
        foreach ($messageParts as $messagePart) {
            if (!empty(trim($messagePart))) {
                $embeddedAttachment = new EmbeddedAttachment(trim($messagePart));
                if ($embeddedAttachment->isValid()) {
                    $attachments[] = $embeddedAttachment;
                }

            }
        }

        return $attachments;
    }
}
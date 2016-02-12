<?php
namespace Ddeboer\Imap\EmbeddedMessage;

use Ddeboer\Imap\Message;
use Ddeboer\Imap\Message\EmailAddress;

/**
 * Class EmbeddedMessage
 * @author Artem Evsin <artem@evsin.cz>
 * @author Cofis CZ <www.cofis.cz>
 * @package Ddeboer\Imap\EmbeddedMessage
 */
class EmbeddedMessage
{
    /**
     * Raw mime header from imap for this part of original message
     *
     * @var string
     */
    private $rawMimeHeader;

    /**
     * Parsed mime header of this part
     *
     * @var \stdClass
     */
    private $parsedHeader;

    /**
     * Original ID of embedded message
     *
     * @var string
     */
    private $id;

    /**
     * Email From from embedded message
     *
     * @var EmailAddress
     */
    private $from;

    /**
     * Emails To from embedded message
     *
     * @var EmailAddress[]
     */
    private $to;

    /**
     * Emails Cc from embedded message
     *
     * @var EmailAddress[]
     */
    private $cc;

    /**
     * Date from embedded message
     *
     * @var \DateTime
     */
    private $date;

    /**
     * Subject from embedded message
     *
     * @var string
     */
    private $subject;

    /**
     * Attachments from embedded message
     *
     * @var EmbeddedAttachment[]
     */
    private $attachments;

    /**
     * Parsed structure of message
     *
     * @var EmbeddedPart[]
     */
    private $structure;

    /**
     * @param resource $stream
     * @param int $messageNumber
     * @param int $partNumber
     */
    public function __construct($stream, $messageNumber, $partNumber)
    {
        $this->stream = $stream;
        $this->messageNumber = $messageNumber;
        $this->partNumber = $partNumber;
        $this->loadStructure();
    }

    /**
     * Get message ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get email From
     *
     * @return EmailAddress
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Get emails To
     *
     * @return EmailAddress[]
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Get emails Cc
     *
     * @return EmailAddress[]
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Get raw content of message
     *
     * @return string
     */
    public function getContent()
    {
        return imap_fetchbody($this->stream, $this->messageNumber, $this->partNumber, \FT_UID);
    }

    /**
     * Get text/html part of message. Returns false if does not exists
     *
     * @return string|bool
     */
    public function getBodyHtml()
    {
        $part = $this->findBy("contentType", "text/html");
        return isset($part[0]) ? $part[0]->getDecodedContent() : false;
    }

    /**
     * Get text/plain part of message. Returns false if does not exists
     *
     * @return string|bool
     */
    public function getBodyText()
    {
        $part = $this->findBy("contentType", "text/plain");
        return isset($part[0]) ? $part[0]->getDecodedContent() : false;
    }

    /**
     * Get array of attachments
     *
     * @return EmbeddedAttachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Load structure and save every part of message to common array EmbeddedMessage::$structure
     */
    private function loadStructure()
    {
        $this->content = imap_fetchbody($this->stream, $this->messageNumber, $this->partNumber, FT_UID);
        $this->rawMimeHeader = imap_fetchmime($this->stream, $this->messageNumber, $this->partNumber, FT_UID);
        $this->parsedHeader = imap_rfc822_parse_headers($this->content);

        //Message ID
        $this->id = $this->parsedHeader->message_id;

        //Email from
        $emailFrom = $this->parsedHeader->from[0];
        $emailFrom->personal = isset($emailFrom->personal) ? $emailFrom->personal : null;
        $this->from = new EmailAddress($emailFrom->mailbox, $emailFrom->host, imap_utf8($emailFrom->personal));

        //EmailsTo
        $emailsTo = [];
        $parsedEmails = $this->parsedHeader->to;
        foreach ($parsedEmails as $email) {
            $email->personal = isset($email->personal) ? $email->personal : null;
            $emailsTo[] = new EmailAddress($email->mailbox, $email->host, imap_utf8($email->personal));
        }
        $this->to = $emailsTo;

        //EmailsCC
        $emailsCc = [];
        $parsedEmails = isset($this->parsedHeader->cc) ? $this->parsedHeader->cc : [];
        foreach ($parsedEmails as $email) {
            $email->personal = isset($email->personal) ? $email->personal : null;
            $emailsCc[] = new EmailAddress($email->mailbox, $email->host, imap_utf8($email->personal));
        }
        $this->cc = $emailsCc;

        //Date
        $this->date = new \DateTime($this->parsedHeader->date);

        //Subject
        $this->subject = imap_utf8($this->parsedHeader->subject);

        $this->structure = $this->parseStructure();

        //extract attachments from common structure
        $this->attachments = $this->parseAttachments();
    }

    /**
     * Parse content of message to single parts
     *
     * @param null $part
     * @return EmbeddedPart[]
     */
    protected function parseStructure($part = null)
    {
        $structure = [];

        if (!$part) {
            //in first iteration we have to get main boundary
            $content = trim($this->getContent());
            $boundary = $this->getBoundary($this->content);
            if (!$boundary && $this->isBase64($content)) {
                //if embedded mail contains only attachment, content IS this attachment (without structure)
                //so we add mime header to attachment's content and create part from it
                $content = $this->rawMimeHeader.$content;
                return array($this->createPart($content));
            }
        } else {
            $content = trim($part);
            $boundary = $this->getBoundary($content);
            $content = preg_replace('/boundary=(.*?)(?: |\n|;)/m', 'boundary="'.$boundary.'" ', $content, 1);
        }

        if ($boundary) {
            //if boundary was found, separate content to parts
            $parts = explode("--".$boundary, $content);
            if (count($parts) === 1) {
                //if there is only one part after separation, we can create part from it immediately
                return $this->createPart($parts[0]);
            }
            foreach ($parts as $part) {
                //if there are many parts try to parse its structure (recursively)
                if (!trim($part) || trim($part) === "--") {
                    continue;
                }
                $structure[] = $this->parseStructure($part);
            }
        } else {
            //if there is no boundary, create part from content
            $structure = $this->createPart($content);
        }

        return $structure;
    }

    /**
     * Create a part from given part content
     *
     * @param $partContent
     * @return EmbeddedAttachment|EmbeddedPart|null
     */
    private function createPart($partContent)
    {
        //TODO add support for inline images (parse it to html)
        $part = new EmbeddedPart($partContent);

        if ($part->getContentType() == "message/rfc822") {
            //TODO add support embedded messages in embedded messages
            return null;
        }

        if ($part->getDisposition() == "attachment") {
            return new EmbeddedAttachment($part->getContentType(), $part->getFilename(), $part->getContentTransferEncoding(), $part->getContent());
        }

        return $part;
    }

    /**
     * Finder for searching parts by given conditions ($field => $value)
     * For example: findBy("contentType", "text/plain")
     * Returns array of matched parts or null if no parts found
     *
     * @param $field
     * @param $value
     * @param null $part
     * @return EmbeddedPart[]|bool
     */
    private function findBy($field, $value, $part = null)
    {
        $matches = [];

        $structure = $part ?: $this->structure;
        foreach ($structure as $part) {
            if (!$part) {
                continue;
            }
            if (is_array($part)) {
                $matches = array_merge($matches, $this->findBy($field, $value, $part));
                continue;
            }
            $getter = "get".$field;
            if ($part->$getter() === $value) {
                $matches[] = $part;
            }
        }

        return empty($matches) ? false : $matches;
    }

    /**
     * Extract boundary from string.
     * Returns empty string if boundary cannot be found
     *
     * @param string $content
     * @return string
     */
    private function getBoundary($content)
    {
        preg_match('/boundary=(.*?)(?: |\n|;)/m', $content, $boundary);
        if (!$boundary) {
            return false;
        }
        return trim(str_replace(['"', ";"], "", $boundary[1]));
    }

    /**
     * Get all parts with "attachment" disposition
     *
     * @return EmbeddedAttachment[]|bool
     */
    private function parseAttachments()
    {
        return $this->findBy("disposition", "attachment");
    }

    /**
     * Check if given string is base64 encoded string
     *
     * @param $string
     * @return bool
     */
    private function isBase64($string)
    {
        return (bool)base64_decode($string);
    }
}

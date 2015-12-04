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

class EmbeddedMessage extends Message
{
    private $rawMimeHeader;
    private $parsedHeader;

    private $id;
    private $from;
    private $to;
    private $cc;
    private $date;

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
        if (!isset($this->id)) {
            $this->id = $this->parsedHeader->message_id;
        }

        return $this->id;
    }

    /**
     * Get message sender
     *
     * @return EmailAddress
     */
    public function getFrom()
    {
        if (!isset($this->from)) {
            $emailFrom = $this->parsedHeader->from[0];
            $this->from = new EmailAddress($emailFrom->mailbox, $emailFrom->host, $emailFrom->personal);
        }

        return $this->from;
    }

    public function getTo()
    {
        if (!isset($this->to)) {
            $emailsTo = [];
            $parsedEmails = $this->parsedHeader->to;
            foreach ($parsedEmails as $email) {
                $emailsTo[] = new EmailAddress($email->mailbox, $email->host, $email->personal);
            }
            $this->to = $emailsTo;
        }

        return $this->to;
    }

    public function getCc()
    {
        if (!isset($this->cc)) {
            $emailsCc = [];
            $parsedEmails = $this->parsedHeader->cc;
            foreach ($parsedEmails as $email) {
                $emailsCc[] = new EmailAddress($email->mailbox, $email->host, $email->personal);
            }
            $this->cc = $emailsCc;
        }

        return $this->cc;
    }

    public function getNumber()
    {
        throw new Exception("Embedded message does not have number");
    }

    public function getDate()
    {
        if (!isset($this->date)) {
            $this->date = new \DateTime($this->parsedHeader->date);
        }

        return $this->date;
    }

    private function loadStructure()
    {
        $this->rawMimeHeader = imap_fetchmime($this->stream, $this->messageNumber, $this->partNumber, FT_UID);
        $this->parsedHeader = imap_rfc822_parse_headers($this->rawMimeHeader);
    }
}
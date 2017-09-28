<?php

declare(strict_types=1);

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\MessageDeleteException;
use Ddeboer\Imap\Exception\MessageDoesNotExistException;
use Ddeboer\Imap\Exception\MessageMoveException;
use Ddeboer\Imap\Message\EmailAddress;

/**
 * An IMAP message (e-mail)
 */
class Message extends Message\Part
{
    private $headers;
    private $rawHeaders;
    private $attachments;

    /**
     * @var bool
     */
    private $keepUnseen = false;

    /**
     * Constructor
     *
     * @param resource $stream        IMAP stream
     * @param int      $messageNumber Message number
     */
    public function __construct($stream, int $messageNumber)
    {
        $this->stream = $stream;
        $this->messageNumber = $messageNumber;

        $this->loadStructure();
    }

    /**
     * Get message id
     *
     * A unique message id in the form <...>
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->getHeaders()->get('message_id');
    }

    /**
     * Get message sender (from headers)
     *
     * @return EmailAddress
     */
    public function getFrom(): EmailAddress
    {
        return $this->getHeaders()->get('from');
    }

    /**
     * Get To recipients
     *
     * @return EmailAddress[] Empty array in case message has no To: recipients
     */
    public function getTo(): array
    {
        return $this->getHeaders()->get('to') ?: [];
    }

    /**
     * Get Cc recipients
     *
     * @return EmailAddress[] Empty array in case message has no CC: recipients
     */
    public function getCc(): array
    {
        return $this->getHeaders()->get('cc') ?: [];
    }

    /**
     * Get Bcc recipients
     *
     * @return EmailAddress[] Empty array in case message has no BCC: recipients
     */
    public function getBcc(): array
    {
        return $this->getHeaders()->get('bcc') ?: [];
    }

    /**
     * Get Reply-To recipients
     *
     * @return EmailAddress[] Empty array in case message has no Reply-To: recipients
     */
    public function getReplyTo(): array
    {
        return $this->getHeaders()->get('reply_to') ?: [];
    }

    /**
     * Get Sender
     *
     * @return EmailAddress[] Empty array in case message has no Sender: recipients
     */
    public function getSender(): array
    {
        return $this->getHeaders()->get('sender') ?: [];
    }

    /**
     * Get Return-Path
     *
     * @return EmailAddress[] Empty array in case message has no Return-Path: recipients
     */
    public function getReturnPath(): array
    {
        return $this->getHeaders()->get('return_path') ?: [];
    }

    /**
     * Get message number (from headers)
     *
     * @return int
     */
    public function getNumber(): int
    {
        return $this->messageNumber;
    }

    /**
     * Get date (from headers)
     *
     * @return \DateTimeImmutable
     */
    public function getDate(): \DateTimeImmutable
    {
        return $this->getHeaders()->get('date');
    }

    /**
     * Get message size (from headers)
     *
     * @return int
     */
    public function getSize()
    {
        return $this->getHeaders()->get('size');
    }

    /**
     * Get raw part content
     *
     * @param mixed $keepUnseen
     *
     * @return string
     */
    public function getContent(bool $keepUnseen = false): string
    {
        // Null headers, so subsequent calls to getHeaders() will return
        // updated seen flag
        $this->headers = null;

        return $this->doGetContent($this->keepUnseen ? $this->keepUnseen : $keepUnseen);
    }

    /**
     * Get message answered flag value (from headers)
     *
     * @return bool
     */
    public function isAnswered()
    {
        return $this->getHeaders()->get('answered');
    }

    /**
     * Get message deleted flag value (from headers)
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->getHeaders()->get('deleted');
    }

    /**
     * Get message draft flag value (from headers)
     *
     * @return bool
     */
    public function isDraft()
    {
        return $this->getHeaders()->get('draft');
    }

    /**
     * Has the message been marked as read?
     *
     * @return bool
     */
    public function isSeen(): bool
    {
        return
                'R' === $this->getHeaders()->get('recent')
            || ('' === $this->getHeaders()->get('recent') && '' !== $this->getHeaders()->get('unseen'))
        ;
    }

    /**
     * Get message subject (from headers)
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->getHeaders()->get('subject');
    }

    /**
     * Get message headers
     *
     * @return Message\Headers
     */
    public function getHeaders(): Message\Headers
    {
        if (null === $this->headers) {
            // imap_headerinfo is much faster than imap_fetchheader
            // imap_headerinfo returns only a subset of all mail headers,
            // but it does include the message flags.
            $headers = imap_headerinfo($this->stream, imap_msgno($this->stream, $this->messageNumber));
            $this->headers = new Message\Headers($headers);
        }

        return $this->headers;
    }

    /**
     * Get raw message headers
     *
     * @return string
     */
    public function getRawHeaders(): string
    {
        if (null === $this->rawHeaders) {
            $this->rawHeaders = imap_fetchheader($this->stream, $this->messageNumber, \FT_UID);
        }

        return $this->rawHeaders;
    }

    /**
     * Get body HTML
     *
     * @return string | null Null if message has no HTML message part
     */
    public function getBodyHtml()
    {
        $iterator = new \RecursiveIteratorIterator($this, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $part) {
            if ($part->getSubtype() == 'HTML') {
                return $part->getDecodedContent($this->keepUnseen);
            }
        }
    }

    /**
     * Get body text
     *
     * @return string
     */
    public function getBodyText(): string
    {
        $iterator = new \RecursiveIteratorIterator($this, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $part) {
            if ($part->getSubtype() == 'PLAIN') {
                return $part->getDecodedContent($this->keepUnseen);
            }
        }

        // If message has no parts, return content of message itself.
        return $this->getDecodedContent($this->keepUnseen);
    }

    /**
     * Get attachments (if any) linked to this e-mail
     *
     * @return Message\Attachment[]
     */
    public function getAttachments(): array
    {
        if (null === $this->attachments) {
            $this->attachments = [];
            foreach ($this->getParts() as $part) {
                if ($part instanceof Message\Attachment) {
                    $this->attachments[] = $part;
                }
                if ($part->hasChildren()) {
                    foreach ($part->getParts() as $child_part) {
                        if ($child_part instanceof Message\Attachment) {
                            $this->attachments[] = $child_part;
                        }
                    }
                }
            }
        }

        return $this->attachments;
    }

    /**
     * Does this message have attachments?
     *
     * @return bool
     */
    public function hasAttachments(): bool
    {
        return count($this->getAttachments()) > 0;
    }

    /**
     * Delete message
     *
     * @throws MessageDeleteException
     */
    public function delete()
    {
        // 'deleted' header changed, force to reload headers, would be better to set deleted flag to true on header
        $this->headers = null;

        if (!imap_delete($this->stream, $this->messageNumber, \FT_UID)) {
            throw new MessageDeleteException(sprintf(
                'Message "%s" cannot be deleted',
                $this->messageNumber
            ));
        }
    }

    /**
     * Move message to another mailbox
     *
     * @param Mailbox $mailbox
     *
     * @throws MessageMoveException
     *
     * @return Message
     */
    public function move(Mailbox $mailbox): self
    {
        if (!imap_mail_move($this->stream, $this->messageNumber, $mailbox->getName(), \CP_UID)) {
            throw new MessageMoveException(sprintf(
                'Message "%s" cannot be moved to "%s"',
                $this->messageNumber,
                $mailbox->getName()
            ));
        }

        return $this;
    }

    /**
     * Prevent the message from being marked as seen
     *
     * Defaults to true, so messages that are read will be still marked as unseen.
     *
     * @param bool $bool
     *
     * @return Message
     */
    public function keepUnseen(bool $bool = true): self
    {
        $this->keepUnseen = $bool;

        return $this;
    }

    /**
     * Load message structure
     */
    private function loadStructure()
    {
        set_error_handler(function ($nr, $error) {
            throw new MessageDoesNotExistException(sprintf(
                'Message %s does not exist: %s',
                $this->messageNumber,
                $error
            ), $nr);
        });

        $structure = imap_fetchstructure(
            $this->stream,
            $this->messageNumber,
            \FT_UID
        );

        restore_error_handler();

        $this->parseStructure($structure);
    }

    /**
     * Set Flag Message
     *
     * @param $flag \Seen, \Answered, \Flagged, \Deleted, and \Draft
     *
     * @return bool
     */
    public function setFlag($flag)
    {
        return imap_setflag_full($this->stream, $this->messageNumber, $flag, \ST_UID);
    }

    /**
     * Clear Flag Message
     *
     * @param $flag \Seen, \Answered, \Flagged, \Deleted, and \Draft
     *
     * @return bool
     */
    public function clearFlag($flag)
    {
        return imap_clearflag_full($this->stream, $this->messageNumber, $flag, \ST_UID);
    }
}

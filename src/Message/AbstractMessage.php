<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Message;

abstract class AbstractMessage extends Part
{
    private $headers;
    private $rawHeaders;
    private $attachments;

    /**
     * Get raw message headers
     *
     * @return string
     */
    abstract public function getRawHeaders(): string;

    /**
     * Get the raw message, including all headers, parts, etc. unencoded and unparsed.
     *
     * @return string the raw message
     */
    abstract public function getRawMessage(): string;

    /**
     * Get message headers
     *
     * @return Headers
     */
    abstract public function getHeaders(): Headers;

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
     * Get message subject (from headers)
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->getHeaders()->get('subject');
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
            if (self::SUBTYPE_HTML === $part->getSubtype()) {
                return $part->getDecodedContent();
            }
        }

        // If message has no parts and is HTML, return content of message itself.
        if (self::SUBTYPE_HTML === $this->getSubtype()) {
            return $this->getDecodedContent();
        }

        return;
    }

    /**
     * Get body text
     *
     * @return string
     */
    public function getBodyText()
    {
        $iterator = new \RecursiveIteratorIterator($this, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $part) {
            if (self::SUBTYPE_PLAIN === $part->getSubtype()) {
                return $part->getDecodedContent();
            }
        }

        // If message has no parts, return content of message itself.
        if (self::SUBTYPE_PLAIN === $this->getSubtype()) {
            return $this->getDecodedContent();
        }

        return;
    }

    /**
     * Get attachments (if any) linked to this e-mail
     *
     * @return Attachment[]
     */
    public function getAttachments(): array
    {
        if (null === $this->attachments) {
            $this->attachments = [];
            foreach ($this->getParts() as $part) {
                if ($part instanceof Attachment) {
                    $this->attachments[] = $part;
                }
                if ($part->hasChildren()) {
                    foreach ($part->getParts() as $child_part) {
                        if ($child_part instanceof Attachment) {
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
}

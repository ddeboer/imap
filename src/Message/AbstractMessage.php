<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Message;

use Ddeboer\Imap\Exception\InvalidDateHeaderException;

abstract class AbstractMessage extends AbstractPart
{
    /**
     * @var null|array
     */
    private $attachments;

    /**
     * Get message id.
     *
     * A unique message id in the form <...>
     *
     * @return null|string
     */
    final public function getId()
    {
        return $this->getHeaders()->get('message_id');
    }

    /**
     * Get message sender (from headers).
     *
     * @return null|EmailAddress
     */
    final public function getFrom()
    {
        $from = $this->getHeaders()->get('from');

        return null !== $from ? $this->decodeEmailAddress($from[0]) : null;
    }

    /**
     * Get To recipients.
     *
     * @return EmailAddress[] Empty array in case message has no To: recipients
     */
    final public function getTo(): array
    {
        return $this->decodeEmailAddresses($this->getHeaders()->get('to') ?: []);
    }

    /**
     * Get Cc recipients.
     *
     * @return EmailAddress[] Empty array in case message has no CC: recipients
     */
    final public function getCc(): array
    {
        return $this->decodeEmailAddresses($this->getHeaders()->get('cc') ?: []);
    }

    /**
     * Get Bcc recipients.
     *
     * @return EmailAddress[] Empty array in case message has no BCC: recipients
     */
    final public function getBcc(): array
    {
        return $this->decodeEmailAddresses($this->getHeaders()->get('bcc') ?: []);
    }

    /**
     * Get Reply-To recipients.
     *
     * @return EmailAddress[] Empty array in case message has no Reply-To: recipients
     */
    final public function getReplyTo(): array
    {
        return $this->decodeEmailAddresses($this->getHeaders()->get('reply_to') ?: []);
    }

    /**
     * Get Sender.
     *
     * @return EmailAddress[] Empty array in case message has no Sender: recipients
     */
    final public function getSender(): array
    {
        return $this->decodeEmailAddresses($this->getHeaders()->get('sender') ?: []);
    }

    /**
     * Get Return-Path.
     *
     * @return EmailAddress[] Empty array in case message has no Return-Path: recipients
     */
    final public function getReturnPath(): array
    {
        return $this->decodeEmailAddresses($this->getHeaders()->get('return_path') ?: []);
    }

    /**
     * Get date (from headers).
     *
     * @return null|\DateTimeImmutable
     */
    final public function getDate()
    {
        $dateHeader = $this->getHeaders()->get('date');
        if (null === $dateHeader) {
            return;
        }

        $alteredValue = \str_replace(',', '', $dateHeader);
        $alteredValue = \preg_replace('/ +\(.*\)/', '', $alteredValue);
        if (0 === \preg_match('/\d\d:\d\d:\d\d.* [\+\-]?\d\d:?\d\d/', $alteredValue)) {
            $alteredValue .= ' +0000';
        }

        try {
            $date = new \DateTimeImmutable($alteredValue);
        } catch (\Throwable $ex) {
            throw new InvalidDateHeaderException(\sprintf('Invalid Date header found: "%s"', $dateHeader), 0, $ex);
        }

        return $date;
    }

    /**
     * Get message size (from headers).
     *
     * @return int
     */
    final public function getSize()
    {
        return $this->getHeaders()->get('size');
    }

    /**
     * Get message subject (from headers).
     *
     * @return string
     */
    final public function getSubject()
    {
        return $this->getHeaders()->get('subject');
    }

    /**
     * Get message In-Reply-To (from headers).
     *
     * @return array
     */
    final public function getInReplyTo(): array
    {
        $inReplyTo = $this->getHeaders()->get('in_reply_to');

        return null !== $inReplyTo ? \explode(' ', $inReplyTo) : [];
    }

    /**
     * Get message References (from headers).
     *
     * @return array
     */
    final public function getReferences(): array
    {
        $references = $this->getHeaders()->get('references');

        return null !== $references ? \explode(' ', $references) : [];
    }

    /**
     * Get body HTML.
     *
     * @return null|string
     */
    final public function getBodyHtml()
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
     * Get body text.
     *
     * @return null|string
     */
    final public function getBodyText()
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
     * Get attachments (if any) linked to this e-mail.
     *
     * @return AttachmentInterface[]
     */
    final public function getAttachments(): array
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
    final public function hasAttachments(): bool
    {
        return \count($this->getAttachments()) > 0;
    }

    /**
     * @param array $addresses Addesses
     *
     * @return array
     */
    private function decodeEmailAddresses(array $addresses): array
    {
        $return = [];
        foreach ($addresses as $address) {
            if (isset($address->mailbox)) {
                $return[] = $this->decodeEmailAddress($address);
            }
        }

        return $return;
    }

    /**
     * @param \stdClass $value
     *
     * @return EmailAddress
     */
    private function decodeEmailAddress(\stdClass $value): EmailAddress
    {
        return new EmailAddress($value->mailbox, $value->host, $value->personal);
    }
}

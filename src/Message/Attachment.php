<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Message;

use Ddeboer\Imap\Exception\NotEmbeddedMessageException;

/**
 * An e-mail attachment.
 */
final class Attachment extends AbstractPart implements AttachmentInterface
{
    /**
     * Get attachment filename.
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->parameters->get('filename')
            ?: $this->parameters->get('name');
    }

    /**
     * Get attachment file size.
     *
     * @return int Number of bytes
     */
    public function getSize()
    {
        return $this->parameters->get('size');
    }

    /**
     * Is this attachment also an Embedded Message?
     *
     * @return bool
     */
    public function isEmbeddedMessage(): bool
    {
        return self::TYPE_MESSAGE === $this->type;
    }

    /**
     * Return embedded message.
     *
     * @throws NotEmbeddedMessageException
     *
     * @return EmbeddedMessageInterface
     */
    public function getEmbeddedMessage(): EmbeddedMessageInterface
    {
        if (!$this->isEmbeddedMessage()) {
            throw new NotEmbeddedMessageException(\sprintf(
                'Attachment "%s" in message "%s" is not embedded message',
                $this->partNumber,
                $this->messageNumber
            ));
        }

        return new EmbeddedMessage($this->resource, $this->messageNumber, $this->partNumber, $this->structure->parts[0]);
    }
}

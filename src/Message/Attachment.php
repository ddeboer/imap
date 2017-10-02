<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Message;

use Ddeboer\Imap\Exception\NotEmbeddedMessageException;

/**
 * An e-mail attachment.
 */
final class Attachment extends Part
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

    public function isEmbeddedMessage()
    {
        return self::TYPE_MESSAGE === $this->type;
    }

    /**
     * Return embedded message.
     *
     * @throws NotEmbeddedMessageException
     *
     * @return EmbeddedMessage
     */
    public function getEmbeddedMessage(): EmbeddedMessage
    {
        if (!$this->isEmbeddedMessage()) {
            throw new NotEmbeddedMessageException(\sprintf(
                'Attachment "%s" in message "%s" is not embedded message',
                $this->partNumber,
                $this->messageNumber
            ));
        }

        return new EmbeddedMessage($this->stream, $this->messageNumber, $this->partNumber, $this->structure->parts[0]);
    }
}

<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Message;

/**
 * An e-mail attachment.
 */
interface AttachmentInterface extends PartInterface
{
    /**
     * Get attachment filename.
     *
     * @return string
     */
    public function getFilename(): string;

    /**
     * Get attachment file size.
     *
     * @return int Number of bytes
     */
    public function getSize();

    public function isEmbeddedMessage(): bool;

    /**
     * Return embedded message.
     *
     * @return EmbeddedMessageInterface
     */
    public function getEmbeddedMessage(): EmbeddedMessageInterface;
}

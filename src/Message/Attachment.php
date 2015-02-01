<?php

namespace Ddeboer\Imap\Message;

/**
 * An e-mail attachment
 */
class Attachment extends Part
{
    /**
     * Get attachment filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->parameters->get('filename')
            ?: $this->parameters->get('name');
    }

    /**
     * Get attachment file size
     *
     * @return int Number of bytes
     */
    public function getSize()
    {
        return $this->parameters->get('size');
    }
}

<?php

namespace Ddeboer\Imap\Message;

/**
 * An e-mail attachment
 */
class Attachment extends Part
{
    protected $filename;

    protected $data;

    protected $contentType;

    protected $size;

    public function __construct($stream, $messageNumber, $partNumber = null, $structure = null)
    {
        parent::__construct($stream, $messageNumber, $partNumber, $structure);
    }

    /**
     * Get attachment filename
     *
     * @return string
     */
    public function getFilename()
    {
        if(isset($this->parameters['filename'])) {
            return $this->parameters['filename'];
        }
    }

    /**
     * Get attachment file size
     *
     * @return int Number of bytes
     */
    public function getSize()
    {
        if(isset($this->parameters['size'])) {
            return $this->parameters['size'];
        }
    }
}

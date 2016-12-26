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
        if(!empty($this->parameters->get('filename'))){
            return $this->parameters->get('filename');
        } else {
            return $this->parameters->get('name');
        }
    }

    /**
     * Get attachment file size
     *
     * @return int Number of bytes
     */
    public function getSize()
    {
        return $this->getBytes();
    }
}

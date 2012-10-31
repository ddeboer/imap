<?php

namespace Ddeboer\Imap\Message;

/**
 * An e-mail attachment
 */
class Attachment extends Part
{
    protected $filename;

    protected $data;

    /**
     * @var string MIME type of an attachment
     */
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
        return $this->parameters->get('filename');
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

    /**
     * Returns content type of an attachment
     *
     * @return string
     */
    public function getContentType()
    {
        if ($this->contentType !== null) {
            return $this->contentType;
        }

        $this->contentType = $this->type;

        if ($this->subtype) {
            if ($this->contentType) {
                $this->contentType .= '/';
            }
            $this->contentType .= $this->subtype;
        }

        $this->contentType = strtolower($this->contentType);

        return $this->contentType;
    }
}

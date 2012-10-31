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

    /**
     * Saves attachment to file
     *
     * @param string $filePath
     *
     * @throws \Ddeboer\Imap\Exception\Exception
     */
    public function saveToFile($filePath)
    {
        $handle = @fopen($filePath, 'w');

        if ($handle === false) {
            throw new \Ddeboer\Imap\Exception\Exception('Failed to open stream');
        }

        switch ($this->encoding) {
            case self::ENCODING_BASE64:
                stream_filter_append($handle, 'convert.base64-decode', STREAM_FILTER_WRITE);
                break;
            case self::ENCODING_QUOTED_PRINTABLE:
                stream_filter_append($handle, 'convert.quoted-printable-decode', STREAM_FILTER_WRITE);
                break;
            default:
                // do nothing
                break;
        }

        if (!imap_savebody($this->stream, $handle, $this->messageNumber, $this->partNumber)) {
            throw new \Ddeboer\Imap\Exception\Exception(imap_last_error());
        }

        fclose($handle);
    }
}

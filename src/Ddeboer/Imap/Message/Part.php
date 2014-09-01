<?php

namespace Ddeboer\Imap\Message;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * A message part
 */
class Part implements \RecursiveIterator
{
    const TYPE_TEXT = 'text';
    const TYPE_MULTIPART = 'multipart';
    const TYPE_MESSAGE = 'message';
    const TYPE_APPLICATION = 'application';
    const TYPE_AUDIO = 'audio';
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_OTHER = 'other';

    const ENCODING_7BIT = '7bit';
    const ENCODING_8BIT = '8bit';
    const ENCODING_BINARY = 'binary';
    const ENCODING_BASE64 = 'base64';
    const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';
    const ENCODING_UNKNOWN = 'unknown';

    const SUBTYPE_TEXT = 'TEXT';
    const SUBTYPE_HTML = 'HTML';

    protected $typesMap = array(
        0 => self::TYPE_TEXT,
        1 => self::TYPE_MULTIPART,
        2 => self::TYPE_MESSAGE,
        3 => self::TYPE_APPLICATION,
        4 => self::TYPE_AUDIO,
        5 => self::TYPE_IMAGE,
        6 => self::TYPE_VIDEO,
        7 => self::TYPE_OTHER
    );

    protected $encodingsMap = array(
        0 => self::ENCODING_7BIT,
        1 => self::ENCODING_8BIT,
        2 => self::ENCODING_BINARY,
        3 => self::ENCODING_BASE64,
        4 => self::ENCODING_QUOTED_PRINTABLE,
        5 => self::ENCODING_UNKNOWN
    );

    protected $type;

    protected $subtype;

    protected $encoding;

    protected $bytes;

    protected $lines;

    protected $parameters;

    protected $messageNumber;

    protected $partNumber;

    protected $structure;

    protected $content;

    protected $decodedContent;

    protected $parts = array();

    protected $key = 0;

    protected $disposition;

    /**
     * Constructor
     *
     * @param \stdClass $part   The part
     * @param string    $number The part number
     */
    public function __construct($stream, $messageNumber, $partNumber = null, $structure = null)
    {
        $this->stream = $stream;
        $this->messageNumber = $messageNumber;
        $this->partNumber = $partNumber;
        $this->structure = $structure;
        $this->parseStructure($structure);
    }

    public function getCharset()
    {
        return $this->parameters->get('charset');
    }

    public function getType()
    {
        return $this->type;
    }

    public function getSubtype()
    {
        return $this->subtype;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function getBytes()
    {
        return $this->bytes;
    }

    public function getLines()
    {
        return $this->lines;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Get raw part content
     *
     * @return string
     */
    public function getContent()
    {
        if (null === $this->content) {
            $this->content = $this->doGetContent();
        }

        return $this->content;
    }

    /**
     * Get decoded part content
     *
     * @return string
     */
    public function getDecodedContent()
    {
        if (null === $this->decodedContent) {
            switch ($this->getEncoding()) {
                case self::ENCODING_BASE64:
                    $this->decodedContent = \base64_decode($this->getContent());
                    break;

                case self::ENCODING_QUOTED_PRINTABLE:
                    $this->decodedContent =  \utf8_encode(\quoted_printable_decode($this->getContent()));
                    break;

                case self::ENCODING_7BIT:
                case self::ENCODING_8BIT:
                case self::ENCODING_BINARY:
                    $this->decodedContent = $this->getContent();
                    break;

                default:
                    throw new \UnexpectedValueException('Cannot decode ' . $this->getEncoding());
            }

            // If this part is a text part, try to convert its encoding to UTF-8.
            // We don't want to convert an attachment's encoding.
            if ($this->getType() === self::TYPE_TEXT
                && null !== $this->getCharset()
                && strtolower($this->getCharset()) != 'utf-8'
            ) {
                $this->decodedContent = \mb_convert_encoding(
                    $this->decodedContent,
                    'UTF-8'
                );
            }
        }

        return $this->decodedContent;
    }

    public function getStructure()
    {
        return $this->structure;
    }

    protected function fetchStructure($partNumber = null)
    {
        if (null === $this->structure) {
            $this->loadStructure();
        }

        if ($partNumber) {
            return $this->structure->parts[$partNumber];
        }

        return $this->structure;
    }

    protected function parseStructure(\stdClass $structure)
    {
        $this->type = $this->typesMap[$structure->type];
        $this->encoding = $this->encodingsMap[$structure->encoding];
        $this->subtype = $structure->subtype;

        if (isset($structure->bytes)) {
            $this->bytes = $structure->bytes;
        }

        foreach (array('disposition', 'bytes', 'description') as $optional) {
            if (isset($structure->$optional)) {
                $this->$optional = $structure->$optional;
            }
        }

        $this->parameters = new ArrayCollection();
        foreach ($structure->parameters as $parameter) {
            $this->parameters->set(strtolower($parameter->attribute), $parameter->value);
        }

        if (isset($structure->dparameters)) {
            foreach ($structure->dparameters as $parameter) {
                $this->parameters->set(strtolower($parameter->attribute), $parameter->value);
            }
        }

        if (isset($structure->parts)) {
            foreach ($structure->parts as $key => $partStructure) {
                if (null === $this->partNumber) {
                    $partNumber = ($key + 1);
                } else {
                    $partNumber = (string) ($this->partNumber . '.' . ($key+1));
                }

                if (isset($partStructure->disposition)
                    && (strtolower($partStructure->disposition) == 'attachment'
                        || strtolower($partStructure->disposition) == 'inline')
                    && strtoupper($partStructure->subtype) != "PLAIN"
                ) {
                    $attachment = new Attachment($this->stream, $this->messageNumber, $partNumber, $partStructure);
                    $this->parts[] = $attachment;
                } else {
                    $this->parts[] = new Part($this->stream, $this->messageNumber, $partNumber, $partStructure);
                }
            }
        }
    }

    /**
     * Get an array of all parts for this message
     *
     * @return self[]
     */
    public function getParts()
    {
        return $this->parts;
    }

    public function current()
    {
        return $this->parts[$this->key];
    }

    public function getChildren()
    {
        return $this->current();
    }

    public function hasChildren()
    {
        return count($this->parts) > 0;
    }

    public function key()
    {
        return $this->key;
    }

    public function next()
    {
        ++$this->key;
    }

    public function rewind()
    {
        $this->key = 0;
    }

    public function valid()
    {
        return isset($this->parts[$this->key]);
    }

    public function getDisposition()
    {
        return $this->disposition;
    }

    /**
     * Get raw message content
     *
     * @param bool $keepUnseen Whether to keep the message unseen.
     *                         Default behaviour is set set the seen flag when
     *                         getting content.
     *
     * @return string
     */
    protected function doGetContent($keepUnseen = false)
    {
        return \imap_fetchbody(
            $this->stream,
            $this->messageNumber,
            $this->partNumber ?: 1,
            \FT_UID | ($keepUnseen ? \FT_PEEK : null)
        );
    }
}

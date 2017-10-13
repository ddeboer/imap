<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Message;

use Ddeboer\Imap\Exception\UnexpectedEncodingException;
use Ddeboer\Imap\ImapResourceInterface;
use Ddeboer\Imap\Message;

/**
 * A message part.
 */
abstract class AbstractPart implements PartInterface
{
    /**
     * @var ImapResourceInterface
     */
    protected $resource;

    /**
     * @var array
     */
    protected $parts = [];

    /**
     * @var string
     */
    private $partNumber;

    /**
     * @var int
     */
    private $messageNumber;

    /**
     * @var \stdClass
     */
    private $structure;

    /**
     * @var Parameters
     */
    private $parameters;

    /**
     * @var null|string
     */
    private $type;

    /**
     * @var null|string
     */
    private $subtype;

    /**
     * @var null|string
     */
    private $encoding;

    /**
     * @var null|string
     */
    private $disposition;

    /**
     * @var null|string
     */
    private $bytes;

    /**
     * @var null|string
     */
    private $lines;

    /**
     * @var null|string
     */
    private $content;

    /**
     * @var null|string
     */
    private $decodedContent;

    /**
     * @var int
     */
    private $key = 0;

    /**
     * @var array
     */
    private $typesMap = [
        \TYPETEXT => self::TYPE_TEXT,
        \TYPEMULTIPART => self::TYPE_MULTIPART,
        \TYPEMESSAGE => self::TYPE_MESSAGE,
        \TYPEAPPLICATION => self::TYPE_APPLICATION,
        \TYPEAUDIO => self::TYPE_AUDIO,
        \TYPEIMAGE => self::TYPE_IMAGE,
        \TYPEVIDEO => self::TYPE_VIDEO,
        \TYPEMODEL => self::TYPE_MODEL,
        \TYPEOTHER => self::TYPE_OTHER,
    ];

    /**
     * @var array
     */
    private $encodingsMap = [
        \ENC7BIT => self::ENCODING_7BIT,
        \ENC8BIT => self::ENCODING_8BIT,
        \ENCBINARY => self::ENCODING_BINARY,
        \ENCBASE64 => self::ENCODING_BASE64,
        \ENCQUOTEDPRINTABLE => self::ENCODING_QUOTED_PRINTABLE,
    ];

    /**
     * Constructor.
     *
     * @param ImapResourceInterface $resource      IMAP resource
     * @param int                   $messageNumber Message number
     * @param string                $partNumber    Part number
     * @param \stdClass             $structure     Part structure
     */
    public function __construct(
        ImapResourceInterface $resource,
        int $messageNumber,
        string $partNumber,
        \stdClass $structure
    ) {
        $this->resource = $resource;
        $this->messageNumber = $messageNumber;
        $this->partNumber = $partNumber;
        $this->structure = $structure;
        $this->parseStructure($structure);
    }

    /**
     * Get message number (from headers).
     *
     * @return int
     */
    final public function getNumber(): int
    {
        return $this->messageNumber;
    }

    /**
     * Part structure.
     *
     * @return \stdClass
     */
    final public function getStructure(): \stdClass
    {
        return $this->structure;
    }

    /**
     * Part parameters.
     *
     * @return Parameters
     */
    final public function getParameters(): Parameters
    {
        return $this->parameters;
    }

    /**
     * Part charset.
     *
     * @return null|string
     */
    final public function getCharset()
    {
        return $this->parameters->get('charset') ?: null;
    }

    /**
     * Part type.
     *
     * @return null|string
     */
    final public function getType()
    {
        return $this->type;
    }

    /**
     * Part subtype.
     *
     * @return null|string
     */
    final public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * Part encoding.
     *
     * @return null|string
     */
    final public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Part disposition.
     *
     * @return null|string
     */
    final public function getDisposition()
    {
        return $this->disposition;
    }

    /**
     * Part bytes.
     *
     * @return null|string
     */
    final public function getBytes()
    {
        return $this->bytes;
    }

    /**
     * Part lines.
     *
     * @return null|string
     */
    final public function getLines()
    {
        return $this->lines;
    }

    /**
     * Get raw part content.
     *
     * @return string
     */
    final public function getContent(): string
    {
        if (null === $this->content) {
            $this->content = $this->doGetContent($this->getContentPartNumber());
        }

        return $this->content;
    }

    /**
     * Get content part number.
     *
     * @return string
     */
    protected function getContentPartNumber(): string
    {
        return $this->partNumber;
    }

    /**
     * Get part number.
     *
     * @return string
     */
    final public function getPartNumber(): string
    {
        return $this->partNumber;
    }

    /**
     * Get decoded part content.
     *
     * @return string
     */
    final public function getDecodedContent(): string
    {
        if (null === $this->decodedContent) {
            $content = $this->getContent();
            if (self::ENCODING_BASE64 === $this->getEncoding()) {
                $content = \base64_decode($content);
            } elseif (self::ENCODING_QUOTED_PRINTABLE === $this->getEncoding()) {
                $content = \quoted_printable_decode($content);
            }

            // If this part is a text part, convert its charset to UTF-8.
            // We don't want to decode an attachment's charset.
            if (!$this instanceof Attachment && null !== $this->getCharset() && self::TYPE_TEXT === $this->getType()) {
                $content = Transcoder::decode($content, $this->getCharset());
            }

            $this->decodedContent = $content;
        }

        return $this->decodedContent;
    }

    /**
     * Get raw message content.
     *
     * @param string $partNumber
     *
     * @return string
     */
    final protected function doGetContent(string $partNumber): string
    {
        return \imap_fetchbody(
            $this->resource->getStream(),
            $this->messageNumber,
            $partNumber,
            \FT_UID | \FT_PEEK
        );
    }

    /**
     * Parse part structure.
     *
     * @param \stdClass $structure
     */
    private function parseStructure(\stdClass $structure)
    {
        $this->type = $this->typesMap[$structure->type] ?? self::TYPE_UNKNOWN;

        if (!isset($this->encodingsMap[$structure->encoding])) {
            throw new UnexpectedEncodingException(\sprintf('Cannot decode "%s"', $structure->encoding));
        }

        $this->encoding = $this->encodingsMap[$structure->encoding];
        $this->subtype = $structure->subtype;

        foreach (['disposition', 'bytes', 'description'] as $optional) {
            if (isset($structure->{$optional})) {
                $this->{$optional} = $structure->{$optional};
            }
        }

        $this->parameters = new Parameters();
        if ($structure->ifparameters) {
            $this->parameters->add($structure->parameters);
        }

        if ($structure->ifdparameters) {
            $this->parameters->add($structure->dparameters);
        }

        // When the message is not multipart and the body is the attachment content
        // Prevents infinite recursion
        if ($this->isAttachment($structure) && !$this instanceof Attachment) {
            $this->parts[] = new Attachment($this->resource, $this->messageNumber, '1', $structure);
        }

        if (isset($structure->parts)) {
            foreach ($structure->parts as $key => $partStructure) {
                $partNumber = (!$this instanceof Message) ? $this->partNumber . '.' : '';
                $partNumber .= (string) ($key + 1);

                $newPartClass = $this->isAttachment($partStructure)
                    ? Attachment::class
                    : SimplePart::class
                ;

                $this->parts[] = new $newPartClass($this->resource, $this->messageNumber, $partNumber, $partStructure);
            }
        }
    }

    /**
     * Get an array of all parts for this message.
     *
     * @return PartInterface[]
     */
    final public function getParts(): array
    {
        return $this->parts;
    }

    /**
     * Get current child part.
     *
     * @return mixed
     */
    final public function current()
    {
        return $this->parts[$this->key];
    }

    /**
     * Get current child part.
     *
     * @return mixed
     */
    final public function getChildren()
    {
        return $this->current();
    }

    /**
     * Get current child part.
     *
     * @return bool
     */
    final public function hasChildren()
    {
        return \count($this->parts) > 0;
    }

    /**
     * Get current part key.
     *
     * @return int
     */
    final public function key()
    {
        return $this->key;
    }

    /**
     * Move to next part.
     *
     * @return int
     */
    final public function next()
    {
        ++$this->key;
    }

    /**
     * Reset part key.
     *
     * @return int
     */
    final public function rewind()
    {
        $this->key = 0;
    }

    /**
     * Check if current part is a valid one.
     *
     * @return bool
     */
    final public function valid()
    {
        return isset($this->parts[$this->key]);
    }

    /**
     * Check if the given part is an attachment.
     *
     * @param \stdClass $part
     *
     * @return bool
     */
    private function isAttachment(\stdClass $part): bool
    {
        // Attachment with correct Content-Disposition header
        if ($part->ifdisposition) {
            if (
                    ('attachment' === \strtolower($part->disposition) || 'inline' === \strtolower($part->disposition))
                && self::SUBTYPE_PLAIN !== \strtoupper($part->subtype)
                && self::SUBTYPE_HTML !== \strtoupper($part->subtype)
            ) {
                return true;
            }
        }

        // Attachment without Content-Disposition header
        if ($part->ifparameters) {
            foreach ($part->parameters as $parameter) {
                if ('name' === \strtolower($parameter->attribute) || 'filename' === \strtolower($parameter->attribute)) {
                    return true;
                }
            }
        }

        /*
        if ($part->ifdparameters) {
            foreach ($part->dparameters as $parameter) {
                if ('name' === strtolower($parameter->attribute) || 'filename' === strtolower($parameter->attribute)) {
                    return true;
                }
            }
        }
        */

        return false;
    }
}

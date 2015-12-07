<?php
/**
 * Created by PhpStorm.
 * User: Artem Evsin
 * Company: Cofis CZ
 * Date: 7.12.15
 * Time: 8:31
 */

namespace Ddeboer\Imap\Message;


class EmbeddedAttachment
{

    private $contentType;
    private $filename;
    private $encoding;
    private $disposition;
    private $content;

    public function __construct($messagePart)
    {
        $this->contentType = $this->parseContentType($messagePart);
        $this->filename = $this->parseFilename($messagePart);
        $this->encoding = $this->parseEncoding($messagePart);
        $this->disposition = $this->parseDisposition($messagePart);
        $this->content = $this->parseContent($messagePart);
    }

    public function isEmbeddedMessage()
    {
        return false;
    }

    public function isValid()
    {
        return $this->disposition === "attachment";
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getSize()
    {
        return mb_strlen($this->content, $this->encoding);
    }

    public function getDecodedContent()
    {
        switch ($this->encoding) {
            case "base64":
                return base64_decode($this->content);
                break;
            default:
                throw new \UnexpectedValueException("Cannot get decoded content");
        }
    }

    private function parseContentType($messagePart)
    {
        return $this->parse($messagePart, "/Content\\-Type: (.*?);/s");
    }

    private function parseFilename($messagePart)
    {
        return $this->parse($messagePart, "/filename=\"(.*?)\"/s");
    }

    private function parseEncoding($messagePart)
    {
        return $this->parse($messagePart, "/Content-Transfer-Encoding: (.*?)\r\n/s");
    }

    private function parseDisposition($messagePart)
    {
        return $this->parse($messagePart, "/Content-Disposition: (.*?);/s");
    }

    private function parseContent($messagePart)
    {
        return $this->parse($messagePart, "/filename=\"(?:.*?)\"(.*)/s");
    }

    private function parse($messagePart, $pattern) {
        preg_match($pattern, $messagePart, $matches);
        return trim($matches[1]);
    }


}
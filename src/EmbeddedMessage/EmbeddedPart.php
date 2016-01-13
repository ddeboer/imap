<?php
namespace Ddeboer\Imap\EmbeddedMessage;

/**
 * Class EmbeddedPart
 * @author Artem Evsin <artem@evsin.cz>
 * @author Cofis CZ <www.cofis.cz>
 * @package Ddeboer\Imap\EmbeddedMessage
 */
class EmbeddedPart
{
    /**
     * Raw content of part
     *
     * @var string
     */
    protected $partContent;

    /**
     * Content-Type
     *
     * @var bool|string
     */
    protected $contentType;

    /**
     * Boundary
     *
     * @var bool|string
     */
    protected $boundary;

    /**
     * Charset
     *
     * @var bool|string
     */
    protected $charset;

    /**
     * Content-Transfer-Encoding
     *
     * @var bool|string
     */
    protected $contentTransferEncoding;

    /**
     * Name parsed from part
     *
     * @var bool|string
     */
    protected $name;

    /**
     * Disposition
     *
     * @var string
     */
    protected $disposition;

    /**
     * Filename
     *
     * @var bool|string
     */
    protected $filename;

    /**
     * Content
     *
     * @var string
     */
    protected $content;

    /**
     * @param $partContent
     */
    public function __construct($partContent)
    {
        $this->partContent = $partContent;

        $this->contentType = $this->parseContentType();
        $this->boundary = $this->parseBoundary();
        $this->charset = $this->parseCharset();
        $this->contentTransferEncoding = $this->parseContentTransferEncoding();


        $this->name = $this->parseName();
        $this->disposition = $this->parseDisposition();
        $this->filename = $this->parseFilename();

        $this->content = $this->parseContent();

        //handle missing disposition for attachments
        $attachmentsTypes = [
            'application/octet-stream'
        ];
        if (!$this->disposition && in_array($this->contentType, $attachmentsTypes)) {
            $this->disposition = "attachment";
        }
    }

    /**
     * Get Content-Type
     *
     * @return bool|string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Get Boundary
     *
     * @return bool|string
     */
    public function getBoundary()
    {
        return $this->boundary;
    }

    /**
     * Get Charset
     *
     * @return bool|string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Get Content-Transfer-Encoding
     *
     * @return bool|string
     */
    public function getContentTransferEncoding()
    {
        return $this->contentTransferEncoding;
    }

    /**
     * Get Name
     *
     * @return bool|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get Disposition
     *
     * @return bool|string
     */
    public function getDisposition()
    {
        return $this->disposition;
    }

    /**
     * Get Filename
     *
     * @return bool|string
     */
    public function getFilename()
    {
        $filename = imap_utf8($this->filename);
        return $filename;
    }

    /**
     * Get Content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get Decoded Content
     *
     * @return string
     */
    public function getDecodedContent()
    {
        return $this->decode($this->content);
    }

    /**
     * Check if current part is embedded message
     *
     * @return bool
     */
    public function isEmbeddedMessage()
    {
        return $this->disposition === "message";
    }

    /**
     * Decode given string with encoding in current string and convert it to UTF-8 from part charset
     *
     * @param $string
     * @return string
     */
    private function decode($string)
    {
        if (!$string) {
            return $string;
        }

        //transfer encoding
        switch (strtolower($this->contentTransferEncoding)) {
            case "7bit":
                $decodedString = mb_convert_encoding($string, "UTF-8", "auto");
                break;
            case "8bit":
                $decodedString = imap_8bit($string);
                break;
            case "binary":
                $decodedString = imap_base64(imap_binary($string));
                break;
            case "base64":
                $decodedString = imap_base64($string);
                break;
            case "quoted-printable":
                $decodedString = imap_qprint($string);
                break;
            default:
                throw new \UnexpectedValueException('Cannot decode ' . $this->contentTransferEncoding);
        }

        //do not convert if string is attachment content
        if ($this->disposition == "attachment") {
            return $decodedString;
        }

        //charset encoding
        //TODO add different charsets
        $decodedString = quoted_printable_decode($decodedString);

        if (in_array($this->charset, ['windows-1250', 'koi8-r'])) {
            return iconv(strtoupper($this->charset), "UTF-8", $decodedString);
        } else {
            return mb_convert_encoding($decodedString, "UTF-8", strtoupper($this->charset));
        }
    }


    /**
     * Extract Content-Type from raw part content
     *
     * @return bool|string
     */
    private function parseContentType()
    {
        return $this->getRegexPart($this->partContent, "/Content-Type: (.*?)(?:;| )/s");
    }

    /**
     * Extract Boundary from raw part content
     *
     * @return bool|string
     */
    private function parseBoundary()
    {
        return $this->getRegexPart($this->partContent, "/boundary=(.*?)(?: |\n|;)/m");
    }

    /**
     * Extract Charset from raw part content
     *
     * @return bool|string
     */
    private function parseCharset()
    {
        return $this->getRegexPart($this->partContent, "/charset=(.*?)(?:;|\n)/s");
    }

    /**
     * Extract Content-Transfer-Encoding from raw part content
     *
     * @return bool|string
     */
    private function parseContentTransferEncoding()
    {
        return $this->getRegexPart($this->partContent, "/Content-Transfer-Encoding:(.*?)(?:;|\n)/s");
    }


    /**
     * Extract Name from raw part content
     *
     * @return bool|string
     */
    private function parseName()
    {
        return $this->getRegexPart($this->partContent, "/(?:;| )name=(.*?)(?: |;|\n)/s");
    }

    /**
     * Extract Disposition from raw part content
     *
     * @return bool|string
     */
    private function parseDisposition()
    {
        return $this->getRegexPart($this->partContent, "/Content-Disposition: (.*?)(?:;| )/s");
    }

    /**
     * Extract Filename from raw part content
     *
     * @return bool|string
     */
    private function parseFilename()
    {
        return $this->getRegexPart($this->partContent, "/filename=\"(.*?)\"/s");
    }

    /**
     * Extract Content from raw part content
     *
     * @return string
     */
    private function parseContent()
    {
        $patterns = [
            "/^(Content-.*)/m",
            "/^( .*)/m",
            "/^(X-.*)/m",
            "/^(Return-.*)/m",
            "/^(Received:.*)/m",
            "/^(Resent-From:.*)/m",
            "/^(Authentication-Results:.*)/m",
            "/^(Received-SPF:.*)/m",
            "/^(From:.*)/m",
            "/^(Subject:.*)/m",
            "/^(To:.*)/m",
            "/^(CC:.*)/m",
            "/^(MIME-Version:.*)/m",
            "/^(Reply-To:.*)/m",
            "/^(Organization:.*)/m",
            "/^(Disposition-Notification-To:.*)/m",
            "/^(Return-Receipt-To:.*)/m",
            "/^(Date:.*)/m",
            "/^(Message-ID:.*)/m",
            "/^(SpamDiagnostic.*)/m",
        ];
        $trim = trim(preg_replace($patterns, "", $this->partContent));
        return $trim;
    }


    /**
     * Common method to find something by regular pattern
     *
     * @param $string
     * @param $pattern
     * @return bool|string
     */
    private function getRegexPart($string, $pattern)
    {
        preg_match($pattern, $string, $match);
        return isset($match[1]) && trim($match[1]) ? trim(str_replace(['"'], "", $match[1])) : false;
    }
}

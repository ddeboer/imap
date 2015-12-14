<?php
/**
 * Created by PhpStorm.
 * User: Artem Evsin
 * Company: Cofis CZ
 * Date: 7.12.15
 * Time: 8:31
 */

namespace Ddeboer\Imap\EmbeddedMessage;

/**
 * Class EmbeddedAttachment
 * @author Artem Evsin <artem.evsin@cofis.cz>
 * @package Ddeboer\Imap\EmbeddedMessage
 */
class EmbeddedAttachment extends EmbeddedPart
{

    /**
     * @param $contentType
     * @param $filename
     * @param $encoding
     * @param $content
     */
    public function __construct($contentType, $filename, $encoding, $content)
    {
        $this->contentType = $contentType;
        $this->filename = $filename;
        $this->contentTransferEncoding = $encoding;
        $this->content = $content;
        $this->disposition = "attachment";

        //handle missing filename
        if (!$this->filename && $this->name) {
            $this->filename = $this->name;
        }
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Get size of attachment
     *
     * @return int
     */
    public function getSize()
    {
        return mb_strlen($this->content, $this->contentTransferEncoding);
    }
}

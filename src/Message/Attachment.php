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
        $fname =  $this->parameters->get('filename')?: $this->parameters->get('name');
        //RFCs 2047, 2231 and 5987
        //http://tools.ietf.org/html/rfc5987
        $marker = "UTF-8''";
        if(stripos($fname,$marker) ===0){
            $fname = substr($fname,strlen($marker));//no mb!
            $fname = urldecode($fname);
        }
        return $fname;
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

<?php

namespace Ddeboer\Imap\Message;

use Ddeboer\Transcoder\Transcoder;

use Exception;

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
        /*return $this->parameters->get('filename')
            ?: $this->parameters->get('name');*/

        $fname =  $this->parameters->get('filename')?: $this->parameters->get('name');
        //RFCs 2047, 2231 and 5987
        //http://tools.ietf.org/html/rfc5987


        $matches = [];
        if(preg_match("@^([^']+)''(.*)@ui",$fname,$matches)){

            $fname = urldecode($matches[2]);


            try{
                $fname = Transcoder::create()->transcode(
                    $fname,
                    $matches[1],
                    "UTF-8//IGNORE"
                );
            }catch(Exception $e){
                $fname = 'filename_wrong_enc';
            }
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

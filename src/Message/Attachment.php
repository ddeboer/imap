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
        $fname =  $this->parameters->get('filename')?: $this->parameters->get('name');
        //RFCs 2047, 2231 and 5987
        //http://tools.ietf.org/html/rfc5987

        $markers = ["UTF-8''","windows-1251''"];
        $found = false;
        foreach($markers as $marker){

            $found = (stripos($fname,$marker) === 0);
            if($found){
                $fname = substr($fname,strlen($marker));//no mb!
                $fname = urldecode($fname);

            }

            if($found && $marker == "windows-1251''"){

                try{
                    $fname = Transcoder::create()->transcode(
                        $fname,
                        "windows-1251",
                        "UTF-8"
                    );
                }catch(Exception $e){
                    $fname = 'filename_wrong_enc';
                }
            }
            if($found){
                break;
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

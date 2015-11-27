<?php

namespace Ddeboer\Imap;

use Ddeboer\Transcoder\Transcoder;
use Ddeboer\Transcoder\Exception\IllegalCharacterException;
use Exception;

class Parameters
{
    protected $parameters = [];

    public function __construct(array $parameters = [])
    {
        $this->add($parameters);
    }

    public function add(array $parameters = [])
    {
        foreach ($parameters as $parameter) {
            $key = strtolower($parameter->attribute);
            $value = $this->decode($parameter->value);
            $this->parameters[$key] = $value;
        }
    }

    public function get($key)
    {
        if (isset($this->parameters[$key])) {
            return $this->parameters[$key];
        }

        return null;
    }

    public function all()
    {
        return $this->parameters;
    }


    protected function decode($value)
    {
        $decoded = '';
        $parts = imap_mime_header_decode($value);

        if(empty($parts)){
            return $decoded;
        }
        try{
            $encs = [];
            $str = '';
            foreach ($parts as $part) {
                $encs[] = $part->charset;
                $str .= $part->text;
            }
            $encs = array_unique($encs);

            if(count($encs) == 1){

                //Google sometimes split title into several
                //chunks with same encoding,so for correct
                // processing we must concat them before transcoding
                $enc = reset($encs);
                $charset = 'default' == $enc ? 'auto' : $part->charset;
                // imap_utf8 doesn't seem to work properly, so use Transcoder instead
                try{
                    return  Transcoder::create()->transcode($str, $charset);
                }catch(IllegalCharacterException $e){
                    //no warn, itis reality
                    return $decoded;
                }
            }

            //if encoding of parts is diffrent
            //i don see such situation, but keep in mind
            foreach ($parts as $part) {
                $charset = 'default' == $part->charset ? 'auto' : $part->charset;
                // imap_utf8 doesn't seem to work properly, so use Transcoder instead
                try{
                    $decoded .= Transcoder::create()->transcode($part->text, $charset);
                }catch(IllegalCharacterException $e){
                    //no warn, itis reality
                }

            }
        }catch(Exception $e){};

        return $decoded;
    }
}

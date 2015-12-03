<?php

namespace Ddeboer\Imap;

use Ddeboer\Transcoder\Transcoder;
use Ddeboer\Transcoder\Exception\IllegalCharacterException;
use Ddeboer\Transcoder\Exception\UndetectableEncodingException;
use Exception;

class Parameters
{
    protected $parameters = [];
    /* @var string Ajout COGIVEA fix encoding */
    protected $charset='';

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
        try{
        foreach ($parts as $part) {
            $charset = 'default' == $part->charset ? 'auto' : $part->charset;
            //Ajout COGIVEA fix encoding :
            $charset = ($this->charset!='' && $charset=='auto') ? $this->charset : $charset;
            //print $part->text."#".$this->charset."/".$charset."#"."\n";
            // imap_utf8 doesn't seem to work properly, so use Transcoder instead
            // Got from: https://github.com/Sawered/imap/commit/e739b7221c6e57521b38f7b56f78ba399acda888 and changed to UndetectableEncodingException
            try{
                $decoded .= Transcoder::create()->transcode($part->text, $charset);
            }catch(IllegalCharacterException $e){
                //no warn, itis reality
            //FIX COGIVEA
            } catch(UndetectableEncodingException $e){
                //no warn, it is reality, handle it somehow
                $decoded = imap_utf8($part->text);
            }
        }
        }catch(Exception $e){};

        return $decoded;
    }

    /**
     * COGIVEA : FIX encoding
     * @param type $value
     */
    public function setCharset($value)
    {
      if($value!="" && $value!="charset") {
        $this->charset=$value;
      }
    }
}

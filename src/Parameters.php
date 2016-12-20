<?php

namespace Ddeboer\Imap;

use Ddeboer\Transcoder\Transcoder;

class Parameters
{
    protected $parameters = [];
    
    public function __construct(array $parameters = [])
    {
        $this->add($parameters);
    }
    
    public function add(array $parameters = [])
    {
        foreach ($parameters as &$parameter) {
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
    
    protected function decode($value)
    {
        $decoded = '';
        $parts = imap_mime_header_decode($value);
        foreach ($parts as &$part) {
            $charset = 'default' == $part->charset ? 'auto' : $part->charset;
            // imap_utf8 doesn't seem to work properly, so use Transcoder instead
            $decoded .= Transcoder::create()->transcode($part->text, $charset);
        }
        
        return $decoded;
    }
}

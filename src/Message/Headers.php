<?php

namespace Ddeboer\Imap\Message;

use Ddeboer\Transcoder\Transcoder;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Collection of message headers
 */
class Headers extends ArrayCollection
{
    /**
     * Constructor
     *
     * @param \stdClass $headers
     */
    public function __construct(\stdClass $headers)
    {
        // Store all headers as lowercase
        $headers = array_change_key_case((array) $headers);

        foreach ($headers as $key => $value) {
            $headers[$key] = $this->parseHeader($key, $value);
        }
        
        parent::__construct($headers);
    }

    /**
     * Get header
     * 
     * @param string $key
     *
     * @return string
     */
    public function get($key)
    {
        return parent::get(strtolower($key));
    }
    
    private function parseHeader($key, $value)
    {
        switch ($key) {
            case 'msgno':
                return (int)$value;
            case 'answered':
                // no break
            case 'deleted':
                // no break
            case 'draft':
                // no break
            case 'unseen':
                return (bool)trim($value);
            case 'date':
                $value = $this->decodeHeader($value);
                $value = preg_replace('/([^\(]*)\(.*\)/', '$1', $value);

                return new \DateTime($value);
            case 'from':
                return $this->decodeEmailAddress(current($value));
            case 'to':
                // no break
            case 'cc':
                $emails = [];
                foreach ($value as $address) {
                    $emails[] = $this->decodeEmailAddress($address);
                }
            
                return $emails;
            case 'subject':
                return $this->decodeHeader($value);
            default:
                return $value;
        }
    }
       
    private function decodeHeader($header)
    {
        $decoded = '';
        $parts = imap_mime_header_decode($header);
        foreach ($parts as $part) {
            $charset = 'default' == $part->charset ? 'auto' : $part->charset;
            // imap_utf8 doesn't seem to work properly, so use Transcoder instead
            $decoded .= Transcoder::create()->transcode($part->text, $charset);
        }
        
        return $decoded;
    }

    private function decodeEmailAddress($value)
    {
        return new EmailAddress(
            $value->mailbox,
            isset($value->host) ? $value->host : null,
            isset($value->personal) ? $this->decodeHeader($value->personal) : null
        );
    }
}

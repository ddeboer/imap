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
        $array = array_change_key_case((array) $headers);

        // Transcode subject to UTF-8 if needed
        if (isset($headers->subject)) {
            $subject = '';
            foreach (\imap_mime_header_decode($headers->subject) as $part) {
                // $part->charset can also be 'default', i.e. plain US-ASCII
                $charset = $part->charset == 'default' ? 'auto' : $part->charset;
                $subject .= $this->convertToUtf8($part->text, $charset);
            }
            $array['subject'] = $subject;
        }

        $array['msgno'] = (int) $array['msgno'];

        foreach (array('answered', 'deleted', 'draft') as $flag) {
            $array[$flag] = (bool) trim($array[$flag]);
        }

        if (isset($array['date'])) {
            $array['date'] = preg_replace('/([^\(]*)\(.*\)/', '$1', $array['date']);
            $array['date'] = new \DateTime($array['date']);
        }

        if (isset($array['from'])) {
            $from = current($array['from']);
            $array['from'] = new EmailAddress(
                $from->mailbox,
                $from->host,
                isset($from->personal) ? \imap_utf8($from->personal) : null
            );
        }

        if (isset($array['to'])) {
            $recipients = array();
            foreach ($array['to'] as $to) {
                $recipients[] = new EmailAddress(
                    str_replace('\'', '', $to->mailbox),
                    str_replace('\'', '', $to->host),
                    isset($to->personal) ? \imap_utf8($to->personal) : null
                );
            }
            $array['to'] = $recipients;
        } else {
            $array['to'] = array();
        }
        
        parent::__construct($array);
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
    
    private function convertToUtf8($string, $fromCharset)
    {
        return Transcoder::create()->transcode($string, $fromCharset);
    }
}

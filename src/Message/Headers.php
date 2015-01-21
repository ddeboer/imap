<?php

namespace Ddeboer\Imap\Message;

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

        // Decode subject, as it may be UTF-8 encoded
        if (isset($headers->subject)) {
            $subject = '';
            foreach (\imap_mime_header_decode($headers->subject) as $part) {
                $subject .= $this->convertToUtf8($part->text, $part->charset);
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

    /**
     * Convert subject part to UTF-8
     *
     * @param string $text
     * @param string $charset
     *
     * @return string
     */
    protected function convertToUtf8($text, $charset)
    {
        $uppercaseCharset = strtoupper($charset);

        $charsetMap = array(
            'WINDOWS-1257' => 'ISO-8859-13',
            'UTF-7'        => 'UTF7-IMAP',
        );
        if (isset($charsetMap[$uppercaseCharset])) {
            $charset          = $charsetMap[$uppercaseCharset];
            $uppercaseCharset = strtoupper($charset);
        }

        $supportedEncodings = array_map('strtoupper', mb_list_encodings());
        if (!in_array($uppercaseCharset, $supportedEncodings)) {
            if (function_exists('iconv') && !in_array($charset, array('auto', 'default'))) {
                return iconv($charset, 'UTF-8', $text);
            }
            else {
                $charset = 'auto';
            }
        }

        return mb_convert_encoding($text, 'UTF-8', $charset);
    }
}

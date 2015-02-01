<?php

namespace Ddeboer\Imap\Message;

use Ddeboer\Imap\Parameters;

/**
 * Collection of message headers
 */
class Headers extends Parameters
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
            $this->parameters[$key] = $this->parseHeader($key, $value);
        }
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
                $value = $this->decode($value);
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
                return $this->decode($value);
            default:
                return $value;
        }
    }

    private function decodeEmailAddress($value)
    {
        return new EmailAddress(
            $value->mailbox,
            isset($value->host) ? $value->host : null,
            isset($value->personal) ? $this->decode($value->personal) : null
        );
    }
}

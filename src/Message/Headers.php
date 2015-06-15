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
        //"date"
        //"subject"
        //"in_reply_to"
        //"message_id"
        //"toaddress"
        //"to"
        //"fromaddress"
        //"from"
        //"reply_toaddress"
        //"reply_to"
        //"senderaddress"
        //"sender"
        //"recent"
        //"unseen"
        //"flagged"
        //"answered"
        //"deleted"
        //"draft"
        //"msgno"
        //"maildate"
        //"size"
        //"udate"

        // Store all headers as lowercase
        $headers = array_change_key_case((array) $headers);


        //var_dump("headers",$headers);
        foreach ($headers as $key => $value) {
            //var_dump($key);
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
            case 'recent':
                // no break
            case 'flagged':
                // no break
            case 'answered':
                // no break
            case 'deleted':
                // no break
            case 'draft':
                // no break
            case 'unseen':
                return trim($value);
            case 'maildate':
                // no break
            case 'date':
                $value = $this->decode($value);
                $value = preg_replace('/([^\(]*)\(.*\)/', '$1', $value);

                try {
                    return new \DateTime($value);
                }catch(\Exception $e){
                    return new \DateTime(date('Y-m-d H:i:s',0));
                }

            case 'sender':
                //nobreak
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
        $mailbox = property_exists($value,'mailbox')?$value->mailbox:null; //sometimes property is not exists
        return new EmailAddress(
            $mailbox,
            isset($value->host) ? $value->host : null,
            isset($value->personal) ? $this->decode($value->personal) : null
        );
    }
}

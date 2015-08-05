<?php

namespace Ddeboer\Imap\Message;

use Ddeboer\Imap\Parameters;

use DateTime;
/**
 * Collection of message headers
 */
class ExtendedHeaders extends Parameters
{
    /**
     * Constructor
     *
     * @param \stdClass $headers
     */
    public function __construct($headersText)
    {
        $items = self::parse($headersText);


        foreach($items as $k => $item){
            $name = strtolower($item['name']);
            $value = $this->parseHeader($name,$item['value']);

            if(isset($this->parameters[$name])){
               if(!is_array($this->parameters[$name])){
                   $this->parameters[$name] = array($this->parameters[$name]);
               }
               $this->parameters[$name][] = $value;
            }

            if(in_array($name,['received'])){

                $this->parameters[$name][] = $value;
            }else{
                $this->parameters[$name] = $value;
            }
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

    /**
     * Function returns headers hronological from first to latest
     * header values not parsed at all
     *
     * @return array
     **/
    public static function parse($headersText)
    {
        $regexp = "@^([a-zA-Z\-]+):[[:space:]]{1,}@mui";
        $lineFolding = "@[\r\n][[:space:]]{1,}@mui";

        $matches = [];
        if(!preg_match_all($regexp,$headersText,$matches,PREG_OFFSET_CAPTURE)){
            return array();
        }


        $headers = [];
        $end = null;
        $len = strlen($headersText);
        while($match = array_pop($matches[0])){
            $end = is_null($end)?null:$end-$match[1];
            $full_header = mb_substr($headersText,$match[1],$end);

            //cut off line folding
            $value = mb_substr($full_header,strlen($match[0]));
            $value = preg_replace($lineFolding,' ',$value);
            $value = trim($value);

            $headers[] = array(
                'name' => trim($match[0],': '),
                'value' => $value,
            );
            $end = $match[1] ;
        }

        return $headers;
    }

    public function parseHeader($name,$value)
    {
        if($name == 'from'
            || $name == 'sender'
            || $name == 'reply-to'
            || $name == 'to'
            || $name == 'cc'
            || $name == 'bcc'
        ){
            if(strpos($value,'undisclosed-recipients') !==false){
                return null;
            }
            //as alternative we can use mailparse_rfc822_parse_addresses
            $items = imap_rfc822_parse_adrlist($value,'example.com');

            foreach($items as $k =>$item){
                if(!property_exists($item,'host') || $item->host === '.SYNTAX-ERROR.'){
                    unset($items[$k]);
                    continue;
                }
                $items[$k] = $this->decodeEmailAddress($item);
            }

            if($name == 'from'
                || $name == 'sender'
                || $name == 'reply-to'
            ){
                return empty($items)?null:reset($items);
            }
            return $items;
        }

        if($name == 'received'){
            return $this->parseReceivedHeader($value);
        }

        if($name == 'return-path'){
            $value = preg_replace('/.*<([^<>]+)>.*/','$1',$value);
        }

        return $value;
    }

    private function decodeEmailAddress($value)
    {
        $mailbox = property_exists($value,'mailbox')?$value->mailbox:null; //sometimes property is not exists
        $host = property_exists($value,'host') ? $value->host : null;
        $personal = property_exists($value, 'personal') ? $this->decode($value->personal) : null;

        return new EmailAddress(
            $mailbox,
            $host,
            $personal
        );
    }

    protected function parseReceivedHeader($value)
    {
        // received    =  "Received"    ":"            ; one per relay
        //                   ["from" domain]           ; sending host
        //                   ["by"   domain]           ; receiving host
        //                   ["via"  atom]             ; physical path
        //                  *("with" atom)             ; link/mail protocol
        //                   ["id"   msg-id]           ; receiver msg id
        //                   ["for"  addr-spec]        ; initial form
        //                    ";"    date-time         ; time received}

        $result = [];
        if(strpos($value,';') !== false){
            list($value,$date) = explode(';',$value);
            $result['date'] = $this->decodeDate($date);
        }

        $regex = "@([[:space:]](from|via|with|id|by|for)(?:[[:space:]]))@mui";

        $value = ' '.$value; //hint for regexp
        $matches = [];
        if(!preg_match_all($regex,$value,$matches,PREG_OFFSET_CAPTURE)){
            return $result;
        }

        $end = null;
        while($match = array_pop($matches[0])){
            $keyLength = mb_strlen($match[0]);
            $end = is_null($end)?null:$end-$match[1]-$keyLength;
            $part = mb_substr($value,$match[1]+$keyLength,$end);

            $key = trim($match[0]);
            $part = trim($part);

            if($key == 'for'){
                $part = preg_replace('/.*<([^<>]+)>.*/','$1',$part);
            }

            if( isset($result[$key])){
                if(!is_array($result[$key])){
                    $result[$key] = array($result[$key]);
                }
                $result[$key][] = $part;
            }else{
                $result[$key] = $part;
            }

            $end = $match[1] ;
        }

        return $result;
    }

    protected function decodeDate($value)
    {

        $value = $this->decode($value);
        $value =  preg_replace('/([^\(]*)\(.*\)/', '$1', $value);
        return new DateTime($value);
    }
}

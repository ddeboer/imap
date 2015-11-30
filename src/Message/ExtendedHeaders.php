<?php

namespace Ddeboer\Imap\Message;

use Ddeboer\Imap\Parameters;
use Ddeboer\Transcoder\Transcoder;
use Ddeboer\Transcoder\MBTranscoder;

use DateTime;
use Exception;
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
        //некоторые умники не кодируют тему письма, и кодировка заголовков
        //не 7/8 бит и не UTF-8 после парсинга заголовков восстановить
        //кодировку уже не получится, поэтому попытаемся это сделать в самом начале
        $headersText = $this->fixEncoding($headersText);
        $items = self::parse($headersText);

        $multiple = ['received'];
        foreach($items as $k => $item){
            $name = strtolower($item['name']);
            $value = $this->parseHeader($name,$item['value']);

            if(isset($this->parameters[$name])){
               if(!is_array($this->parameters[$name])){
                   $this->parameters[$name] = array($this->parameters[$name]);
               }
               $this->parameters[$name][] = $value;
               continue;
            }

            if(in_array($name,$multiple)){

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
        $regexp = "@^([a-zA-Z\-]+):[[:space:]]{1,}@mi";
        $lineFolding = "@[\r\n][[:space:]]{1,}@mi";

        $matches = [];
        if(!preg_match_all($regexp,$headersText,$matches,PREG_OFFSET_CAPTURE)){
            return array();
        }

        //do not use unicode functions

        $headers = [];
        $end = null;
        $len = strlen($headersText);
        while($match = array_pop($matches[0])){
            $end = is_null($end)?null:$end-$match[1];
            $full_header = substr($headersText,$match[1],$end);

            //cut off line folding
            $value = substr($full_header,strlen($match[0]));
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
            $items = $this->parseAddrList($value);

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

        if($name == 'subject'){
            return $this->decode($value);
        }

        return $value;
    }

    protected function parseAddrList($value)
    {
        $items = imap_rfc822_parse_adrlist($value,'nodomain');

        //as alternative we can use mailparse_rfc822_parse_addresses

        return $items;
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
            list($value,$date) = $this->splitReceivedDate($value);
            if($date){
                $result['date'] = $this->decodeDate($date);
            }
        }

        $regex = "@([[:space:]](from|via|with|id|by|for)(?:[[:space:]]))@mi";

        $value = ' '.$value; //hint for regexp
        $matches = [];
        if(!preg_match_all($regex,$value,$matches,PREG_OFFSET_CAPTURE)){
            return $result;
        }

        $end = strlen($value);
        while($match = array_pop($matches[0])){
            $keyLength = strlen($match[0]);
            $end = is_null($end)?null:$end-$match[1]-$keyLength;
            $part = substr($value,$match[1]+$keyLength,$end);

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

    protected function splitReceivedDate($value)
    {
        $len = strlen($value);
        $lastPos =  strrpos($value,';');
        if($lastPos ===false){
            return [$value,null];
        }

        $date = substr($value,$lastPos+1);
        $other = substr($value,0,$len - 1 - strlen($date) );
        return [$other,$date];
    }

    protected function decodeDate($value)
    {
        $value = $this->decode($value);

        if(empty($value)){
            return null;
        }

        $value =  preg_replace('/([^\(]*)\(.*\)/', '$1', $value);
        $value =  preg_replace('/(UT|UCT)(?!C)/','UTC',$value);

        try{
            return new DateTime($value);
        }catch(Exception $e){
            return null;
        }
    }

    /**
     * Tries to right decode wrong coded header fields like subject
     *
     * @return string
     **/
    public function fixEncoding($content)
    {
        $charset = $this->getCharset($content);

        if(!is_null($charset)){

            try{
                $content =  Transcoder::create()->transcode(
                    $content,
                    $charset,
                    'UTF-8'
                );
                $content = MBTranscoder::removeInvalidUTF8Bytes($content);
            }catch(RuntimeException $e){
                return $content;
            }
        }
        return $content;
    }

    /**
     * tries to find the charset of message
     *
     * @return string
     * @author skoryukin
     **/
    protected function getCharset($content)
    {
        $matches = [];
        $charset = null;

        if(preg_match('@^Content-Type:.*charset=([^;=[:space:]]+)[;]*@mi',$content,$matches)){
            $charset = $matches[1];
        }elseif(preg_match('@^Content.*charset=([^;=[:space:]]+)[;]*@mi',$content,$matches)){
            $charset = $matches[1];
        }

        return $charset;
    }
}

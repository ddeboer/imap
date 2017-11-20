<?php
/**
 * Created by PhpStorm.
 * User: Lukasz
 * Date: 2017-11-10
 * Time: 19:50
 */

namespace Ddeboer\Imap\MailboxesParser;


class MailboxesTreeParser
{
    /**
     * @param ParsedMailbox[] $input
     *
     * @return array
     */
    public function parse($input)
    {
        $newKeys = array_map(
            function ($key, $value) {
                /**
                 * @var ParsedMailbox $value
                 */
                $k = explode($value->getDelimiter(), $value->getMailboxName());
                $newkey = [];
                foreach ($k as $segment) {
                    $newkey[] = $segment;
                    $newkey[] = "subfolders";
                }

                return implode(".", $newkey);
            }, array_keys($input), $input);

        $arrayToParse = [];
        foreach ($newKeys AS $index => $value) {
            $k = explode(".", $value);
            $keyWithoutLast = implode('.', array_splice($k, 0, -1));
            $arrayToParse[$value] = [];
            if ($input[$index]) {
                /** @var ParsedMailbox $parsedMailbox */
                $parsedMailbox = $input[$index];
                $arrayToParse[$keyWithoutLast . '.mailboxName'] = $parsedMailbox->getMailboxName();
                $arrayToParse[$keyWithoutLast . '.name'] = $parsedMailbox->getName();
            }
        }

        $res = [];
        array_walk($arrayToParse, function ($value, $key) use (&$res) {
            $this->set($res, $key, $value);
        });

        return $res;
    }

    private function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        $array[array_shift($keys)] = $value;

        return $array;
    }
}
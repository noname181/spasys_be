<?php

namespace App\Utils;
use DateTime;

class CommonFunc
{
    function renderMessage($msg, $array)
    {

        if ($array) {
            for ($i = 0; $i < count($array); $i++) {
                $msg = str_replace('{' . $i . '}', $array[$i], $msg);
            }
        }
        return $msg;
    }

    function isMail($email)
    {
        $regex = '/^([a-z0-9A-Z](\.?[a-z0-9A-Z]){1,})\@\w+([\.-]?\w+)(\.\w{2,3})+$/';
        if (preg_match($regex, $email)) {
            return true;
        } else {
            return false;
        }
    }


    function generate_w_schedule_number($data,$type)
    {
        $string = 'SPA';
        $string = $string.'_'.date('Ymd').$data.'_'.$type;
        return $string;
    }
}

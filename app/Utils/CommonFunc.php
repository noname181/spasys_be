<?php

namespace App\Utils;
use DateTime;

class CommonFunc
{
    static function renderMessage($msg, $array)
    {

        if ($array) {
            for ($i = 0; $i < count($array); $i++) {
                $msg = str_replace('{' . $i . '}', $array[$i], $msg);
            }
        }
        return $msg;
    }

    static function isMail($email)
    {
        $regex = '/^([a-z0-9A-Z](\.?[a-z0-9A-Z]){1,})\@\w+([\.-]?\w+)(\.\w{2,3})+$/';
        if (preg_match($regex, $email)) {
            return true;
        } else {
            return false;
        }
    }


    static function generate_w_schedule_number($data, $type, $key="")
    {
        $string = 'SPA';
        if($key){
            $string = $string.'_'.date('Ymd').$data.'_'.$key.'_'.$type;
        }else{
            $string = $string.'_'.date('Ymd').$data.'_'.$type;
        }
        
        return $string;
    }

    static function generate_rmd_number($id, $index)
    {
        $string = date('Ymd').$id.'_'.$index;
        return $string;
    }

    static function generate_tax_number($data)
    {
        $string = 'TAX';
      
        $string = $string.'_'.date('Ymd').$data;
        
        return $string;
    }
}

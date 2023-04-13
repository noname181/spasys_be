<?php

namespace App\Utils;
use App\Models\AlarmData;
use App\Models\Alarm;
use App\Models\Member;
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

    static function report_number($data)
    {
        $string = 'PHOTO';
      
        $string = $string.'_'.date('Ymd').$data;
        
        return $string;
    }

    static function insert_alarm($ad_title, $rgd, $user)
    {
        $price = 0;
        $cargo_number = '';

        if($rgd->service_korean_name == '유통가공'){
            $price = $rgd->rate_data_general->rdg_sum4;
            $cargo_number = $rgd->warehousing->w_schedule_number2;

        }else if($rgd->service_korean_name == '보세화물'){
            $price = $rgd->rate_data_general->rdg_sum7;
            $cargo_number = $rgd->t_import_expected->tie_h_bl;
        }else if($rgd->service_korean_name == '수입풀필먼트'){
            $price = $rgd->rate_data_general->rdg_sum6;
            $cargo_number = '0000년 00월';
        }

        $alarm_content = AlarmData::where('ad_title', $ad_title)->first()->ad_content;
        $alarm_content = str_replace('aaaaa', $cargo_number ,$alarm_content);
        $alarm_content = str_replace('bbbbb', $rgd->rgd_settlement_number ,$alarm_content);
        $alarm_content = str_replace('ccccc', $price ,$alarm_content);
        $alarm_content = str_replace('ddddd', str_contains($rgd->rgd_bill_type, 'month') ? '월별 확정청구서로 결제요청 예정입니다.' : '결제를 진행해주세요.' , $alarm_content);

        Alarm::insertGetId(
            [
                'w_no' => $rgd->w_no,
                'mb_no' => $user->mb_no,
                'alarm_content' => $alarm_content,
                'alarm_h_bl' => $cargo_number,
                'alarm_type' => 'auto',
            ]
        );

        if($user->mb_type == 'spasys'){
            $receiver_company = $rgd->warehousing->company->co_parent;
            $receiver_list = Member::where('co_no', $receiver_company->co_no)->where('mb_push_yn', 'y')->get();

            //PUSH FUNCTION HERE

        }else if($user->mb_type == 'shop'){
            $receiver_company = $rgd->warehousing->company;
            $receiver_list = Member::where('co_no', $receiver_company->co_no)->where('mb_push_yn', 'y')->get();

            //PUSH FUNCTION HERE
        }


    }
}

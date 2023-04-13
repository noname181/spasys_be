<?php

namespace App\Utils;
use App\Models\AlarmData;
use App\Models\Alarm;
use App\Models\Member;
use DateTime;
use \Carbon\Carbon;

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

    static function insert_alarm($ad_title, $rgd, $sender, $w_no, $type)
    {
        $ccccc = 0;
        $aaaaa = '';
        $bbbbb = '';
        $ddddd = '';
        $cargo_number = '';

        if($rgd->service_korean_name == '유통가공'){
            $ccccc = $rgd->rate_data_general->rdg_sum4;
            $bbbbb = $rgd->rgd_settlement_number;
            $aaaaa = $rgd->warehousing->w_schedule_number2;
            $ddddd = str_contains($rgd->rgd_bill_type, 'month') ? '월별 확정청구서로 결제요청 예정입니다.' : '결제를 진행해주세요.';
            $cargo_number = $rgd->warehousing->w_schedule_number2;

        }else if($rgd->service_korean_name == '보세화물'){
            $ccccc = $rgd->rate_data_general->rdg_sum7;
            $bbbbb = $rgd->rgd_settlement_number;
            $aaaaa = $rgd->t_import_expected->tie_h_bl;
            $ddddd = str_contains($rgd->rgd_bill_type, 'month') ? '월별 확정청구서로 결제요청 예정입니다.' : '결제를 진행해주세요.';
            $cargo_number = $rgd->t_import_expected->tie_h_bl;

        }else if($rgd->service_korean_name == '수입풀필먼트'){
            $ccccc = $rgd->rate_data_general->rdg_sum6;
            $bbbbb = $rgd->rgd_settlement_number;
            $aaaaa = Carbon::parse($rgd->created_at)->format('Y.m');
            $aaaaa = str_replace('.', '년 ', $aaaaa) .'월';
            $ddddd = str_contains($rgd->rgd_bill_type, 'month') ? '월별 확정청구서로 결제요청 예정입니다.' : '결제를 진행해주세요.';
            $cargo_number = $rgd->warehousing->w_schedule_number_settle;
        }

        $alarm_data = AlarmData::where('ad_title', $ad_title)->first();

        $alarm_content = $alarm_data->ad_content;
        $alarm_content = str_replace('aaaaa', $aaaaa ,$alarm_content);
        $alarm_content = str_replace('bbbbb', $bbbbb ,$alarm_content);
        $alarm_content = str_replace('ccccc', $ccccc ,$alarm_content);
        $alarm_content = str_replace('ddddd', $ddddd , $alarm_content);

        if($type == 'settle_payment'){
            $alarm_type = 'auto';
        }

        Alarm::insertGetId(
            [
                'w_no' => $rgd->w_no,
                'mb_no' => $sender->mb_no,
                'alarm_content' => $alarm_content,
                'alarm_h_bl' => $cargo_number,
                'alarm_type' => $alarm_type,
                'ad_no' => $alarm_data->ad_no,
            ]
        );

        if($alarm_data->ad_must_yn == 'y'){
            if($sender->mb_type == 'spasys'){
                $receiver_company = $rgd->warehousing->company->co_parent;
            }else if($sender->mb_type == 'shop'){
                $receiver_company = $rgd->warehousing->company;
            }
            //ad_must_yn == 'y' send all members of receiver company
            $receiver_list = Member::where('co_no', $receiver_company->co_no)->get();

        }else if($alarm_data->ad_must_yn == 'n'){
            if($sender->mb_type == 'spasys'){
                $receiver_company = $rgd->warehousing->company->co_parent;
            }else if($sender->mb_type == 'shop'){
                $receiver_company = $rgd->warehousing->company;
            }
            //ad_must_yn == 'n' send only members who have mb_push_yn = 'y'
            $receiver_list = Member::where('co_no', $receiver_company->co_no)->where('mb_push_yn', 'y')->get();
        }


        foreach($receiver_list as $receiver){
            //PUSH FUNCTION HERE
        }





    }
}

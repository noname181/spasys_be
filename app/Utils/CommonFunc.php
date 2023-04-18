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


    static function generate_w_schedule_number($data, $type, $key = "")
    {
        $string = 'SPA';
        if ($key) {
            $string = $string . '_' . date('Ymd') . $data . '_' . $key . '_' . $type;
        } else {
            $string = $string . '_' . date('Ymd') . $data . '_' . $type;
        }

        return $string;
    }

    static function generate_rmd_number($id, $index)
    {
        $string = date('Ymd') . $id . '_' . $index;
        return $string;
    }

    static function generate_tax_number($data)
    {
        $string = 'TAX';

        $string = $string . '_' . date('Ymd') . $data;

        return $string;
    }

    static function report_number($data)
    {
        $string = 'PHOTO';

        $string = $string . '_' . date('Ymd') . $data;

        return $string;
    }

    static function insert_alarm($ad_title, $rgd, $sender, $w_no, $type)
    {
        $ccccc = 0;
        $aaaaa = '';
        $bbbbb = '';
        $ddddd = '';
        $cargo_number = '';

        if ($rgd->service_korean_name == '유통가공') {
            $ccccc = $rgd->rate_data_general->rdg_sum4;
            $bbbbb = $rgd->rgd_settlement_number;
            $aaaaa = $rgd->warehousing->w_schedule_number2;
            $ddddd = str_contains($rgd->rgd_bill_type, 'month') ? '월별 확정청구서로 결제요청 예정입니다.' : '결제를 진행해주세요.';
            $cargo_number = $rgd->warehousing->w_schedule_number2;
        } else if ($rgd->service_korean_name == '보세화물') {
            $ccccc = $rgd->rate_data_general->rdg_sum7;
            $bbbbb = $rgd->rgd_settlement_number;
            $aaaaa = $rgd->t_import_expected->tie_h_bl;
            $ddddd = str_contains($rgd->rgd_bill_type, 'month') ? '월별 확정청구서로 결제요청 예정입니다.' : '결제를 진행해주세요.';
            $cargo_number = $rgd->t_import_expected->tie_h_bl;
        } else if ($rgd->service_korean_name == '수입풀필먼트') {
            $ccccc = $rgd->rate_data_general->rdg_sum6;
            $bbbbb = $rgd->rgd_settlement_number;
            $aaaaa = Carbon::parse($rgd->created_at)->format('Y.m');
            $aaaaa = str_replace('.', '년 ', $aaaaa) . '월';
            $ddddd = str_contains($rgd->rgd_bill_type, 'month') ? '월별 확정청구서로 결제요청 예정입니다.' : '결제를 진행해주세요.';
            $cargo_number = $rgd->warehousing->w_schedule_number_settle;
        }

        if($ad_title == '[공통] 계산서발행 안내' || $ad_title == '[공통] 계산서취소 안내'){
            $aaaaa = $rgd->rgd_settlement_number;
        }

        $alarm_data = AlarmData::where('ad_title', $ad_title)->first();

        $alarm_content = $alarm_data->ad_content;
        $alarm_content = str_replace('aaaaa', $aaaaa, $alarm_content);
        $alarm_content = str_replace('bbbbb', $bbbbb, $alarm_content);
        $alarm_content = str_replace('ccccc', $ccccc, $alarm_content);
        $alarm_content = str_replace('ddddd', $ddddd, $alarm_content);

        if ($type == 'settle_payment') {
            $alarm_type = 'auto';
        }



        if ($alarm_data->ad_must_yn == 'y') {
            if($rgd->service_korean_name == '수입풀필먼트'){
                $receiver_company = $rgd->warehousing->company;
            }else if ($sender->mb_type == 'spasys') {
                $receiver_company = $rgd->warehousing->company->co_parent;
            } else if ($sender->mb_type == 'shop') {
                $receiver_company = $rgd->warehousing->company;
            }
            //ad_must_yn == 'y' send all members of receiver company
            $receiver_list = Member::where('co_no', $receiver_company->co_no)->get();
        } else if ($alarm_data->ad_must_yn == 'n') {
            if($rgd->service_korean_name == '수입풀필먼트'){
                $receiver_company = $rgd->warehousing->company;
            }else if ($sender->mb_type == 'spasys') {
                $receiver_company = $rgd->warehousing->company->co_parent;
            } else if ($sender->mb_type == 'shop') {
                $receiver_company = $rgd->warehousing->company;
            }
            //ad_must_yn == 'n' send only members who have mb_push_yn = 'y'
            $receiver_list = Member::where('co_no', $receiver_company->co_no)->where('mb_push_yn', 'y')->get();
        }


        foreach ($receiver_list as $receiver) {
            //INSERT ALARM FOR RECEIVER LIST USER
            Alarm::insertGetId(
                [
                    'w_no' => $rgd->w_no,
                    'mb_no' => $sender->mb_no,
                    'receiver_no' => $receiver->mb_no,
                    'alarm_content' => $alarm_content,
                    'alarm_h_bl' => $cargo_number,
                    'alarm_type' => $alarm_type,
                    'ad_no' => $alarm_data->ad_no,
                ]
            );

            //PUSH FUNCTION HERE
        }
    }

    static function insert_alarm_cargo($ad_title, $rgd, $sender, $w_no, $type)
    {
        $ccccc = 0;
        $aaaaa = '';
        $bbbbb = '';
        $ddddd = '';
        $cargo_number = '';

        if ($w_no->w_category_name == '유통가공') {
            if ($type == 'cargo_IW') {
                $status2  = isset($w_no->receving_goods_delivery[0]->rgd_status2) ? $w_no->receving_goods_delivery[0]->rgd_status2 : null;
                if ($status2 == "작업완료") {
                    $aaaaa = $w_no->w_schedule_number2;
                } else {
                    $aaaaa = $w_no->w_schedule_number;
                }

                $bbbbb = $w_no->w_schedule_amount;
                $ccccc = $w_no->w_amount;
                $ddddd = $w_no->w_schedule_amount - $w_no->w_amount;

                $cargo_number = $w_no->w_schedule_number;
            } else {

                $aaaaa = $w_no->w_import_parent->w_schedule_number2;

                $bbbbb = $w_no->receving_goods_delivery[0]->rgd_delivery_schedule_day;

                $bbbbb = str_replace('-', '.', $bbbbb);

                $cargo_number = $w_no->w_schedule_number;
            }
        } else if ($w_no->w_category_name == '보세화물') {
        } else if ($w_no->w_category_name == '수입풀필먼트') {
        }

        $alarm_data = AlarmData::where('ad_title', $ad_title)->first();

        $alarm_content = $alarm_data->ad_content;
        $alarm_content = str_replace('aaaaa', $aaaaa, $alarm_content);
        $alarm_content = str_replace('bbbbb', $bbbbb, $alarm_content);
        $alarm_content = str_replace('ccccc', $ccccc, $alarm_content);
        $alarm_content = str_replace('ddddd', $ddddd, $alarm_content);

        if ($type == 'cargo_IW') {
            $alarm_type = 'cargo_IW';
        }else{
            $alarm_type = 'cargo_EW';
        }



        if ($alarm_data->ad_must_yn == 'y') {
            if ($sender->mb_type == 'spasys') {
                $receiver_company = $w_no->company->co_parent;
            } else if ($sender->mb_type == 'shop') {
                $receiver_company = $w_no->company;
            } else if ($sender->mb_type == 'shipper') {
                $receiver_company = $w_no->company;
                $receiver_company_parent = $w_no->company->co_parent;
            }
            //ad_must_yn == 'y' send all members of receiver company
            if(isset($receiver_company_parent)){
                $receiver_list = Member::where('co_no', $receiver_company->co_no)->orwhere('co_no', $receiver_company_parent->co_no)->get();
            }else{
                $receiver_list = Member::where('co_no', $receiver_company->co_no)->get();
            }
        } else if ($alarm_data->ad_must_yn == 'n') {
            if ($sender->mb_type == 'spasys') {
                $receiver_company = $w_no->company->co_parent;
            } else if ($sender->mb_type == 'shop') {
                $receiver_company = $w_no->company;
            } else if ($sender->mb_type == 'shipper') {
                $receiver_company = $w_no->company;
                $receiver_company_parent = $w_no->company->co_parent;
            }
            //ad_must_yn == 'n' send only members who have mb_push_yn = 'y'
            if(isset($receiver_company_parent)){
                $receiver_list = Member::where('co_no', $receiver_company->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_company_parent->co_no)->where('mb_push_yn', 'y')->get();
            }else{
                $receiver_list = Member::where('co_no', $receiver_company->co_no)->where('mb_push_yn', 'y')->get();
            }
        }


        foreach ($receiver_list as $receiver) {

            //INSERT ALARM FOR RECEIVER LIST USER
            Alarm::insertGetId(
                [
                    'w_no' => $w_no->w_no,
                    'mb_no' => $sender->mb_no,
                    'receiver_no' => $receiver->mb_no,
                    'alarm_content' => $alarm_content,
                    'alarm_h_bl' => $cargo_number,
                    'alarm_type' => $alarm_type,
                    'ad_no' => $alarm_data->ad_no,
                ]
            );

            //PUSH FUNCTION HERE
        }
    }
}

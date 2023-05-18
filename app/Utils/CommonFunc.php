<?php

namespace App\Utils;

use App\Models\AlarmData;
use App\Models\Alarm;
use App\Models\Member;
use App\Models\Company;
use App\Models\Warehousing;
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
        $string = date('Ymd') . $id;
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

    static function insert_alarm($ad_title, $rgd, $sender, $w_no, $type, $price)
    {
        $ccccc = 0;
        $aaaaa = '';
        $bbbbb = '';
        $ddddd = '';
        $cargo_number = '';

        if ($type == 'settle_payment') {
            $alarm_type = 'auto';
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
        } else if ($type == 'update_company') {
            $alarm_type = 'update_company';
            $aaaaa = $rgd->co_name;
            $bbbbb = $rgd->co_license;
            $ccccc = '휴폐업';
        }


        //SPECIFIC CASE
        if ($ad_title == '[공통] 계산서발행 안내' || $ad_title == '[공통] 계산서취소 안내') {
            $aaaaa = $rgd->rgd_settlement_number;
        }

        if ($price != null) {
            $ccccc = $price;
            $aaaaa = $rgd->rgd_status4;
        }

        //END SPECIFIC CASE

        $alarm_data = AlarmData::where('ad_title', $ad_title)->first();

        $alarm_content = $alarm_data->ad_content;
        $alarm_content = str_replace('aaaaa', $aaaaa, $alarm_content);
        $alarm_content = str_replace('bbbbb', $bbbbb, $alarm_content);
        $alarm_content = str_replace('ccccc', $ccccc, $alarm_content);
        $alarm_content = str_replace('ddddd', $ddddd, $alarm_content);

        $receiver_list = null;
        if ($type == 'settle_payment') {
            if ($alarm_data->ad_must_yn == 'y') {
                if ($rgd->service_korean_name == '수입풀필먼트') {
                    $receiver_company = $rgd->warehousing->company;
                } else if ($sender->mb_type == 'spasys') {
                    $receiver_company = $rgd->warehousing->company->co_parent;
                } else if ($sender->mb_type == 'shop') {
                    $receiver_company = $rgd->warehousing->company;
                }
                //ad_must_yn == 'y' send all members of receiver company
                $receiver_list = Member::where('co_no', $receiver_company->co_no)->get();
            } else if ($alarm_data->ad_must_yn == 'n') {
                if ($rgd->service_korean_name == '수입풀필먼트') {
                    $receiver_company = $rgd->warehousing->company;
                } else if ($sender->mb_type == 'spasys') {
                    $receiver_company = $rgd->warehousing->company->co_parent;
                } else if ($sender->mb_type == 'shop') {
                    $receiver_company = $rgd->warehousing->company;
                }
                //ad_must_yn == 'n' send only members who have mb_push_yn = 'y'
                $receiver_list = Member::where('co_no', $receiver_company->co_no)->where('mb_push_yn', 'y')->get();
            }
        } else if ($type == 'update_company' && $rgd->contract->c_transaction_yn == 'c') {

            //SPASYS UPDATE SHOP
            if ($sender->mb_type == 'spasys' && $rgd->co_type == 'shop') {
                $receiver_list = Member::where(function ($q) use ($sender, $rgd) {
                    $q->where('co_no', $sender->co_no)
                        ->orwhere('co_no', $rgd->co_no)
                        ->orwherehas('company.co_parent', function ($q) use ($sender, $rgd) {
                            $q->where('co_no', $rgd->co_no);
                        });
                });
                //SPASYS UPDATE SHIPPER
            } else if ($sender->mb_type == 'spasys' && $rgd->co_type == 'shipper') {
                $receiver_list = Member::where(function ($q) use ($sender, $rgd) {
                    $q->where('co_no', $sender->co_no)
                        ->orwhere('co_no', $rgd->co_no)
                        ->orwhere('co_no', $rgd->co_parent_no);
                });
                //SHOP UPDATE SHIPPER
            } else if ($sender->mb_type == 'shop' && $rgd->co_type == 'shipper') {
                $receiver_list = Member::where(function ($q) use ($sender, $rgd) {
                    $shop = Company::with(['co_parent'])->where('co_no', $sender->co_no)->first();

                    $q->where('co_no', $sender->co_no)
                        ->orwhere('co_no', $rgd->co_no)
                        ->orwhere('co_no', $shop->co_parent_no);
                });
            }

            if ($alarm_data->ad_must_yn == 'y') {
                $receiver_list =  $receiver_list->get();
            } else {
                $receiver_list = $receiver_list->where('co_push_yn', 'y')->get();
            }
        }


        foreach ($receiver_list as $receiver) {
            //INSERT ALARM FOR RECEIVER LIST USER
            if ($type == 'settle_payment') {
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
            } else if ($type == 'update_company') {
                Alarm::insertGetId(
                    [
                        'w_no' => null,
                        'mb_no' => $sender->mb_no,
                        'co_no' => $rgd->co_no,
                        'receiver_no' => $receiver->mb_no,
                        'alarm_content' => $alarm_content,
                        'alarm_h_bl' => null,
                        'alarm_type' => $alarm_type,
                        'ad_no' => $alarm_data->ad_no,
                    ]
                );
            }

            //PUSH FUNCTION HERE
        }
    }

    static function insert_alarm_cargo_api_service1($ad_title, $rgd, $sender, $w_no, $type)
    {
        $ccccc = 0;
        $aaaaa = '';
        $bbbbb = '';
        $ddddd = '';
        $cargo_number = '';
        $w_no_save = '';

        if ($type == 'cargo_TIE') {
            $aaaaa = $w_no['h_bl'];
            $cargo_number = $w_no['h_bl'];
            $w_no_save = $w_no['h_bl'];
        } else if ($type == 'cargo_TI') {
            $aaaaa = $w_no['h_bl'];
            $cargo_number = $w_no['h_bl'];
            $w_no_save = 'in_' . $w_no['carry_in_number'];
        } else if ($type == 'cargo_TE') {
            $aaaaa = $w_no['h_bl'];
            $cargo_number = $w_no['h_bl'];
            $w_no_save = 'out_' . $w_no['carry_out_number'];
        }
        $alarm_data = AlarmData::where('ad_title', $ad_title)->first();

        $alarm_content = $alarm_data->ad_content;
        $alarm_content = str_replace('aaaaa', $aaaaa, $alarm_content);
        $alarm_content = str_replace('bbbbb', $bbbbb, $alarm_content);
        $alarm_content = str_replace('ccccc', $ccccc, $alarm_content);
        $alarm_content = str_replace('ddddd', $ddddd, $alarm_content);

        if ($type == 'cargo_TIE') {
            $alarm_type = 'cargo_TIE';
        } else if ($type == 'cargo_TI') {
            $alarm_type = 'cargo_TI';
        } else if ($type == 'cargo_TE') {
            $alarm_type = 'cargo_TE';
        }

        if ($alarm_data->ad_must_yn == 'y') {
            $receiver_shipper = Company::with(['co_parent'])->where('co_license', $w_no['co_license'])->first();

            if (isset($receiver_shipper)) {
                $receiver_list = Member::where('co_no', $receiver_shipper->co_no)->orwhere('co_no', $receiver_shipper->co_parent->co_no)->get();
            }
        } else if ($alarm_data->ad_must_yn == 'n') {
            $receiver_shipper = Company::with(['co_parent'])->where('co_license', $w_no['co_license'])->first();

            if (isset($receiver_shipper)) {
                $receiver_list = Member::where('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_parent->co_no)->where('mb_push_yn', 'y')->get();
            }
        }

        if (isset($receiver_list)) {
            foreach ($receiver_list as $receiver) {

                //INSERT ALARM FOR RECEIVER LIST USER
                Alarm::insertGetId(
                    [
                        'w_no' => $w_no_save,
                        'mb_no' => null,
                        'receiver_no' => $receiver->mb_no,
                        'alarm_content' => $alarm_content,
                        'alarm_h_bl' => $cargo_number,
                        'alarm_type' => $alarm_type,
                        'ad_no' => $alarm_data->ad_no,
                    ]
                );
            }
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
            } else if ($type == 'cargo_EW') {

                $aaaaa = $w_no->w_import_parent->w_schedule_number2;

                $bbbbb = $w_no->receving_goods_delivery[0]->rgd_delivery_schedule_day;

                $bbbbb = str_replace('-', '.', $bbbbb);

                $cargo_number = $w_no->w_schedule_number;
            } else if ($type == 'cargo_status3_EW') {

                $aaaaa = $w_no->w_schedule_number2;

                $cargo_number = $w_no->w_schedule_number;
            } else if ($type == 'cargo_delivery') {

                $aaaaa = $w_no->w_schedule_number2;

                $bbbbb = $w_no->receving_goods_delivery[0]->rgd_contents;

                $ccccc = $w_no->receving_goods_delivery[0]->rgd_delivery_company;

                $ddddd = $w_no->receving_goods_delivery[0]->rgd_tracking_code;

                $cargo_number = $w_no->w_schedule_number;
            }
        } else if ($w_no->w_category_name == '보세화물') {
            if ($type == 'cargo_delivery') {

                $aaaaa = $w_no->order_number;

                $bbbbb = $w_no->rgd_contents;

                $ccccc = $w_no->rgd_delivery_company;

                $ddddd = $w_no->rgd_tracking_code;

                $cargo_number = $w_no->order_number;
            }
        } else if ($w_no->w_category_name == '수입풀필먼트') {
            if ($type == 'cargo_delivery') {

                $aaaaa = $w_no->ss_no;

                $bbbbb = $w_no->receving_goods_delivery[0]->rgd_contents;

                $ccccc = $w_no->receving_goods_delivery[0]->rgd_delivery_company;

                $ddddd = $w_no->receving_goods_delivery[0]->rgd_tracking_code;

                $cargo_number = $w_no->order_id;
            }
        }

        $alarm_data = AlarmData::where('ad_title', $ad_title)->first();

        $alarm_content = $alarm_data->ad_content;
        $alarm_content = str_replace('aaaaa', $aaaaa, $alarm_content);
        $alarm_content = str_replace('bbbbb', $bbbbb, $alarm_content);
        $alarm_content = str_replace('ccccc', $ccccc, $alarm_content);
        $alarm_content = str_replace('ddddd', $ddddd, $alarm_content);

        if ($type == 'cargo_IW') {
            $alarm_type = 'cargo_IW';
        } else if ($type == 'cargo_EW') {
            $alarm_type = 'cargo_EW';
        } else if ($type == 'cargo_status3_EW') {
            $alarm_type = 'cargo_status3_EW';
        } else if ($type == 'cargo_delivery') {
            $alarm_type = 'cargo_delivery';
        }

        if ($type == 'cargo_IW' ||  $type == 'cargo_EW' || $type == 'cargo_status3_EW') {
            if ($alarm_data->ad_must_yn == 'y') {
                if ($sender->mb_type == 'spasys') {
                    $receiver_spasys = $w_no->company->co_parent->co_parent;
                    $receiver_shop = $w_no->company->co_parent;
                    $receiver_shipper = $w_no->company;
                } else if ($sender->mb_type == 'shop') {
                    $receiver_shipper = $w_no->company;
                    $receiver_shop = $w_no->company->co_parent;
                } else if ($sender->mb_type == 'shipper') {
                    $receiver_shipper = $w_no->company;
                    $receiver_shop = $w_no->company->co_parent;
                }
                //ad_must_yn == 'n' send only members who have mb_push_yn = 'y'
                if (isset($receiver_spasys)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                }
            } else if ($alarm_data->ad_must_yn == 'n') {
                if ($sender->mb_type == 'spasys') {
                    $receiver_spasys = $w_no->company->co_parent->co_parent;
                    $receiver_shop = $w_no->company->co_parent;
                    $receiver_shipper = $w_no->company;
                } else if ($sender->mb_type == 'shop') {
                    $receiver_shipper = $w_no->company;
                    $receiver_shop = $w_no->company->co_parent;
                } else if ($sender->mb_type == 'shipper') {
                    $receiver_shipper = $w_no->company;
                    $receiver_shop = $w_no->company->co_parent;
                }
                //ad_must_yn == 'n' send only members who have mb_push_yn = 'y'
                if (isset($receiver_spasys)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
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
        } else if ($type == 'cargo_delivery') {
            if ($w_no->w_category_name == '유통가공') {
                if ($alarm_data->ad_must_yn == 'y') {
                    if ($sender->mb_type == 'spasys') {
                        $receiver_spasys = $w_no->company->co_parent->co_parent;
                        $receiver_shop = $w_no->company->co_parent;
                        $receiver_shipper = $w_no->company;
                    } else if ($sender->mb_type == 'shop') {
                        $receiver_shipper = $w_no->company;
                        $receiver_shop = $w_no->company->co_parent;
                    } else if ($sender->mb_type == 'shipper') {
                        $receiver_shipper = $w_no->company;
                        $receiver_shop = $w_no->company->co_parent;
                    }
                    //ad_must_yn == 'n' send only members who have mb_push_yn = 'y'
                    if (isset($receiver_spasys)) {
                        $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                    } else {
                        $receiver_list = Member::where('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                    }
                } else if ($alarm_data->ad_must_yn == 'n') {
                    if ($sender->mb_type == 'spasys') {
                        $receiver_spasys = $w_no->company->co_parent->co_parent;
                        $receiver_shop = $w_no->company->co_parent;
                        $receiver_shipper = $w_no->company;
                    } else if ($sender->mb_type == 'shop') {
                        $receiver_shipper = $w_no->company;
                        $receiver_shop = $w_no->company->co_parent;
                    } else if ($sender->mb_type == 'shipper') {
                        $receiver_shipper = $w_no->company;
                        $receiver_shop = $w_no->company->co_parent;
                    }
                    //ad_must_yn == 'n' send only members who have mb_push_yn = 'y'
                    if (isset($receiver_spasys)) {
                        $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
                    } else {
                        $receiver_list = Member::where('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
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
            } else if ($w_no->w_category_name == '수입풀필먼트') {

                if ($alarm_data->ad_must_yn == 'y') {
                    if ($sender->mb_type == 'spasys') {
                        $receiver_spasys = $w_no->ContractWms->company->co_parent->co_parent;
                        $receiver_shop = $w_no->ContractWms->company->co_parent;
                        $receiver_shipper = $w_no->ContractWms->company;
                    } else if ($sender->mb_type == 'shop') {
                        $receiver_shipper = $w_no->ContractWms->company;
                        $receiver_shop = $w_no->ContractWms->company->co_parent;
                    } else if ($sender->mb_type == 'shipper') {
                        $receiver_shipper = $w_no->ContractWms->company;
                        $receiver_shop = $w_no->ContractWms->company->co_parent;
                    }
                    //ad_must_yn == 'n' send only members who have mb_push_yn = 'y'
                    if (isset($receiver_spasys)) {
                        $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                    } else {
                        $receiver_list = Member::where('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                    }
                } else if ($alarm_data->ad_must_yn == 'n') {
                    if ($sender->mb_type == 'spasys') {
                        $receiver_spasys = $w_no->ContractWms->company->co_parent->co_parent;
                        $receiver_shop = $w_no->ContractWms->company->co_parent;
                        $receiver_shipper = $w_no->ContractWms->company;
                    } else if ($sender->mb_type == 'shop') {
                        $receiver_shipper = $w_no->ContractWms->company;
                        $receiver_shop = $w_no->ContractWms->company->co_parent;
                    } else if ($sender->mb_type == 'shipper') {
                        $receiver_shipper = $w_no->ContractWms->company;
                        $receiver_shop = $w_no->ContractWms->company->co_parent;
                    }
                    //ad_must_yn == 'n' send only members who have mb_push_yn = 'y'
                    if (isset($receiver_spasys)) {
                        $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
                    } else {
                        $receiver_list = Member::where('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
                    }
                }

                foreach ($receiver_list as $receiver) {

                    //INSERT ALARM FOR RECEIVER LIST USER
                    Alarm::insertGetId(
                        [
                            'ss_no' => $w_no->ss_no,
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
            } else if ($w_no->w_category_name == '보세화물') {

                if ($alarm_data->ad_must_yn == 'y') {
                    if ($sender->mb_type == 'spasys') {
                        $receiver_spasys = $w_no->company->co_parent->co_parent;
                        $receiver_shop = $w_no->company->co_parent;
                        $receiver_shipper = $w_no->company;
                    } else if ($sender->mb_type == 'shop') {
                        $receiver_shipper = $w_no->company;
                        $receiver_shop = $w_no->company->co_parent;
                    } else if ($sender->mb_type == 'shipper') {
                        $receiver_shipper = $w_no->company;
                        $receiver_shop = $w_no->company->co_parent;
                    }
                    //ad_must_yn == 'n' send only members who have mb_push_yn = 'y'
                    if (isset($receiver_spasys)) {
                        $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                    } else {
                        $receiver_list = Member::where('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                    }
                } else if ($alarm_data->ad_must_yn == 'n') {
                    if ($sender->mb_type == 'spasys') {
                        $receiver_spasys = $w_no->company->co_parent->co_parent;
                        $receiver_shop = $w_no->company->co_parent;
                        $receiver_shipper = $w_no->company;
                    } else if ($sender->mb_type == 'shop') {
                        $receiver_shipper = $w_no->company;
                        $receiver_shop = $w_no->company->co_parent;
                    } else if ($sender->mb_type == 'shipper') {
                        $receiver_shipper = $w_no->company;
                        $receiver_shop = $w_no->company->co_parent;
                    }
                    //ad_must_yn == 'n' send only members who have mb_push_yn = 'y'
                    if (isset($receiver_spasys)) {
                        $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
                    } else {
                        $receiver_list = Member::where('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
                    }
                }

                foreach ($receiver_list as $receiver) {

                    //INSERT ALARM FOR RECEIVER LIST USER
                    Alarm::insertGetId(
                        [
                            'w_no' => $w_no->is_no,
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
    }

    static function insert_alarm_photo($ad_title, $rgd, $sender, $request, $type)
    {
        $ccccc = 0;
        $aaaaa = '';
        $bbbbb = '';
        $ddddd = '';
        $cargo_number = '';

        // if ($request->type == '유통가공') {
        // } else if ($request->type == '보세화물') {
        // } else if ($request->type == '수입풀필먼트') {
        // }

        //foreach ($request->rp_content as $rp_content) {
        $aaaaa = $request->w_schedule_number;
        $bbbbb = $request->rp_cate;
        $ccccc = isset($request->rp_content[0]) ? $request->rp_content[0] : '';

        $alarm_data = AlarmData::where('ad_title', $ad_title)->first();

        $alarm_content = $alarm_data->ad_content;
        $alarm_content = str_replace('aaaaa', $aaaaa, $alarm_content);
        $alarm_content = str_replace('bbbbb', $bbbbb, $alarm_content);
        $alarm_content = str_replace('ccccc', $ccccc, $alarm_content);
        $alarm_content = str_replace('ddddd', $ddddd, $alarm_content);
        $cargo_number = $request->w_schedule_number;

        if ($type == 'photo') {
            $alarm_type = 'photo';
        }

        if ($sender->mb_type == 'spasys') {
            if ($request->type == '보세화물') {
                $receiver = Member::where('co_no', $request->co_no)->first();
                $receiver_spasys = $receiver->company->co_parent->co_parent;
                $receiver_shop = $receiver->company->co_parent;
                $receiver_shipper = $receiver->company;
                $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
            } else {
                $receiver = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->where('w_no', $request->w_no)
                    ->orderBy('w_completed_day', 'DESC')->first();
                $receiver_spasys = $receiver->company->co_parent->co_parent;
                $receiver_shop = $receiver->company->co_parent;
                $receiver_shipper = $receiver->company;
                $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
            }
        }

        foreach ($receiver_list as $receiver) {

            //INSERT ALARM FOR RECEIVER LIST USER
            Alarm::insertGetId(
                [
                    'w_no' => $request->w_no,
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
        //}
    }

    static function insert_alarm_cargo_request($ad_title, $content, $sender, $w_no, $type)
    {
        $ccccc = 0;
        $aaaaa = '';
        $bbbbb = '';
        $ddddd = '';
        $cargo_number = '';

        if ($w_no->w_category_name == '유통가공') {
            $aaaaa = $content;
            $cargo_number = $w_no->w_schedule_number;
        } else if ($w_no->w_category_name == '보세화물') {
        } else if ($w_no->w_category_name == '수입풀필먼트') {
        }

        $alarm_data = AlarmData::where('ad_title', $ad_title)->first();

        $alarm_content = $alarm_data->ad_content;
        $alarm_content = str_replace('aaaaa', $aaaaa, $alarm_content);
        $alarm_content = str_replace('bbbbb', $bbbbb, $alarm_content);
        $alarm_content = str_replace('ccccc', $ccccc, $alarm_content);
        $alarm_content = str_replace('ddddd', $ddddd, $alarm_content);

        if ($w_no->receving_goods_delivery[0]->rgd_status1 && $w_no->w_schedule_number) {
            $alarm_content = '[' . $w_no->receving_goods_delivery[0]->rgd_status1 . ']' . ' ' . $w_no->w_schedule_number . ' ' . $alarm_content;
        }


        if ($type == 'cargo_request') {
            $alarm_type = 'cargo_request';
        }


        if ($alarm_data->ad_must_yn == 'y') {
            if ($sender->mb_type == 'shop') {
                $receiver_spasys = $w_no->company->co_parent->co_parent;
            } else if ($sender->mb_type == 'shipper') {
                $receiver_spasys = $w_no->company->co_parent->co_parent;
            }
            //ad_must_yn == 'n' send only members who have mb_push_yn = 'y'

            $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->get();
        } else if ($alarm_data->ad_must_yn == 'n') {
            if ($sender->mb_type == 'shop') {
                $receiver_spasys = $w_no->company->co_parent->co_parent;
            } else if ($sender->mb_type == 'shipper') {
                $receiver_spasys = $w_no->company->co_parent->co_parent;
            }
            //ad_must_yn == 'n' send only members who have mb_push_yn = 'y'

            $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->get();
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

    static function insert_alarm_company_daily($ad_title, $content, $sender, $company, $type)
    {
        $ccccc = 0;
        $aaaaa = '';
        $bbbbb = '';
        $ddddd = '';
        $cargo_number = '';

        $co_no = isset($company->co_no) ? $company->co_no : '';

        if ($type == 'alarm_daily7') {
            $aaaaa = 7;
        } elseif ($type == 'alarm_daily30') {
            $aaaaa = 30;
        }

        $alarm_data = AlarmData::where('ad_title', $ad_title)->first();

        $alarm_content = $alarm_data->ad_content;
        $alarm_content = str_replace('aaaaa', $aaaaa, $alarm_content);
        $alarm_content = str_replace('bbbbb', $bbbbb, $alarm_content);
        $alarm_content = str_replace('ccccc', $ccccc, $alarm_content);
        $alarm_content = str_replace('ddddd', $ddddd, $alarm_content);

        if ($type == 'alarm_daily7') {
            $alarm_type = 'alarm_daily7';
        } elseif ($type == 'alarm_daily30') {
            $alarm_type = 'alarm_daily30';
        }


        if ($alarm_data->ad_must_yn == 'y') {

            if (isset($company->co_type) && $company->co_type == "spasys") {
                $receiver_spasys = $company;
                $receiver_shop = $company->co_childen;
                $receiver_shipper = $company->co_childen;

                if (isset($receiver_shipper)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                } elseif (isset($receiver_shop)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->get();
                }
            } else if (isset($company->co_type) && $company->co_type == "shop") {
                $receiver_spasys = $company->co_parent;
                $receiver_shop = $company;
                $receiver_shipper = $company->co_childen;

                if (isset($receiver_shipper)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                } elseif (isset($receiver_shop)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->get();
                }
            } else if (isset($company->co_type) && $company->co_type == "shipper") {
                $receiver_spasys = $company->co_parent->co_parent;
                $receiver_shop = $company->co_parent;
                $receiver_shipper = $company;
                if (isset($receiver_shipper)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                } elseif (isset($receiver_shop)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->get();
                }
            }
        } else if ($alarm_data->ad_must_yn == 'n') {
            if (isset($company->co_type) && $company->co_type == "spasys") {
                $receiver_spasys = $company;
                $receiver_shop = $company->co_childen;
                $receiver_shipper = $company->co_childen;
                if (isset($receiver_shipper)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
                } elseif (isset($receiver_shop)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->get();
                }
            } else if (isset($company->co_type) && $company->co_type == "shop") {
                $receiver_spasys = $company->co_parent;
                $receiver_shop = $company;
                $receiver_shipper = $company->co_childen;
                if (isset($receiver_shipper)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
                } elseif (isset($receiver_shop)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->get();
                }
            } else if (isset($company->co_type) && $company->co_type == "shipper") {
                $receiver_spasys = $company->co_parent->co_parent;
                $receiver_shop = $company->co_parent;
                $receiver_shipper = $company;
                if (isset($receiver_shipper)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
                } elseif (isset($receiver_shop)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->get();
                }
            }
        }


        foreach ($receiver_list as $receiver) {

            //INSERT ALARM FOR RECEIVER LIST USER
            Alarm::insertGetId(
                [
                    'w_no' => null,
                    'mb_no' => null,
                    'co_no' => $co_no,
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

    static function insert_alarm_insulace_company_daily($ad_title, $content, $sender, $company, $type)
    {
        $ccccc = 0;
        $aaaaa = '';
        $bbbbb = '';
        $ddddd = '';
        $cargo_number = '';

        $co_no = isset($company->co_no) ? $company->co_no : '';

        if ($type == 'alarm_daily_insulace7') {
            $aaaaa = 7;
        } elseif ($type == 'alarm_daily_insulace30') {
            $aaaaa = 30;
        }

        $alarm_data = AlarmData::where('ad_title', $ad_title)->first();

        $alarm_content = $alarm_data->ad_content;
        $alarm_content = str_replace('aaaaa', $aaaaa, $alarm_content);
        $alarm_content = str_replace('bbbbb', $bbbbb, $alarm_content);
        $alarm_content = str_replace('ccccc', $ccccc, $alarm_content);
        $alarm_content = str_replace('ddddd', $ddddd, $alarm_content);

        if ($type == 'alarm_daily_insulace7') {
            $alarm_type = 'alarm_daily_insulace7';
        } elseif ($type == 'alarm_daily_insulace30') {
            $alarm_type = 'alarm_daily_insulace30';
        }


        if ($alarm_data->ad_must_yn == 'y') {

            if (isset($company->co_type) && $company->co_type == "spasys") {
                $receiver_spasys = $company;
                $receiver_shop = $company->co_childen;
                $receiver_shipper = $company->co_childen->co_childen;
                if (isset($receiver_shipper)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                } elseif (isset($receiver_shop)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->get();
                }
            } else if (isset($company->co_type) && $company->co_type == "shop") {
                $receiver_spasys = $company->co_parent;
                $receiver_shop = $company;
                $receiver_shipper = $company->co_childen;
                if (isset($receiver_shipper)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                } elseif (isset($receiver_shop)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->get();
                }
            } else if (isset($company->co_type) && $company->co_type == "shipper") {
                $receiver_spasys = $company->co_parent->co_parent;
                $receiver_shop = $company->co_parent;
                $receiver_shipper = $company;
                if (isset($receiver_shipper)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                } elseif (isset($receiver_shop)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->get();
                }
            }
        } else if ($alarm_data->ad_must_yn == 'n') {
            if (isset($company->co_type) && $company->co_type == "spasys") {
                $receiver_spasys = $company;
                $receiver_shop = $company->co_childen;
                $receiver_shipper = $company->co_childen;
                if (isset($receiver_shipper)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
                } elseif (isset($receiver_shop)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->get();
                }
            } else if (isset($company->co_type) && $company->co_type == "shop") {
                $receiver_spasys = $company->co_parent;
                $receiver_shop = $company;
                $receiver_shipper = $company->co_childen;
                if (isset($receiver_shipper)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
                } elseif (isset($receiver_shop)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->get();
                }
            } else if (isset($company->co_type) && $company->co_type == "shipper") {
                $receiver_spasys = $company->co_parent->co_parent;
                $receiver_shop = $company->co_parent;
                $receiver_shipper = $company;
                if (isset($receiver_shipper)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shipper->co_no)->where('mb_push_yn', 'y')->get();
                } elseif (isset($receiver_shop)) {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->orwhere('co_no', $receiver_shop->co_no)->where('mb_push_yn', 'y')->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->where('mb_push_yn', 'y')->get();
                }
            }
        }


        foreach ($receiver_list as $receiver) {

            //INSERT ALARM FOR RECEIVER LIST USER
            Alarm::insertGetId(
                [
                    'w_no' => null,
                    'mb_no' => null,
                    'co_no' => $co_no,
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

    static function insert_alarm_pw_company_90($ad_title, $content, $sender, $company, $type)
    {
        $ccccc = 0;
        $aaaaa = '';
        $bbbbb = '';
        $ddddd = '';
        $cargo_number = '';

        $co_no = isset($company->co_no) ? $company->co_no : null;

        $alarm_data = AlarmData::where('ad_title', $ad_title)->first();

        $alarm_content = $alarm_data->ad_content;
        $alarm_content = str_replace('aaaaa', $aaaaa, $alarm_content);
        $alarm_content = str_replace('bbbbb', $bbbbb, $alarm_content);
        $alarm_content = str_replace('ccccc', $ccccc, $alarm_content);
        $alarm_content = str_replace('ddddd', $ddddd, $alarm_content);

        if ($type == 'alarm_pw_company_90') {
            $alarm_type = 'alarm_pw_company_90';
        }


        if ($alarm_data->ad_must_yn == 'y') {
            $receiver_list = Member::where('mb_no', $company->mb_no)->get();
        } else if ($alarm_data->ad_must_yn == 'n') {
            $receiver_list = Member::where('mb_no', $company->mb_no)->where('mb_push_yn', 'y')->get();
        }


        foreach ($receiver_list as $receiver) {

            //INSERT ALARM FOR RECEIVER LIST USER
            Alarm::insertGetId(
                [
                    'w_no' => null,
                    'mb_no' => null,
                    'co_no' => $co_no,
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

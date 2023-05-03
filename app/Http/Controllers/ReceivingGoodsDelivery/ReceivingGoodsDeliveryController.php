<?php

namespace App\Http\Controllers\ReceivingGoodsDelivery;

use DateTime;
use App\Models\Warehousing;
use App\Models\Member;
use App\Models\WarehousingRequest;
use App\Models\WarehousingStatus;
use App\Models\ReceivingGoodsDelivery;
use App\Models\RateDataGeneral;
use App\Models\WarehousingItem;
use App\Models\WarehousingSettlement;
use App\Models\AdjustmentGroup;
use App\Models\Alarm;
use App\Models\ScheduleShipment;
use App\Models\Package;
use App\Models\ItemChannel;
use App\Models\Company;
use App\Models\TaxInvoiceDivide;
use App\Models\CancelBillHistory;
//use App\Models\CargoConnect;
use App\Utils\Messages;
use App\Utils\CommonFunc;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;
use App\Models\File;
use App\Models\Item;
use App\Models\Payment;
use App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryRequest;
use App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryCreateRequest;
use App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryCreateApiRequest;
use App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryCreateMobileRequest;
use App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryFileRequest;
use App\Models\Export;
use App\Models\Import;
use App\Models\ImportExpected;
use \Carbon\Carbon;
use PhpParser\Node\Expr\Cast\Object_;

class ReceivingGoodsDeliveryController extends Controller
{
    /**
     * Fetch data
     * @param  \App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(ReceivingGoodsDeliveryRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $rgd = ReceivingGoodsDelivery::with('mb_no')->with('w_no')->paginate($per_page, ['*'], 'page', $page);

            return response()->json($rgd);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create_warehousing(ReceivingGoodsDeliveryCreateRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $co_no = Auth::user()->co_no ? Auth::user()->co_no : null;
            $user = Auth::user();

            if (isset($validated['w_no'])) {
                Warehousing::where('w_no', $validated['w_no'])->update([
                    'w_schedule_amount' => $validated['w_schedule_amount'],
                    'w_schedule_day' => isset($validated['w_schedule_day']) ? DateTime::createFromFormat('Y-m-d', $validated['w_schedule_day']) : null,
                    'w_amount' => $validated['w_amount'],
                    'w_type' => 'IW',
                    'w_category_name' => $request->w_category_name,
                ]);
            } else {
                //return $co_no;
                $w_no_data = Warehousing::insertGetId([
                    'mb_no' => $member->mb_no,

                    'w_schedule_day' => isset($validated['w_schedule_day']) ? DateTime::createFromFormat('Y-m-d', $validated['w_schedule_day']) : null,
                    'w_schedule_amount' => $validated['w_schedule_amount'],
                    'w_amount' => $validated['w_amount'],
                    'w_type' => 'IW',
                    'w_category_name' => $request->w_category_name,
                    'co_no' => isset($validated['co_no']) ? $validated['co_no'] : $co_no,
                ]);
            }

            $w_no = isset($validated['w_no']) ? $validated['w_no'] : $w_no_data;
            //Connect_id
            if (isset($validated['w_no'])) {
            } else {
            }

            if (!isset($validate['w_schedule_number'])) {
                $w_schedule_number = (new CommonFunc)->generate_w_schedule_number($w_no, 'IW');
                if (isset($validated['page_type']) && $validated['page_type'] == 'Page130146') {
                    $w_schedule_number2 = (new CommonFunc)->generate_w_schedule_number($w_no, 'IWC');
                }
            }

            Warehousing::where('w_no', $w_no)->update([
                'w_schedule_number' =>  $w_schedule_number
            ]);

            if (isset($validated['page_type']) && $validated['page_type'] == 'Page130146') {
                $mytime = Carbon::now();
                Warehousing::where('w_no', $w_no)->update([
                    'w_schedule_number2' =>  $w_schedule_number2,
                    'w_completed_day' => $mytime->toDateTimeString()
                ]);
            }

            if (isset($validated['connection_number'])) {

                if (isset($validated['w_no'])) {
                    Warehousing::where('w_no', $validated['w_no'])->update([
                        'connection_number' => $validated['connection_number']
                    ]);
                    $check_ex = Warehousing::where('w_import_no', '=', $w_no)->get();
                    if ($check_ex) {
                        foreach ($check_ex as $ex) {
                            Warehousing::where('w_no', $ex->w_no)->update([
                                'connection_number' => $validated['connection_number']
                            ]);
                        }
                    }
                    // if ($validated['type_w_choose'] == "export") {
                    //     CargoConnect::insertGetId([
                    //         'w_no' => $validated['w_no'],
                    //         'is_no' => $validated['connect_w'],
                    //         'type' => "3_1"
                    //     ]);
                    // }else{
                    //     CargoConnect::insertGetId([
                    //         'w_no' => $validated['w_no'],
                    //         'ss_no' => $validated['connect_w'],
                    //         'type' => "3_2"
                    //     ]);
                    // }

                    // if ($validated['type_w_choose'] == "export") {
                    //     $connection_number_old = Export::where('te_carry_out_number', $validated['connect_w'])->first();

                    //     if (isset($connection_number_old->connection_number)) {
                    //         Warehousing::where('connection_number', $connection_number_old->connection_number)->update([
                    //             'connection_number' => null
                    //         ]);
                    //     }
                    //     Export::where('te_carry_out_number', $validated['connect_w'])->update([
                    //         'connection_number' => $validated['connection_number']
                    //     ]);
                    // } else {
                    //     $connection_number_old =  Warehousing::where('w_no', $validated['connect_w'])->first();
                    //     if (isset($connection_number_old->connection_number)) {
                    //         Warehousing::where('connection_number', $connection_number_old->connection_number)->update([
                    //             'connection_number' => null
                    //         ]);
                    //     }
                    //     Warehousing::where('w_no', $validated['connect_w'])->update([
                    //         'connection_number' => $validated['connection_number']
                    //     ]);
                    // }
                } else {
                    // if ($validated['type_w_choose'] == "export") {
                    //     CargoConnect::insertGetId([
                    //         'w_no' => $w_no_data,
                    //         'is_no' => $validated['connect_w'],
                    //         'type' => "3_1"
                    //     ]);
                    // }else{
                    //     CargoConnect::insertGetId([
                    //         'w_no' => $w_no_data,
                    //         'ss_no' => $validated['connect_w'],
                    //         'type' => "3_2"
                    //     ]);
                    // }
                    Warehousing::where('w_no', $w_no_data)->update([
                        'connection_number' => $validated['connection_number']
                    ]);
                    // if ($validated['type_w_choose'] == "export") {
                    //     $connection_number_old = Export::where('te_carry_out_number', $validated['connect_w'])->first();
                    //     if (isset($connection_number_old->connection_number)) {
                    //         Warehousing::where('connection_number', $connection_number_old->connection_number)->update([
                    //             'connection_number' => null
                    //         ]);
                    //     }
                    //     Export::where('te_carry_out_number', $validated['connect_w'])->update([
                    //         'connection_number' => $validated['connection_number'] . $w_no_data
                    //     ]);
                    // } else {
                    //     $connection_number_old =  Warehousing::where('w_no', $validated['connect_w'])->first();
                    //     if (isset($connection_number_old->connection_number)) {
                    //         Warehousing::where('connection_number', $connection_number_old->connection_number)->update([
                    //             'connection_number' => null
                    //         ]);
                    //     }
                    //     Warehousing::where('w_no', $validated['connect_w'])->update([
                    //         'connection_number' => $validated['connection_number'] . $w_no_data
                    //     ]);
                    // }
                }
            }

            //warehousing rgd
            $status1 = "";
            $status2 = "";
            foreach ($validated['location'] as $rgd) {
                if (!isset($rgd['rgd_no'])) {
                    $rgd_no = ReceivingGoodsDelivery::insertGetId([
                        'mb_no' => $member->mb_no,
                        'w_no' => $w_no,
                        'service_korean_name' => $request->w_category_name,
                        'rgd_contents' => $rgd['rgd_contents'],
                        'rgd_address' => $rgd['rgd_address'],
                        'rgd_address_detail' => $rgd['rgd_address_detail'],
                        'rgd_receiver' => $rgd['rgd_receiver'],
                        'rgd_hp' => $rgd['rgd_hp'],
                        'rgd_memo' => $rgd['rgd_memo'],
                        'rgd_status1' => isset($rgd['rgd_status1']) ? $rgd['rgd_status1'] : null,
                        'rgd_status2' => isset($rgd['rgd_status2']) ? $rgd['rgd_status2'] : null,
                        'rgd_status3' => isset($rgd['rgd_status3']) ? $rgd['rgd_status3'] : null,
                        'rgd_status4' => isset($rgd['rgd_status4']) ? $rgd['rgd_status4'] : null,
                        'rgd_delivery_company' => isset($rgd['rgd_delivery_company']) ? $rgd['rgd_delivery_company'] : null,
                        'rgd_tracking_code' => isset($rgd['rgd_tracking_code']) ? $rgd['rgd_tracking_code'] : null,
                        'rgd_delivery_man' => isset($rgd['rgd_delivery_man']) ? $rgd['rgd_delivery_man'] : null,
                        'rgd_delivery_man_hp' => isset($rgd['rgd_delivery_man_hp']) ? $rgd['rgd_delivery_man_hp'] : null,
                        'rgd_delivery_schedule_day' => isset($rgd['rgd_delivery_schedule_day']) ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']) : null,
                        'rgd_arrive_day' => isset($rgd['rgd_arrive_day']) ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']) : null,
                    ]);
                    $warehousing_status =  isset($rgd['rgd_status1']) ? $rgd['rgd_status1'] : null;
                } else {

                    $warehousing_status = isset($rgd['rgd_status1']) ? $rgd['rgd_status1'] : null;
                    $rgd_data = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->first();

                    if ($warehousing_status != $rgd_data->rgd_status1) {
                        $warehousing_status = isset($rgd['rgd_status1']) ? $rgd['rgd_status1'] : null;
                    } else {
                        $warehousing_status = null;
                    }

                    $rgd_no = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                        'rgd_contents' => $rgd['rgd_contents'],
                        'rgd_address' => $rgd['rgd_address'],
                        'rgd_address_detail' => $rgd['rgd_address_detail'],
                        'rgd_receiver' => $rgd['rgd_receiver'],
                        'rgd_hp' => $rgd['rgd_hp'],
                        'rgd_memo' => $rgd['rgd_memo'],
                        'rgd_status1' => $rgd['rgd_status1'],
                        'rgd_status2' => $rgd['rgd_status2'],
                        'rgd_status3' => $rgd['rgd_status3'],
                        'rgd_status4' => isset($rgd['rgd_status4']) ? $rgd['rgd_status4'] : null,
                        'rgd_delivery_company' => isset($rgd['rgd_delivery_company']) ? $rgd['rgd_delivery_company'] : null,
                        'rgd_tracking_code' => $rgd['rgd_tracking_code'],
                        'rgd_delivery_man' => $rgd['rgd_delivery_man'],
                        'rgd_delivery_man_hp' => $rgd['rgd_delivery_man_hp'],
                        'rgd_delivery_schedule_day' => $rgd['rgd_delivery_schedule_day'] ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']) : null,
                        'rgd_arrive_day' => $rgd['rgd_arrive_day'] ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']) : null,
                    ]);
                }

                $status1 = isset($rgd['rgd_status1']) ? $rgd['rgd_status1'] : null;
                $status2 = isset($rgd['rgd_status2']) ? $rgd['rgd_status2'] : null;
            }

            if ($warehousing_status) {
                WarehousingStatus::insert([
                    'w_no' => $w_no,
                    'mb_no' => $member->mb_no,
                    'status' => $warehousing_status,
                    'w_category_name' => $request->w_category_name,
                ]);
            }

            //warehousing content
            if ($validated['wr_contents']) {
                WarehousingRequest::insert([
                    'w_no' => $w_no,
                    'mb_no' => $member->mb_no,
                    'wr_contents' => $validated['wr_contents'],
                    'wr_type' => 'IW',
                ]);
            }

            //warehousing item
            foreach ($validated['remove'] as $remove) {
                WarehousingItem::where('item_no', $remove['item_no'])->where('w_no', $w_no)->delete();
            }

            $warehousing_items = [];

            if (isset($validated['item_new'])) {
                foreach ($validated['item_new'] as $item_new) {
                    if (isset($item_new['item_name'])) {
                        $item_no_new = Item::insertGetId([
                            'mb_no' => Auth::user()->mb_no,
                            'item_bar_code' => $item_new['item_bar_code'],
                            'item_brand' => $item_new['item_brand'],
                            'item_service_name' => '유통가공',
                            'co_no' => $validated['co_no'] ? $validated['co_no'] : $co_no,
                            'item_name' => $item_new['item_name'],
                            'item_option1' => $item_new['item_option1'],
                            'item_option2' => $item_new['item_option2'],
                            'item_price3' => isset($item_new['item_price3']) ? $item_new['item_price3'] : 0,
                            'item_price4' => $item_new['item_price4']
                        ]);

                        WarehousingItem::insert([
                            'item_no' => $item_no_new,
                            'w_no' => $w_no,
                            'wi_number' => $item_new['wi_number'],
                            'wi_type' => '입고_shipper'
                        ]);

                        ItemChannel::insert(
                            [
                                'item_no' => $item_no_new,
                                'item_channel_code' => isset($item_new['item_channel_code']) ? $item_new['item_channel_code'] : '',
                                'item_channel_name' => isset($item_new['item_channel_name']) ? $item_new['item_channel_name'] : '',
                            ]
                        );
                    }
                }
            }

            if (isset($validated['w_no'])) {
                // $sql_r = ReceivingGoodsDelivery::where('w_no', '=', $validated['w_no'])->first();

                // if ($sql_r->rgd_status1 != "입고" && $sql_r->rgd_status2 != "작업완료") {
                if (isset($validated['items'])) {
                    foreach ($validated['items'] as $warehousing_item) {


                        $item_no = $warehousing_item['item_no'] ? $warehousing_item['item_no'] : '';

                        if (isset($item_no) && $item_no) {

                            if (isset($validated['page_type']) && $validated['page_type'] == 'Page130146') {
                                $checkexit1 = WarehousingItem::where('item_no', $item_no)->where('w_no', $validated['w_no'])->where('wi_type', '입고_spasys')->first();
                                if (!isset($checkexit1->wi_no)) {
                                    WarehousingItem::insert([
                                        'item_no' => $item_no,
                                        'w_no' => $w_no,
                                        'wi_number' => $warehousing_item['warehousing_item2'][0]['wi_number'],
                                        'wi_type' => '입고_spasys'
                                    ]);
                                } else {
                                    $warehousing_items = WarehousingItem::where('item_no', $item_no)->where('w_no', $validated['w_no'])->where('wi_type', '입고_spasys')->update([
                                        'wi_number' => $warehousing_item['warehousing_item2'][0]['wi_number'],
                                    ]);
                                }

                                $checkexit2 = WarehousingItem::where('item_no', $item_no)->where('w_no', $validated['w_no'])->where('wi_type', '입고_shipper')->first();
                                if (!isset($checkexit2->wi_no)) {
                                    WarehousingItem::insert([
                                        'item_no' => $item_no,
                                        'w_no' => $w_no,
                                        'wi_number' => $warehousing_item['warehousing_item'][0]['wi_number'],
                                        'wi_type' => '입고_shipper'
                                    ]);
                                } else {
                                    $warehousing_items = WarehousingItem::where('item_no', $item_no)->where('w_no', $validated['w_no'])->where('wi_type', '입고_shipper')->update([
                                        'wi_number' => $warehousing_item['warehousing_item'][0]['wi_number'],
                                    ]);
                                }
                            } else {

                                $checkexit3 = WarehousingItem::where('item_no', $item_no)->where('w_no', $validated['w_no'])->where('wi_type', '입고_shipper')->first();
                                if (!isset($checkexit3->wi_no)) {
                                    WarehousingItem::insert([
                                        'item_no' => $item_no,
                                        'w_no' => $w_no,
                                        'wi_number' => $warehousing_item['warehousing_item'][0]['wi_number'],
                                        'wi_type' => '입고_shipper'
                                    ]);
                                } else {
                                    if (isset($warehousing_item['warehousing_item'][0]['wi_number'])) {
                                        $warehousing_items = WarehousingItem::where('item_no', $item_no)->where('w_no', $validated['w_no'])->where('wi_type', '입고_shipper')->update([
                                            'wi_number' => $warehousing_item['warehousing_item'][0]['wi_number'],
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
                //}
            } else {
                if (isset($validated['items'])) {
                    foreach ($validated['items'] as $warehousing_item) {
                        if (isset($warehousing_item['item_no']) && $warehousing_item['item_no']) {
                            WarehousingItem::insert([
                                'item_no' => $warehousing_item['item_no'],
                                'w_no' => $w_no,
                                'wi_number' => isset($warehousing_item['warehousing_item'][0]['wi_number']) ? $warehousing_item['warehousing_item'][0]['wi_number'] : null,
                                'wi_type' => '입고_shipper'
                            ]);
                        }
                    }
                }
            }

            //Create 출고예정 after 입고 is done.


            if (isset($validated['page_type']) && $validated['page_type'] == 'Page130146') {
                if ($status1 == "입고" && $status2 == "작업완료") {
                    $check_ex = Warehousing::where('w_import_no', '=', $w_no)->first();
                    if (!$check_ex) {

                        $w_schedule_amount = 0;
                        foreach ($validated['items'] as $item) {
                            $w_schedule_amount += $item['warehousing_item2'][0]['wi_number'];
                        }
                        $w_no_ew = Warehousing::insertGetId([
                            'mb_no' => $member->mb_no,
                            'w_schedule_amount' => $w_schedule_amount,
                            'w_schedule_day' => $validated['w_schedule_day'],
                            'w_import_no' => $w_no,
                            'w_type' => 'EW',
                            'w_category_name' => $request->w_category_name,
                            'co_no' => $validated['co_no'],
                            'connection_number' => isset($validated['connection_number']) ? $validated['connection_number'] : null,
                        ]);

                        Warehousing::where('w_no', $w_no)->update([
                            'w_children_yn' => "y"
                        ]);
                        $w_schedule_number = CommonFunc::generate_w_schedule_number($w_no_ew, 'EW');
                        Warehousing::where('w_no', $w_no_ew)->update([
                            'w_schedule_number' =>   CommonFunc::generate_w_schedule_number($w_no_ew, 'EW')
                        ]);

                        foreach ($validated['location'] as $rgd) {
                            $rgd_no = ReceivingGoodsDelivery::insertGetId([
                                'mb_no' => $member->mb_no,
                                'w_no' => $w_no_ew,
                                'service_korean_name' => $request->w_category_name,
                                'rgd_contents' => $rgd['rgd_contents'],
                                'rgd_address' => $rgd['rgd_address'],
                                'rgd_address_detail' => $rgd['rgd_address_detail'],
                                'rgd_receiver' => $rgd['rgd_receiver'],
                                'rgd_hp' => $rgd['rgd_hp'],
                                'rgd_memo' => $rgd['rgd_memo'],
                                'rgd_status1' => '출고예정',
                                'rgd_status2' => isset($rgd['rgd_status2']) ? $rgd['rgd_status2'] : null,
                                'rgd_status3' => isset($rgd['rgd_status3']) ? $rgd['rgd_status3'] : null,
                                'rgd_status4' => isset($rgd['rgd_status4']) ? $rgd['rgd_status4'] : null,
                                'rgd_delivery_company' => isset($rgd['rgd_delivery_company']) ? $rgd['rgd_delivery_company'] : null,
                                'rgd_tracking_code' => isset($rgd['rgd_tracking_code']) ? $rgd['rgd_tracking_code'] : null,
                                'rgd_delivery_man' => isset($rgd['rgd_delivery_man']) ? $rgd['rgd_delivery_man'] : null,
                                'rgd_delivery_man_hp' => isset($rgd['rgd_delivery_man_hp']) ? $rgd['rgd_delivery_man_hp'] : null,
                                'rgd_delivery_schedule_day' => null,
                                'rgd_arrive_day' => null,
                            ]);
                        }

                        foreach ($validated['items'] as $item) {
                            WarehousingItem::insert([
                                'item_no' => $item['item_no'],
                                'w_no' => $w_no_ew,
                                'wi_number' => $item['warehousing_item2'][0]['wi_number'],
                                'wi_type' => '출고_shipper'
                            ]);
                        }

                        WarehousingStatus::insert([
                            'w_no' => $w_no_ew,
                            'mb_no' => $member->mb_no,
                            'status' => '출고예정',
                            'w_category_name' => $request->w_category_name,
                        ]);
                    } else {
                        $check_ew = Warehousing::where('w_import_no', '=', $w_no)->get();
                        foreach ($validated['location'] as $rgd) {
                            foreach ($check_ew as $w_no_ew) {
                                ReceivingGoodsDelivery::where('w_no', '=', $w_no_ew['w_no'])->whereNull('rgd_parent_no')->update([
                                    'rgd_status2' => isset($rgd['rgd_status2']) ? $rgd['rgd_status2'] : null,
                                ]);
                            }
                        }
                    }
                } else {
                    $check_ex = Warehousing::where('w_import_no', '=', $w_no)->first();
                    if ($check_ex) {
                        $check_ew = Warehousing::where('w_import_no', '=', $w_no)->get();
                        foreach ($validated['location'] as $rgd) {
                            foreach ($check_ew as $w_no_ew) {
                                ReceivingGoodsDelivery::where('w_no', '=', $w_no_ew['w_no'])->whereNull('rgd_parent_no')->update([
                                    'rgd_status2' => isset($rgd['rgd_status2']) ? $rgd['rgd_status2'] : null,
                                ]);
                            }
                        }
                    }
                }
            }
            //Alert

            if (isset($validated['page_type']) && $validated['page_type'] == 'Page130146') {
                if (isset($validated['w_no'])) {
                    $w_no_alert = Warehousing::with(['receving_goods_delivery'])->where('w_no', $validated['w_no'])->first();

                    $status2  = isset($w_no_alert->receving_goods_delivery[0]->rgd_status2) ? $w_no_alert->receving_goods_delivery[0]->rgd_status2 : null;

                    if ($status2 == "작업완료") {
                        CommonFunc::insert_alarm_cargo('[유통가공] 작업완료', null, $user, $w_no_alert, 'cargo_IW');
                    } else {
                        CommonFunc::insert_alarm_cargo('[유통가공] 입고', null, $user, $w_no_alert, 'cargo_IW');
                    }
                }
            } else {
                if (!isset($validated['w_no'])) {
                    $w_no_alert = Warehousing::with(['receving_goods_delivery'])->where('w_no', $w_no)->first();
                    CommonFunc::insert_alarm_cargo('[유통가공] 입고예정', null, $user, $w_no_alert, 'cargo_IW');
                }
                if ($user->mb_type != 'spasys' && $validated['wr_contents']) {
                    $w_no_alert = Warehousing::with(['receving_goods_delivery'])->where('w_no', $w_no)->first();
                    CommonFunc::insert_alarm_cargo_request('[유통가공]', $validated['wr_contents'], $user, $w_no_alert, 'cargo_request');
                }
            }


            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rgd_no' => $rgd_no,
                'w_schedule_number' =>  $w_schedule_number,
                'w_schedule_number2' => isset($w_schedule_number2) ? $w_schedule_number2 : '',
                'w_no' => isset($w_no) ? $w_no :  $validated['w_no'],
                'w_no_alert' => isset($w_no_alert) ? $w_no_alert :  null,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function create_item_mobile(ReceivingGoodsDeliveryCreateRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $co_no = Auth::user()->co_no ? Auth::user()->co_no : null;

            $item_no_new = Item::insertGetId([
                'mb_no' => Auth::user()->mb_no,
                'item_brand' => isset($request['item_brand']) ? $request['item_brand'] : null,
                'item_service_name' => '유통가공',
                'co_no' => $request['co_no'] ? $request['co_no'] : $co_no,
                'item_name' => isset($request['item_name']) ? $request['item_name'] : null,
                'item_name' => isset($request['item_bar_code']) ? $request['item_bar_code'] : null,
                'item_option1' => isset($request['item_option1']) ? $request['item_option1'] : null,
                'item_option2' => isset($request['item_option2']) ? $request['item_option2'] : null,
                'item_price4' => isset($request['item_price4']) ? $request['item_price4'] : null
            ]);

            // WarehousingItem::insert([
            //     'item_no' => $item_no_new,
            //     'w_no' => $validated['w_no'],
            //     'wi_number' =>  isset($request['item_price4']) ? $request['item_price4'] : null,
            //     'wi_type' => '입고_shipper'
            // ]);
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'w_no' => isset($w_no) ? $w_no :  $validated['w_no'],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function create_warehousing_release(Request $request)
    {

        try {
            DB::beginTransaction();
            $user = Auth::user();
            $co_no = Auth::user()->co_no ? Auth::user()->co_no : null;
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();

            //Page130146 spasys
            if ($request->page_type == 'Page130146') {
                $warehousing_data = Warehousing::where('w_no', $request->w_no)->first();
                if (isset($request->data)) {
                    foreach ($request->data as $data) {
                        Warehousing::where('w_no', $request->w_no)->update([
                            'w_schedule_amount' => $data['w_schedule_amount'],
                            'w_schedule_day' => $request->w_schedule_day,
                            'w_import_no' => $warehousing_data->w_import_no,
                            'w_amount' => $data['w_amount'],
                            'w_type' => 'EW',
                            'w_category_name' => $request->w_category_name,
                        ]);

                        $w_schedule_number = CommonFunc::generate_w_schedule_number($request->w_no, 'EW');
                        Warehousing::where('w_no', $request->w_no)->update([
                            'w_schedule_number' =>   CommonFunc::generate_w_schedule_number($request->w_no, 'EW')
                        ]);

                        if (isset($request->page_type) && $request->page_type == 'Page130146') {
                            $w_schedule_number2 = CommonFunc::generate_w_schedule_number($request->w_no, 'EWC');

                            Warehousing::where('w_no', $request->w_no)->update([
                                'w_schedule_number2' =>  CommonFunc::generate_w_schedule_number($request->w_no, 'EWC'),
                                'w_completed_day' => Carbon::now()->toDateTimeString()
                            ]);
                        }

                        if ($request->wr_contents) {
                            // WarehousingRequest::where('w_no', $request->w_no)->update([
                            //     'mb_no' => $member->mb_no,
                            //     'wr_contents' => $request->wr_contents,
                            //     'wr_type' => 'EW',
                            // ]);

                            if ($request->wr_contents) {
                                WarehousingRequest::insert([
                                    'w_no' => $request->w_no,
                                    'mb_no' => $member->mb_no,
                                    'wr_contents' => $request->wr_contents,
                                    'wr_type' => 'EW',
                                ]);
                            }
                        }


                        foreach ($data['remove_items'] as $remove) {
                            WarehousingItem::where('item_no', $remove['item_no'])->where('w_no', $request->w_no)->delete();
                        }

                        //WarehousingItem::where('w_no', $request->w_no)->where('wi_type','=','출고')->delete();

                        foreach ($data['items'] as $item) {


                            // if(isset($item['warehousing_item'][0]['wi_type']) && $item['warehousing_item'][0]['wi_type'] == "출고_spasys"){
                            //     WarehousingItem::where('wi_no', $item['warehousing_item'][0]['wi_no'])->update([
                            //         'item_no' => $item['item_no'],
                            //         'w_no' => $request->w_no,
                            //         'wi_number' => $item['schedule_wi_number'],
                            //        //'wi_number_received' =>  $item['warehousing_item']['wi_number_received'],
                            //         'wi_type' => '출고_spasys'
                            //     ]);

                            // }else{
                            //     WarehousingItem::insert([
                            //         'item_no' => $item['item_no'],
                            //         'w_no' => $request->w_no,
                            //         'wi_number' => $item['schedule_wi_number'],
                            //         //'wi_number_received' =>  $item['warehousing_item']['wi_number_received'],
                            //         'wi_type' => '출고_spasys'
                            //     ]);
                            // }
                            $checkexit = WarehousingItem::where('item_no', $item['item_no'])->where('w_no', $request->w_no)->where('wi_type', '출고_shipper')->first();
                            if (!isset($checkexit->wi_no)) {
                                WarehousingItem::insert([
                                    'item_no' => $item['item_no'],
                                    'w_no' => $request->w_no,
                                    'wi_number' => $item['schedule_wi_number'],
                                    'wi_type' => '출고_shipper'
                                ]);
                            }

                            WarehousingItem::updateOrCreate(
                                [
                                    'item_no' => $item['item_no'],
                                    'w_no' => $request->w_no,
                                    'wi_type' => '출고_spasys'
                                ],
                                [
                                    'item_no' => $item['item_no'],
                                    'w_no' => $request->w_no,
                                    'wi_number' => $item['schedule_wi_number'],
                                    //'wi_number_received' =>  $item['warehousing_item']['wi_number_received'],
                                    'wi_type' => '출고_spasys',
                                ]
                            );
                        }

                        foreach ($data['location'] as $location) {

                            $warehousing_status = isset($location['rgd_status1']) ? $location['rgd_status1'] : null;
                            //$warehousing_status3 = isset($location['rgd_status3']) ? $location['rgd_status3'] : null;
                            $rgd_data = ReceivingGoodsDelivery::where('w_no', $request->w_no)->first();

                            if ($warehousing_status != $rgd_data->rgd_status1) {
                                $warehousing_status = isset($location['rgd_status1']) ? $location['rgd_status1'] : null;
                            } else {
                                $warehousing_status = null;
                            }

                            // if ($warehousing_status != $rgd_data->rgd_status3) {
                            //     $warehousing_status3 = isset($location['rgd_status3']) ? $location['rgd_status3'] : null;
                            // } else {
                            //     $warehousing_status3 = null;
                            // }

                            $rgd_no = ReceivingGoodsDelivery::where('w_no', $request->w_no)->update([
                                'mb_no' => $member->mb_no,
                                'w_no' => $request->w_no,
                                'service_korean_name' => $request->w_category_name,
                                'rgd_contents' => $location['rgd_contents'],
                                'rgd_address' => $location['rgd_address'],
                                'rgd_address_detail' => $location['rgd_address_detail'],
                                'rgd_receiver' => $location['rgd_receiver'],
                                'rgd_hp' => $location['rgd_hp'],
                                'rgd_memo' => $location['rgd_memo'],
                                'rgd_status1' => $location['rgd_status1'],
                                'rgd_status2' => $location['rgd_status2'],
                                'rgd_status3' => $location['rgd_status3'],
                                'rgd_status4' => isset($location['rgd_status4']) ? $location['rgd_status4'] : null,
                                'rgd_delivery_company' => $location['rgd_delivery_company'],
                                'rgd_tracking_code' => $location['rgd_tracking_code'],
                                'rgd_delivery_man' => $location['rgd_delivery_man'],
                                'rgd_delivery_man_hp' => $location['rgd_delivery_man_hp'],
                                'rgd_delivery_schedule_day' => $location['rgd_delivery_schedule_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_delivery_schedule_day']) : null,
                                'rgd_arrive_day' =>  $location['rgd_arrive_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_arrive_day']) : null,
                            ]);
                        }

                        if ($warehousing_status) {
                            WarehousingStatus::insert([
                                'w_no' => $request->w_no,
                                'mb_no' => $member->mb_no,
                                'status' => $warehousing_status,
                                'w_category_name' => $request->w_category_name,
                            ]);
                        }
                        // if ($warehousing_status3) {
                        //     WarehousingStatus::insert([
                        //         'w_no' => $request->w_no,
                        //         'mb_no' => $member->mb_no,
                        //         'status' => $warehousing_status3,
                        //         'w_category_name' => $request->w_category_name,
                        //     ]);
                        // }
                    }
                }

                $package = $request->package;

                if (isset($package)) {
                    //foreach ($data['package'] as $package) {
                    if (isset($package['p_no'])) {
                        Package::where('p_no', $package['p_no'])->update([
                            'w_no' => $request->w_no,
                            'note' => $package['note'],
                            'order_number' => $package['order_number'] ? $package['order_number'] : CommonFunc::generate_w_schedule_number($request->w_no, 'EWC'),
                            'pack_type' => $package['pack_type'],
                            'quantity' => $package['quantity'],
                            'reciever' => $package['reciever'],
                            'reciever_address' => $package['reciever_address'],
                            'reciever_contract' => $package['reciever_contract'],
                            'reciever_detail_address' => $package['reciever_detail_address'],
                            'sender' => $package['sender'],
                            'sender_address' => $package['sender_address'],
                            'sender_contract' => $package['sender_contract'],
                            'sender_detail_address' => $package['sender_detail_address']
                        ]);
                    } else {
                        Package::insert([
                            'w_no' => $request->w_no,
                            'note' => $package['note'],
                            'order_number' => $package['order_number'] ? $package['order_number'] : CommonFunc::generate_w_schedule_number($request->w_no, 'EWC'),
                            'pack_type' => $package['pack_type'],
                            'quantity' => $package['quantity'],
                            'reciever' => $package['reciever'],
                            'reciever_address' => $package['reciever_address'],
                            'reciever_contract' => $package['reciever_contract'],
                            'reciever_detail_address' => $package['reciever_detail_address'],
                            'sender' => $package['sender'],
                            'sender_address' => $package['sender_address'],
                            'sender_contract' => $package['sender_contract'],
                            'sender_detail_address' => $package['sender_detail_address']
                        ]);
                    }
                    //}
                }
            } else {
                //Page141
                $warehousing_data = Warehousing::where('w_no', $request->w_no)->first();

                if ($warehousing_data->w_type == 'IW') {
                    foreach ($request->data as $key => $data) {
                        $w_no = Warehousing::insertGetId([
                            'mb_no' => $member->mb_no,
                            'w_schedule_amount' => $data['w_schedule_amount'],
                            'w_schedule_day' => $request->w_schedule_day,
                            'w_import_no' => $data['w_import_no'],
                            'w_type' => 'EW',
                            'w_category_name' => '유통가공',
                            'co_no' => $warehousing_data->co_no
                        ]);

                        Warehousing::where('w_no', $data['w_import_no'])->update([
                            'w_children_yn' => "y"
                        ]);
                        $w_schedule_number = CommonFunc::generate_w_schedule_number($request->w_no, 'EW', ($key + 1));
                        Warehousing::where('w_no', $w_no)->update([
                            'w_schedule_number' =>   CommonFunc::generate_w_schedule_number($request->w_no, 'EW', ($key + 1))
                        ]);

                        if (isset($request->page_type) && $request->page_type == 'Page130146') {
                            $w_schedule_number2 = CommonFunc::generate_w_schedule_number($request->w_no, 'EWC', ($key + 1));
                            Warehousing::where('w_no', $request->w_no)->update([
                                'w_schedule_number2' =>   CommonFunc::generate_w_schedule_number($request->w_no, 'EWC', ($key + 1)),
                                'w_completed_day' => Carbon::now()->toDateTimeString()
                            ]);
                        }

                        if ($request->wr_contents) {
                            WarehousingRequest::insert([
                                'w_no' => $w_no,
                                'mb_no' => $member->mb_no,
                                'wr_contents' => $request->wr_contents,
                                'wr_type' => 'EW',
                            ]);
                        }
                        foreach ($data['items'] as $item) {
                            WarehousingItem::insert([
                                'item_no' => $item['item_no'],
                                'w_no' => $w_no,
                                'wi_number' => $item['schedule_wi_number'],
                                'wi_type' => '출고_shipper'
                            ]);
                        }

                        foreach ($data['location'] as $location) {
                            $warehousing_status =  isset($location['rgd_status1']) ? $location['rgd_status1'] : null;
                            //$warehousing_status3 =  isset($location['rgd_status3']) ? $location['rgd_status3'] : null;

                            $rgd_no = ReceivingGoodsDelivery::insertGetId([
                                'mb_no' => $member->mb_no,
                                'w_no' => $w_no,
                                'service_korean_name' => '유통가공',
                                'rgd_contents' => $location['rgd_contents'],
                                'rgd_address' => $location['rgd_address'],
                                'rgd_address_detail' => $location['rgd_address_detail'],
                                'rgd_receiver' => $location['rgd_receiver'],
                                'rgd_hp' => $location['rgd_hp'],
                                'rgd_memo' => $location['rgd_memo'],
                                'rgd_status1' => $location['rgd_status1'],
                                'rgd_status2' => $location['rgd_status2'],
                                'rgd_status3' => $location['rgd_status3'],
                                'rgd_status4' => isset($location['rgd_status4']) ? $location['rgd_status4'] : null,
                                'rgd_delivery_company' => $location['rgd_delivery_company'],
                                'rgd_tracking_code' => $location['rgd_tracking_code'],
                                'rgd_delivery_man' => $location['rgd_delivery_man'],
                                'rgd_delivery_man_hp' => $location['rgd_delivery_man_hp'],
                                'rgd_delivery_schedule_day' => $location['rgd_delivery_schedule_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_delivery_schedule_day']) : null,
                                'rgd_arrive_day' => $location['rgd_arrive_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_arrive_day']) : null,
                            ]);
                        }

                        if ($warehousing_status) {
                            WarehousingStatus::insert([
                                'w_no' => $w_no,
                                'mb_no' => $member->mb_no,
                                'status' => $warehousing_status,
                                'w_category_name' => '유통가공',
                            ]);
                        }
                        // if ($warehousing_status) {
                        //     WarehousingStatus::insert([
                        //         'w_no' => $w_no,
                        //         'mb_no' => $member->mb_no,
                        //         'status' => $warehousing_status3,
                        //         'w_category_name' => $request->w_category_name,
                        //     ]);
                        // }
                    }
                } else {
                    if (isset($request->data)) {
                        $data_request = [];

                        foreach ($request->data as $key => $data) {
                            if ($data['w_no'] != ""){
                                $warehousing_request = WarehousingRequest::with('warehousing')->where('w_no', $data['w_no'])->whereHas('warehousing.w_import_parent',function ($q) use ($data){
                                    $q->where('w_no',$data['w_import_no']);
                                })->get();         
                            }
                        }

                        $data_request = isset($warehousing_request) ? $warehousing_request : null;

                       

                        foreach ($request->data as $key => $data) {
                                    
                            if ($data['w_no'] != "") {

                                $w_no = Warehousing::where('w_no', $request->w_no)->update([
                                    'w_schedule_amount' => $data['w_schedule_amount'],
                                    'w_schedule_day' => $request->w_schedule_day,
                                    'w_import_no' => $warehousing_data->w_import_no,
                                    'w_amount' => $data['w_amount'],
                                    'w_type' => 'EW',
                                    'w_category_name' => '유통가공',
                                ]);

                                Warehousing::where('w_no', $warehousing_data->w_import_no)->update([
                                    'w_children_yn' => "y"
                                ]);

                                $w_schedule_number = CommonFunc::generate_w_schedule_number($request->w_no, 'EW', $key);
                                Warehousing::where('w_no', $request->w_no)->update([
                                    'w_schedule_number' =>   CommonFunc::generate_w_schedule_number($request->w_no, 'EW', $key)
                                ]);

                                if (isset($request->page_type) && $request->page_type == 'Page130146') {
                                    $w_schedule_number2 = CommonFunc::generate_w_schedule_number($request->w_no, 'EWC', $key);
                                    Warehousing::where('w_no', $request->w_no)->update([
                                        'w_schedule_number2' =>   CommonFunc::generate_w_schedule_number($request->w_no, 'EWC', $key),
                                        'w_completed_day' => Carbon::now()->toDateTimeString()
                                    ]);
                                }


                                if ($request->wr_contents) {
                                    WarehousingRequest::insert([
                                        'w_no' => $request->w_no,
                                        'mb_no' => $member->mb_no,
                                        'wr_contents' => $request->wr_contents,
                                        'wr_type' => 'EW',
                                    ]);
                                }


                                foreach ($data['remove_items'] as $remove) {
                                    WarehousingItem::where('item_no', $remove['item_no'])->where('w_no', $request->w_no)->delete();
                                }

                                if ($request->page_type != 'Page146') {
                                    foreach ($data['items'] as $item) {
                                        WarehousingItem::where('w_no', $request->w_no)->where('item_no', $item['item_no'])->where('wi_type', '=', '출고_shipper')->update([
                                            'item_no' => $item['item_no'],
                                            'w_no' => $request->w_no,
                                            'wi_number' => $item['schedule_wi_number'],
                                            'wi_type' => '출고_shipper'
                                        ]);
                                        WarehousingItem::where('w_no', $request->w_no)->where('item_no', $item['item_no'])->where('wi_type', '=', '출고_spasys')->update([
                                            'item_no' => $item['item_no'],
                                            'w_no' => $request->w_no,
                                            'wi_number' => $item['schedule_wi_number'],
                                            'wi_type' => '출고_spasys'
                                        ]);
                                    }
                                }

                                foreach ($data['location'] as $location) {
                                    $warehousing_status = isset($location['rgd_status1']) ? $location['rgd_status1'] : null;
                                    //$warehousing_status3 = isset($location['rgd_status3']) ? $location['rgd_status3'] : null;
                                    $rgd_data = ReceivingGoodsDelivery::where('w_no', $request->w_no)->first();

                                    if ($warehousing_status != $rgd_data->rgd_status1) {
                                        $warehousing_status = isset($location['rgd_status1']) ? $location['rgd_status1'] : null;
                                    } else {
                                        $warehousing_status = null;
                                    }

                                    // if ($warehousing_status != $rgd_data->rgd_status3) {
                                    //     $warehousing_status3 = isset($location['rgd_status3']) ? $location['rgd_status3'] : null;
                                    // } else {
                                    //     $warehousing_status3 = null;
                                    // }

                                    $rgd_no = ReceivingGoodsDelivery::where('w_no', $request->w_no)->update([
                                        'mb_no' => $member->mb_no,
                                        'w_no' => $request->w_no,
                                        'service_korean_name' => '유통가공',
                                        'rgd_contents' => $location['rgd_contents'],
                                        'rgd_address' => $location['rgd_address'],
                                        'rgd_address_detail' => $location['rgd_address_detail'],
                                        'rgd_receiver' => $location['rgd_receiver'],
                                        'rgd_hp' => $location['rgd_hp'],
                                        'rgd_memo' => $location['rgd_memo'],
                                        'rgd_status1' => $location['rgd_status1'],
                                        'rgd_status2' => $location['rgd_status2'],
                                        'rgd_status3' => $location['rgd_status3'],
                                        'rgd_status4' => isset($location['rgd_status4']) ? $location['rgd_status4'] : null,
                                        'rgd_delivery_company' => $location['rgd_delivery_company'],
                                        'rgd_tracking_code' => $location['rgd_tracking_code'],
                                        'rgd_delivery_man' => $location['rgd_delivery_man'],
                                        'rgd_delivery_man_hp' => $location['rgd_delivery_man_hp'],
                                        'rgd_delivery_schedule_day' => $location['rgd_delivery_schedule_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_delivery_schedule_day']) : null,
                                        'rgd_arrive_day' => $location['rgd_arrive_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_arrive_day']) : ($request->page_type == 'Page146' && $location['rgd_status3'] == "배송완료" ? Carbon::now()->toDateTimeString() : null),
                                    ]);
                                }

                                if ($warehousing_status) {
                                    WarehousingStatus::insert([
                                        'w_no' => $w_no,
                                        'mb_no' => $member->mb_no,
                                        'status' => $warehousing_status,
                                        'w_category_name' => '유통가공',
                                    ]);
                                }

                                // if ($warehousing_status3) {
                                //     WarehousingStatus::insert([
                                //         'w_no' => $w_no,
                                //         'mb_no' => $member->mb_no,
                                //         'status' => $warehousing_status3,
                                //         'w_category_name' => $request->w_category_name,
                                //     ]);
                                // }
                            } else {
                                $w_no = Warehousing::insertGetId([
                                    'mb_no' => $member->mb_no,
                                    'w_schedule_amount' => $data['w_schedule_amount'],
                                    'w_schedule_day' => $request->w_schedule_day,
                                    'w_import_no' => $data['w_import_no'],
                                    'w_amount' => $data['w_amount'],
                                    'w_type' => 'EW',
                                    'w_category_name' => '유통가공',
                                    'co_no' => $request->co_no ? $request->co_no : $co_no,
                                    'connection_number' => $request->connection_number
                                ]);
                                $w_schedule_number = CommonFunc::generate_w_schedule_number($w_no, 'EW');
                                Warehousing::where('w_no', $w_no)->update([
                                    'w_schedule_number' =>   CommonFunc::generate_w_schedule_number($w_no, 'EW')
                                ]);

                                if (isset($request->page_type) && $request->page_type == 'Page130146') {
                                    $w_schedule_number2 = CommonFunc::generate_w_schedule_number($w_no, 'EWC');
                                    Warehousing::where('w_no', $w_no)->update([
                                        'w_schedule_number2' =>   CommonFunc::generate_w_schedule_number($w_no, 'EWC'),
                                        'w_completed_day' => Carbon::now()->toDateTimeString()
                                    ]);
                                }

                                if ($request->wr_contents) {
                                    WarehousingRequest::insert([
                                        'w_no' => $w_no,
                                        'mb_no' => $member->mb_no,
                                        'wr_contents' => $request->wr_contents,
                                        'wr_type' => 'EW',
                                    ]);
                                }

                                if(isset($data_request)){
                                    foreach ($data_request as $warehousing_r) {
                                        WarehousingRequest::insert([
                                            'w_no' => $w_no,
                                            'mb_no' => $member->mb_no,
                                            'wr_contents' => $warehousing_r->wr_contents,
                                            'wr_type' => 'EW',
                                        ]);
                                    }
                                }
                                

                                foreach ($data['items'] as $item) {
                                    WarehousingItem::insert([
                                        'item_no' => $item['item_no'],
                                        'w_no' => $w_no,
                                        'wi_number' => $item['schedule_wi_number'],
                                        'wi_type' => '출고_shipper'
                                    ]);
                                }

                                foreach ($data['location'] as $location) {
                                    $warehousing_status =  isset($location['rgd_status1']) ? $location['rgd_status1'] : null;
                                    //$warehousing_status3 =  isset($location['rgd_status3']) ? $location['rgd_status3'] : null;
                                    $rgd_no = ReceivingGoodsDelivery::insertGetId([
                                        'mb_no' => $member->mb_no,
                                        'w_no' => $w_no,
                                        'service_korean_name' => '유통가공',
                                        'rgd_contents' => $location['rgd_contents'],
                                        'rgd_address' => $location['rgd_address'],
                                        'rgd_address_detail' => $location['rgd_address_detail'],
                                        'rgd_receiver' => $location['rgd_receiver'],
                                        'rgd_hp' => $location['rgd_hp'],
                                        'rgd_memo' => $location['rgd_memo'],
                                        'rgd_status1' => $location['rgd_status1'],
                                        'rgd_status2' => $location['rgd_status2'],
                                        'rgd_status3' => $location['rgd_status3'],
                                        'rgd_status4' => isset($location['rgd_status4']) ? $location['rgd_status4'] : null,
                                        'rgd_delivery_company' => $location['rgd_delivery_company'],
                                        'rgd_tracking_code' => $location['rgd_tracking_code'],
                                        'rgd_delivery_man' => $location['rgd_delivery_man'],
                                        'rgd_delivery_man_hp' => $location['rgd_delivery_man_hp'],
                                        'rgd_delivery_schedule_day' => $location['rgd_delivery_schedule_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_delivery_schedule_day']) : null,
                                        'rgd_arrive_day' => $location['rgd_arrive_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_arrive_day']) : ($request->page_type == 'Page146' && $location['rgd_status3'] == "배송완료" ? Carbon::now()->toDateTimeString() : null),
                                    ]);
                                }

                                if ($warehousing_status) {
                                    WarehousingStatus::insert([
                                        'w_no' => $w_no,
                                        'mb_no' => $member->mb_no,
                                        'status' => $warehousing_status,
                                        'w_category_name' => '유통가공',
                                    ]);
                                }
                                // if ($warehousing_status3) {
                                //     WarehousingStatus::insert([
                                //         'w_no' => $w_no,
                                //         'mb_no' => $member->mb_no,
                                //         'status' => $warehousing_status3,
                                //         'w_category_name' => $request->w_category_name,
                                //     ]);
                                // }
                            }
                        }
                    }
                }
            }

            if (isset($request->connection_number)) {
                if (isset($request->w_no)) {
                    Warehousing::where('w_no', $request->w_no)->update([
                        'connection_number' => $request->connection_number
                    ]);
                    // if ($request->type_w_choose == "export") {
                    //     $connection_number_old = Export::where('te_carry_out_number',  $request->connect_w)->first();

                    //     if (isset($connection_number_old->connection_number)) {
                    //         Warehousing::where('connection_number', $connection_number_old->connection_number)->update([
                    //             'connection_number' => null
                    //         ]);
                    //     }
                    //     Export::where('te_carry_out_number', $request->connect_w)->update([
                    //         'connection_number' => $request->connection_number
                    //     ]);
                    // } else {
                    //     $connection_number_old = Warehousing::where('w_no', $request->connect_w)->first();

                    //     if (isset($connection_number_old->connection_number)) {
                    //         Warehousing::where('connection_number', $connection_number_old->connection_number)->update([
                    //             'connection_number' => null
                    //         ]);
                    //     }
                    //     Warehousing::where('w_no', $request->connect_w)->update([
                    //         'connection_number' => $request->connection_number
                    //     ]);
                    // }
                }
            }

            //Alert
            if (isset($request->page_type) && $request->page_type == 'Page130146') {
                if (isset($request->w_no)) {
                    $w_no_alert = Warehousing::with(['receving_goods_delivery', 'w_import_parent'])->where('w_no', $request->w_no)->first();

                    $rgd_delivery_schedule_day  = isset($w_no_alert->receving_goods_delivery[0]->rgd_delivery_schedule_day) ? $w_no_alert->receving_goods_delivery[0]->rgd_delivery_schedule_day : null;

                    if ($rgd_delivery_schedule_day) {
                        CommonFunc::insert_alarm_cargo('[유통가공] 출고예정', null, $user, $w_no_alert, 'cargo_EW');
                    }

                    $rgdstatus1  = isset($w_no_alert->receving_goods_delivery[0]->rgd_status1) ? $w_no_alert->receving_goods_delivery[0]->rgd_status1 : null;

                    if ($rgdstatus1 == "출고") {
                        CommonFunc::insert_alarm_cargo('[유통가공] 출고', null, $user, $w_no_alert, 'cargo_EW');
                    }

                    $check_alarm_first = Alarm::with(['alarm_data'])->where('w_no', isset($request->w_no) ? $request->w_no : $w_no)->whereHas('alarm_data', function ($query) {
                        $query->where(DB::raw('lower(ad_title)'), 'like', '%' . strtolower('[유통가공] 배송중') . '%');
                    })->first();

                    $rgd_status3  = isset($w_no_alert->receving_goods_delivery[0]->rgd_status3) ? $w_no_alert->receving_goods_delivery[0]->rgd_status3 : null;

                    if ($check_alarm_first === null && $rgd_status3 == '배송중')
                        CommonFunc::insert_alarm_cargo('[유통가공] 배송중', null, $user, $w_no_alert, 'cargo_delivery');
                }
            } else {
                if ($user->mb_type != 'spasys' && $request->wr_contents) {
                    $w_no_alert = Warehousing::with(['receving_goods_delivery'])->where('w_no', isset($request->w_no) ? $request->w_no : $w_no)->first();
                    CommonFunc::insert_alarm_cargo_request('[유통가공]', $request->wr_contents, $user, $w_no_alert, 'cargo_request');
                }

                if ($request->page_type == 'Page146') {
                    $w_no_alert = Warehousing::with(['receving_goods_delivery'])->where('w_no', isset($request->w_no) ? $request->w_no : $w_no)->first();
                    $check_alarm_first = Alarm::with(['alarm_data'])->where('w_no', isset($request->w_no) ? $request->w_no : $w_no)->whereHas('alarm_data', function ($query) {
                        $query->where(DB::raw('lower(ad_title)'), 'like', '%' . strtolower('[유통가공] 배송중') . '%');
                    })->first();

                    $rgd_status3  = isset($w_no_alert->receving_goods_delivery[0]->rgd_status3) ? $w_no_alert->receving_goods_delivery[0]->rgd_status3 : null;

                    if ($check_alarm_first === null && $rgd_status3 == '배송중')
                        CommonFunc::insert_alarm_cargo('[유통가공] 배송중', null, $user, $w_no_alert, 'cargo_delivery');
                }
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'w_schedule_number' =>  isset($w_schedule_number) ? $w_schedule_number : '',
                'w_schedule_number2' => isset($w_schedule_number2) ? $w_schedule_number2 : '',
                'w_no' => isset($w_no) ? $w_no :  $request->w_no,
                //'w_no_alert' => isset($w_no_alert) ? $w_no_alert : null,
                //'check_alarm_first' => isset($check_alarm_first) ? $check_alarm_first : null,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function create_warehousing_release_fulfillment(Request $request)
    {

        try {
            DB::beginTransaction();

            $co_no = Auth::user()->co_no ? Auth::user()->co_no : null;
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $user = Auth::user();

            foreach ($request->location as $location) {
                if ($request->ss_no) {

                    $rgd_no = ReceivingGoodsDelivery::updateOrCreate(
                        [
                            'ss_no' => $request->ss_no
                        ],
                        [
                            'ss_no' => $request->ss_no,
                            'mb_no' => $member->mb_no,
                            'service_korean_name' => $request->w_category_name,
                            'rgd_status1' => isset($location['rgd_status1']) ? $location['rgd_status1'] : null,
                            'rgd_status2' => isset($location['rgd_status2']) ? $location['rgd_status2'] : null,
                            'rgd_status3' => isset($location['rgd_status3']) ? $location['rgd_status3'] : null,
                            'rgd_contents' => isset($location['rgd_contents']) ? $location['rgd_contents'] : null,
                            'rgd_delivery_company' => isset($location['rgd_delivery_company']) ? $location['rgd_delivery_company'] : null,
                            'rgd_tracking_code' => isset($location['rgd_tracking_code']) ? $location['rgd_tracking_code'] : null,
                            'rgd_delivery_man' => isset($location['rgd_delivery_man']) ? $location['rgd_delivery_man'] : '',
                            'rgd_delivery_man_hp' => isset($location['rgd_delivery_man_hp']) ? $location['rgd_delivery_man_hp'] : '',

                            'rgd_delivery_schedule_day' => isset($location['rgd_delivery_schedule_day']) ? DateTime::createFromFormat('Y-m-d', $location['rgd_delivery_schedule_day']) : null,
                            'rgd_arrive_day' =>  isset($location['rgd_arrive_day']) ? DateTime::createFromFormat('Y-m-d', $location['rgd_arrive_day']) : null,
                        ]
                    );
                }
            }

            if ($request->wr_contents) {
                // WarehousingRequest::where('w_no', $request->w_no)->update([
                //     'mb_no' => $member->mb_no,
                //     'wr_contents' => $request->wr_contents,
                //     'wr_type' => 'EW',
                // ]);

                if ($request->wr_contents) {
                    WarehousingRequest::insert([
                        'w_no' => $request->ss_no,
                        'mb_no' => $member->mb_no,
                        'wr_contents' => $request->wr_contents,
                        'wr_type' => 'IW',
                    ]);
                }
            }

            //alert
            $w_no_alert = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('ss_no', $request->ss_no)->first();

            $check_alarm_first = Alarm::with(['alarm_data'])->where('w_no', $request->ss_no)->whereHas('alarm_data', function ($query) {
                $query->where(DB::raw('lower(ad_title)'), 'like', '%' . strtolower('[수입풀필먼트] 배송중') . '%');
            })->first();

            $rgd_status3  = isset($w_no_alert->receving_goods_delivery[0]->rgd_status3) ? $w_no_alert->receving_goods_delivery[0]->rgd_status3 : null;

            if ($check_alarm_first === null && $rgd_status3 == '배송중')
                CommonFunc::insert_alarm_cargo('[수입풀필먼트] 배송중', null, $user, $w_no_alert, 'cargo_delivery');



            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'ss_no' => isset($ss_no) ? $ss_no :  $request->ss_no,
                'w_no_alert' => isset($w_no_alert) ? $w_no_alert : null,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }
    public function create_warehousing_release_mobile(Request $request)
    {

        try {
            DB::beginTransaction();

            $co_no = Auth::user()->co_no ? Auth::user()->co_no : null;
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();


            if ($request->page_type == 'Page130146') {
                $warehousing_data = Warehousing::where('w_no', $request->w_no)->first();

                foreach ($request->data as $data) {
                    Warehousing::where('w_no', $request->w_no)->update([
                        //'mb_no' => $member->mb_no,
                        'w_schedule_amount' => $data['w_schedule_amount'],
                        'w_schedule_day' => $request->w_schedule_day,
                        'w_import_no' => $warehousing_data->w_import_no,
                        'w_amount' => $data['w_amount'],
                        'w_type' => 'EW',
                        'w_category_name' => $request->w_category_name,
                        //'co_no' => $co_no
                    ]);

                    $w_schedule_number = CommonFunc::generate_w_schedule_number($request->w_no, 'EW');
                    Warehousing::where('w_no', $request->w_no)->update([
                        'w_schedule_number' =>   CommonFunc::generate_w_schedule_number($request->w_no, 'EW')
                    ]);

                    if (isset($request->page_type) && $request->page_type == 'Page130146') {
                        $w_schedule_number2 = CommonFunc::generate_w_schedule_number($request->w_no, 'EWC');

                        Warehousing::where('w_no', $request->w_no)->update([
                            'w_schedule_number2' =>  CommonFunc::generate_w_schedule_number($request->w_no, 'EWC'),
                            'w_completed_day' => Carbon::now()->toDateTimeString()
                        ]);
                    }

                    if ($request->wr_contents) {
                        // WarehousingRequest::where('w_no', $request->w_no)->update([
                        //     'mb_no' => $member->mb_no,
                        //     'wr_contents' => $request->wr_contents,
                        //     'wr_type' => 'EW',
                        // ]);

                        if ($request->wr_contents) {
                            WarehousingRequest::insert([
                                'w_no' => $request->w_no,
                                'mb_no' => $member->mb_no,
                                'wr_contents' => $request->wr_contents,
                                'wr_type' => 'EW',
                            ]);
                        }
                    }
                    $package = $request->package;

                    if (isset($package)) {
                        //foreach ($data['package'] as $package) {
                        if (isset($package['p_no'])) {
                            Package::where('p_no', $package['p_no'])->update([
                                'w_no' => $request->w_no,
                                'note' => $package['note'],
                                'order_number' => $package['order_number'],
                                'pack_type' => $package['pack_type'],
                                'quantity' => $package['quantity'],
                                'reciever' => $package['reciever'],
                                'reciever_address' => $package['reciever_address'],
                                'reciever_contract' => $package['reciever_contract'],
                                'reciever_detail_address' => $package['reciever_detail_address'],
                                'sender' => $package['sender'],
                                'sender_address' => $package['sender_address'],
                                'sender_contract' => $package['sender_contract'],
                                'sender_detail_address' => $package['sender_detail_address']
                            ]);
                        } else {
                            Package::insert([
                                'w_no' => $request->w_no,
                                'note' => $package['note'],
                                'order_number' => $package['order_number'],
                                'pack_type' => $package['pack_type'],
                                'quantity' => $package['quantity'],
                                'reciever' => $package['reciever'],
                                'reciever_address' => $package['reciever_address'],
                                'reciever_contract' => $package['reciever_contract'],
                                'reciever_detail_address' => $package['reciever_detail_address'],
                                'sender' => $package['sender'],
                                'sender_address' => $package['sender_address'],
                                'sender_contract' => $package['sender_contract'],
                                'sender_detail_address' => $package['sender_detail_address']
                            ]);
                        }
                        //}
                    }

                    foreach ($data['remove_items'] as $remove) {
                        WarehousingItem::where('item_no', $remove['item_no'])->where('w_no', $request->w_no)->delete();
                    }

                    //WarehousingItem::where('w_no', $request->w_no)->where('wi_type','=','출고')->delete();

                    foreach ($data['items'] as $item) {




                        WarehousingItem::updateOrCreate(
                            [
                                'item_no' => $item['item_no'],
                                'w_no' => $request->w_no,
                                'wi_type' => '출고_spasys'
                            ],
                            [
                                'item_no' => $item['item_no'],
                                'w_no' => $request->w_no,
                                'wi_number' => $item['schedule_wi_number'],
                                //'wi_number_received' =>  $item['warehousing_item']['wi_number_received'],
                                'wi_type' => '출고_spasys',
                            ]
                        );
                    }

                    foreach ($data['location'] as $location) {
                        $rgd_no = ReceivingGoodsDelivery::where('w_no', $request->w_no)->update([
                            'mb_no' => $member->mb_no,
                            'w_no' => $request->w_no,
                            'service_korean_name' => $request->w_category_name,
                            'rgd_contents' => $location['rgd_contents'],
                            'rgd_address' => $location['rgd_address'],
                            'rgd_address_detail' => $location['rgd_address_detail'],
                            'rgd_receiver' => $location['rgd_receiver'],
                            'rgd_hp' => $location['rgd_hp'],
                            'rgd_memo' => $location['rgd_memo'],
                            'rgd_status1' => $location['rgd_status1'],
                            'rgd_status2' => $location['rgd_status2'],
                            'rgd_status3' => $location['rgd_status3'],
                            'rgd_delivery_company' => $location['rgd_delivery_company'],
                            'rgd_tracking_code' => $location['rgd_tracking_code'],
                            'rgd_delivery_man' => $location['rgd_delivery_man'],
                            'rgd_delivery_man_hp' => $location['rgd_delivery_man_hp'],
                            'rgd_delivery_schedule_day' => $location['rgd_delivery_schedule_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_delivery_schedule_day']) : null,
                            'rgd_arrive_day' =>  $location['rgd_arrive_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_arrive_day']) : null,
                        ]);
                    }
                }
            } else {
                //Page141
                $warehousing_data = Warehousing::where('w_no', $request->w_no)->first();

                if ($warehousing_data->w_type == 'IW') {
                    foreach ($request->data as $key => $data) {
                        $w_no = Warehousing::insertGetId([
                            'mb_no' => $member->mb_no,
                            'w_schedule_amount' => $data['w_schedule_amount'],
                            'w_schedule_day' => $request->w_schedule_day,
                            'w_import_no' => $data['w_import_no'],
                            'w_type' => 'EW',
                            'w_category_name' => $request->w_category_name,
                            'co_no' => $warehousing_data->co_no
                        ]);

                        Warehousing::where('w_no', $data['w_import_no'])->update([
                            'w_children_yn' => "y"
                        ]);
                        $w_schedule_number = CommonFunc::generate_w_schedule_number($request->w_no, 'EW', ($key + 1));
                        Warehousing::where('w_no', $w_no)->update([
                            'w_schedule_number' =>   CommonFunc::generate_w_schedule_number($request->w_no, 'EW', ($key + 1))
                        ]);

                        if (isset($request->page_type) && $request->page_type == 'Page130146') {
                            $w_schedule_number2 = CommonFunc::generate_w_schedule_number($request->w_no, 'EWC', ($key + 1));
                            Warehousing::where('w_no', $request->w_no)->update([
                                'w_schedule_number2' =>   CommonFunc::generate_w_schedule_number($request->w_no, 'EWC', ($key + 1)),
                                'w_completed_day' => Carbon::now()->toDateTimeString()
                            ]);
                        }

                        if ($request->wr_contents) {
                            WarehousingRequest::insert([
                                'w_no' => $w_no,
                                'mb_no' => $member->mb_no,
                                'wr_contents' => $request->wr_contents,
                                'wr_type' => 'EW',
                            ]);
                        }
                        foreach ($data['items'] as $item) {
                            WarehousingItem::insert([
                                'item_no' => $item['item_no'],
                                'w_no' => $w_no,
                                'wi_number' => $item['schedule_wi_number'],
                                'wi_type' => '출고_shipper'
                            ]);
                        }

                        foreach ($data['location'] as $location) {
                            $rgd_no = ReceivingGoodsDelivery::insertGetId([
                                'mb_no' => $member->mb_no,
                                'w_no' => $w_no,
                                'service_korean_name' => $request->w_category_name,
                                'rgd_contents' => $location['rgd_contents'],
                                'rgd_address' => $location['rgd_address'],
                                'rgd_address_detail' => $location['rgd_address_detail'],
                                'rgd_receiver' => $location['rgd_receiver'],
                                'rgd_hp' => $location['rgd_hp'],
                                'rgd_memo' => $location['rgd_memo'],
                                'rgd_status1' => $location['rgd_status1'],
                                'rgd_status2' => $location['rgd_status2'],
                                'rgd_status3' => $location['rgd_status3'],
                                'rgd_delivery_company' => $location['rgd_delivery_company'],
                                'rgd_tracking_code' => $location['rgd_tracking_code'],
                                'rgd_delivery_man' => $location['rgd_delivery_man'],
                                'rgd_delivery_man_hp' => $location['rgd_delivery_man_hp'],
                                'rgd_delivery_schedule_day' => $location['rgd_delivery_schedule_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_delivery_schedule_day']) : null,
                                'rgd_arrive_day' => $location['rgd_arrive_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_arrive_day']) : null,
                            ]);
                        }
                    }
                } else {
                    foreach ($request->data as $key => $data) {
                        if ($data['w_no'] != "") {
                            $w_no = Warehousing::where('w_no', $request->w_no)->update([
                                'w_schedule_amount' => $data['w_schedule_amount'],
                                'w_schedule_day' => $request->w_schedule_day,
                                'w_import_no' => $warehousing_data->w_import_no,
                                'w_amount' => $data['w_amount'],
                                'w_type' => 'EW',
                                'w_category_name' => $request->w_category_name,
                            ]);
                            Warehousing::where('w_no', $warehousing_data->w_import_no)->update([
                                'w_children_yn' => "y"
                            ]);
                            $w_schedule_number = CommonFunc::generate_w_schedule_number($request->w_no, 'EW', $key);
                            Warehousing::where('w_no', $request->w_no)->update([
                                'w_schedule_number' =>   CommonFunc::generate_w_schedule_number($request->w_no, 'EW', $key)
                            ]);
                            if (isset($request->page_type) && $request->page_type == 'Page130146') {
                                $w_schedule_number2 = CommonFunc::generate_w_schedule_number($request->w_no, 'EWC', $key);
                                Warehousing::where('w_no', $request->w_no)->update([
                                    'w_schedule_number2' =>   CommonFunc::generate_w_schedule_number($request->w_no, 'EWC', $key),
                                    'w_completed_day' => Carbon::now()->toDateTimeString()
                                ]);
                            }
                            if ($request->wr_contents) {
                                if ($request->wr_contents) {
                                    WarehousingRequest::insert([
                                        'w_no' => $request->w_no,
                                        'mb_no' => $member->mb_no,
                                        'wr_contents' => $request->wr_contents,
                                        'wr_type' => 'EW',
                                    ]);
                                }
                            }

                            foreach ($data['items'] as $item) {
                                WarehousingItem::where('w_no', $request->w_no)->where('item_no', $item['item_no'])->update([
                                    'item_no' => $item['item_no'],
                                    'w_no' => $request->w_no,
                                    'wi_number' => $item['schedule_wi_number'],
                                    'wi_type' => '출고_shipper'
                                ]);
                            }

                            foreach ($data['location'] as $location) {
                                $rgd_no = ReceivingGoodsDelivery::where('w_no', $request->w_no)->update([
                                    'mb_no' => $member->mb_no,
                                    'w_no' => $request->w_no,
                                    'service_korean_name' => $request->w_category_name,
                                    'rgd_contents' => $location['rgd_contents'],
                                    'rgd_address' => $location['rgd_address'],
                                    'rgd_address_detail' => $location['rgd_address_detail'],
                                    'rgd_receiver' => $location['rgd_receiver'],
                                    'rgd_hp' => $location['rgd_hp'],
                                    'rgd_memo' => $location['rgd_memo'],
                                    'rgd_status1' => $location['rgd_status1'],
                                    'rgd_status2' => $location['rgd_status2'],
                                    'rgd_status3' => $location['rgd_status3'],
                                    'rgd_delivery_company' => $location['rgd_delivery_company'],
                                    'rgd_tracking_code' => $location['rgd_tracking_code'],
                                    'rgd_delivery_man' => $location['rgd_delivery_man'],
                                    'rgd_delivery_man_hp' => $location['rgd_delivery_man_hp'],
                                    'rgd_delivery_schedule_day' => $location['rgd_delivery_schedule_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_delivery_schedule_day']) : null,
                                    'rgd_arrive_day' => $location['rgd_arrive_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_arrive_day']) : null,
                                ]);
                            }
                        } else {
                            $w_no = Warehousing::insertGetId([
                                'mb_no' => $member->mb_no,
                                'w_schedule_amount' => $data['w_schedule_amount'],
                                'w_schedule_day' => $request->w_schedule_day,
                                'w_import_no' => $data['w_import_no'],
                                'w_amount' => $data['w_amount'],
                                'w_type' => 'EW',
                                'w_category_name' => $request->w_category_name,
                                'co_no' => $request->co_no ? $request->co_no : $co_no
                            ]);
                            $w_schedule_number = CommonFunc::generate_w_schedule_number($w_no, 'EW');
                            Warehousing::where('w_no', $w_no)->update([
                                'w_schedule_number' =>   CommonFunc::generate_w_schedule_number($w_no, 'EW')
                            ]);

                            if (isset($request->page_type) && $request->page_type == 'Page130146') {
                                $w_schedule_number2 = CommonFunc::generate_w_schedule_number($w_no, 'EWC');
                                Warehousing::where('w_no', $w_no)->update([
                                    'w_schedule_number2' =>   CommonFunc::generate_w_schedule_number($w_no, 'EWC'),
                                    'w_completed_day' => Carbon::now()->toDateTimeString()
                                ]);
                            }

                            if ($request->wr_contents) {
                                WarehousingRequest::insert([
                                    'w_no' => $w_no,
                                    'mb_no' => $member->mb_no,
                                    'wr_contents' => $request->wr_contents,
                                    'wr_type' => 'EW',
                                ]);
                            }

                            foreach ($data['items'] as $item) {
                                WarehousingItem::insert([
                                    'item_no' => $item['item_no'],
                                    'w_no' => $w_no,
                                    'wi_number' => $item['schedule_wi_number'],
                                    'wi_type' => '출고_shipper'
                                ]);
                            }

                            foreach ($data['location'] as $location) {
                                $rgd_no = ReceivingGoodsDelivery::insertGetId([
                                    'mb_no' => $member->mb_no,
                                    'w_no' => $w_no,
                                    'service_korean_name' => $request->w_category_name,
                                    'rgd_contents' => $location['rgd_contents'],
                                    'rgd_address' => $location['rgd_address'],
                                    'rgd_address_detail' => $location['rgd_address_detail'],
                                    'rgd_receiver' => $location['rgd_receiver'],
                                    'rgd_hp' => $location['rgd_hp'],
                                    'rgd_memo' => $location['rgd_memo'],
                                    'rgd_status1' => $location['rgd_status1'],
                                    'rgd_status2' => $location['rgd_status2'],
                                    'rgd_status3' => $location['rgd_status3'],
                                    'rgd_delivery_company' => $location['rgd_delivery_company'],
                                    'rgd_tracking_code' => $location['rgd_tracking_code'],
                                    'rgd_delivery_man' => $location['rgd_delivery_man'],
                                    'rgd_delivery_man_hp' => $location['rgd_delivery_man_hp'],
                                    'rgd_delivery_schedule_day' => $location['rgd_delivery_schedule_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_delivery_schedule_day']) : null,
                                    'rgd_arrive_day' => $location['rgd_arrive_day'] ? DateTime::createFromFormat('Y-m-d', $location['rgd_arrive_day']) : null,
                                ]);
                            }
                        }
                    }
                }
            }




            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'w_schedule_number' =>  $w_schedule_number,
                'w_schedule_number2' => isset($w_schedule_number2) ? $w_schedule_number2 : '',
                'w_no' => isset($w_no) ? $w_no :  $request->w_no,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function create_warehousing_api(ReceivingGoodsDeliveryCreateApiRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $co_no = Auth::user()->co_no ? Auth::user()->co_no : null;

            if (isset($validated['w_no'])) {
                Warehousing::where('w_no', $validated['w_no'])->update([
                    'w_schedule_amount' => $validated['w_schedule_amount'],
                    'w_schedule_day' => $validated['w_schedule_day'],
                    'w_amount' => $validated['w_amount'],
                    'w_type' => 'IW',
                    'w_category_name' => $request->w_category_name,
                    'w_completed_day' => Carbon::now()->toDateTimeString(),

                ]);
            } else {
                $w_no_data = Warehousing::insertGetId([
                    'mb_no' => $member->mb_no,
                    'w_schedule_day' => isset($validated['w_schedule_day']) ? DateTime::createFromFormat('Y-m-d', $validated['w_schedule_day']) : null,
                    'w_schedule_amount' => $validated['w_schedule_amount'],
                    'w_amount' => $validated['w_amount'],
                    'w_type' => 'IW',
                    'w_category_name' => $request->w_category_name,
                    'co_no' => isset($validated['co_no']) ? $validated['co_no'] : $co_no,
                    'w_completed_day' => Carbon::now()->toDateTimeString()
                ]);

                //THUONG EDIT TO MAKE SETTLEMENT
                $rgd_no = ReceivingGoodsDelivery::insertGetId([
                    'mb_no' => $member->mb_no,
                    'w_no' => $w_no_data,
                    'service_korean_name' => $request->w_category_name,
                    'rgd_status1' => '입고',
                    'rgd_status2' => '작업완료',
                ]);
            }

            $w_no = isset($validated['w_no']) ? $validated['w_no'] : $w_no_data;



            if (!isset($validate['w_schedule_number'])) {
                $w_schedule_number = (new CommonFunc)->generate_w_schedule_number($w_no, 'IWC');
            }


            Warehousing::where('w_no', $w_no)->update([
                'w_schedule_number' =>  $w_schedule_number
            ]);

            //T part


            if ($validated['wr_contents']) {
                WarehousingRequest::insert([
                    'w_no' => $w_no,
                    'mb_no' => $member->mb_no,
                    'wr_contents' => $validated['wr_contents'],
                    'wr_type' => 'IW',
                ]);
            }

            foreach ($validated['remove'] as $remove) {
                WarehousingItem::where('item_no', $remove['item_no'])->where('w_no', $w_no)->delete();
            }

            $warehousing_items = [];

            if (isset($validated['item_new'])) {
                foreach ($validated['item_new'] as $item_new) {
                    if (isset($item_new['item_name'])) {
                        $item_no_new = Item::insertGetId([
                            'mb_no' => Auth::user()->mb_no,
                            'item_brand' => $item_new['item_brand'],
                            'item_service_name' => '유통가공',
                            'co_no' => $validated['co_no'] ? $validated['co_no'] : $co_no,
                            'item_name' => $item_new['item_name'],
                            'item_option1' => $item_new['item_option1'],
                            'item_option2' => $item_new['item_option2'],
                            'item_price3' => $item_new['item_price3'],
                            'item_price4' => $item_new['item_price4']
                        ]);

                        WarehousingItem::insert([
                            'item_no' => $item_no_new,
                            'w_no' => $w_no,
                            'wi_number' => $item_new['wi_number'],
                            'wi_type' => '입고_shipper'
                        ]);

                        ItemChannel::insert(
                            [
                                'item_no' => $item_no_new,
                                'item_channel_code' => $item_new['item_channel_code'],
                                'item_channel_name' => $item_new['item_channel_name']
                            ]
                        );
                    }
                }
            }


            if (isset($validated['w_no'])) {
                foreach ($validated['items'] as $warehousing_item) {
                    $item_no = $warehousing_item['item_no'] ? $warehousing_item['item_no'] : '';


                    $checkexit1 = WarehousingItem::where('item_no', $item_no)->where('w_no', $validated['w_no'])->where('wi_type', '입고_spasys')->first();
                    if (!isset($checkexit1->wi_no)) {
                        WarehousingItem::insert([
                            'item_no' => isset($item_no) && $item_no != null ? $item_no : null,
                            'w_no' => $w_no,
                            'wi_number' => $warehousing_item['warehousing_item2'][0]['wi_number'],
                            'wi_type' => '입고_spasys'
                        ]);
                    } else {
                        $warehousing_items = WarehousingItem::where('item_no', $item_no)->where('w_no', $validated['w_no'])->where('wi_type', '입고_spasys')->update([
                            'wi_number' => $warehousing_item['warehousing_item2'][0]['wi_number'],
                        ]);
                    }

                    $checkexit2 = WarehousingItem::where('item_no', $item_no)->where('w_no', $validated['w_no'])->where('wi_type', '입고_shipper')->first();
                    if (!isset($checkexit2->wi_no)) {
                        WarehousingItem::insert([
                            'item_no' => isset($item_no) && $item_no != null ? $item_no : null,
                            'w_no' => $w_no,
                            'wi_number' => null,
                            'wi_type' => '입고_shipper'
                        ]);
                    } else {
                        $warehousing_items = WarehousingItem::where('item_no', $item_no)->where('w_no', $validated['w_no'])->where('wi_type', '입고_shipper')->update([
                            'wi_number' => null,
                        ]);
                    }
                }
            } else {
                foreach ($validated['items'] as $warehousing_item) {
                    WarehousingItem::insert([
                        'item_no' => $warehousing_item['item_no'],
                        'w_no' => $w_no,
                        'wi_number' => isset($warehousing_item['warehousing_item'][0]['wi_number']) ? $warehousing_item['warehousing_item'][0]['wi_number'] : null,
                        'wi_type' => '입고_shipper'
                    ]);

                    WarehousingItem::insert([
                        'item_no' => $warehousing_item['item_no'],
                        'w_no' => $w_no,
                        'wi_number' => isset($warehousing_item['warehousing_item2'][0]['wi_number']) ? $warehousing_item['warehousing_item2'][0]['wi_number'] : null,
                        'wi_type' => '입고_spasys'
                    ]);
                }
            }
            if (isset($request->connection_number)) {
                if (isset($request->w_no)) {
                    Warehousing::where('w_no', $request->w_no)->update([
                        'connection_number' => $request->connection_number
                    ]);
                } else {
                    Warehousing::where('w_no', $w_no)->update([
                        'connection_number' => $request->connection_number
                    ]);
                }
            }
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'w_schedule_number' =>  $w_schedule_number,
                'w_schedule_number2' => isset($w_schedule_number2) ? $w_schedule_number2 : '',
                'w_no' => isset($w_no) ? $w_no :  $validated['w_no'],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function update_rdc_cancel(Request $request)
    {

        try {

            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            if ($request->page_type == 'IW') {
                if ($request->datachkbox) {

                    foreach ($request->datachkbox as $value) {
                        //return $value['w_no']['w_no'];
                        if ($value['w_no']['w_no']) {
                            ReceivingGoodsDelivery::where('w_no', $value['w_no']['w_no'])->where('rgd_status1', '!=', '입고')->where(
                                function ($query) {
                                    $query->where('rgd_status2', '!=', '작업완료')
                                        ->orWhereNull('rgd_status2');
                                }
                            )->update([
                                'rgd_status1' => '입고예정 취소'
                            ]);

                            WarehousingStatus::insert([
                                'w_no' => $value['w_no']['w_no'],
                                'mb_no' => $member->mb_no,
                                'status' => '입고예정 취소',
                                'w_category_name' => '유통가공',
                            ]);
                        }
                    }
                } else {
                    if ($request->w_no) {
                        ReceivingGoodsDelivery::where('w_no', $request->w_no)
                            ->where('rgd_status1', '!=', '입고')->where(
                                function ($query) {
                                    $query->where('rgd_status2', '!=', '작업완료')
                                        ->orWhereNull('rgd_status2');
                                }
                            )->update([
                                'rgd_status1' => '입고예정 취소'
                            ]);

                        WarehousingStatus::insert([
                            'w_no' => $request->w_no,
                            'mb_no' => $member->mb_no,
                            'status' => '입고예정 취소',
                            'w_category_name' => '유통가공',
                        ]);
                    }
                }
            } else {
                if ($request->datachkbox) {

                    foreach ($request->datachkbox as $value) {
                        //return $value['w_no']['w_no'];
                        if ($value['w_no']['w_no']) {
                            ReceivingGoodsDelivery::where('w_no', $value['w_no']['w_no'])->where('rgd_status1', '!=', '출고')->update([
                                'rgd_status1' => '출고예정 취소'
                            ]);

                            WarehousingStatus::insert([
                                'w_no' => $value['w_no']['w_no'],
                                'mb_no' => $member->mb_no,
                                'status' => '출고예정 취소',
                                'w_category_name' => '유통가공',
                            ]);
                        }
                    }
                } else {
                    if ($request->w_no) {
                        ReceivingGoodsDelivery::where('w_no', $request->w_no)->where('rgd_status1', '!=', '출고')->update([
                            'rgd_status1' => '출고예정 취소'
                        ]);

                        WarehousingStatus::insert([
                            'w_no' => $request->w_no,
                            'mb_no' => $member->mb_no,
                            'status' => '출고예정 취소',
                            'w_category_name' => '유통가공',
                        ]);
                    }
                }
            }



            return response()->json(['message' => 'ok']);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function update_rdc_api_cancel(Request $request)
    {

        try {

            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            if ($request->page_type == 'IW') {
                //Service 2 change
                if ($request->datachkbox) {

                    foreach ($request->datachkbox as $value) {
                        //return $value['w_no']['w_no'];
                        if ($value['w_no']['w_no']) {
                            ReceivingGoodsDelivery::where('w_no', $value['w_no']['w_no'])->update([
                                'rgd_status1' => '입고 취소'
                            ]);

                            //THUONG
                            $warehousing_settlement = WarehousingSettlement::where('w_no', $value['w_no']['w_no'])->get();
                            foreach ($warehousing_settlement as $ws) {
                                $warehousing =  Warehousing::where(['w_no' => $ws['w_no_settlement']])->first();
                                Warehousing::where([
                                    'w_no' => $ws['w_no_settlement'],
                                ])->update([
                                    'w_amount' => $warehousing->w_amount - $ws['w_amount']
                                ]);
                            }
                            //THUONG

                            WarehousingStatus::insert([
                                'w_no' => $value['w_no']['w_no'],
                                'mb_no' => $member->mb_no,
                                'status' => '입고 취소',
                                'w_category_name' => '수입풀필먼트',
                            ]);
                        }
                    }
                } else {
                    //Service 2 change
                    if ($request->w_no) {
                        ReceivingGoodsDelivery::where('w_no', $request->w_no)
                            ->update([
                                'rgd_status1' => '입고 취소'
                            ]);

                        //THUONG
                        $warehousing_settlement = WarehousingSettlement::where('w_no', $request->w_no)->get();
                        foreach ($warehousing_settlement as $ws) {
                            $warehousing =  Warehousing::where(['w_no' => $ws['w_no_settlement']])->first();
                            Warehousing::where([
                                'w_no' => $ws['w_no_settlement'],
                            ])->update([
                                'w_amount' => $warehousing->w_amount - $ws['w_amount']
                            ]);
                        }
                        //THUONG

                        WarehousingStatus::insert([
                            'w_no' => $request->w_no,
                            'mb_no' => $member->mb_no,
                            'status' => '입고 취소',
                            'w_category_name' => '수입풀필먼트',
                        ]);
                    }
                }
            } else {
                if ($request->datachkbox) {

                    foreach ($request->datachkbox as $value) {
                        //return $value['w_no']['w_no'];
                        if ($value['w_no']['w_no']) {
                            ReceivingGoodsDelivery::where('w_no', $value['w_no']['w_no'])->where('rgd_status1', '!=', '출고')->update([
                                'rgd_status1' => '출고예정 취소'
                            ]);

                            WarehousingStatus::insert([
                                'w_no' => $value['w_no']['w_no'],
                                'mb_no' => $member->mb_no,
                                'status' => '출고예정 취소',
                                'w_category_name' => '수입풀필먼트',
                            ]);
                        }
                    }
                } else {
                    if ($request->w_no) {
                        ReceivingGoodsDelivery::where('w_no', $request->w_no)->where('rgd_status1', '!=', '출고')->update([
                            'rgd_status1' => '출고예정 취소'
                        ]);

                        WarehousingStatus::insert([
                            'w_no' => $request->w_no,
                            'mb_no' => $member->mb_no,
                            'status' => '출고예정 취소',
                            'w_category_name' => '수입풀필먼트',
                        ]);
                    }
                }
            }



            return response()->json(['message' => 'ok']);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function update_rdc_cancel_warehousing(Request $request)
    {

        try {
            DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            if ($request->page_type == 'IW') {
                if (isset($request->w_no)) {
                    $check = ReceivingGoodsDelivery::where('w_no', $request->w_no)->where('rgd_status2', '!=', '작업완료')->orwherenull('rgd_status2')->first();

                    if (isset($check->rgd_no)) {
                        Warehousing::where('w_no', $request->w_no)->update([
                            'w_schedule_number2' => null,
                            'w_completed_day' => null,
                            'w_amount' => 0
                        ]);

                        WarehousingItem::where('w_no', $request->w_no)->where('wi_type', '=', '입고_spasys')->delete();

                        ReceivingGoodsDelivery::where('w_no', $request->w_no)->update([
                            'rgd_status1' => '입고예정',
                            'rgd_status2' => null,
                            'rgd_delivery_schedule_day' => null,
                            'rgd_arrive_day' => null
                        ]);

                        WarehousingStatus::insert([
                            'w_no' => $request->w_no,
                            'mb_no' => $member->mb_no,
                            'status' => '입고취소',
                            'w_category_name' => "유통가공",
                        ]);
                    }
                }
            } else {
                if (isset($request->w_no)) {
                    $check = ReceivingGoodsDelivery::where('w_no', $request->w_no)->where('rgd_status3', '!=', '배송완료')->orwherenull('rgd_status3')->where('w_no', $request->w_no)->first();
                    //return $check;
                    if (isset($check->rgd_no)) {
                        Warehousing::where('w_no', $request->w_no)->update([
                            'w_schedule_number2' => null,
                            'w_completed_day' => null,
                            'w_amount' => null
                        ]);

                        WarehousingItem::where('w_no', $request->w_no)->where('wi_type', '=', '출고_spasys')->delete();

                        Package::where('w_no', $request->w_no)->delete();

                        ReceivingGoodsDelivery::where('w_no', $request->w_no)->update([
                            'rgd_delivery_company' => null,
                            'rgd_tracking_code' => null,
                            'rgd_delivery_man' => null,
                            'rgd_delivery_man_hp' => null,
                            'rgd_delivery_schedule_day' => null,
                            'rgd_arrive_day' => null,
                            'rgd_status1' => '출고예정',
                            'rgd_status2' => '작업완료',
                            'rgd_status3' => null
                        ]);

                        WarehousingStatus::insert([
                            'w_no' => $request->w_no,
                            'mb_no' => $member->mb_no,
                            'status' => '출고취소',
                            'w_category_name' => "유통가공",
                        ]);
                    }
                }
            }
            DB::commit();
            return response()->json(['message' => 'ok']);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function update_ReceivingGoodsDelivery_cancel($rgd_no)
    {

        try {

            $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->update([
                'rgd_status5' => 'cancel'
            ]);

            return response()->json(['message' => 'ok']);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function getReceivingGoodsDelivery($is_no)
    {

        try {

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $rgd = ReceivingGoodsDelivery::with('mb_no')->with('w_no')->where('is_no', $is_no)->whereNull('rgd_parent_no')->get();

            return response()->json($rgd);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getReceivingGoodsDeliveryWarehousing($w_no)
    {

        try {

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $rgd = ReceivingGoodsDelivery::with('mb_no')->with('w_no')->whereHas('w_no', function ($q) use ($w_no) {
                return $q->where('w_no', $w_no);
            })->get();

            return response()->json($rgd);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function create_import_schedule(ReceivingGoodsDeliveryCreateRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $member = Member::where('mb_id', Auth::user()->mb_id)->first();


            foreach ($validated['location']  as $rgd) {

                if (!isset($rgd['rgd_no'])) {
                    $warehousing_status =  isset($rgd['rgd_status3']) ? $rgd['rgd_status3'] : null;

                    $rgd_no = ReceivingGoodsDelivery::insertGetId([
                        'mb_no' => $member->mb_no,
                        'is_no' => $rgd['is_no'],
                        'service_korean_name' => '보세화물',
                        'rgd_contents' => $rgd['rgd_contents'],
                        'rgd_address' => $rgd['rgd_address'],
                        'rgd_address_detail' => $rgd['rgd_address_detail'],
                        'rgd_receiver' => $rgd['rgd_receiver'],
                        'rgd_hp' => $rgd['rgd_hp'],
                        'rgd_memo' => $rgd['rgd_memo'],
                        'rgd_status1' => $rgd['rgd_status1'],
                        'rgd_status2' => $rgd['rgd_status2'],
                        'rgd_status3' => $rgd['rgd_status3'],
                        'rgd_delivery_company' => $rgd['rgd_delivery_company'],
                        'rgd_tracking_code' => $rgd['rgd_tracking_code'],
                        'rgd_delivery_man' => $rgd['rgd_delivery_man'],
                        'rgd_delivery_man_hp' => $rgd['rgd_delivery_man_hp'],
                        'rgd_delivery_schedule_day' => isset($rgd['rgd_delivery_schedule_day']) ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']) : null,
                        'rgd_arrive_day' => isset($rgd['rgd_arrive_day']) ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']) : (isset($validated['type']) && $rgd['rgd_status3'] == "배송완료" ? Carbon::now()->toDateTimeString() : null),
                        'rgd_confirmed_date' => $rgd['rgd_status3'] == '배송완료' ? Carbon::now()->toDateTimeString() : null,

                    ]);

                    if ($warehousing_status) {
                        WarehousingStatus::insert([
                            'w_no' => $rgd['is_no'],
                            'mb_no' => $member->mb_no,
                            'status' => $warehousing_status,
                            'w_category_name' => "보세화물",
                        ]);
                    }
                } else {
                    $warehousing_status = isset($rgd['rgd_status3']) ? $rgd['rgd_status3'] : null;
                    $rgd_data = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->first();

                    if ($warehousing_status != $rgd_data->rgd_status3) {
                        $warehousing_status = isset($rgd['rgd_status3']) ? $rgd['rgd_status3'] : null;
                    } else {
                        $warehousing_status = null;
                    }
                    $rgd_no = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                        'rgd_contents' => $rgd['rgd_contents'],
                        'rgd_address' => $rgd['rgd_address'],
                        'rgd_address_detail' => $rgd['rgd_address_detail'],
                        'rgd_receiver' => $rgd['rgd_receiver'],
                        'rgd_hp' => $rgd['rgd_hp'],
                        'rgd_memo' => $rgd['rgd_memo'],
                        'rgd_status1' => $rgd['rgd_status1'],
                        'rgd_status2' => $rgd['rgd_status2'],
                        'rgd_status3' => $rgd['rgd_status3'],
                        'rgd_delivery_company' => $rgd['rgd_delivery_company'],
                        'rgd_tracking_code' => $rgd['rgd_tracking_code'],
                        'rgd_delivery_man' => $rgd['rgd_delivery_man'],
                        'rgd_delivery_man_hp' => $rgd['rgd_delivery_man_hp'],
                        'rgd_delivery_schedule_day' => isset($rgd['rgd_delivery_schedule_day']) ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']) : null,
                        'rgd_arrive_day' => isset($rgd['rgd_arrive_day']) ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']) : (isset($validated['type']) && $rgd['rgd_status3'] == "배송완료" ? Carbon::now()->toDateTimeString() : null),
                        'rgd_confirmed_date' => $rgd['rgd_status3'] == '배송완료' ? Carbon::now()->toDateTimeString() : null,
                    ]);

                    if ($warehousing_status) {
                        WarehousingStatus::insert([
                            'w_no' => $rgd['is_no'],
                            'mb_no' => $member->mb_no,
                            'status' => $warehousing_status,
                            'w_category_name' => "보세화물",
                        ]);
                    }
                }
            }





            foreach ($validated['remove'] as $remove) {
                ReceivingGoodsDelivery::where('rgd_no', $remove['rgd_no'])->delete();
                Package::where('rgd_no', $remove['rgd_no'])->delete();
            }

            if (isset($validated['connection_number'])) {
                if (isset($validated['is_no'])) {
                    Export::where('te_carry_out_number', $validated['is_no'])->update([
                        'connection_number' => $validated['connection_number']
                    ]);
                    // if ($validated['type_w_choose'] == "export") {
                    //     Export::where('te_carry_out_number', $validated['connect_w'])->update([
                    //         'connection_number' => $validated['connection_number']
                    //     ]);
                    // } else {
                    //     Warehousing::where('w_no', $validated['connect_w'])->update([
                    //         'connection_number' => $validated['connection_number']
                    //     ]);
                    // }
                }
            }


            if (isset($validated['wr_contents'])) {

                // WarehousingRequest::insert([
                //     'w_no' => $validated['is_no'],
                //     'wr_type' => "IW",
                //     'mb_no' => $member->mb_no,
                //     'wr_contents' => $validated['wr_contents'],
                // ]);
                //return $validated['is_no'];
                if (isset($validated['is_no'])) {
                    $warehousingrequest = WarehousingRequest::where('w_no', $validated['is_no'])->where('wr_type', 'List')->first();
                    if (!is_null($warehousingrequest)) {
                        $warehousingrequest->update([
                            'w_no' => $validated['is_no'],
                            'wr_type' => "IW",
                            'mb_no' => $member->mb_no,
                            'wr_contents' => $validated['wr_contents'],
                        ]);
                    } else {
                        WarehousingRequest::insert([
                            'w_no' => $validated['is_no'],
                            'wr_type' => "IW",
                            'mb_no' => $member->mb_no,
                            'wr_contents' => $validated['wr_contents'],
                        ]);
                    }
                }



                // WarehousingRequest::updateOrNew(
                //     [
                //         'w_no' => $validated['is_no'],
                //         'wr_type' => "List",
                //     ],
                //     [
                //         'wr_type' => "IW",
                //         'w_no' => $validated['is_no'],
                //         'mb_no' => $member->mb_no,
                //         'wr_contents' => $validated['wr_contents'],
                //     ]
                // );
            }


            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rgd_no' => $rgd_no
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function create_import_schedule_list(ReceivingGoodsDeliveryCreateRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $member = Member::where('mb_id', Auth::user()->mb_id)->first();


            foreach ($validated['location'] as $rgd) {

                if (!isset($rgd['rgd_no'])) {
                    if (isset($rgd['is_no'])) {
                        $rgd_no = ReceivingGoodsDelivery::insertGetId([
                            'mb_no' => $member->mb_no,
                            'is_no' => $rgd['is_no'],
                            'service_korean_name' => '보세화물',
                            'rgd_contents' => $rgd['rgd_contents'],
                            'rgd_address' => $rgd['rgd_address'],
                            'rgd_address_detail' => $rgd['rgd_address_detail'],
                            'rgd_receiver' => $rgd['rgd_receiver'],
                            'rgd_hp' => $rgd['rgd_hp'],
                            'rgd_memo' => $rgd['rgd_memo'],

                        ]);
                    }
                } else {

                    $rgd_no = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                        'rgd_contents' => $rgd['rgd_contents'],
                        'rgd_address' => $rgd['rgd_address'],
                        'rgd_address_detail' => $rgd['rgd_address_detail'],
                        'rgd_receiver' => $rgd['rgd_receiver'],
                        'rgd_hp' => $rgd['rgd_hp'],
                        'rgd_memo' => $rgd['rgd_memo'],

                    ]);
                }
            }

            foreach ($validated['remove'] as $remove) {
                ReceivingGoodsDelivery::where('rgd_no', $remove['rgd_no'])->delete();
                Package::where('rgd_no', $remove['rgd_no'])->delete();
            }

            if ($validated['wr_contents'] && $validated['is_no']) {
                WarehousingRequest::insert([
                    'wr_type' => "IW",
                    'w_no' => $validated['is_no'],
                    'mb_no' => $member->mb_no,
                    'wr_contents' => $validated['wr_contents'],
                ]);
                // Change wr_type List if need popup function show on textarae
                // WarehousingRequest::updateOrCreate(
                //     [
                //         'w_no' => $validated['is_no'],
                //         'wr_type' => "IW",
                //     ],
                //     [
                //         'wr_type' => "IW",
                //         'w_no' => $validated['is_no'],
                //         'mb_no' => $member->mb_no,
                //         'wr_contents' => $validated['wr_contents'],
                //     ]
                // );
            }


            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rgd_no' => $rgd_no
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }


    public function create_import_schedule_mobile(ReceivingGoodsDeliveryCreateMobileRequest $request)
    {
        $validated = $request->validated();

        try {
            //DB::beginTransaction();

            $member = Member::where('mb_id', Auth::user()->mb_id)->first();


            foreach ($validated['location'] as $rgd) {

                if (!isset($rgd['rgd_no'])) {

                    $rgd_no = ReceivingGoodsDelivery::insertGetId([
                        'mb_no' => $member->mb_no,
                        'is_no' => $rgd['is_no'],
                        'service_korean_name' => '보세화물',
                        'rgd_contents' => $rgd['rgd_contents'],
                        'rgd_address' => $rgd['rgd_address'],
                        'rgd_address_detail' => $rgd['rgd_address_detail'],
                        'rgd_receiver' => $rgd['rgd_receiver'],
                        'rgd_hp' => $rgd['rgd_hp'],
                        'rgd_memo' => $rgd['rgd_memo'],
                        'rgd_status1' => $rgd['rgd_status1'],
                        'rgd_status2' => $rgd['rgd_status2'],
                        'rgd_status3' => $rgd['rgd_status3'],
                        'rgd_delivery_company' => $rgd['rgd_delivery_company'],
                        'rgd_tracking_code' => $rgd['rgd_tracking_code'],
                        'rgd_delivery_man' => $rgd['rgd_delivery_man'],
                        'rgd_delivery_man_hp' => $rgd['rgd_delivery_man_hp'],
                        'rgd_delivery_schedule_day' => $rgd['rgd_delivery_schedule_day'] ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']) : null,
                        'rgd_arrive_day' => $rgd['rgd_arrive_day'] ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']) : null,
                    ]);
                } else {

                    $rgd_no = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                        'rgd_contents' => $rgd['rgd_contents'],
                        'rgd_address' => $rgd['rgd_address'],
                        'rgd_address_detail' => $rgd['rgd_address_detail'],
                        'rgd_receiver' => $rgd['rgd_receiver'],
                        'rgd_hp' => $rgd['rgd_hp'],
                        'rgd_memo' => $rgd['rgd_memo'],
                        'rgd_status1' => $rgd['rgd_status1'],
                        'rgd_status2' => $rgd['rgd_status2'],
                        'rgd_status3' => $rgd['rgd_status3'],
                        'rgd_delivery_company' => $rgd['rgd_delivery_company'],
                        'rgd_tracking_code' => $rgd['rgd_tracking_code'],
                        'rgd_delivery_man' => $rgd['rgd_delivery_man'],
                        'rgd_delivery_man_hp' => $rgd['rgd_delivery_man_hp'],
                        'rgd_delivery_schedule_day' => $rgd['rgd_delivery_schedule_day'] ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']) : null,
                        'rgd_arrive_day' => $rgd['rgd_arrive_day'] ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']) : null,
                    ]);
                }
            }

            foreach ($validated['remove'] as $remove) {
                ReceivingGoodsDelivery::where('rgd_no', $remove['rgd_no'])->delete();
            }

            if ($validated['wr_contents']) {
                WarehousingRequest::insert([
                    'mb_no' => $member->mb_no,
                    'wr_contents' => $validated['wr_contents'],
                ]);
            }


            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rgd_no' => $rgd_no
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function update_rgd_file(ReceivingGoodsDeliveryFileRequest $request)
    {
        $validated = $request->validated();
        try {
            $path = join('/', ['files', 'import_schedule', $validated['is_no']]);
            //return $validated['is_no'];
            if ($request->remove_files) {
                foreach ($request->remove_files as $key => $file_no) {
                    $file = File::where('file_no', $file_no)->get()->first();
                    if (isset($file->file_name)) {
                        $url = Storage::disk('public')->delete($path . '/' . $file->file_name);
                        $file->delete();
                    }
                }
            }

            $files = [];
            if (isset($validated['files'])) {
                foreach ($validated['files'] as $key => $file) {
                    $url = Storage::disk('public')->put($path, $file);
                    $files[] = [
                        'file_table' => 'import_schedule',
                        'file_table_key' => $validated['is_no'],
                        'file_name' => basename($url),
                        'file_name_old' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                        'file_extension' => $file->extension(),
                        'file_position' => $key,
                        'file_url' => $url
                    ];
                }
            }


            File::insert($files);

            DB::commit();
            return response()->json(['message' => Messages::MSG_0007], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function get_rgd_file(Request $request)
    {
        try {
            //$validated = $request->validated();

            // If per_page is null set default data = 15
            //$per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            //$page = isset($validated['page']) ? $validated['page'] : 1;
            $file = File::where('file_table_key', '=', $request->is_no)->where('file_table', '=', 'import_schedule')->get();
            return response()->json($file);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function update_status7(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            if ($request->type == 'add_all') {
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
                TaxInvoiceDivide::where('tid_no', $rgd->tid_no)->delete();

                $rgds = ReceivingGoodsDelivery::where('tid_no', $rgd->tid_no)->get();

                foreach ($rgds as $rgd) {
                    CommonFunc::insert_alarm('[공통] 계산서취소 안내', $rgd, $user, null, 'settle_payment', null);


                    $cbh = CancelBillHistory::insertGetId([
                        'rgd_no' => $rgd['rgd_no'],
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_before' => 'taxed',
                        'cbh_status_after' => 'cancel'
                    ]);

                    if ($rgd->rgd_status8 == 'completed') {
                        ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                            'rgd_status8' => 'in_process',
                        ]);

                        CancelBillHistory::insertGetId([
                            'rgd_no' => $request->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_before' => $rgd->rgd_status8,
                            'cbh_status_after' => 'in_process'
                        ]);

                        //UPDATE EST BILL
                        $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                        if ($est_rgd->rgd_status8 != 'in_process') {
                            ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                                'rgd_status8' => 'in_process',
                            ]);
                            CancelBillHistory::insertGetId([
                                'rgd_no' => $est_rgd->rgd_no,
                                'mb_no' => $user->mb_no,
                                'cbh_type' => 'tax',
                                'cbh_status_before' => $est_rgd->rgd_status8,
                                'cbh_status_after' => 'in_process'
                            ]);
                        }
                    }
                }

                ReceivingGoodsDelivery::where('tid_no', $rgd->tid_no)->update([
                    'rgd_status7' => 'cancel',
                    'rgd_tax_invoice_date' => NULL,
                    'rgd_tax_invoice_number' => NULL,
                    'tid_no' => NULL,
                ]);
            } else {
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();

                ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                    'rgd_status7' => 'cancel',
                    'rgd_tax_invoice_date' => NULL,
                    'rgd_tax_invoice_number' => NULL,
                    'tid_no' => NULL,
                ]);

                CommonFunc::insert_alarm('[공통] 계산서취소 안내', $rgd, $user, null, 'settle_payment', null);

                $cbh = CancelBillHistory::insertGetId([
                    'rgd_no' => $request->rgd_no,
                    'mb_no' => $user->mb_no,
                    'cbh_type' => 'tax',
                    'cbh_status_before' => 'taxed',
                    'cbh_status_after' => 'cancel'
                ]);

                if ($rgd->rgd_status6 == 'paid') {
                    ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                        'rgd_status8' => 'in_process',
                    ]);

                    CancelBillHistory::insertGetId([
                        'rgd_no' => $request->rgd_no,
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_before' => $rgd->rgd_status8,
                        'cbh_status_after' => 'in_process'
                    ]);

                    //UPDATE EST BILL
                    $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                    if ($est_rgd->rgd_status8 != 'in_process') {
                        ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                            'rgd_status8' => 'in_process',
                        ]);
                        CancelBillHistory::insertGetId([
                            'rgd_no' => $est_rgd->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_before' => $est_rgd->rgd_status8,
                            'cbh_status_after' => 'in_process'
                        ]);
                    }
                }
            }




            $rgd = ReceivingGoodsDelivery::with(['cancel_bill_history', 'rgd_child'])->where('rgd_no', $request->rgd_no)->first();

            TaxInvoiceDivide::where('rgd_no', $request->rgd_no)->delete();
            DB::commit();
            return response()->json([
                'message' => 'Success',
                'rgd' => $rgd,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }


    public function update_settlement_status(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();

            $rgd = ReceivingGoodsDelivery::with(['cancel_bill_history', 'rgd_child'])->where('rgd_no', $request->rgd_no)->first();

            if ($request->cancel_status == '발행취소' && $rgd->rgd_status5 == null) {
                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                    'rgd_status5' => 'cancel',
                    'rgd_canceled_date' =>  Carbon::now(),
                ]);
            } else if ($request->cancel_status == '발행' && $rgd->rgd_status5 == 'cancel') {
                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                    'rgd_status5' => null,
                    'rgd_canceled_date' => null,
                ]);
            }

            $rgd = ReceivingGoodsDelivery::with(['cancel_bill_history', 'rgd_child'])->where('rgd_no', $request->rgd_no)->first();

            if ($request->confirm_status == '요청중' && $rgd->rgd_status5 == 'confirmed') {
                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                    'rgd_status5' => null,
                    'rgd_confirmed_date' => null
                ]);

                CancelBillHistory::insertGetId([
                    'rgd_no' => $request->rgd_no,
                    'mb_no' => $user->mb_no,
                    'cbh_type' => 'revert_approval',
                    'cbh_status_before' => 'confirmed',
                    'cbh_status_after' => null
                ]);
            } else if ($request->confirm_status == '승인완료' && $rgd->rgd_status5 == null) {
                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                    'rgd_status5' => 'confirmed',
                    'rgd_confirmed_date' => Carbon::now(),
                ]);

                CancelBillHistory::insertGetId([
                    'rgd_no' => $request->rgd_no,
                    'mb_no' => $user->mb_no,
                    'cbh_type' => 'approval',
                    'cbh_status_before' => null,
                    'cbh_status_after' => 'confirmed'
                ]);
            }

            $rgd = ReceivingGoodsDelivery::with(['cancel_bill_history', 'rgd_child'])->where('rgd_no', $request->rgd_no)->first();

            if ($request->payment_status == '결제완료' && $rgd->rgd_status6 != 'paid') {
                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                    'rgd_status6' => 'paid',
                    'rgd_paid_date' => Carbon::now()->toDateTimeString()
                ]);

                //UPDATE EST BILL
                // if($rgd->service_korean_name == '보세화물' && !str_contains($rgd->service_korean_name, 'month')){
                //     ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->update([
                //         'rgd_status6' => 'paid',
                //         'rgd_paid_date' => Carbon::now()->toDateTimeString()
                //     ]);

                //     CancelBillHistory::insertGetId([
                //         'rgd_no' => $rgd->rgd_parent_no,
                //         'mb_no' => $user->mb_no,
                //         'cbh_type' => 'payment',
                //         'cbh_status_before' => $rgd->rgd_status6,
                //         'cbh_status_after' => 'payment_bill'
                //     ]);
                // }

                Payment::updateOrCreate(
                    [
                        'rgd_no' => $request['rgd_no'],
                    ],
                    [
                        // 'p_price' => $request->sumprice,
                        // 'p_method' => $request->p_method,
                        'p_success_yn' => 'y',
                        'p_cancel_yn' => 'y',
                        'p_cancel_time' => Carbon::now(),
                    ]
                );

                CancelBillHistory::insertGetId([
                    'rgd_no' => $request->rgd_no,
                    'mb_no' => $user->mb_no,
                    'cbh_type' => 'payment',
                    'cbh_status_before' => $rgd->rgd_status6,
                    'cbh_status_after' => 'payment_bill'
                ]);

                if ($rgd->rgd_status7 == 'taxed') {

                    if ($rgd->rgd_status8 != 'completed') {
                        ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                            'rgd_status8' => 'completed',
                        ]);
    
                        CancelBillHistory::insertGetId([
                            'rgd_no' => $rgd->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_before' => $rgd->rgd_status8,
                            'cbh_status_after' => 'completed'
                        ]);
    
                        //UPDATE EST BILL
                        $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                        if ($est_rgd->rgd_status8 != 'completed') {
                            ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                                'rgd_status8' => 'completed',
                            ]);
                            CancelBillHistory::insertGetId([
                                'rgd_no' => $est_rgd->rgd_no,
                                'mb_no' => $user->mb_no,
                                'cbh_type' => 'tax',
                                'cbh_status_before' => $est_rgd->rgd_status8,
                                'cbh_status_after' => 'completed'
                            ]);
                        }

                        $request->complete_status = null;
                    }
                }
            } else if ($request->payment_status == '진행중' && $rgd->rgd_status6 != null) {
                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                    'rgd_status6' => null,
                    'rgd_paid_date' => null,
                ]);

                Payment::updateOrCreate(
                    [
                        'rgd_no' => $request['rgd_no'],
                    ],
                    [
                        // 'p_price' => $request->sumprice,
                        // 'p_method' => $request->p_method,
                        'p_success_yn' => null,
                        'p_cancel_yn' => 'y',
                        'p_cancel_time' => Carbon::now(),
                    ]
                );

                CancelBillHistory::insertGetId([
                    'rgd_no' => $request->rgd_no,
                    'mb_no' => $user->mb_no,
                    'cbh_type' => 'payment',
                    'cbh_status_before' => $rgd->rgd_status6,
                    'cbh_status_after' => 'request_bill'
                ]);

                if ($rgd->rgd_status8 == 'completed') {
                    CancelBillHistory::insertGetId([
                        'rgd_no' => $request->rgd_no,
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_before' => $rgd->rgd_status8,
                        'cbh_status_after' => 'in_process'
                    ]);
                }
            } else if ($request->payment_status == '결제취소' && $rgd->rgd_status6 != 'cancel') {
                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                    'rgd_status6' => 'cancel',
                    'rgd_paid_date' => null,
                    'rgd_canceled_date' => Carbon::now(),
                ]);

                Payment::updateOrCreate(
                    [
                        'rgd_no' => $request['rgd_no'],
                    ],
                    [
                        // 'p_price' => $request->sumprice,
                        // 'p_method' => $request->p_method,
                        'p_success_yn' => null,
                        'p_cancel_yn' => 'y',
                        'p_cancel_time' => Carbon::now(),
                    ]
                );

                CancelBillHistory::insertGetId([
                    'rgd_no' => $request->rgd_no,
                    'mb_no' => $user->mb_no,
                    'cbh_type' => 'payment',
                    'cbh_status_before' => $rgd->rgd_status6,
                    'cbh_status_after' => 'cancel'
                ]);

                CancelBillHistory::insertGetId([
                    'rgd_no' => $request->rgd_no,
                    'mb_no' => $user->mb_no,
                    'cbh_type' => 'payment',
                    'cbh_status_before' => $rgd->rgd_status6,
                    'cbh_status_after' => 'request_bill'
                ]);

                if ($rgd->rgd_status8 == 'completed') {
                    CancelBillHistory::insertGetId([
                        'rgd_no' => $request->rgd_no,
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_before' => $rgd->rgd_status8,
                        'cbh_status_after' => 'in_process'
                    ]);

                    ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                        'rgd_status8' =>  'in_process',
                    ]);

                    //UPDATE EST BILL
                    $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                    if ($est_rgd->rgd_status8 != 'in_process') {
                        ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                            'rgd_status8' => 'in_process',
                        ]);
                        CancelBillHistory::insertGetId([
                            'rgd_no' => $est_rgd->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_before' => $est_rgd->rgd_status8,
                            'cbh_status_after' => 'in_process'
                        ]);
                    }
                }
            }

            $rgd = ReceivingGoodsDelivery::with(['cancel_bill_history', 'rgd_child'])->where('rgd_no', $request->rgd_no)->first();

            if ($request->complete_status == '정산완료' && $rgd->rgd_status6 != 'paid') {
                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                    'rgd_status6' => 'paid',
                    'rgd_paid_date' => Carbon::now()->toDateTimeString()
                ]);

                Payment::updateOrCreate(
                    [
                        'rgd_no' => $request['rgd_no'],
                    ],
                    [
                        // 'p_price' => $request->sumprice,
                        // 'p_method' => $request->p_method,
                        'p_success_yn' => 'y',
                        'p_cancel_yn' => 'y',
                        'p_cancel_time' => Carbon::now(),
                    ]
                );

                CancelBillHistory::insertGetId([
                    'rgd_no' => $request->rgd_no,
                    'mb_no' => $user->mb_no,
                    'cbh_type' => 'payment',
                    'cbh_status_before' => $rgd->rgd_status6,
                    'cbh_status_after' => 'payment_bill'
                ]);

                if ($rgd->rgd_status7 != 'taxed') {
                    $tax_number = CommonFunc::generate_tax_number($rgd['rgd_no']);

                    ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                        'rgd_tax_invoice_date' => Carbon::now()->toDateTimeString(),
                        'rgd_status7' => 'taxed',
                        'rgd_tax_invoice_number' => $tax_number ? $tax_number : null,
                    ]);

                    $rgd = ReceivingGoodsDelivery::with(['warehousing', 'rate_data_general'])->where('rgd_no', $rgd['rgd_no'])->first();

                    if ($user->mb_type == 'spasys' && $rgd->service_korean_name == '수입풀필먼트') {
                        $company = Company::with(['co_parent', 'adjustment_group'])->where('co_no', $rgd['warehousing']['co_no'])->first();
                    } else {
                        $company = Company::with(['co_parent', 'adjustment_group'])->where('co_no', $rgd['warehousing']['company']['co_parent']['co_no'])->first();
                    }

                    $ag = AdjustmentGroup::where('ag_no', $rgd->rate_data_general->ag_no)->first();

                    $id = TaxInvoiceDivide::insertGetId([
                        'tid_supply_price' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : ($rgd['service_korean_name']  == '수입풀필먼트' ? $rgd['rate_data_general']['rdg_supply_price6'] : $rgd['rate_data_general']['rdg_supply_price4']),
                        'tid_vat' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_vat7'] : ($rgd['service_korean_name']  == '수입풀필먼트' ? $rgd['rate_data_general']['rdg_vat6'] : $rgd['rate_data_general']['rdg_vat4']),
                        'tid_sum' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_sum7'] : ($rgd['service_korean_name']  == '수입풀필먼트' ? $rgd['rate_data_general']['rdg_sum6'] : $rgd['rate_data_general']['rdg_sum4']),
                        'rgd_no' => $rgd['rgd_no'],
                        'tid_number' => $rgd['rgd_settlement_number'],
                        'co_license' => $company['co_license'],
                        'co_owner' => $company['co_owner'],
                        'co_name' => $company['co_name'],
                        'co_major' => $company['co_major'],
                        'co_address' => $company['co_address'],
                        'co_email' => $ag['ag_email'] ? $ag['ag_email'] : null,
                        'co_email2' => $ag['ag_email2'] ? $ag['ag_email2'] : null,
                        'rgd_number' => $tax_number ? $tax_number : null,
                        'mb_no' => $user->mb_no,
                    ]);

                    $cbh = CancelBillHistory::insertGetId([
                        'rgd_no' => $rgd['rgd_no'],
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_after' => 'taxed'
                    ]);

                    CommonFunc::insert_alarm('[공통] 계산서발행 안내', $rgd, $user, null, 'settle_payment', null);
                }

                if ($rgd->rgd_status8 != 'completed') {
                    ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                        'rgd_status8' => 'completed',
                    ]);

                    CancelBillHistory::insertGetId([
                        'rgd_no' => $request->rgd_no,
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_before' => $rgd->rgd_status8,
                        'cbh_status_after' => 'completed'
                    ]);

                    //UPDATE EST BILL
                    $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                    if ($est_rgd->rgd_status8 != 'completed') {
                        ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                            'rgd_status8' => 'completed',
                        ]);
                        CancelBillHistory::insertGetId([
                            'rgd_no' => $est_rgd->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_before' => $est_rgd->rgd_status8,
                            'cbh_status_after' => 'completed'
                        ]);
                    }
                }
            } else if ($request->complete_status == '정산완료' && $rgd->rgd_status7 != 'taxed') {
                $tax_number = CommonFunc::generate_tax_number($rgd['rgd_no']);

                ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                    'rgd_tax_invoice_date' => Carbon::now()->toDateTimeString(),
                    'rgd_status7' => 'taxed',
                    'rgd_tax_invoice_number' => $tax_number ? $tax_number : null,
                ]);

                $rgd = ReceivingGoodsDelivery::with(['warehousing', 'rate_data_general'])->where('rgd_no', $rgd['rgd_no'])->first();

                if ($user->mb_type == 'spasys' && $rgd->service_korean_name == '수입풀필먼트') {
                    $company = Company::with(['co_parent', 'adjustment_group'])->where('co_no', $rgd['warehousing']['co_no'])->first();
                } else {
                    $company = Company::with(['co_parent', 'adjustment_group'])->where('co_no', $rgd['warehousing']['company']['co_parent']['co_no'])->first();
                }

                $ag = AdjustmentGroup::where('ag_no', $rgd->rate_data_general->ag_no)->first();

                $id = TaxInvoiceDivide::insertGetId([
                    'tid_supply_price' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : ($rgd['service_korean_name']  == '수입풀필먼트' ? $rgd['rate_data_general']['rdg_supply_price6'] : $rgd['rate_data_general']['rdg_supply_price4']),
                    'tid_vat' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_vat7'] : ($rgd['service_korean_name']  == '수입풀필먼트' ? $rgd['rate_data_general']['rdg_vat6'] : $rgd['rate_data_general']['rdg_vat4']),
                    'tid_sum' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_sum7'] : ($rgd['service_korean_name']  == '수입풀필먼트' ? $rgd['rate_data_general']['rdg_sum6'] : $rgd['rate_data_general']['rdg_sum4']),
                    'rgd_no' => $rgd['rgd_no'],
                    'tid_number' => $rgd['rgd_settlement_number'],
                    'co_license' => $company['co_license'],
                    'co_owner' => $company['co_owner'],
                    'co_name' => $company['co_name'],
                    'co_major' => $company['co_major'],
                    'co_address' => $company['co_address'],
                    'co_email' => $ag['ag_email'] ? $ag['ag_email'] : null,
                    'co_email2' => $ag['ag_email2'] ? $ag['ag_email2'] : null,
                    'rgd_number' => $tax_number ? $tax_number : null,
                    'mb_no' => $user->mb_no,
                ]);

                $cbh = CancelBillHistory::insertGetId([
                    'rgd_no' => $rgd['rgd_no'],
                    'mb_no' => $user->mb_no,
                    'cbh_type' => 'tax',
                    'cbh_status_after' => 'taxed'
                ]);

                CommonFunc::insert_alarm('[공통] 계산서발행 안내', $rgd, $user, null, 'settle_payment', null);



                if ($rgd->rgd_status7 != 'paid') {
                    ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                        'rgd_status6' => 'paid',
                        'rgd_paid_date' => Carbon::now()->toDateTimeString()
                    ]);

                    Payment::updateOrCreate(
                        [
                            'rgd_no' => $request['rgd_no'],
                        ],
                        [
                            // 'p_price' => $request->sumprice,
                            // 'p_method' => $request->p_method,
                            'p_success_yn' => 'y',
                            'p_cancel_yn' => 'y',
                            'p_cancel_time' => Carbon::now(),
                        ]
                    );

                    CancelBillHistory::insertGetId([
                        'rgd_no' => $request->rgd_no,
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'payment',
                        'cbh_status_before' => $rgd->rgd_status6,
                        'cbh_status_after' => 'payment_bill'
                    ]);
                }

                if ($rgd->rgd_status8 != 'completed') {
                    ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                        'rgd_status8' => 'completed',
                    ]);

                    CancelBillHistory::insertGetId([
                        'rgd_no' => $request->rgd_no,
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_before' => $rgd->rgd_status8,
                        'cbh_status_after' => 'completed'
                    ]);

                    //UPDATE EST BILL
                    $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                    if ($est_rgd->rgd_status8 != 'completed') {
                        ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                            'rgd_status8' => 'completed',
                        ]);
                        CancelBillHistory::insertGetId([
                            'rgd_no' => $est_rgd->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_before' => $est_rgd->rgd_status8,
                            'cbh_status_after' => 'completed'
                        ]);
                    }
                }
            } else if ($request->complete_status == '정산완료' && $rgd->rgd_status8 != 'completed') {
                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                    'rgd_status8' => 'completed',
                ]);
                CancelBillHistory::insertGetId([
                    'rgd_no' => $request->rgd_no,
                    'mb_no' => $user->mb_no,
                    'cbh_type' => 'tax',
                    'cbh_status_before' => $rgd->rgd_status8,
                    'cbh_status_after' => 'completed'
                ]);

                //UPDATE EST BILL
                $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                if ($est_rgd->rgd_status8 != 'completed') {
                    ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                        'rgd_status8' => 'completed',
                    ]);
                    CancelBillHistory::insertGetId([
                        'rgd_no' => $est_rgd->rgd_no,
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_before' => $est_rgd->rgd_status8,
                        'cbh_status_after' => 'completed'
                    ]);
                }
            } else if ($request->complete_status == "진행중" && $rgd->rgd_status8 != 'in_process') {
                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                    'rgd_status8' => 'in_process',
                ]);

                CancelBillHistory::insertGetId([
                    'rgd_no' => $request->rgd_no,
                    'mb_no' => $user->mb_no,
                    'cbh_type' => 'tax',
                    'cbh_status_before' => $rgd->rgd_status7,
                    'cbh_status_after' => 'in_process'
                ]);

                //UPDATE EST BILL
                $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                if ($est_rgd->rgd_status8 != 'in_process') {
                    ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                        'rgd_status8' => 'in_process',
                    ]);
                    CancelBillHistory::insertGetId([
                        'rgd_no' => $est_rgd->rgd_no,
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_before' => $est_rgd->rgd_status8,
                        'cbh_status_after' => 'in_process'
                    ]);
                }
            } else if ($request->complete_status == '정산완료') {
                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                    'rgd_status8' => 'completed',
                ]);

                //UPDATE EST BILL
                $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                if ($est_rgd->rgd_status8 != 'completed') {
                    ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                        'rgd_status8' => 'completed',
                    ]);
                    CancelBillHistory::insertGetId([
                        'rgd_no' => $est_rgd->rgd_no,
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_before' => $est_rgd->rgd_status8,
                        'cbh_status_after' => 'completed'
                    ]);
                }
            }

            $rgd = ReceivingGoodsDelivery::with(['cancel_bill_history', 'rgd_child'])->where('rgd_no', $request->rgd_no)->first();

            DB::commit();
            return response()->json([
                'message' => 'Success',
                'rgd' => $rgd,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function update_request(Request $request)
    {
        try {

            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                'rgd_settlement_request' => $request->warehousing_request,
            ]);

            return response()->json([
                'message' => 'Success'
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function update_status5(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();

            //UPDATE FOR CASE BILL
            if ($request->bill_type == 'case') {
                $rgd = ReceivingGoodsDelivery::with(['rate_data_general', 'warehousing'])->where('rgd_no', $request->rgd_no)->first();

                $rate_data_general = RateDataGeneral::where('rgd_no', $request->rgd_no)->first();

                if (isset($rate_data_general->ag_no)) {
                    $ag = AdjustmentGroup::where('ag_no', $rate_data_general->ag_no)->first();
                    $company = Company::where('co_no', $ag->co_no)->first();

                    ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                        'rgd_status5' => 'confirmed',
                        'rgd_confirmed_date' => Carbon::now()->toDateTimeString(),
                    ]);

                    $creater = Member::where('mb_no', $rgd->mb_no)->first();
                    if ($rgd->service_korean_name == '보세화물') {
                        CommonFunc::insert_alarm('[보세화물] 결제요청', $rgd, $creater, null, 'settle_payment', null);
                    } else if ($rgd->service_korean_name == '수입풀필먼트') {
                        CommonFunc::insert_alarm('[수입풀필먼트] 결제요청', $rgd, $creater, null, 'settle_payment', null);
                    } else if ($rgd->service_korean_name == '유통가공') {
                        CommonFunc::insert_alarm('[유통가공] 결제요청', $rgd, $creater, null, 'settle_payment', null);
                    }



                    $cbh = CancelBillHistory::insertGetId([
                        'rgd_no' => $request->rgd_no,
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'approval',
                        'cbh_status_before' => null,
                        'cbh_status_after' => 'confirmed'
                    ]);

                    if ($rgd->rgd_calculate_deadline_yn == 'y') {
                        CancelBillHistory::insertGetId([
                            'rgd_no' => $request->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'payment',
                            'cbh_status_before' => null,
                            'cbh_status_after' => 'request_bill'
                        ]);
                    } else {
                        ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                            'rgd_status8' => 'completed',
                        ]);
    
                        CancelBillHistory::insertGetId([
                            'rgd_no' => $rgd->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_before' => $rgd->rgd_status8,
                            'cbh_status_after' => 'completed'
                        ]);

                        //UPDATE EST BILL
                        $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                        if ($est_rgd->rgd_status8 != 'completed') {
                            ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                                'rgd_status8' => 'completed',
                            ]);
                            CancelBillHistory::insertGetId([
                                'rgd_no' => $est_rgd->rgd_no,
                                'mb_no' => $user->mb_no,
                                'cbh_type' => 'tax',
                                'cbh_status_before' => $est_rgd->rgd_status8,
                                'cbh_status_after' => 'completed'
                            ]);
                        }

                    }

                    //CHECK AUTO TAX INVOICE ISSUE
                    if ($ag->ag_auto_issue == 'y' && $rgd->rgd_calculate_deadline_yn == 'y') {

                        $cbh = CancelBillHistory::where('rgd_no', $request->rgd_no)->where('cbh_type', 'tax')->where('cbh_status_after', 'taxed')->first();
                        if (!isset($cbh->cbh_no)) {
                            ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                                'rgd_status7' => $ag->ag_auto_issue == 'y' ? 'taxed' : NULL,
                                'rgd_tax_invoice_date' =>  Carbon::now()->toDateTimeString(),
                            ]);

                            $tax_number = CommonFunc::generate_tax_number($rgd->rgd_no);

                            $tid = TaxInvoiceDivide::updateOrCreate(
                                [
                                    'rgd_no' => $rgd->rgd_no,
                                ],
                                [
                                    'tid_supply_price' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : $rgd['rate_data_general']['rdg_supply_price4'],
                                    'tid_vat' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : $rgd['rate_data_general']['rdg_supply_price4'],
                                    'tid_sum' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : $rgd['rate_data_general']['rdg_supply_price4'],
                                    'tid_number' => $rgd['rgd_settlement_number'],
                                    'mb_no' => $user->mb_no,
                                    'co_license'  => $company->co_license,
                                    'co_owner'  => $company->co_owner,
                                    'co_name'  => $company->co_name,
                                    'co_major'  => $company->co_major,
                                    'co_address'  => $company->co_address,
                                    'co_email'  => $ag->ag_email,
                                    'co_email2'  => $ag->ag_email2,
                                    'rgd_number' => $tax_number,
                                ]
                            );

                            CancelBillHistory::insertGetId([
                                'rgd_no' => $request->rgd_no,
                                'tid_no' => $tid->tid_no,
                                'mb_no' => $rgd->mb_no,
                                'cbh_type' => 'tax',
                                'cbh_status_before' => null,
                                'cbh_status_after' => 'taxed'
                            ]);


                            $sender = Member::where('mb_no', $rgd->mb_no)->first();
                            CommonFunc::insert_alarm('[공통] 계산서발행 안내', $rgd, $sender, null, 'settle_payment', null);
                        }
                    }
                } else {
                    ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                        'rgd_status5' => 'confirmed',
                        'rgd_confirmed_date' => Carbon::now()->toDateTimeString(),
                    ]);
                }
                //UPDATE FOR MONTH BILL
            } else if ($request->bill_type == 'monthly') {
                $i = 0;
                foreach ($request->rgds as $rgd) {
                    $rgd = ReceivingGoodsDelivery::where('rgd_parent_no', $rgd['rgd_no'])->where(function ($q) {
                        $q->where('rgd_status5', '!=', 'cancel')
                            ->orwhereNull('rgd_status5');
                    })->first();

                    if ($i == 0) {
                        $creater = Member::where('mb_no', $rgd->mb_no)->first();
                        if ($rgd->service_korean_name == '보세화물') {
                            CommonFunc::insert_alarm('[보세화물] 결제요청', $rgd, $creater, null, 'settle_payment', null);
                        } else if ($rgd->service_korean_name == '수입풀필먼트') {
                            CommonFunc::insert_alarm('[수입풀필먼트] 결제요청', $rgd, $creater, null, 'settle_payment', null);
                        } else if ($rgd->service_korean_name == '유통가공') {
                            CommonFunc::insert_alarm('[유통가공] 결제요청', $rgd, $creater, null, 'settle_payment', null);
                        }
                    }

                    $rate_data_general = RateDataGeneral::where('rgd_no', $rgd['rgd_no'])->first();

                    $cbh = CancelBillHistory::insertGetId([
                        'rgd_no' => $rgd['rgd_no'],
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'approval',
                        'cbh_status_before' => null,
                        'cbh_status_after' => 'confirmed'
                    ]);

                    if (isset($rate_data_general->ag_no)) {
                        $ag = AdjustmentGroup::where('ag_no', $rate_data_general->ag_no)->first();
                        $company = Company::where('co_no', $ag->co_no)->first();

                        ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                            'rgd_status5' => 'confirmed',
                            'rgd_confirmed_date' => Carbon::now()->toDateTimeString(),
                        ]);

                        if ($rgd['rgd_calculate_deadline_yn'] == 'y') {
                            CancelBillHistory::insertGetId([
                                'rgd_no' => $rgd['rgd_no'],
                                'mb_no' => $user->mb_no,
                                'cbh_type' => 'payment',
                                'cbh_status_before' => null,
                                'cbh_status_after' => 'request_bill'
                            ]);
                        }else {
                            ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd['rgd_settlement_number'])->update([
                                'rgd_status8' => 'completed',
                            ]);
        
                            CancelBillHistory::insertGetId([
                                'rgd_no' => $rgd['rgd_no'],
                                'mb_no' => $user->mb_no,
                                'cbh_type' => 'tax',
                                'cbh_status_before' => $rgd->rgd_status8,
                                'cbh_status_after' => 'completed'
                            ]);

                            //UPDATE EST BILL
                            $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_parent_no'])->first();
                            if ($est_rgd->rgd_status8 != 'completed') {
                                ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                                    'rgd_status8' => 'completed',
                                ]);
                                CancelBillHistory::insertGetId([
                                    'rgd_no' => $est_rgd->rgd_no,
                                    'mb_no' => $user->mb_no,
                                    'cbh_type' => 'tax',
                                    'cbh_status_before' => $est_rgd->rgd_status8,
                                    'cbh_status_after' => 'completed'
                                ]);
                            }

                        }


                        //CHECK AUTO TAX INVOICE ISSUE
                        if ($ag->ag_auto_issue == 'y' && $rgd->rgd_calculate_deadline_yn == 'y') {

                            $cbh = CancelBillHistory::where('rgd_no', $rgd->rgd_no)->where('cbh_type', 'tax')->where('cbh_status_after', 'taxed')->first();
                            if (!isset($cbh->cbh_no)) {
                                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                                    'rgd_status7' => $ag->ag_auto_issue == 'y' ? 'taxed' : NULL,
                                    'rgd_tax_invoice_date' =>  Carbon::now()->toDateTimeString(),
                                ]);

                                $tax_number = CommonFunc::generate_tax_number($rgd->rgd_no);

                                $tid = TaxInvoiceDivide::updateOrCreate(
                                    [
                                        'rgd_no' => $rgd->rgd_no,
                                    ],
                                    [
                                        'tid_supply_price' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : $rgd['rate_data_general']['rdg_supply_price4'],
                                        'tid_vat' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : $rgd['rate_data_general']['rdg_supply_price4'],
                                        'tid_sum' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : $rgd['rate_data_general']['rdg_supply_price4'],
                                        'mb_no' => $user->mb_no,
                                        'co_license'  => $company->co_license,
                                        'co_owner'  => $company->co_owner,
                                        'co_name'  => $company->co_name,
                                        'co_major'  => $company->co_major,
                                        'co_address'  => $company->co_address,
                                        'co_email'  => $ag->ag_email,
                                        'co_email2'  => $ag->ag_email2,
                                        'rgd_number' => $tax_number,
                                        'tid_number' => $rgd['rgd_settlement_number'],
                                    ]
                                );

                                CancelBillHistory::insertGetId([
                                    'rgd_no' => $rgd->rgd_no,
                                    'tid_no' => $tid->tid_no,
                                    'mb_no' => $rgd->mb_no,
                                    'cbh_type' => 'tax',
                                    'cbh_status_before' => null,
                                    'cbh_status_after' => 'taxed'
                                ]);
                                if ($rgd->rgd_is_show == 'y') {
                                    $sender = Member::where('mb_no', $rgd->mb_no)->first();
                                    CommonFunc::insert_alarm('[공통] 계산서발행 안내', $rgd, $sender, null, 'settle_payment', null);
                                }
                            }
                        }
                    } else {
                        ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                            'rgd_status5' => 'confirmed',
                            'rgd_confirmed_date' => Carbon::now()->toDateTimeString(),
                        ]);
                    }
                    $i++;
                }

                //UPDATE FOR MANY BILLS FROM LIST PAGE
            } else if ($request->bill_type == 'multiple') {
                foreach ($request->rgds as $rgd) {
                    // if ($rgd['rgd_bill_type'] == 'final') {
                    $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->first();

                    $rate_data_general = RateDataGeneral::where('rgd_no', $rgd['rgd_no'])->first();

                    $cbh = CancelBillHistory::insertGetId([
                        'rgd_no' => $rgd['rgd_no'],
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'approval',
                        'cbh_status_before' => null,
                        'cbh_status_after' => 'confirmed'
                    ]);

                    if ($rgd['rgd_calculate_deadline_yn'] == 'y') {
                        CancelBillHistory::insertGetId([
                            'rgd_no' => $rgd['rgd_no'],
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'payment',
                            'cbh_status_before' => null,
                            'cbh_status_after' => 'request_bill'
                        ]);
                    }else {
                        ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd['rgd_settlement_number'])->update([
                            'rgd_status8' => 'completed',
                        ]);
    
                        CancelBillHistory::insertGetId([
                            'rgd_no' => $rgd['rgd_no'],
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_before' => $rgd->rgd_status8,
                            'cbh_status_after' => 'completed'
                        ]);

                        //UPDATE EST BILL
                        $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_parent_no'])->first();
                        if ($est_rgd->rgd_status8 != 'completed') {
                            ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                                'rgd_status8' => 'completed',
                            ]);
                            CancelBillHistory::insertGetId([
                                'rgd_no' => $est_rgd->rgd_no,
                                'mb_no' => $user->mb_no,
                                'cbh_type' => 'tax',
                                'cbh_status_before' => $est_rgd->rgd_status8,
                                'cbh_status_after' => 'completed'
                            ]);
                        }
                    }



                    if (isset($rate_data_general->ag_no)) {
                        $ag = AdjustmentGroup::where('ag_no', $rate_data_general->ag_no)->first();
                        $company = Company::where('co_no', $ag->co_no)->first();

                        ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                            'rgd_status5' => 'confirmed',
                            'rgd_confirmed_date' => Carbon::now()->toDateTimeString(),
                        ]);

                        $creater = Member::where('mb_no', $rgd->mb_no)->first();
                        if ($rgd->service_korean_name == '보세화물') {
                            CommonFunc::insert_alarm('[보세화물] 결제요청', $rgd, $creater, null, 'settle_payment', null);
                        } else if ($rgd->service_korean_name == '수입풀필먼트') {
                            CommonFunc::insert_alarm('[수입풀필먼트] 결제요청', $rgd, $creater, null, 'settle_payment', null);
                        } else if ($rgd->service_korean_name == '유통가공') {
                            CommonFunc::insert_alarm('[유통가공] 결제요청', $rgd, $creater, null, 'settle_payment', null);
                        }


                        //CHECK AUTO TAX INVOICE ISSUE
                        if ($ag->ag_auto_issue == 'y' && $rgd->rgd_calculate_deadline_yn == 'y') {

                            $cbh = CancelBillHistory::where('rgd_no', $rgd->rgd_no)->where('cbh_type', 'tax')->where('cbh_status_after', 'taxed')->first();
                            if (!isset($cbh->cbh_no)) {
                                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                                    'rgd_status7' => $ag->ag_auto_issue == 'y' ? 'taxed' : NULL,
                                    'rgd_tax_invoice_date' =>  $ag->ag_auto_issue == 'y' ? Carbon::now()->toDateTimeString() : NULL,
                                ]);

                                $tax_number = CommonFunc::generate_tax_number($rgd->rgd_no);

                                $tid = TaxInvoiceDivide::updateOrCreate(
                                    [
                                        'rgd_no' => $rgd->rgd_no,
                                    ],
                                    [
                                        'tid_supply_price' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : $rgd['rate_data_general']['rdg_supply_price4'],
                                        'tid_vat' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : $rgd['rate_data_general']['rdg_supply_price4'],
                                        'tid_sum' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : $rgd['rate_data_general']['rdg_supply_price4'],
                                        'mb_no' => $user->mb_no,
                                        'co_license'  => $company->co_license,
                                        'co_owner'  => $company->co_owner,
                                        'co_name'  => $company->co_name,
                                        'co_major'  => $company->co_major,
                                        'co_address'  => $company->co_address,
                                        'co_email'  => $ag->ag_email,
                                        'co_email2'  => $ag->ag_email2,
                                        'rgd_number' => $tax_number,
                                        'tid_number' => $rgd['rgd_settlement_number'],
                                    ]
                                );

                                CancelBillHistory::insertGetId([
                                    'rgd_no' => $rgd->rgd_no,
                                    'tid_no' => $tid->tid_no,
                                    'mb_no' => $rgd->mb_no,
                                    'cbh_type' => 'tax',
                                    'cbh_status_before' => null,
                                    'cbh_status_after' => 'taxed'
                                ]);
                                if ($rgd->rgd_is_show == 'y') {
                                    $sender = Member::where('mb_no', $rgd->mb_no)->first();
                                    CommonFunc::insert_alarm('[공통] 계산서발행 안내', $rgd, $sender, null, 'settle_payment', null);
                                }
                            }
                        }
                    } else {
                        ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                            'rgd_status5' => 'confirmed',
                            'rgd_confirmed_date' => Carbon::now()->toDateTimeString(),
                        ]);
                        
                    }
                    // } else if ($rgd['rgd_bill_type'] == 'final_monthly') {
                    //     $rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $rgd['rgd_no'])->first();
                    //     $co_no = $rgd->warehousing->co_no;

                    //     $updated_at = Carbon::createFromFormat('Y.m.d H:i:s',  $rgd->updated_at->format('Y.m.d H:i:s'));

                    //     $start_date = $updated_at->startOfMonth()->toDateString();
                    //     $end_date = $updated_at->endOfMonth()->toDateString();

                    //     $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general'])
                    //         ->whereHas('w_no', function ($q) use ($co_no) {
                    //             $q->where('co_no', $co_no);
                    //         })
                    //         ->where('updated_at', '>=', date('Y-m-d 00:00:00', strtotime($start_date)))
                    //         ->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($end_date)))
                    //         ->where('rgd_status1', '=', '입고')
                    //         ->where('rgd_bill_type', 'final_monthly')
                    //         ->update([
                    //             'rgd_status5' => 'confirmed'
                    //         ]);
                    // }
                }
            }
            DB::commit();
            return response()->json([
                'message' => 'Success'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function cancel_settlement(Request $request)
    {
        try {
            if ($request->bill_type == 'case') {
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            } else if ($request->bill_type == 'monthly') {
                foreach ($request->rgds as $rgd) {
                    ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->delete();
                }
            }
            return response()->json([
                'message' => 'Success'
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function get_rgd_package($w_no)
    {

        try {
            $package = Package::where('w_no', $w_no)->get();
            return response()->json($package);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function updateRgdState3(Request $request)
    {
        try {
            ReceivingGoodsDelivery::where('w_no', $request['w_no'])->update([
                'rgd_status3' => '배송완료'
            ]);
            return response()->json([
                'status' => 1,
                'message' => 'Success'
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function payment_from_est(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $rgd = ReceivingGoodsDelivery::with(['rgd_parent_payment', 'rate_data_general'])->where('rgd_no', $request->rgd_no)->first();
            $est_payment = Payment::where('rgd_no', $rgd->rgd_parent_no)->where('p_success_yn', 'y')->first();

            $left_fee = 0;
            if(isset($rgd->rgd_parent_payment)){
                if ($rgd->rgd_parent_payment->rgd_status6 == 'paid' && $rgd->rgd_parent_payment->is_expect_payment == 'n') {
                    $est_fee = (int)$rgd->rgd_parent_payment->rate_data_general->rdg_sum7;
                    $final_fee = (int)$rgd->rate_data_general->rdg_sum7;
                    $left_fee =  $final_fee - $est_fee;
    
                }else {
                    $final_fee = (int)$rgd->rate_data_general->rdg_sum7;
                }
            }

            Payment::insertGetId(
                [
                    'mb_no' => $user->mb_no,
                    'rgd_no' => $request->rgd_no,
                    'p_price' =>  $rgd->service_korean_name == '보세화물' ? $rgd->rate_data_general->rdg_sum7 : $rgd->rate_data_general->rdg_sum4,
                    'p_method' => $left_fee < 0 ? 'deposit_without_bankbook' : $est_payment->p_method,
                    'p_success_yn' => 'y',
                    'p_method_fee' => $left_fee,
                ]
            );

            ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                'rgd_status6' => $left_fee < 0 ? 'deposit_without_bankbook' : 'paid',
                'rgd_paid_date' =>   $left_fee < 0 ? null : Carbon::now(),
            ]);


            CancelBillHistory::insertGetId([
                'mb_no' => $user->mb_no,
                'rgd_no' => $request->rgd_no,
                'cbh_status_after' => 'payment_bill',
                'cbh_type' => 'payment',
                'cbh_pay_method' => $left_fee < 0 ? 'deposit_without_bankbook' : $est_payment->p_method,
            ]);

            if ($rgd->rgd_status7 == 'taxed') {
                CancelBillHistory::insertGetId([
                    'rgd_no' => $request->rgd_no,
                    'mb_no' => $user->mb_no,
                    'cbh_type' => 'tax',
                    'cbh_status_before' => $rgd->rgd_status7,
                    'cbh_status_after' => 'completed',
                ]);

                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                    'rgd_status8' => $left_fee < 0 ? 'in_process' : 'completed',
                ]);

                if ($request->p_method != 'deposit_without_bankbook') {
                    //UPDATE EST BILL
                    $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                    if ($est_rgd->rgd_status8 != 'completed') {
                        ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                            'rgd_status8' => 'completed',
                        ]);
                        CancelBillHistory::insertGetId([
                            'rgd_no' => $est_rgd->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_before' => $est_rgd->rgd_status8,
                            'cbh_status_after' => 'completed'
                        ]);
                    }
                }
            }
            DB::commit();
            return response()->json([
                'message' => 'Success',
                //'check_payment' =>$check_payment->rgd_no
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function payment(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $check_payment = Payment::where('rgd_no', $request->rgd_no)->where('p_cancel_yn', 'y')->first();
            if (isset($request->sumprice) && $request->p_method == 'card') {
                $p_method_fee = $request->sumprice + round($request->sumprice * 0.029);
            } else if (isset($request->sumprice) && $request->p_method == 'deposit_without_bankbook') {
                $p_method_fee = $request->sumprice;
            } else if (isset($request->sumprice) && $request->p_method == 'virtual_account') {
                $p_method_fee = $request->sumprice;
            } else if (isset($request->sumprice) && $request->p_method == 'kakao_pay') {
                $p_method_fee = $request->sumprice + round($request->sumprice * 0.029);
            } else {
                $p_method_fee = $request->sumprice;
            }
            if (isset($check_payment)) {
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();

                Payment::where('rgd_no', $check_payment->rgd_no)->update([
                    'p_price' => $request->sumprice,
                    'p_method' => $request->p_method,
                    'p_success_yn' => 'y',
                    'p_cancel_yn' => null,
                    'p_cancel_time' => null,
                ]);

                CancelBillHistory::insertGetId([
                    'rgd_no' => $request->rgd_no,
                    'mb_no' => $user->mb_no,
                    'cbh_type' => 'payment',
                    'cbh_status_before' => $rgd->rgd_status6,
                    'cbh_status_after' => 'payment_bill',
                    'cbh_pay_method' => isset($request->p_method) ? $request->p_method : null
                ]);

                if ($rgd->rgd_status7 == 'taxed') {
                    CancelBillHistory::insertGetId([
                        'rgd_no' => $request->rgd_no,
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_before' => $rgd->rgd_status7,
                        'cbh_status_after' => 'completed',
                    ]);

                    ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                        'rgd_status8' => isset($request->p_method) && $request->p_method == 'deposit_without_bankbook'  ? 'in_process' : 'completed',
                    ]);

                    if ($request->p_method != 'deposit_without_bankbook') {
                        //UPDATE EST BILL
                        $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                        if ($est_rgd->rgd_status8 != 'completed') {
                            ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                                'rgd_status8' => 'completed',
                            ]);
                            CancelBillHistory::insertGetId([
                                'rgd_no' => $est_rgd->rgd_no,
                                'mb_no' => $user->mb_no,
                                'cbh_type' => 'tax',
                                'cbh_status_before' => $est_rgd->rgd_status8,
                                'cbh_status_after' => 'completed'
                            ]);
                        }
                    }
                }


                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                    'is_expect_payment' => isset($request->p_method) && $request->p_method == 'deposit_without_bankbook'  ? 'y' : 'n',
                    'rgd_status6' => isset($request->p_method) && $request->p_method == 'deposit_without_bankbook'  ? null : 'paid',
                    'rgd_paid_date' =>  isset($request->p_method) && $request->p_method == 'deposit_without_bankbook'  ? null : Carbon::now(),
                ]);

                if ($rgd->service_korean_name == '보세화물') {
                    $ad_tile = '[보세화물] 결제완료';
                } else if ($rgd->service_korean_name == '수입풀필먼트') {
                    $ad_tile = '[수입풀필먼트] 결제완료';
                } else if ($rgd->service_korean_name == '유통가공') {
                    $ad_tile = '[유통가공] 결제완료';
                }

                if ($request->p_method != 'deposit_without_bankbook') {
                    $sender = Member::where('mb_no', $rgd->mb_no)->first();
                    CommonFunc::insert_alarm($ad_tile, $rgd, $sender, null, 'settle_payment', $p_method_fee);
                }
            } else {
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
                Payment::insertGetId(
                    [
                        'mb_no' => Auth::user()->mb_no,
                        'rgd_no' => $request->rgd_no,
                        'p_price' => $request->sumprice,
                        'p_method' => $request->p_method,
                        'p_success_yn' => 'y',
                        'p_method_fee' => isset($p_method_fee) ? $p_method_fee : null,
                        'p_method_name' => $request->p_method_name,
                        'p_method_number' => $request->p_method_number,
                        'p_card_name' => $request->p_card_name,
                    ]
                );
                CancelBillHistory::insertGetId([
                    'rgd_no' => $request->rgd_no,
                    'mb_no' => $user->mb_no,
                    'cbh_type' => 'payment',
                    'cbh_status_before' => $rgd->rgd_status6,
                    'cbh_status_after' => 'payment_bill',
                    'cbh_pay_method' => isset($request->p_method) ? $request->p_method : null
                ]);

                if ($rgd->rgd_status7 == 'taxed') {
                    CancelBillHistory::insertGetId([
                        'rgd_no' => $request->rgd_no,
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_before' => $rgd->rgd_status7,
                        'cbh_status_after' => 'completed',
                        'cbh_pay_method' => isset($request->p_method) ? $request->p_method : null
                    ]);

                    ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                        'rgd_status8' => isset($request->p_method) && $request->p_method == 'deposit_without_bankbook'  ? 'in_process' : 'completed',
                    ]);

                    if ($request->p_method != 'deposit_without_bankbook') {
                        //UPDATE EST BILL
                        $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                        if ($est_rgd->rgd_status8 != 'completed') {
                            ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                                'rgd_status8' => 'completed',
                            ]);
                            CancelBillHistory::insertGetId([
                                'rgd_no' => $est_rgd->rgd_no,
                                'mb_no' => $user->mb_no,
                                'cbh_type' => 'tax',
                                'cbh_status_before' => $est_rgd->rgd_status8,
                                'cbh_status_after' => 'completed'
                            ]);
                        }
                    }
                }

                ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                    'rgd_status6' => isset($request->p_method) && $request->p_method == 'deposit_without_bankbook'  ? 'deposit_without_bankbook' : 'paid',
                    'rgd_paid_date' =>  isset($request->p_method) && $request->p_method == 'deposit_without_bankbook'  ? null : Carbon::now(),
                ]);

                if ($rgd->service_korean_name == '보세화물') {
                    $ad_tile = '[보세화물] 결제완료';
                } else if ($rgd->service_korean_name == '수입풀필먼트') {
                    $ad_tile = '[수입풀필먼트] 결제완료';
                } else if ($rgd->service_korean_name == '유통가공') {
                    $ad_tile = '[유통가공] 결제완료';
                }

                if ($request->p_method != 'deposit_without_bankbook') {
                    $sender = Member::where('mb_no', $rgd->mb_no)->first();
                    CommonFunc::insert_alarm($ad_tile, $rgd, $sender, null, 'settle_payment', $p_method_fee);
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Success',
                //'check_payment' =>$check_payment->rgd_no
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function cancel_payment(Request $request)
    {
        try {
            DB::beginTransaction();
            $check_payment = Payment::where('rgd_no', $request->rgd_no)->where('p_success_yn', 'y')->first();
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            if (isset($check_payment)) {
                Payment::where('rgd_no', $check_payment->rgd_no)->update([
                    // 'p_price' => $request->sumprice,
                    // 'p_method' => $request->p_method,
                    'p_success_yn' => null,
                    'p_cancel_yn' => 'y',
                    'p_cancel_time' => Carbon::now(),
                ]);

                ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                    'rgd_status6' => 'cancel',
                    'rgd_paid_date' => null,
                    'rgd_canceled_date' => Carbon::now(),
                ]);

                CancelBillHistory::insertGetId([
                    'mb_no' => Auth::user()->mb_no,
                    'rgd_no' => $request->rgd_no,
                    'cbh_status_before' => 'paid',
                    'cbh_status_after' => 'cancel',
                    'cbh_type' => 'cancel_payment',
                ]);

                CancelBillHistory::insertGetId([
                    'mb_no' => Auth::user()->mb_no,
                    'rgd_no' => $request->rgd_no,
                    'cbh_status_before' => 'cancel',
                    'cbh_status_after' => 'request_bill',
                    'cbh_type' => 'payment',
                ]);

                if ($rgd->rgd_status8 == 'completed') {
                    CancelBillHistory::insertGetId([
                        'rgd_no' => $request->rgd_no,
                        'mb_no' => Auth::user()->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_before' => $rgd->rgd_status8,
                        'cbh_status_after' => 'in_process'
                    ]);

                    ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                        'rgd_status8' =>  'in_process',
                    ]);

                    //UPDATE EST BILL
                    $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                    if ($est_rgd->rgd_status8 != 'in_process') {
                        ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                            'rgd_status8' => 'in_process',
                        ]);
                        CancelBillHistory::insertGetId([
                            'rgd_no' => $est_rgd->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_before' => $est_rgd->rgd_status8,
                            'cbh_status_after' => 'in_process'
                        ]);
                    }
                }
            }


            DB::commit();
            return response()->json([
                'message' => 'Success'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function load_payment(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = Payment::where('rgd_no', '=', $request->rgd_no)->orderBy('p_no', 'desc')->first();
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function check_settlement_number(Request $request)
    {
        try {
            $bill_type = $request->bill_type;

            if ($bill_type == 'MF' || $bill_type == 'CF' || $bill_type == 'CA' || $bill_type == 'MA') {
                $settlement_number = ReceivingGoodsDelivery::select(DB::raw('receiving_goods_delivery.*'))
                    ->whereNotNull('rgd_settlement_number')
                    ->whereMonth('created_at', Carbon::today()->month)
                    ->whereYear('created_at', Carbon::today()->year)
                    ->where(\DB::raw('substr(rgd_settlement_number, -2)'), '=', $bill_type)->get();
            } else if ($bill_type == 'C' || $bill_type == 'M') {
                $settlement_number = ReceivingGoodsDelivery::select(DB::raw('receiving_goods_delivery.*'))
                    ->whereNotNull('rgd_settlement_number')
                    ->whereMonth('created_at', Carbon::today()->month)
                    ->whereYear('created_at', Carbon::today()->year)
                    ->where(\DB::raw('substr(rgd_settlement_number, -1)'), '=', $bill_type)->get();
            }


            $data = [];
            $number = [];
            foreach ($settlement_number  as $i => $rgd_settlement_number) {
                $number[$i] = substr($rgd_settlement_number->rgd_settlement_number, 7, 5);
                // $bigest_number =  substr($rgd_settlement_number->rgd_settlement_number,7,5);
                // $last_bill_type = substr($rgd_settlement_number->rgd_settlement_number, -2);
            }
            if (!empty($number)) {
                $max_number = max($number);
                $data_key = array_search($max_number, $number);
                $data = $settlement_number[$data_key];
            }


            // $settlement_number->setCollection(
            //     $settlement_number->getCollection()->map(function ($item){

            //         $bigest_number =  substr($item->rgd_settlement_number,7,5);

            //         return $item;
            //     })
            // );

            return response()->json([
                'message' => 'Success',
                'settlement_number' => $settlement_number,
                'data' => $data
                //'last_bill_type' => $last_bill_type
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function connection(Request $request)
    {
        try {
            if ($request->type == 'export') {
                $export = Export::where('te_carry_out_number', $request->is_no)->first();

                if (isset($export->connection_number)) {
                    $export_connect = Export::where('connection_number', $export->connection_number)->where('te_carry_out_number', '!=', $request->is_no)->first();
                    $warehousing_connect = Warehousing::where('connection_number', $export->connection_number)->first();

                    $connection_number =  $export->connection_number;

                    $connect_w_no = isset($export_connect->te_carry_out_number) ? $export_connect->te_carry_out_number : (isset($warehousing_connect->w_no) ? $warehousing_connect->w_no : '');

                    $type_w_choose = isset($export_connect->te_carry_out_number) ? "export" : "warehousing";
                }
            } else {
                $warehousing = Warehousing::where('w_no', $request->w_no)->first();
                if (isset($warehousing->connection_number)) {
                    $export_connect = Export::where('connection_number', $warehousing->connection_number)->first();
                    $warehousing_connect = Warehousing::where('connection_number', $warehousing->connection_number)->where('w_no', '!=', $request->w_no)->first();

                    $connection_number = $warehousing->connection_number;

                    $connect_w_no = isset($export_connect->te_carry_out_number) ? $export_connect->te_carry_out_number : (isset($warehousing_connect->w_no) ? $warehousing_connect->w_no : '');

                    $type_w_choose = isset($export_connect->te_carry_out_number) ? "export" : "warehousing";
                }
            }



            return response()->json([
                'connection_number' => isset($connection_number) ? $connection_number : '',
                'connect_w_no' => isset($connect_w_no) ? $connect_w_no : '',
                'type_w_choose' => isset($type_w_choose) ? $type_w_choose : ''
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function create_package_delivery(Request $request)
    {

        try {
            DB::beginTransaction();

            $co_no = Auth::user()->co_no ? Auth::user()->co_no : null;
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $data = $request->data;
            $dataSubmit = $request->dataSubmit;

            $user = Auth::user();


            if ($dataSubmit) {
                $rgd = ReceivingGoodsDelivery::updateOrCreate(

                    [
                        'rgd_no' =>  isset($dataSubmit['rgd_no']) ? $dataSubmit['rgd_no'] : null,
                    ],
                    [
                        'is_no' =>  $dataSubmit['is_no'],
                        'mb_no' => $member->mb_no,
                        'service_korean_name' => '보세화물',
                        'rgd_contents' => $dataSubmit['rgd_contents'],
                        'rgd_address' => $dataSubmit['rgd_address'],
                        'rgd_address_detail' => $dataSubmit['rgd_address_detail'],
                        'rgd_receiver' => $dataSubmit['rgd_receiver'],
                        'rgd_hp' => $dataSubmit['rgd_hp'],
                        'rgd_memo' => $dataSubmit['rgd_memo'],
                        'rgd_status1' => $dataSubmit['rgd_status1'],
                        'rgd_status2' => $dataSubmit['rgd_status2'],
                        'rgd_status3' => $dataSubmit['rgd_status3'],
                        'rgd_status4' => isset($dataSubmit['rgd_status4']) ? $dataSubmit['rgd_status4'] : null,
                        'rgd_delivery_company' => $dataSubmit['rgd_delivery_company'],
                        'rgd_tracking_code' => $dataSubmit['rgd_tracking_code'],
                        'rgd_delivery_man' => $dataSubmit['rgd_delivery_man'],
                        'rgd_delivery_man_hp' => $dataSubmit['rgd_delivery_man_hp'],
                        'rgd_delivery_schedule_day' => $dataSubmit['rgd_delivery_schedule_day'] ? $dataSubmit['rgd_delivery_schedule_day'] : null,
                        //'rgd_arrive_day' => $dataSubmit['rgd_arrive_day'] ? $dataSubmit['rgd_arrive_day'] : null,
                        'rgd_arrive_day' => isset($dataSubmit['rgd_arrive_day']) ? DateTime::createFromFormat('Y-m-d', $dataSubmit['rgd_arrive_day']) : (isset($data['type']) && $dataSubmit['rgd_status3'] == "배송완료" ? Carbon::now()->toDateTimeString() : null),
                    ]

                );
                Package::updateOrCreate(
                    [
                        'p_no' =>  $dataSubmit['p_no']
                    ],
                    [
                        'w_no' => $dataSubmit['is_no'],
                        'rgd_no' => $rgd->rgd_no,
                        'note' => $dataSubmit['note'],
                        'order_number' => $dataSubmit['order_number'],
                        'pack_type' => $dataSubmit['pack_type'],
                        'quantity' => $dataSubmit['quantity'],
                        'reciever' => $dataSubmit['reciever'],
                        'reciever_address' => $dataSubmit['reciever_address'],
                        'reciever_contract' => $dataSubmit['reciever_contract'],
                        'reciever_detail_address' => $dataSubmit['reciever_detail_address'],
                        'sender' => $dataSubmit['sender'],
                        'sender_address' => $dataSubmit['sender_address'],
                        'sender_contract' => $dataSubmit['sender_contract'],
                        'sender_detail_address' => $dataSubmit['sender_detail_address']

                    ]

                );

                //alert

                $w_no_alert = (object)$dataSubmit;
                $w_no_alert->w_category_name = '보세화물';

                $w_no_alert->company = (object)$request->company;
                $w_no_alert->company->co_parent = (object)$request->company['co_parent'];
                $w_no_alert->company->co_parent->co_parent = (object)$w_no_alert->company->co_parent->co_parent;

                $rgd_status3  = isset($dataSubmit['rgd_status3']) ? $dataSubmit['rgd_status3'] : null;

                $check_alarm_first = Alarm::with(['alarm_data'])->where('w_no', $dataSubmit['is_no'])->whereHas('alarm_data', function ($query) {
                    $query->where(DB::raw('lower(ad_title)'), 'like', '%' . strtolower('[보세화물] 배송중') . '%');
                })->first();

                if ($check_alarm_first === null && $rgd_status3 == '배송중')
                    CommonFunc::insert_alarm_cargo('[보세화물] 배송중', null, $user, $w_no_alert, 'cargo_delivery');
            }
            if (isset($data['remove'])) {
                foreach ($data['remove'] as $remove) {
                    ReceivingGoodsDelivery::where('rgd_no', $remove['rgd_no'])->delete();
                    Package::where('rgd_no', $remove['rgd_no'])->delete();
                }
            }



            // if ($request->is_no){
            //     if (isset($package)) {
            //         foreach ($package['location'] as $packages) {

            //         }

            //     }
            // }


            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'is_no' => isset($dataSubmit['is_no']) ? $dataSubmit['is_no'] :  null,
                'w_no_alert' => isset($w_no_alert) ? $w_no_alert :  null,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }
}

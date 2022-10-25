<?php

namespace App\Http\Controllers\ReceivingGoodsDelivery;

use DateTime;
use App\Models\Warehousing;
use App\Models\Member;
use App\Models\WarehousingRequest;
use App\Models\ReceivingGoodsDelivery;
use App\Models\RateDataGeneral;
use App\Models\WarehousingItem;
use App\Models\Package;
use App\Models\ItemChannel;
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
use App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryRequest;
use App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryCreateRequest;
use App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryCreateApiRequest;
use App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryCreateMobileRequest;
use App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryFileRequest;
use \Carbon\Carbon;

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

            if (isset($validated['w_no'])) {
                Warehousing::where('w_no', $validated['w_no'])->update([

                    'w_schedule_amount' => $validated['w_schedule_amount'],
                    'w_schedule_day' => $validated['w_schedule_day'],
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
                        'rgd_delivery_company' => isset($rgd['rgd_delivery_company']) ? $rgd['rgd_delivery_company'] : null,
                        'rgd_tracking_code' => isset($rgd['rgd_tracking_code']) ? $rgd['rgd_tracking_code'] : null,
                        'rgd_delivery_man' => isset($rgd['rgd_delivery_man']) ? $rgd['rgd_delivery_man'] : null,
                        'rgd_delivery_man_hp' => isset($rgd['rgd_delivery_man_hp']) ? $rgd['rgd_delivery_man_hp'] : null,
                        'rgd_delivery_schedule_day' => isset($rgd['rgd_delivery_schedule_day']) ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']) : null,
                        'rgd_arrive_day' => isset($rgd['rgd_arrive_day']) ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']) : null,
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

                $status1 = isset($rgd['rgd_status1']) ? $rgd['rgd_status1'] : null;
                $status2 = isset($rgd['rgd_status2']) ? $rgd['rgd_status2'] : null;
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
                if (isset($validated['item_new']['item_name'])) {
                    $item_no_new = Item::insertGetId([
                        'mb_no' => Auth::user()->mb_no,
                        'item_brand' => $validated['item_new']['item_brand'],
                        'item_service_name' => '유통가공',
                        'co_no' => $validated['co_no'] ? $validated['co_no'] : $co_no,
                        'item_name' => $validated['item_new']['item_name'],
                        'item_option1' => $validated['item_new']['item_option1'],
                        'item_option2' => $validated['item_new']['item_option2'],
                        'item_price3' => $validated['item_new']['item_price3'],
                        'item_price4' => $validated['item_new']['item_price4']
                    ]);

                    WarehousingItem::insert([
                        'item_no' => $item_no_new,
                        'w_no' => $w_no,
                        'wi_number' => $validated['item_new']['wi_number'],
                        'wi_type' => '입고_shipper'
                    ]);

                    ItemChannel::insert(
                        [
                            'item_no' => $item_no_new,
                            'item_channel_code' => $validated['item_new']['item_channel_code'],
                            'item_channel_name' => $validated['item_new']['item_channel_name']
                        ]
                    );
                }
            }

            if (isset($validated['w_no'])) {
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
                                    'wi_number' => 0,
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
            } else {
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

            //Create 출고예정 after 입고 is done.


            if (isset($validated['page_type']) && $validated['page_type'] == 'Page130146') {
            if($status1 == "입고" && $status2 == "작업완료"){
                $check_ex = Warehousing::where('w_import_no','=',$w_no)->first();
                if(!$check_ex){
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
                    'co_no' => $validated['co_no']
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
                        'rgd_status2' => '작업완료',
                        'rgd_status3' => isset($rgd['rgd_status3']) ? $rgd['rgd_status3'] : null,
                        'rgd_delivery_company' => isset($rgd['rgd_delivery_company']) ? $rgd['rgd_delivery_company'] : null,
                        'rgd_tracking_code' => isset($rgd['rgd_tracking_code']) ? $rgd['rgd_tracking_code'] : null,
                        'rgd_delivery_man' => isset($rgd['rgd_delivery_man']) ? $rgd['rgd_delivery_man'] : null,
                        'rgd_delivery_man_hp' => isset($rgd['rgd_delivery_man_hp']) ? $rgd['rgd_delivery_man_hp'] : null,
                        'rgd_delivery_schedule_day' => isset($rgd['rgd_delivery_schedule_day']) ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']) : null,
                        'rgd_arrive_day' => isset($rgd['rgd_arrive_day']) ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']) : null,
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
                }

                }
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rgd_no' => $rgd_no,
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

    public function create_warehousing_release(Request $request)
    {
        // $validated = $request->validated();
        //return $request;
        try {
            DB::beginTransaction();

            $co_no = Auth::user()->co_no ? Auth::user()->co_no : null;
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();

            //Page130146 spasys
            if ($request->page_type == 'Page130146') {
                $warehousing_data = Warehousing::where('w_no', $request->w_no)->first();
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
    public function create_warehousing_release_mobile(Request $request)
    {
        // $validated = $request->validated();
        //return $request;
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
                $warehousing_data = Warehousing::where('w_no', $request->w_no)->first();

                if ($warehousing_data->w_type == 'IW') {
                    foreach ($request->data as $key => $data) {
                        $w_no = Warehousing::insertGetId([
                            'mb_no' => $member->mb_no,
                            'w_schedule_amount' => $data['w_schedule_amount'],
                            'w_schedule_day' => $request->w_schedule_day,
                            'w_import_no' => $data['w_import_no'],
                            //'w_amount' => $data['w_amount'],
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
                                //'mb_no' => $member->mb_no,
                                'w_schedule_amount' => $data['w_schedule_amount'],
                                'w_schedule_day' => $request->w_schedule_day,
                                'w_import_no' => $warehousing_data->w_import_no,
                                'w_amount' => $data['w_amount'],
                                'w_type' => 'EW',
                                'w_category_name' => $request->w_category_name,
                                //'co_no' => $co_no
                            ]);
                            Warehousing::where('w_no', $warehousing_data->w_import_no)->update([
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
                                    'rgd_delivery_schedule_day' => isset($location['rgd_delivery_schedule_day']) ? DateTime::createFromFormat('Y-m-d', $location['rgd_delivery_schedule_day']) : null,
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
                                'co_no' => $co_no
                            ]);
                            $w_schedule_number = CommonFunc::generate_w_schedule_number($w_no, 'EW');
                            Warehousing::where('w_no', $w_no)->update([
                                'w_schedule_number' =>   CommonFunc::generate_w_schedule_number($w_no, 'EW')
                            ]);

                            if (isset($request->page_type) && $request->page_type == 'Page130146') {
                                $w_schedule_number2 = CommonFunc::generate_w_schedule_number($request->w_no, 'EWC');
                                Warehousing::where('w_no', $request->w_no)->update([
                                    'w_schedule_number2' =>   CommonFunc::generate_w_schedule_number($request->w_no, 'EWC'),
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
                if (isset($validated['item_new']['item_name'])) {
                    $item_no_new = Item::insertGetId([
                        'mb_no' => Auth::user()->mb_no,
                        'item_brand' => $validated['item_new']['item_brand'],
                        'item_service_name' => '유통가공',
                        'co_no' => $validated['co_no'] ? $validated['co_no'] : $co_no,
                        'item_name' => $validated['item_new']['item_name'],
                        'item_option1' => $validated['item_new']['item_option1'],
                        'item_option2' => $validated['item_new']['item_option2'],
                        'item_price3' => $validated['item_new']['item_price3'],
                        'item_price4' => $validated['item_new']['item_price4']
                    ]);

                    WarehousingItem::insert([
                        'item_no' => $item_no_new,
                        'w_no' => $w_no,
                        'wi_number' => $validated['item_new']['wi_number'],
                        'wi_type' => '입고_shipper'
                    ]);

                    ItemChannel::insert(
                        [
                            'item_no' => $item_no_new,
                            'item_channel_code' => $validated['item_new']['item_channel_code'],
                            'item_channel_name' => $validated['item_new']['item_channel_name']
                        ]
                    );
                }
            }


            if (isset($validated['w_no'])) {
                foreach ($validated['items'] as $warehousing_item) {
                    $item_no = $warehousing_item['item_no'] ? $warehousing_item['item_no'] : '';


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

            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function update_rdc_cancel(Request $request)
    {

        try {

            Warehousing::where('w_no', $request->w_no)->update([
                'w_cancel_yn' => 'y'
            ]);

            return response()->json(['message' => 'ok']);
        } catch (\Exception $e) {
            Log::error($e);

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
            $rgd = ReceivingGoodsDelivery::with('mb_no')->with('w_no')->where('is_no', $is_no)->get();

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
                        'rgd_delivery_schedule_day' => isset($rgd['rgd_delivery_schedule_day']) ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']) : null,
                        'rgd_arrive_day' => isset($rgd['rgd_arrive_day']) ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']) : null,
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
                        'rgd_delivery_schedule_day' => isset($rgd['rgd_delivery_schedule_day']) ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']) : null,
                        'rgd_arrive_day' => isset($rgd['rgd_arrive_day']) ? DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']) : null,
                    ]);
                }
            }

            foreach ($validated['remove'] as $remove) {
                ReceivingGoodsDelivery::where('rgd_no', $remove['rgd_no'])->delete();
            }

            if ($validated['wr_contents']) {

                WarehousingRequest::insert([
                    'w_no' => $validated['is_no'],
                    'wr_type' => "IW",
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
                    if($rgd['is_no']){
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
            }

            if ($validated['wr_contents'] && $validated['is_no']) {
                WarehousingRequest::insert([
                    'wr_type' => "IW",
                    'w_no' => $validated['is_no'],
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
                    $url = Storage::disk('public')->delete($path . '/' . $file->file_name);
                    $file->delete();
                }
            }

            $files = [];
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

            File::insert($files);

            DB::commit();
            return response()->json(['message' => Messages::MSG_0007], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

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

    public function update_status5(Request $request)
    {
        try {
            if ($request->bill_type == 'case') {
                ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                    'rgd_status5' => 'confirmed'
                ]);
            } else if ($request->bill_type == 'monthly') {
                foreach ($request->rgds as $rgd) {
                    ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                        'rgd_status5' => 'confirmed'
                    ]);
                }
            } else if ($request->bill_type == 'multiple') {
                foreach ($request->rgds as $rgd) {
                    if ($rgd['rgd_bill_type'] == 'final') {
                        ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                            'rgd_status5' => 'confirmed'
                        ]);
                    } else if ($rgd['rgd_bill_type'] == 'final_monthly') {
                        $rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $rgd['rgd_no'])->first();
                        $co_no = $rgd->warehousing->co_no;

                        $updated_at = Carbon::createFromFormat('Y.m.d H:i:s',  $rgd->updated_at->format('Y.m.d H:i:s'));

                        $start_date = $updated_at->startOfMonth()->toDateString();
                        $end_date = $updated_at->endOfMonth()->toDateString();

                        $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general'])
                            ->whereHas('w_no', function ($q) use ($co_no) {
                                $q->where('co_no', $co_no);
                            })
                            ->where('updated_at', '>=', date('Y-m-d 00:00:00', strtotime($start_date)))
                            ->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($end_date)))
                            ->where('rgd_status1', '=', '입고')
                            ->where('rgd_bill_type', 'final_monthly')
                            ->update([
                                'rgd_status5' => 'confirmed'
                            ]);
                    }
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

    public function update_status5_fulfillment(Request $request)
    {
        try {
            if ($request->bill_type == 'case') {
                ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                    'rgd_status5' => 'confirmed'
                ]);
            } else if ($request->bill_type == 'monthly') {
                foreach ($request->rgds as $rgd) {
                    ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                        'rgd_status5' => 'confirmed'
                    ]);
                }
            } else if ($request->bill_type == 'multiple') {
                foreach ($request->rgds as $rgd) {

                    ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                        'rgd_status5' => 'confirmed'
                    ]);
                }
            }

            return response()->json([
                'message' => 'Success'
            ]);
        } catch (\Exception $e) {
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
}

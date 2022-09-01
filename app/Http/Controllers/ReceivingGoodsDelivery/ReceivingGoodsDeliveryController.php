<?php

namespace App\Http\Controllers\ReceivingGoodsDelivery;

use DateTime;
use App\Models\Warehousing;
use App\Models\Member;
use App\Models\WarehousingRequest;
use App\Models\ReceivingGoodsDelivery;
use App\Models\WarehousingItem;
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
use App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryCreateMobileRequest;
use App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryFileRequest;

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
            // $warehousing = Warehousing::where('w_no', $validated['w_no'])->first();
            // $warehousing_item = WarehousingItem::where('wi_no', $validated['wi_no'])->first();
            // $warehousing_request = WarehousingRequest::where('wr_no', $validated['wr_no'])->first();

            //$item = Item::where('item_no', $validated['item_no'])->first();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $co_no = Auth::user()->co_no ? Auth::user()->co_no : null;

            if(isset($validated['w_no'])){
                Warehousing::where('w_no', $validated['w_no'])->update([
                    'mb_no' => $member->mb_no,
                    'w_schedule_amount' => $validated['w_schedule_amount'],
                    'w_schedule_day' => $validated['w_schedule_day'],
                    'w_amount' => $validated['w_amount'],
                    'w_type' => 'IW',
                    'w_category_name' => $request->w_category_name,
                    'co_no' => $co_no
                ]);
            }else{
                $w_no_data = Warehousing::insertGetId([
                    'mb_no' => $member->mb_no,
                    // 'co_no' => $validated['co_no'],
                    'w_schedule_day' => DateTime::createFromFormat('Y-m-d', $validated['w_schedule_day']),
                    'w_schedule_amount' => $validated['w_schedule_amount'],
                    'w_amount' => $validated['w_amount'],
                    'w_type' => 'IW',
                    'w_category_name' => $request->w_category_name,
                    'co_no' => $co_no
                ]);
            }

            $w_no = isset($validated['w_no']) ? $validated['w_no'] : $w_no_data;

            if(!isset($validate['w_schedule_number'])){
                $w_schedule_number = (new CommonFunc)->generate_w_schedule_number($w_no,'IW');
            }


            Warehousing::where('w_no', $w_no)->update([
                'w_schedule_number' =>  $w_schedule_number
            ]);

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
                        'rgd_status1' => $rgd['rgd_status1'],
                        'rgd_status2' => $rgd['rgd_status2'],
                        'rgd_status3' => $rgd['rgd_status3'],
                        'rgd_delivery_company' => $rgd['rgd_delivery_company'],
                        'rgd_tracking_code' => $rgd['rgd_tracking_code'],
                        'rgd_delivery_man' => $rgd['rgd_delivery_man'],
                        'rgd_delivery_man_hp' => $rgd['rgd_delivery_man_hp'],
                        'rgd_delivery_schedule_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']),
                        'rgd_arrive_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']),
                    ]);
                }else{

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
                        'rgd_delivery_schedule_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']),
                        'rgd_arrive_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']),
                    ]);
                }
            }

            //T part


            if($validated['wr_contents']){
                WarehousingRequest::insert([
                    'mb_no' => $member->mb_no,
                    'wr_contents' => $validated['wr_contents'],
                ]);
            }

            foreach ($validated['remove'] as $remove) {
                WarehousingItem::where('item_no', $remove['item_no'])->where('w_no', $w_no)->delete();
            }

            WarehousingItem::where('w_no', $w_no)->where('wi_type','=','입고')->delete();

            $warehousing_items = [];



            foreach ($validated['items'] as $warehousing_item) {
                if(!isset($warehousing_item['item_no'])){
                    {
                        $item_no_new = Item::insertGetId([
                            'mb_no' => Auth::user()->mb_no,

                            'item_brand' => $warehousing_item['item_brand'],
                            'item_service_name' => '유통가공',
                            'item_name' => $warehousing_item['item_name'],
                            'item_option1' => $warehousing_item['item_option1'],
                            'item_option2' => $warehousing_item['item_option2'],

                            'item_price3' => $warehousing_item['item_price3'],
                            'item_price4' => $warehousing_item['item_price4'],

                        ]);


                    }
                }
                $item_no = $warehousing_item['item_no'] ? $warehousing_item['item_no'] : $item_no_new;

                $wi_number_received = isset($warehousing_item['warehousing_item']['wi_number_received']) ? $warehousing_item['warehousing_item']['wi_number_received'] : null;
                if (isset($warehousing_item['warehousing_item']['wi_number'])) {

                    // if(isset($warehousing_item['warehousing_item']['wi_no'])){

                    //     $warehousing_items = WarehousingItem::where('wi_no', $warehousing_item['warehousing_item']['wi_no'])->update([
                    //         'item_no' => $warehousing_item['item_no'],
                    //         'w_no' => $w_no,
                    //         'wi_number' => $warehousing_item['warehousing_item']['wi_number'],
                    //         'wi_number_received' => $wi_number_received,
                    //         'wi_number_left' =>  $wi_number_received ? $wi_number_received : $warehousing_item['warehousing_item']['wi_number'],
                    //         'wi_type' => '입고'
                    //     ]);
                    // }
                    // else{
                        WarehousingItem::insert([
                            'item_no' => $item_no,
                            'w_no' => $w_no,
                            'wi_number' => $warehousing_item['warehousing_item']['wi_number'],
                            'wi_number_received' =>  $wi_number_received,
                            'wi_number_left' =>  $wi_number_received > 0 ? $wi_number_received : $warehousing_item['warehousing_item']['wi_number'],
                            'wi_type' => '입고'
                        ]);

                    //}
              }
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

    public function create_warehousing_release(Request $request)
    {
        // $validated = $request->validated();
        //return $request;
        try {
            DB::beginTransaction();

            $co_no = Auth::user()->co_no ? Auth::user()->co_no : null;
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();

             
            if($request->page_type == 'Page146_1'){
               
                foreach($request->data as $data){
                    Warehousing::where('w_no', $request->w_no)->update([
                        'mb_no' => $member->mb_no,
                        'w_schedule_amount' => $data['w_schedule_amount'],
                        'w_schedule_day' => $request->w_schedule_day,
                        'w_import_no' => $data['w_import_no'],
                        'w_amount' => $data['w_amount'],
                        'w_type' => 'EW',
                        'w_category_name' => $request->w_category_name,
                        'co_no' => $co_no
                    ]);
                    Warehousing::where('w_no', $request->w_no)->update([
                        'w_schedule_number' =>   CommonFunc::generate_w_schedule_number($request->w_no,'EW')
                    ]);
                    if($request->wr_contents){
                        WarehousingRequest::where('w_no', $request->w_no)->update([
                            'mb_no' => $member->mb_no,
                            'wr_contents' => $request->wr_contents,
                            'wr_type' => 'EW',
                        ]);
                    }
                    
                    WarehousingItem::where('w_no', $request->w_no)->where('wi_type','=','출고')->delete();

                    foreach ($data['items'] as $item) {
                        WarehousingItem::insert([
                            'item_no' => $item['item_no'],
                            'w_no' => $request->w_no,
                            'wi_number' => $item['schedule_wi_number'],
                            'wi_number_received' =>  $item['warehousing_item']['wi_number_received'],
                            'wi_type' => '출고'
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
                            'rgd_delivery_schedule_day' => DateTime::createFromFormat('Y-m-d', $location['rgd_delivery_schedule_day']),
                            'rgd_arrive_day' => DateTime::createFromFormat('Y-m-d', $location['rgd_arrive_day']),
                        ]);
                    }
                }

            }else{
                foreach($request->data as $data){
                    $request->w_no = Warehousing::insertGetId([
                        'mb_no' => $member->mb_no,
                        'w_schedule_amount' => $data['w_schedule_amount'],
                        'w_schedule_day' => $request->w_schedule_day,
                        'w_import_no' => $data['w_import_no'],
                        'w_amount' => $data['w_amount'],
                        'w_type' => 'EW',
                        'w_category_name' => $request->w_category_name,
                        'co_no' => $co_no
                    ]);
                    Warehousing::where('w_no', $request->w_no)->update([
                        'w_schedule_number' =>   CommonFunc::generate_w_schedule_number($request->w_no,'EW')
                    ]);
                    if($request->wr_contents){
                        WarehousingRequest::insert([
                            'mb_no' => $member->mb_no,
                            'wr_contents' => $request->wr_contents,
                            'wr_type' => 'EW',
                        ]);
                    }
                    foreach ($data['items'] as $item) {
                        WarehousingItem::insert([
                            'item_no' => $item['item_no'],
                            'w_no' => $request->w_no,
                            'wi_number' => $item['schedule_wi_number'],
                            'wi_number_received' =>  $item['warehousing_item']['wi_number'],
                            'wi_type' => '출고'
                        ]);
                    }

                    foreach ($data['location'] as $location) {
                        $rgd_no = ReceivingGoodsDelivery::insertGetId([
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
                            'rgd_delivery_schedule_day' => DateTime::createFromFormat('Y-m-d', $location['rgd_delivery_schedule_day']),
                            'rgd_arrive_day' => DateTime::createFromFormat('Y-m-d', $location['rgd_arrive_day']),
                        ]);
                    }
                }

            }
            // if(isset($validated['w_no'])){
            //     Warehousing::where('w_no', $validated['w_no'])->update([
            //         'mb_no' => $member->mb_no,
            //         'w_schedule_amount' => $validated['w_schedule_amount'],
            //         'w_schedule_day' => $validated['w_schedule_day'],
            //         'w_amount' => $validated['w_amount'],
            //         'w_type' => 'EW',
            //         'w_category_name' => '유통 가공'
            //     ]);
            // }else{
            //     $w_no_data = Warehousing::insertGetId([
            //         'mb_no' => $member->mb_no,
            //         // 'co_no' => $validated['co_no'],
            //         'w_schedule_day' => DateTime::createFromFormat('Y-m-d', $validated['w_schedule_day']),
            //         'w_schedule_amount' => $validated['w_schedule_amount'],
            //         'w_amount' => $validated['w_amount'],
            //         'w_type' => 'EW',
            //         'w_category_name' => '유통 가공'
            //     ]);
            // }

            // $w_no = isset($validated['w_no']) ? $validated['w_no'] : $w_no_data;

            // if(!isset($validate['w_schedule_number'])){
            //     $w_schedule_number = (new CommonFunc)->generate_w_schedule_number($w_no,'RW');
            // }

            // Warehousing::where('w_no', $w_no)->update([
            //     'w_schedule_number' =>  $w_schedule_number
            // ]);

            // foreach ($validated['location'] as $rgd) {

            //     if (!isset($rgd['rgd_no'])) {

            //         $rgd_no = ReceivingGoodsDelivery::insertGetId([
            //             'mb_no' => $member->mb_no,
            //             'w_no' => $w_no,
            //             'service_korean_name' => '유통 가공',
            //             'rgd_contents' => $rgd['rgd_contents'],
            //             'rgd_address' => $rgd['rgd_address'],
            //             'rgd_address_detail' => $rgd['rgd_address_detail'],
            //             'rgd_receiver' => $rgd['rgd_receiver'],
            //             'rgd_hp' => $rgd['rgd_hp'],
            //             'rgd_memo' => $rgd['rgd_memo'],
            //             'rgd_status1' => $rgd['rgd_status1'],
            //             'rgd_status2' => $rgd['rgd_status2'],
            //             'rgd_status3' => $rgd['rgd_status3'],
            //             'rgd_delivery_company' => $rgd['rgd_delivery_company'],
            //             'rgd_tracking_code' => $rgd['rgd_tracking_code'],
            //             'rgd_delivery_man' => $rgd['rgd_delivery_man'],
            //             'rgd_delivery_man_hp' => $rgd['rgd_delivery_man_hp'],
            //             'rgd_delivery_schedule_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']),
            //             'rgd_arrive_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']),
            //         ]);
            //     }else{

            //         $rgd_no = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
            //             'rgd_contents' => $rgd['rgd_contents'],
            //             'rgd_address' => $rgd['rgd_address'],
            //             'rgd_address_detail' => $rgd['rgd_address_detail'],
            //             'rgd_receiver' => $rgd['rgd_receiver'],
            //             'rgd_hp' => $rgd['rgd_hp'],
            //             'rgd_memo' => $rgd['rgd_memo'],
            //             'rgd_status1' => $rgd['rgd_status1'],
            //             'rgd_status2' => $rgd['rgd_status2'],
            //             'rgd_status3' => $rgd['rgd_status3'],
            //             'rgd_delivery_company' => $rgd['rgd_delivery_company'],
            //             'rgd_tracking_code' => $rgd['rgd_tracking_code'],
            //             'rgd_delivery_man' => $rgd['rgd_delivery_man'],
            //             'rgd_delivery_man_hp' => $rgd['rgd_delivery_man_hp'],
            //             'rgd_delivery_schedule_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']),
            //             'rgd_arrive_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']),
            //         ]);
            //     }
            // }

            // //T part


            // if($validated['wr_contents']){
            //     WarehousingRequest::insert([
            //         'mb_no' => $member->mb_no,
            //         'wr_contents' => $validated['wr_contents'],
            //     ]);
            // }

            // foreach ($validated['remove'] as $remove) {
            //     WarehousingItem::where('item_no', $remove['item_no'])->where('w_no', $w_no)->delete();
            // }

            // WarehousingItem::where('w_no', $w_no)->where('wi_type','=','출고')->delete();

            // $warehousing_items = [];

            // foreach ($validated['items'] as $warehousing_item) {
            //     if(!isset($warehousing_item['item_no'])){
            //         {
            //             $item_no_new = Item::insertGetId([
            //                 'mb_no' => Auth::user()->mb_no,

            //                 'item_brand' => $warehousing_item['item_brand'],
            //                 'item_service_name' => '유통가공',
            //                 'item_name' => $warehousing_item['item_name'],
            //                 'item_option1' => $warehousing_item['item_option1'],
            //                 'item_option2' => $warehousing_item['item_option2'],

            //                 'item_price3' => $warehousing_item['item_price3'],
            //                 'item_price4' => $warehousing_item['item_price4'],

            //             ]);


            //         }
            //     }
            //     $item_no = $warehousing_item['item_no'] ? $warehousing_item['item_no'] : $item_no_new;

            //     $wi_number_received = isset($warehousing_item['warehousing_item']['wi_number_received']) ? $warehousing_item['warehousing_item']['wi_number_received'] : null;
            //     if (isset($warehousing_item['warehousing_item']['wi_number'])) {

            //         // if(isset($warehousing_item['warehousing_item']['wi_no'])){

            //         //     $warehousing_items = WarehousingItem::where('wi_no', $warehousing_item['warehousing_item']['wi_no'])->update([
            //         //         'item_no' => $warehousing_item['item_no'],
            //         //         'w_no' => $w_no,
            //         //         'wi_number' => $warehousing_item['warehousing_item']['wi_number'],
            //         //         'wi_number_received' => $wi_number_received,
            //         //     ]);
            //         // }
            //         // else{
            //             WarehousingItem::insert([
            //                 'item_no' => $item_no,
            //                 'w_no' => $w_no,
            //                 'wi_number' => $warehousing_item['warehousing_item']['wi_number'],
            //                 'wi_number_received' =>  $wi_number_received,
            //                 'wi_type' => '출고'
            //             ]);

            //         //}
            //   }
            // }



            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function getReceivingGoodsDelivery($is_no){

        try {

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $rgd = ReceivingGoodsDelivery::with('mb_no')->with('w_no')->where('is_no', $is_no)->get();

            return response()->json($rgd);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
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
            $rgd = ReceivingGoodsDelivery::with('mb_no')->with('w_no')->whereHas('w_no', function($q) use ($w_no) {
                return $q->where('w_no', $w_no);
            })->get();

            return response()->json($rgd);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function create_import_schedule(ReceivingGoodsDeliveryCreateRequest $request)
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
                            'rgd_delivery_schedule_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']),
                            'rgd_arrive_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']),
                        ]);
                    }else{

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
                            'rgd_delivery_schedule_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']),
                            'rgd_arrive_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']),
                        ]);
                    }
                }

                foreach ($validated['remove'] as $remove) {
                    ReceivingGoodsDelivery::where('rgd_no', $remove['rgd_no'])->delete();
                }

                if($validated['wr_contents']){
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
                            'rgd_delivery_schedule_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']),
                            'rgd_arrive_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']),
                        ]);
                    }else{

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
                            'rgd_delivery_schedule_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']),
                            'rgd_arrive_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']),
                        ]);
                    }
                }

                foreach ($validated['remove'] as $remove) {
                    ReceivingGoodsDelivery::where('rgd_no', $remove['rgd_no'])->delete();
                }

                if($validated['wr_contents']){
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
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function update_rgd_file(ReceivingGoodsDeliveryFileRequest $request){
        $validated = $request->validated();
        try{
            $path = join('/', ['files', 'import_schedule', $validated['is_no']]);
            //return $validated['is_no'];
            if($request->remove_files){
                foreach($request->remove_files as $key => $file_no) {
                    $file = File::where('file_no', $file_no)->get()->first();
                    $url = Storage::disk('public')->delete($path. '/' . $file->file_name);
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
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function get_rgd_file(Request $request){
        try {
            //$validated = $request->validated();

            // If per_page is null set default data = 15
            //$per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            //$page = isset($validated['page']) ? $validated['page'] : 1;
            $file = File::where('file_table_key', '=', $request->is_no)->
            where('file_table', '=', 'import_schedule')->get();
            return response()->json($file);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ReceivingGoodsDelivery  $receivingGoodsDelivery
     * @return \Illuminate\Http\Response
     */
    public function show(ReceivingGoodsDelivery $receivingGoodsDelivery)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ReceivingGoodsDelivery  $receivingGoodsDelivery
     * @return \Illuminate\Http\Response
     */
    public function edit(ReceivingGoodsDelivery $receivingGoodsDelivery)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ReceivingGoodsDelivery  $receivingGoodsDelivery
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ReceivingGoodsDelivery $receivingGoodsDelivery)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ReceivingGoodsDelivery  $receivingGoodsDelivery
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReceivingGoodsDelivery $receivingGoodsDelivery)
    {
        //
    }
}

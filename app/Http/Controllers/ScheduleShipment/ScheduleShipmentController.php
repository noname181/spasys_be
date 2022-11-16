<?php

namespace App\Http\Controllers\ScheduleShipment;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleShipment\ScheduleShipmentRequest;
use App\Http\Requests\ScheduleShipment\ScheduleShipmentSearchRequest;
use App\Models\Warehousing;
use App\Models\WarehousingItem;
use App\Models\ScheduleShipment;
use App\Models\ScheduleShipmentInfo;
use App\Models\Company;
use App\Models\File;
use App\Models\ItemChannel;
use App\Utils\Messages;
use App\Http\Requests\Item\ExcelRequest;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Utils\CommonFunc;

class ScheduleShipmentController extends Controller
{
    /**
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function paginateScheduleShipments(ScheduleShipmentSearchRequest $request)
    {
        try {

            $validated = $request->validated();
            $user = Auth::user();
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            if( $request->type == 'page136'){
                if ($user->mb_type == 'shop') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms'])->whereNull('trans_no')->whereHas('ContractWms.company.co_parent', function ($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    })->orderBy('ss_no', 'DESC');
                }else if($user->mb_type == 'shipper'){
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms'])->whereNull('trans_no')->whereHas('ContractWms.company', function ($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    })->orderBy('ss_no', 'DESC');
                }else if($user->mb_type == 'spasys'){
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms'])->whereNull('trans_no')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    })->orderBy('ss_no', 'DESC');
                }
            }else{
                if ($user->mb_type == 'shop') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms','receving_goods_delivery'])->whereNotNull('trans_no')->whereHas('ContractWms.company.co_parent', function ($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    })->orderBy('ss_no', 'DESC');
                }else if($user->mb_type == 'shipper'){
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms','receving_goods_delivery'])->whereNotNull('trans_no')->whereHas('ContractWms.company', function ($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    })->orderBy('ss_no', 'DESC');
                }else if($user->mb_type == 'spasys'){
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms','receving_goods_delivery'])->whereNotNull('trans_no')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    })->orderBy('ss_no', 'DESC');
                }
            }

            if (isset($validated['from_date'])) {
                $schedule_shipment->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $schedule_shipment->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $schedule_shipment->whereHas('ContractWms.company.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }

            if (isset($validated['co_name'])) {
                $schedule_shipment->whereHas('ContractWms.company', function($q) use($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['item_brand'])) {
                $schedule_shipment->whereHas('schedule_shipment_info.item', function($q) use($validated) {
                    return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }

            if (isset($validated['item_channel_name'])) {
                $schedule_shipment->whereHas('schedule_shipment_info.item.item_channels', function($q) use($validated) {
                    return $q->where(DB::raw('lower(item_channel_name)'), 'like', '%' . strtolower($validated['item_channel_name']) . '%');
                });
            }

            if (isset($validated['item_name'])) {
                $schedule_shipment->whereHas('schedule_shipment_info.item', function($q) use($validated) {
                    return $q->where(DB::raw('lower(item_name)'), 'like', '%' . strtolower($validated['item_name']) . '%');
                });
            }

            if (isset($validated['status'])) {
                if($validated['status'] > 0){
                    $schedule_shipment->where('status', '=', $validated['status']);
                }
            }

            if (isset($validated['order_id'])) {

                $schedule_shipment->where(DB::raw('lower(order_id)'), 'like', '%' . strtolower($validated['order_id']) . '%');
                
            }
            if (isset($validated['recv_name'])) {
                $schedule_shipment->where(DB::raw('lower(recv_name)'), 'like', '%' . strtolower($validated['recv_name']) . '%');
            }
            if (isset($validated['name'])) {
                $schedule_shipment->whereHas('schedule_shipment_info', function($q) use($validated) {
                    return $q->where(DB::raw('lower(name)'), 'like', '%' . strtolower($validated['name']) . '%');
                });
            }
            if (isset($validated['qty'])) {
                $schedule_shipment->whereHas('schedule_shipment_info', function($q) use($validated) {
                    return $q->where(DB::raw('lower(qty)'), 'like', '%' . strtolower($validated['qty']) . '%');
                });
            }
            if (isset($validated['trans_corp'])) {
                $schedule_shipment->where(DB::raw('lower(trans_corp)'), 'like', '%' . strtolower($validated['trans_corp']) . '%');
            }

            $schedule_shipment = $schedule_shipment->paginate($per_page, ['*'], 'page', $page);

            return response()->json($schedule_shipment);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018,], 500);
        }
    }
    public function apiScheduleShipments(Request $request)
    {
        //return $request;
        //$validated = $request->validated();
        try {
            DB::beginTransaction();
            $user = Auth::user();
                foreach ($request->data as $i_schedule => $schedule) {
                    $ss_no = ScheduleShipment::insertGetId([
                        'co_no' => $user->co_no,
                        'seq' => isset($schedule['seq']) ? $schedule['seq'] : null,
                        'pack' => isset($schedule['pack']) ? $schedule['pack'] : null,
                        'shop_code' => isset($schedule['shop_id']) ? $schedule['shop_id'] : null,
                        'shop_name' => isset($schedule['shop_name']) ? $schedule['shop_name'] : null,
                        'order_id' => isset($schedule['order_id']) ? $schedule['order_id'] : null,
                        'order_id_seq' => isset($schedule['order_id_seq']) ? $schedule['order_id_seq'] : null,
                        'order_id_seq2' => isset($schedule['order_id_seq2']) ? $schedule['order_id_seq2'] : null,
                        'shop_product_id' => isset($schedule['shop_product_id']) ? $schedule['shop_product_id'] : null,
                        'product_name' => isset($schedule['product_name']) ? $schedule['product_name'] : null,
                        'options' => isset($schedule['options']) ? $schedule['options'] : null,
                        'qty' => isset($schedule['qty']) ? $schedule['qty'] : null,
                        'order_name' => isset($schedule['order_name']) ? $schedule['order_name'] : null,
                        'order_mobile' => isset($schedule['order_mobile']) ? $schedule['order_mobile'] : null,
                        'order_tel' => isset($schedule['order_tel']) ? $schedule['order_tel'] : null,
                        'recv_name' => isset($schedule['recv_name']) ? $schedule['recv_name'] : null,
                        'recv_mobile' => isset($schedule['recv_mobile']) ? $schedule['recv_mobile'] : null,
                        'recv_tel' => isset($schedule['recv_tel']) ? $schedule['recv_tel'] : null,
                        'recv_address' => isset($schedule['recv_address']) ? $schedule['recv_address'] : null,
                        'recv_zip' => isset($schedule['recv_zip']) ? $schedule['recv_zip'] : null,
                        'memo' => isset($schedule['memo']) ? $schedule['memo'] : null,
                        'status' => isset($schedule['status']) ? $schedule['status'] : null,
                        'order_cs' => isset($schedule['order_cs']) ? $schedule['order_cs'] : null,
                        'collect_date' => isset($schedule['collect_date']) ? $schedule['collect_date'] : null,
                        'order_date' => isset($schedule['order_date']) ? $schedule['order_date'] : null,
                        'trans_date' => isset($schedule['trans_date']) ? $schedule['trans_date'] : null,
                        'trans_date_pos' => isset($schedule['trans_date_pos']) ? $schedule['trans_date_pos'] : null,
                        'shopstat_date' => isset($schedule['shopstat_date']) ? $schedule['shopstat_date'] : null,
                        'supply_price' => isset($schedule['supply_price']) ? $schedule['supply_price'] : null,
                        'amount' => isset($schedule['amount']) ? $schedule['amount'] : null,
                        'extra_money' => isset($schedule['extra_money']) ? $schedule['extra_money'] : null,
                        'trans_corp' => isset($schedule['trans_corp']) ? $schedule['trans_corp'] : null,
                        'trans_no' => isset($schedule['trans_no']) ? '출고' : '출고예정',
                        'trans_who' => isset($schedule['trans_who']) ? $schedule['trans_who'] : null,
                        'prepay_price' => isset($schedule['prepay_price']) ? $schedule['prepay_price'] : null,
                        'gift' => isset($schedule['gift']) ? $schedule['gift'] : null,
                        'hold' => isset($schedule['hold']) ? $schedule['hold'] : null,
                        'org_seq' => isset($schedule['org_seq']) ? $schedule['org_seq'] : null,
                        'deal_no' => isset($schedule['deal_no']) ? $schedule['deal_no'] : null,
                        'sub_domain' => isset($schedule['sub_domain']) ? $schedule['sub_domain'] : null,
                        'sub_domain_seq' => isset($schedule['sub_domain_seq']) ? $schedule['sub_domain_seq'] : null,
                    ]);

                    if(isset($schedule['order_products'])){
                        foreach ($schedule['order_products'] as $ss_info => $schedule_info) {
                            $ss_info_no = ScheduleShipmentInfo::insertGetId([
                                'ss_no' => $ss_no,
                                'co_no' => $user->co_no,
                                'barcode' => isset($schedule_info['barcode']) ? $schedule_info['barcode'] : null,
                                'brand' => isset($schedule_info['brand']) ? $schedule_info['brand'] : null,
                                'cancel_date' =>isset( $schedule_info['cancel_date']) ? $schedule_info['cancel_date'] : null,
                                'change_date' => isset($schedule_info['change_date']) ? $schedule_info['change_date'] : null,
                                'enable_sale' => isset($schedule_info['enable_sale']) ? $schedule_info['enable_sale'] : null,
                                'extra_money' => isset($schedule_info['extra_money']) ? $schedule_info['extra_money'] : null,
                                'is_gift' => isset($schedule_info['is_gift']) ? $schedule_info['is_gift'] : null,
                                'link_id' => isset($schedule_info['link_id']) ? $schedule_info['link_id'] : null,
                                'name' => isset($schedule_info['name']) ? $schedule_info['name'] : null,
                                'new_link_id' => isset($schedule_info['new_link_id']) ? $schedule_info['new_link_id'] : null,
                                'options' => isset($schedule_info['options']) ? $schedule_info['options'] : null,
                                'order_cs' => isset($schedule_info['order_cs']) ? $schedule_info['order_cs'] : null,
                                'prd_amount' => isset($schedule_info['prd_amount']) ? $schedule_info['prd_amount'] : null,
                                'prd_seq' => isset($schedule_info['prd_seq']) ? $schedule_info['prd_seq'] : null,
                                'prd_supply_price' => isset($schedule_info['prd_supply_price']) ? $schedule_info['prd_supply_price'] : null,
                                'product_id' => isset($schedule_info['product_id']) ? $schedule_info['product_id'] : null,
                                'qty' => isset($schedule_info['qty']) ? $schedule_info['qty'] : null,
                                'shop_price' => isset($schedule_info['shop_price']) ? $schedule_info['shop_price'] : null,
                                'supply_code' =>isset( $schedule_info['supply_code']) ? $schedule_info['supply_code'] : null,
                                'supply_name' => isset($schedule_info['supply_name']) ? $schedule_info['supply_name'] : null,
                                'supply_options' => isset($schedule_info['supply_options']) ? $schedule_info['supply_options'] : null,
    
                            ]);
                        }
                    }
                   

                }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,

            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            //return $e;
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }
    public function getScheduleShipmentById($ss_no)
    {
        $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info','receving_goods_delivery'])->find($ss_no);
        if (!empty($schedule_shipment)) {
            return response()->json(
                ['message' => Messages::MSG_0007,
                 'data' => $schedule_shipment
                ], 200);
        } else {
            return response()->json(['message' => CommonFunc::renderMessage(Messages::MSG_0016, ['ScheduleShipment'])], 400);
        }
    }
    public function deleteScheduleShipmentInfo(ScheduleShipmentInfo $scheduleShipmentInfo)
    {
        try {
            $scheduleShipmentInfo->delete();
            return response()->json([
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0006], 500);
        }
    }
    public function deleteScheduleShipment(ScheduleShipment $scheduleShipment)
    {
        try {
            $scheduleShipment->delete();
            return response()->json([
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0006], 500);
        }
    }
    public function CreateOrUpdateByCoPu(ScheduleShipmentRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            //$ssi_no = $request->get('ssi_no');

                if(isset($validated['co_no'])){
                    if(isset($validated['schedule_shipment_info'])){
                        foreach ($validated['schedule_shipment_info'] as $ssi) {
                            $co_no = $request->get('co_no');
                            ScheduleShipmentInfo::updateOrCreate(
                                [
                                    'ssi_no' => $ssi['ssi_no'] ?: null,
                                ],
                                [
                                    'co_no' => $co_no,
                                    'supply_code' => ($ssi['supply_code'] && $ssi['supply_code'] !='null') ? $ssi['supply_code']  : null,
                                    'supply_name' => ($ssi['supply_name'] && $ssi['supply_name'] !='null') ? $ssi['supply_name']  : null,
                                ]
                            );
                        }
                    }
                    if(isset($validated['schedule_shipment'])){
                        foreach ($validated['schedule_shipment'] as $ss) {
                            $co_no = $request->get('co_no');
                            ScheduleShipment::updateOrCreate(
                                [
                                    'ss_no' => ($ss['ss_no'] &&  $ss['ss_no'] != 'undefined') ?  $ss['ss_no'] : null,
                                ],
                                [
                                    'co_no' => $co_no,
                                    'shop_code' => ($ss['shop_code'] && $ss['shop_code'] !='null') ? $ss['shop_code']: null,
                                    'shop_name' => ($ss['shop_name'] && $ss['shop_name'] !='null') ? $ss['shop_name']: null,
                                ]
                            );
                        }
                    }

                }



            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'co_no' => $co_no ? $co_no : ($ssi ? $ssi->co_no : null),
                '$validated' => isset($validated['co_no']) ? $validated['co_no'] : ''
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }
    public function getScheduleShipmentInfoByCono($request)
    {
        try {
            $schedule_shipment_info = ScheduleShipmentInfo::where('co_no','=',$request)->get();
            $schedule_shipment = ScheduleShipment::where('co_no','=',$request)->get();

                return response()->json(
                    ['message' => Messages::MSG_0007,
                    'data' => $schedule_shipment_info,
                    'data2' => $schedule_shipment
                    ], 200);

        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0006], 500);
        }

    }




}

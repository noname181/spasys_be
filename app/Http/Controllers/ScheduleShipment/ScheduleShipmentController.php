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
    public function paginateScheduleShipments(ScheduleShipmentRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $schedule_shipment = ScheduleShipment::with('schedule_shipment_info')->orderBy('ss_no', 'DESC')->paginate($per_page, ['*'], 'page', $page);

            return response()->json($schedule_shipment);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
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
                        'seq' => $schedule['seq'],
                        'pack' => $schedule['pack'],
                        'shop_name' => $schedule['shop_name'],
                        'order_id' => $schedule['order_id'],
                        'order_id_seq' => $schedule['order_id_seq'],
                        'order_id_seq2' => $schedule['order_id_seq2'],
                        'shop_product_id' => $schedule['shop_product_id'],
                        'product_name' => $schedule['product_name'],
                        'options' => $schedule['options'],
                        'qty' => $schedule['qty'],
                        'order_name' => $schedule['order_name'],
                        'order_mobile' => $schedule['order_mobile'],
                        'order_tel' => $schedule['order_tel'],
                        'recv_name' => $schedule['recv_name'],
                        'recv_mobile' => $schedule['recv_mobile'],
                        'recv_tel' => $schedule['recv_tel'],
                        'recv_address' => $schedule['recv_address'],
                        'recv_zip' => $schedule['recv_zip'],
                        'memo' => $schedule['memo'],
                        'status' => $schedule['status'],
                        'order_cs' => $schedule['order_cs'],
                        'collect_date' => $schedule['collect_date'],
                        'order_date' => $schedule['order_date'],
                        'trans_date' => $schedule['trans_date'],
                        'trans_date_pos' => $schedule['trans_date_pos'],
                        'shopstat_date' => $schedule['shopstat_date'],
                        'supply_price' => $schedule['supply_price'],
                        'amount' => $schedule['amount'],
                        'extra_money' => $schedule['extra_money'],
                        'trans_corp' => $schedule['trans_corp'],
                        'trans_no' => $schedule['trans_no'],
                        'trans_who' => $schedule['trans_who'],
                        'prepay_price' => $schedule['prepay_price'],
                        'gift' => $schedule['gift'],
                        'hold' => $schedule['hold'],
                        'org_seq' => $schedule['org_seq'],
                        'deal_no' => $schedule['deal_no'],
                        'sub_domain' => $schedule['sub_domain'],
                        'sub_domain_seq' => $schedule['sub_domain_seq'],
                    ]);
                    foreach ($schedule['order_products'] as $ss_info => $schedule_info) {
                        $ss_info_no = ScheduleShipmentInfo::insertGetId([
                            'ss_no' => $ss_no,
                            'barcode' => $schedule_info['barcode'],
                            'brand' => $schedule_info['brand'],
                            'cancel_date' => $schedule_info['cancel_date'],
                            'change_date' => $schedule_info['change_date'],
                            'enable_sale' => $schedule_info['enable_sale'],
                            'extra_money' => $schedule_info['extra_money'],
                            'is_gift' => $schedule_info['is_gift'],
                            'link_id' => $schedule_info['link_id'],
                            'name' => $schedule_info['name'],
                            'new_link_id' => $schedule_info['new_link_id'],
                            'options' => $schedule_info['options'],
                            'order_cs' => $schedule_info['order_cs'],
                            'prd_amount' => $schedule_info['prd_amount'],
                            'prd_seq' => $schedule_info['prd_seq'],
                            'prd_supply_price' => $schedule_info['prd_supply_price'],
                            'product_id' => $schedule_info['product_id'],
                            'qty' => $schedule_info['qty'],
                            'shop_price' => $schedule_info['shop_price'],
                            'supply_code' => $schedule_info['supply_code'],
                            'supply_name' => $schedule_info['supply_name'],
                            'supply_options' => $schedule_info['supply_options'],
  
                        ]);
                    }

                }
            
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }
    public function getScheduleShipmentById($ss_no)
    {
        $schedule_shipment = ScheduleShipment::find($ss_no);
        if (!empty($schedule_shipment)) {
            return response()->json(
                ['message' => Messages::MSG_0007,
                 'data' => $schedule_shipment
                ], 200);
        } else {
            return response()->json(['message' => CommonFunc::renderMessage(Messages::MSG_0016, ['ScheduleShipment'])], 400);
        }
    }



}

<?php

namespace App\Http\Controllers\EWHP;

use DateTime;

use App\Models\ImportExpected;
use App\Models\Import;
use App\Models\ExportConfirm;
use App\Models\Export;
use App\Utils\Messages;
use App\Models\ReceivingGoodsDelivery;
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

use App\Http\Requests\EWHP\EWHPRequest;

class EWHPController extends Controller
{
    /**
     * Fetch data
     * @param  \App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import_schedule(ReceivingGoodsDeliveryRequest $request)
    {
        try {

            return $request;
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function import(EWHPRequest $request)
    {
        $validated = $request->validated();
        try {

            DB::beginTransaction();

            $count = 0;
            foreach ($validated['import'] as $value) {
                $ti_carry_in_number = 'in_'.$value['carry_in_number'];
                $import = Import::insertGetId([
                    "ti_status" => $value['status'],
                    "ti_logistic_manage_number" => $value['logistic_manage_number'],
                    "ti_carry_in_number" => $ti_carry_in_number,
                    "ti_register_id" => $value['register_id'],
                    "ti_i_date" => $value['i_date'],
                    "ti_i_time" => $value['i_time'],
                    "ti_i_report_type" => $value['i_report_type'],
                    "ti_i_division_type" => $value['i_division_type'],
                    "ti_i_confirm_number" => $value['i_confirm_number'],
                    "ti_i_order" => $value['i_order'],
                    "ti_i_type" => $value['i_type'],
                    "ti_m_bl" => $value['m_bl'],
                    "ti_h_bl" => $value['h_bl'],
                    "ti_i_report_number" => $value['i_report_number'],
                    "ti_i_packing_type" => $value['i_packing_type'],
                    "ti_i_number" => $value['i_number'],
                    "ti_i_weight" => $value['i_weight'],
                    "ti_i_weight_unit" => $value['i_weight_unit'],
                    "ti_co_license" => $value['co_license'],
                    "ti_logistic_type" => $value['logistic_type'],
                    "ti_warehouse_code" => "abc001",
                ]);

                if (isset($value['logistic_manage_number']) && $value['logistic_manage_number']) {
                    ImportExpected::where('tie_logistic_manage_number', $value['logistic_manage_number'])->update([
                        'tie_co_license' => isset($value['co_license']) ? $value['co_license'] : null,
                    ]);
                }
                if ($import >= 1) {
                    $count++;
                }
            }
            DB::commit();
            return response()->json(['message' => 'ok', 'count' => $count]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => "no"], 500);
        }
    }

    public function export(EWHPRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            $count = 0;
            foreach ($validated['export'] as $key => $value) {
                $te_carry_in_number = 'in_'.$value['carry_in_number'];
                $te_carry_out_number = 'out_'.$value['carry_out_number'];
                $export = Export::insertGetId([
                    "te_status" => $value['status'],
                    "te_logistic_manage_number" => $value['logistic_manage_number'],
                    "te_e_confirm_number" => $value['e_confirm_number'],
                    "te_e_confirm_type" => $value['e_confirm_type'],
                    "te_e_confirm_date" => $value['e_confirm_date'],
                    "te_carry_in_number" => $te_carry_in_number,
                    "te_carry_out_number" => $te_carry_out_number,
                    "te_register_id" => $value['register_id'],
                    "te_e_date" => $value['e_date'],
                    "te_e_time" => $value['e_time'],
                    "te_e_order" => $value['e_order'],
                    "te_m_bl" => $value['m_bl'],
                    "te_h_bl" => $value['h_bl'],
                    "te_e_division_type" => $value['e_division_type'],
                    "te_e_packing_type" => $value['e_packing_type'],
                    "te_e_number" => $value['e_number'],
                    "te_e_weight" => $value['e_weight'],
                    "te_e_weight_unit" => $value['e_weight_unit'],
                    "te_e_type" => $value['e_type'],
                    "te_e_do_number" => $value['e_do_number'],
                    "te_e_price" => $value['e_price'],
                    "te_co_license" => $value['co_license'],
                    "te_logistic_type" => $value['logistic_type']
                ]);
                if ($export >= 1) {
                    $count++;
                }
            
                $is_no = $value['carry_out_number'];
                if(isset($is_no)){
                    $check = ReceivingGoodsDelivery::where('is_no',$is_no)->first();
                    if($check === null){
                        ReceivingGoodsDelivery::insertGetId([
                            'is_no' => $is_no,
                            'service_korean_name' => '보세화물',
                            'rgd_status1' => '반출',
                            'rgd_status3' => '배송준비',
                        ]);
                    }
                }  
            }
            DB::commit();
            return response()->json(['message' => 'ok', 'count' => $count]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => "no"], 500);
        }
    }

    public function import_expected(EWHPRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            $count = 0;
            foreach ($validated['import_expected'] as $key => $value) {
                $import_expected = ImportExpected::insertGetId([
                    'tie_status' => $value['status'],
                    'tie_logistic_manage_number' => $value['logistic_manage_number'],
                    'tie_register_id' => $value['register_id'],
                    'tie_is_date' => $value['is_date'],
                    'tie_is_ship' => $value['is_ship'],
                    'tie_co_license' => $value['co_license'],
                    'tie_is_cargo_eng' => $value['is_cargo_eng'],
                    'tie_is_number' => $value['is_number'],
                    'tie_is_weight' => $value['is_weight'],
                    'tie_is_weight_unit' => $value['is_weight_unit'],
                    'tie_m_bl' => $value['m_bl'],
                    'tie_h_bl' => $value['h_bl'],
                    'tie_is_name_eng' => $value['is_name_eng'],
                    'tie_warehouse_code' => "abc001",
                ]);
                if (isset($value['logistic_manage_number']) && $value['logistic_manage_number']) {
                    $import = Import::where('ti_logistic_manage_number', $value['logistic_manage_number'])->first();
                    if (isset($import) && $import) {
                        ImportExpected::where('tie_no', $import_expected)->update([
                            'tie_co_license' => isset($import->ti_co_license) ? $import->ti_co_license : null,
                        ]);
                    }
                }
                if ($import_expected >= 1) {
                    $count++;
                }
            }
            DB::commit();
            return response()->json(['message' => 'ok', 'count' => $count]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => "no"], 500);
        }
    }


    public function export_confirm(EWHPRequest $request)
    {
        $validated = $request->validated();
        try {

            DB::beginTransaction();

            $count = 0;
            foreach ($validated['export_confirm'] as $key => $value) {
                $export_confirm = ExportConfirm::insertGetId([
                    "tec_status" => $value['status'],
                    "tec_logistic_manage_number" => $value['logistic_manage_number'],
                    "tec_ec_confirm_number" => $value['ec_confirm_number'],
                    "tec_ec_type" => $value['ec_type'],
                    "tec_ec_date" => $value['ec_date'],
                    "tec_register_id" => $value['register_id'],
                    "tec_ec_number" => $value['ec_number'],
                ]);
                if ($export_confirm >= 1) {
                    $count++;
                }
            }
            DB::commit();
            return response()->json(['message' => 'ok', 'count' => $count]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => "no"], 500);
        }
    }
}

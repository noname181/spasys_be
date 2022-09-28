<?php

namespace App\Http\Controllers\RateData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\RateData\RateDataImportFulfillmentRequest;
use App\Http\Requests\RateData\RateDataRequest;
use App\Http\Requests\RateData\RateDataSendMailRequest;
use App\Models\File;
use App\Models\ReceivingGoodsDelivery;
use App\Models\RateData;
use App\Models\AdjustmentGroup;
use App\Models\Warehousing;
use App\Models\RateDataGeneral;
use App\Models\RateMeta;
use App\Models\RateMetaData;
use App\Utils\CommonFunc;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class RateDataController extends Controller
{
    /**
     * Register RateDataCreate
     * @param  App\Http\Requests\RateDataRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(RateDataRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();

            if (empty($validated['newRmd_no']) && isset($validated['rm_no'])) {
                $index = RateMetaData::where('rm_no', $validated['rm_no'])->get()->count() + 1;
                $rmd_no = RateMetaData::insertGetId(
                    [
                        'mb_no' => Auth::user()->mb_no,
                        'rm_no' => $validated['rm_no'],
                        'rmd_number' => CommonFunc::generate_rmd_number($validated['rm_no'], $index),
                    ]
                );
            } else if (empty($validated['newRmd_no']) && isset($validated['co_no'])) {
                $index = RateMetaData::where('co_no', $validated['co_no'])->get()->count() + 1;
                $rmd_no = RateMetaData::insertGetId(
                    [
                        'mb_no' => Auth::user()->mb_no,
                        'co_no' => $validated['co_no'],
                        'rmd_number' => CommonFunc::generate_rmd_number($validated['co_no'], $index),
                    ]
                );
            }

            foreach ($validated['rate_data'] as $val) {
                Log::error($val);
                $rd_no = RateData::updateOrCreate(
                    [
                        'rd_no' => (isset($rmd_no) || empty($val['rmd_no']) || ($val['rmd_no'] != $validated['newRmd_no'])) ? null : $val['rd_no'],
                        'rmd_no' => isset($rmd_no) ? $rmd_no : $validated['newRmd_no'],
                        'rm_no' => isset($validated['rm_no']) ? $validated['rm_no'] : null,
                        'rd_co_no' => isset($validated['co_no']) ? $validated['co_no'] : null,
                    ],
                    [
                        'rd_cate_meta1' => $val['rd_cate_meta1'],
                        'rd_cate_meta2' => $val['rd_cate_meta2'],
                        'rd_cate1' => $val['rd_cate1'],
                        'rd_cate2' => $val['rd_cate2'],
                        'rd_cate3' => $val['rd_cate3'],
                        'rd_data1' => $val['rd_data1'],
                        'rd_data2' => $val['rd_data2'],
                        'rd_data3' => isset($val['rd_data3']) ? $val['rd_data3'] : '',

                    ],
                );
            }
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rmd_no' => isset($rmd_no) ? $rmd_no : $validated['newRmd_no'],
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function register_set_data(RateDataRequest $request) {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            if(isset($validated['rgd_no'])){
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $validated['rgd_no'])->first();
                $w_no = $rgd->w_no;
            }else{

                $w_no = null;
            }
            if (isset($w_no)) {
                $is_new = RateMetaData::where(['w_no' => $w_no,
                'set_type' => $validated['set_type']])->first();
                $rmd = RateMetaData::updateOrCreate(
                    [
                        'rgd_no' => $validated['rgd_no'],
                        'w_no' => $w_no,
                        'set_type' => $validated['set_type'],
                    ],
                    [
                        'mb_no' => Auth::user()->mb_no,
                    ]
                );
            }

            foreach ($validated['rate_data'] as $val) {
                Log::error($val);
                $rd_no = RateData::updateOrCreate(
                    [
                        'rd_no' => isset($is_new->rmd_no) ? (isset($val['rd_no']) ? $val['rd_no'] : null) : null,
                        'rmd_no' => isset($rmd) ? $rmd->rmd_no : null,
                    ],
                    [
                        'w_no' => isset($w_no) ? $w_no : null,
                        'rd_cate_meta1' => $val['rd_cate_meta1'],
                        'rd_cate_meta2' => $val['rd_cate_meta2'],
                        'rd_cate1' => isset($val['rd_cate1']) ? $val['rd_cate1'] : '',
                        'rd_cate2' => isset($val['rd_cate2']) ? $val['rd_cate2'] : '',
                        'rd_cate3' => isset($val['rd_cate3']) ? $val['rd_cate3'] : '',
                        'rd_data1' => isset($val['rd_data1']) ? $val['rd_data1'] : '',
                        'rd_data2' => isset($val['rd_data2']) ? $val['rd_data2'] : '',
                        'rd_data3' => isset($val['rd_data3']) ? $val['rd_data3'] : '',
                        'rd_data4' => isset($val['rd_data4']) ? $val['rd_data4'] : '',
                        'rd_data5' => isset($val['rd_data5']) ? $val['rd_data5'] : '',
                        'rd_data6' => isset($val['rd_data6']) ? $val['rd_data6'] : '',
                        'rd_data7' => isset($val['rd_data7']) ? $val['rd_data7'] : '',
                        'rd_data8' => isset($val['rd_data8']) ? $val['rd_data8'] : '',
                    ],
                );
            }
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rmd_no' => isset($rmd) ? $rmd->rmd_no : null,
                'w_no' => isset($w_no) ? $w_no : null,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function get_rmd_no($rgd_no, $set_type){
        $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();
        $w_no = $rgd->w_no;

        $rmd = RateMetaData::where(
            [
                'w_no' => $w_no,
                'rgd_no' => $rgd_no,
                'set_type' => $set_type
            ]
        )->first();

        if(!isset($rmd->rmd_no) && $set_type == 'work_final'){
            $rmd = RateMetaData::where(
                [
                    'w_no' => $w_no,
                    'rgd_no' => $rgd_no,
                    'set_type' => 'work_final'
                ]
            )->first();
            if(empty($rmd)){
                $rmd = RateMetaData::where(
                    [
                        'w_no' => $w_no,
                        'rgd_no' => $rgd_no,
                        'set_type' => 'work'
                    ]
                )->first();
            }
        }else if(!isset($rmd->rmd_no) && $set_type == 'storage_final'){
            $rmd = RateMetaData::where(
                [
                    'w_no' => $w_no,
                    'rgd_no' => $rgd_no,
                    'set_type' => 'storage_final'
                ]
            )->first();
            if(empty($rmd)){
                $rmd = RateMetaData::where(
                    [
                        'w_no' => $w_no,
                        'rgd_no' => $rgd_no,
                        'set_type' => 'storage'
                    ]
                )->first();
            }
        }else if(!isset($rmd->rmd_no) && $set_type == 'work_additional'){
            $rmd = RateMetaData::where(
                [
                    'w_no' => $w_no,
                    'rgd_no' => $rgd_no,
                    'set_type' => 'work_additional'
                ]
            )->first();
            if(empty($rmd)){
                $rmd = RateMetaData::where(
                    [
                        'w_no' => $w_no,
                        'rgd_no' => $rgd_no,
                        'set_type' => 'work_final'
                    ]
                )->first();
            }
        }else if(!isset($rmd->rmd_no) && $set_type == 'storage_additional'){
            $rmd = RateMetaData::where(
                [
                    'w_no' => $w_no,
                    'rgd_no' => $rgd_no,
                    'set_type' => 'storage_additional'
                ]
            )->first();
            if(empty($rmd)){
                $rmd = RateMetaData::where(
                    [
                        'w_no' => $w_no,
                        'rgd_no' => $rgd_no,
                        'set_type' => 'storage_final'
                    ]
                )->first();
            }
        }else  if(!isset($rmd->rmd_no) && $set_type == 'work_monthly_final'){
            $rmd = RateMetaData::where(
                [
                    'w_no' => $w_no,
                    'rgd_no' => $rgd_no,
                    'set_type' => 'work_monthly_final'
                ]
            )->first();
            if(empty($rmd)){
                $rmd = RateMetaData::where(
                    [
                        'w_no' => $w_no,
                        'rgd_no' => $rgd_no,
                        'set_type' => 'work_monthly'
                    ]
                )->first();
            }
        }else if(!isset($rmd->rmd_no) && $set_type == 'storage_monthly_final'){
            $rmd = RateMetaData::where(
                [
                    'w_no' => $w_no,
                    'rgd_no' => $rgd_no,
                    'set_type' => 'storage_monthly_final'
                ]
            )->first();
            if(empty($rmd)){
                $rmd = RateMetaData::where(
                    [
                        'w_no' => $w_no,
                        'rgd_no' => $rgd_no,
                        'set_type' => 'storage_monthly'
                    ]
                )->first();
            }
        }else  if(!isset($rmd->rmd_no) && $set_type == 'work_monthly_additional'){
            $rmd = RateMetaData::where(
                [
                    'w_no' => $w_no,
                    'rgd_no' => $rgd_no,
                    'set_type' => 'work_monthly_additional'
                ]
            )->first();
            if(empty($rmd)){
                $rmd = RateMetaData::where(
                    [
                        'w_no' => $w_no,
                        'rgd_no' => $rgd_no,
                        'set_type' => 'work_monthly_final'
                    ]
                )->first();
            }
        }else if(!isset($rmd->rmd_no) && $set_type == 'storage_monthly_additional'){
            $rmd = RateMetaData::where(
                [
                    'w_no' => $w_no,
                    'rgd_no' => $rgd_no,
                    'set_type' => 'storage_monthly_additional'
                ]
            )->first();
            if(empty($rmd)){
                $rmd = RateMetaData::where(
                    [
                        'w_no' => $w_no,
                        'rgd_no' => $rgd_no,
                        'set_type' => 'storage_monthly_final'
                    ]
                )->first();
            }
        }else if(!isset($rmd->rmd_no) && $set_type == 'storage_final'){
            $rmd = RateMetaData::where(
                [
                    'w_no' => $w_no,
                    'rgd_no' => $rgd_no,
                    'set_type' => 'storage_final'
                ]
            )->first();
            if(empty($rmd)){
                $rmd = RateMetaData::where(
                    [
                        'w_no' => $w_no,
                        'rgd_no' => $rgd_no,
                        'set_type' => 'storage'
                    ]
                )->first();
            }
        }

        return response()->json([
            'rmd_no' => $rmd ?  $rmd->rmd_no : null,
        ], 200);
    }

    public function get_set_data($rmd_no)
    {
        try {
            $rate_data = RateData::where('rmd_no', $rmd_no)->where(function($q) {
                $q->where('rd_cate_meta1', '유통가공')
                ->orWhere('rd_cate_meta1', '수입풀필먼트');
            })->get();
            $w_no = $rate_data[0]->w_no;
            $warehousing = Warehousing::with(['co_no', 'w_import_parent'])->where('w_no', $w_no)->first();
            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data,'warehousing'=>$warehousing], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
    public function get_set_data_mobile($bill_type,$rmd_no)
    {
        try {
            $rate_data = RateData::where('rmd_no', $rmd_no)->where('rd_cate_meta1', '유통가공')->get();
            $w_no = $rate_data[0]->w_no;
            $warehousing = Warehousing::with(['co_no', 'w_import_parent'])->where('w_no', $w_no)->first();
            $rdg = RateDataGeneral::where('w_no', $w_no)->where('rdg_bill_type', $bill_type)->first();
            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data,'rdg'=>$rdg,'warehousing'=>$warehousing], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }


    public function getRateData($rm_no, $rmd_no)
    {
        $co_no = Auth::user()->co_no;
        try {
            $rate_data1 = RateData::where('rm_no', $rm_no)->where('rmd_no', $rmd_no)->where('rd_cate_meta1', '보세화물')->get();
            $rate_data2 = RateData::where('rm_no', $rm_no)->where('rmd_no', $rmd_no)->where('rd_cate_meta1', '수입풀필먼트')->get();
            $rate_data3 = RateData::where('rm_no', $rm_no)->where('rmd_no', $rmd_no)->where('rd_cate_meta1', '유통가공')->get();
            $co_rate_data1 = RateData::where('co_no', $co_no)->where('rd_cate_meta1', '보세화물')->get();
            $co_rate_data2 = RateData::where('co_no', $co_no)->where('rd_cate_meta1', '수입풀필먼트')->get();
            $co_rate_data3 = RateData::where(['co_no' => $co_no, 'rd_cate_meta1' => '유통가공'])->get();

            return response()->json([
                'message' => Messages::MSG_0007,
                'rate_data1' => $rate_data1,
                'rate_data2' => $rate_data2,
                'rate_data3' => $rate_data3,
                'co_rate_data1' => $co_rate_data1,
                'co_rate_data2' => $co_rate_data2,
                'co_rate_data3' => $co_rate_data3,
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getRateDataByCono($rd_co_no, $rmd_no)
    {
        $co_no = Auth::user()->co_no;
        try {
            $rate_data1 = RateData::where('rd_co_no', $rd_co_no)->where('rmd_no', $rmd_no)->where('rd_cate_meta1', '보세화물')->get();
            $rate_data2 = RateData::where('rd_co_no', $rd_co_no)->where('rmd_no', $rmd_no)->where('rd_cate_meta1', '수입풀필먼트')->get();
            $rate_data3 = RateData::where('rd_co_no', $rd_co_no)->where('rmd_no', $rmd_no)->where('rd_cate_meta1', '유통가공')->get();
            $co_rate_data1 = RateData::where('rd_cate_meta1', '보세화물');
            $co_rate_data2 = RateData::where('rd_cate_meta1', '수입풀필먼트');
            $co_rate_data3 = RateData::where('rd_cate_meta1', '유통가공');

            if(Auth::user()->mb_type == 'spasys'){
                $co_rate_data1 = $co_rate_data1->where('co_no', $co_no);
                $co_rate_data2 = $co_rate_data2->where('co_no', $co_no);
                $co_rate_data3 = $co_rate_data3->where('co_no', $co_no);
            }else if(Auth::user()->mb_type == 'shop'){
                $rmd = RateMetaData::where('co_no', $co_no)->latest('created_at')->first();
                $co_rate_data1 = $co_rate_data1->where('rd_co_no', $co_no);
                $co_rate_data2 = $co_rate_data2->where('rd_co_no', $co_no);
                $co_rate_data3 = $co_rate_data3->where('rd_co_no', $co_no);
                if(isset($rmd->rmd_no)){
                    $co_rate_data1 = $co_rate_data1->where('rmd_no', $rmd->rmd_no);
                    $co_rate_data2 = $co_rate_data2->where('rmd_no', $rmd->rmd_no);
                    $co_rate_data3 = $co_rate_data3->where('rmd_no', $rmd->rmd_no);
                }
            }
            $co_rate_data1 = $co_rate_data1->get();
            $co_rate_data2 = $co_rate_data2->get();
            $co_rate_data3 = $co_rate_data3->get();

            return response()->json([
                'message' => Messages::MSG_0007,
                'rate_data1' => $rate_data1,
                'rate_data2' => $rate_data2,
                'rate_data3' => $rate_data3,
                'co_rate_data1' => $co_rate_data1,
                'co_rate_data2' => $co_rate_data2,
                'co_rate_data3' => $co_rate_data3,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }


    public function spasysRegisterRateData(RateDataRequest $request)
    {
        $validated = $request->validated();
        $co_no = Auth::user()->co_no;
        try {
            DB::beginTransaction();
            foreach ($validated['rate_data'] as $val) {
                Log::error($val);
                $rdsm_no = RateData::updateOrCreate(
                    [
                        'co_no' => isset($co_no) ? $co_no : null,
                        'rd_no' => isset($val['rd_no']) ? $val['rd_no'] : null,
                    ],
                    [
                        'rm_no' => isset($val['rm_no']) ? $val['rm_no'] : null,
                        'rd_cate_meta1' => $val['rd_cate_meta1'],
                        'rd_cate_meta2' => $val['rd_cate_meta2'],
                        'rd_cate1' => $val['rd_cate1'],
                        'rd_cate2' => $val['rd_cate2'],
                        'rd_cate3' => $val['rd_cate3'],
                        'rd_data1' => $val['rd_data1'],
                        'rd_data2' => $val['rd_data2'],
                        'rd_data3' => isset($val['rd_data3']) ? $val['rd_data3'] : '',
                    ],
                );
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function spasysRegisterRateData2(Request $request)
    {
        //$validated = $request->validated();
        $co_no = Auth::user()->co_no;
        try {
            DB::beginTransaction();

            if (isset($request->rmd_no)) {
                foreach ($request->rate_data as $val) {
                    RateData::where('rmd_no', $request->rmd_no)->update(
                        [
                            'rmd_no' => $request->rmd_no,
                            'co_no' => $request->co_no,
                            'rd_cate_meta1' => $val['rd_cate_meta1'],
                            'rd_cate_meta2' => $val['rd_cate_meta2'],
                            'rd_cate1' => $val['rd_cate1'],
                            'rd_cate2' => $val['rd_cate2'],
                            'rd_cate3' => '',
                            'rd_data1' => $val['rd_data1'],
                            'rd_data2' => $val['rd_data2'],
                            'rd_data3' => $val['rd_data3'],
                            'rd_data4' => isset($val['rd_data4']) ? $val['rd_data4'] : '',
                            'rd_data5' => isset($val['rd_data5']) ? $val['rd_data5'] : '',
                            'rd_data6' => isset($val['rd_data6']) ? $val['rd_data6'] : '',
                            'rd_data7' => isset($val['rd_data7']) ? $val['rd_data7'] : '',
                            'rd_data8' => isset($val['rd_data8']) ? $val['rd_data8'] : '',
                        ]
                    );
                }

                $rdg = RateDataGeneral::where('rmd_no', $request->rmd_no)->update(
                    [
                        'rmd_no' => $request->rmd_no,
                        'mb_no' => Auth::user()->mb_no,
                        'rdg_set_type' => 'estimated_costs',
                        'rdg_supply_price1' => $request->total1['total1_3'],
                        'rdg_supply_price2' => $request->total2['total2_3'],
                        'rdg_supply_price3' => isset($request->total3['total3_3']) ? $request->total3['total3_3'] : '',
                        'rdg_supply_price4' => isset($request->total4['total4_3']) ? $request->total4['total4_3'] : '',
                        'rdg_supply_price5' => isset($request->total5['total5_3']) ? $request->total5['total5_3'] : '',
                        'rdg_supply_price6' => isset($request->total['totalall3']) ? $request->total['totalall3'] : '',
                        'rdg_vat1' => isset($request->total1['total1_4']) ? $request->total1['total1_4'] : '',
                        'rdg_vat2' => isset($request->total2['total2_4']) ? $request->total2['total2_4'] : '',
                        'rdg_vat3' => isset($request->total3['total3_4']) ? $request->total3['total3_4'] : '',
                        'rdg_vat4' => isset($request->total4['total4_4']) ? $request->total4['total4_4'] : '',
                        'rdg_vat5' => isset($request->total5['total5_4']) ? $request->total5['total5_4'] : '',
                        'rdg_vat6' => isset($request->total['totalall4']) ? $request->total['totalall4'] : '',
                        'rdg_sum1' => isset($request->total1['total1_5']) ? $request->total1['total1_5'] : '',
                        'rdg_sum2' => isset($request->total2['total2_5']) ? $request->total2['total2_5'] : '',
                        'rdg_sum3' => isset($request->total3['total3_5']) ? $request->total3['total3_5'] : '',
                        'rdg_sum4' => isset($request->total4['total4_5']) ? $request->total4['total4_5'] : '',
                        'rdg_sum5' => isset($request->total5['total5_5']) ? $request->total5['total5_5'] : '',
                        'rdg_sum6' => isset($request->total['totalall5']) ? $request->total['totalall5'] : '',
                        'rdg_etc1' => isset($request->total1['total1_6']) ? $request->total1['total1_6'] : '',
                        'rdg_etc2' => isset($request->total2['total2_6']) ? $request->total2['total2_6'] : '',
                        'rdg_etc3' => isset($request->total3['total3_6']) ? $request->total3['total3_6'] : '',
                        'rdg_etc4' => isset($request->total4['total4_6']) ? $request->total4['total4_6'] : '',
                        'rdg_etc5' => isset($request->total5['total5_6']) ? $request->total5['total5_6'] : '',
                        'rdg_etc6' => isset($request->total['totalall6']) ? $request->total['totalall6'] : '',
                    ]
                );
            }else{
                if (isset($request->co_no)) {
                    $rmd_no = RateMetaData::insertGetId([
                        'co_no' => $request->co_no,
                        'mb_no' => Auth::user()->mb_no,
                        'set_type' => 'estimated_costs'
                    ]);
                }

                foreach ($request->rate_data as $val) {
                    RateData::insertGetId(
                        [
                            'rmd_no' => $rmd_no,
                            'co_no' => $request->co_no,
                            'rd_cate_meta1' => $val['rd_cate_meta1'],
                            'rd_cate_meta2' => $val['rd_cate_meta2'],
                            'rd_cate1' => $val['rd_cate1'],
                            'rd_cate2' => $val['rd_cate2'],
                            'rd_cate3' => '',
                            'rd_data1' => $val['rd_data1'],
                            'rd_data2' => $val['rd_data2'],
                            'rd_data3' => $val['rd_data3'],
                            'rd_data4' => isset($val['rd_data4']) ? $val['rd_data4'] : '',
                            'rd_data5' => isset($val['rd_data5']) ? $val['rd_data5'] : '',
                            'rd_data6' => isset($val['rd_data6']) ? $val['rd_data6'] : '',
                            'rd_data7' => isset($val['rd_data7']) ? $val['rd_data7'] : '',
                            'rd_data8' => isset($val['rd_data8']) ? $val['rd_data8'] : '',
                        ]
                    );
                }

                $rdg = RateDataGeneral::insertGetId(
                    [
                        'rmd_no' => $rmd_no,
                        'mb_no' => Auth::user()->mb_no,
                        'rdg_set_type' => 'estimated_costs',
                        'rdg_supply_price1' => $request->total1['total1_3'],
                        'rdg_supply_price2' => $request->total2['total2_3'],
                        'rdg_supply_price3' => isset($request->total3['total3_3']) ? $request->total3['total3_3'] : '',
                        'rdg_supply_price4' => isset($request->total4['total4_3']) ? $request->total4['total4_3'] : '',
                        'rdg_supply_price5' => isset($request->total5['total5_3']) ? $request->total5['total5_3'] : '',
                        'rdg_supply_price6' => isset($request->total['totalall3']) ? $request->total['totalall3'] : '',
                        'rdg_vat1' => isset($request->total1['total1_4']) ? $request->total1['total1_4'] : '',
                        'rdg_vat2' => isset($request->total2['total2_4']) ? $request->total2['total2_4'] : '',
                        'rdg_vat3' => isset($request->total3['total3_4']) ? $request->total3['total3_4'] : '',
                        'rdg_vat4' => isset($request->total4['total4_4']) ? $request->total4['total4_4'] : '',
                        'rdg_vat5' => isset($request->total5['total5_4']) ? $request->total5['total5_4'] : '',
                        'rdg_vat6' => isset($request->total['totalall4']) ? $request->total['totalall4'] : '',
                        'rdg_sum1' => isset($request->total1['total1_5']) ? $request->total1['total1_5'] : '',
                        'rdg_sum2' => isset($request->total2['total2_5']) ? $request->total2['total2_5'] : '',
                        'rdg_sum3' => isset($request->total3['total3_5']) ? $request->total3['total3_5'] : '',
                        'rdg_sum4' => isset($request->total4['total4_5']) ? $request->total4['total4_5'] : '',
                        'rdg_sum5' => isset($request->total5['total5_5']) ? $request->total5['total5_5'] : '',
                        'rdg_sum6' => isset($request->total['totalall5']) ? $request->total['totalall5'] : '',
                        'rdg_etc1' => isset($request->total1['total1_6']) ? $request->total1['total1_6'] : '',
                        'rdg_etc2' => isset($request->total2['total2_6']) ? $request->total2['total2_6'] : '',
                        'rdg_etc3' => isset($request->total3['total3_6']) ? $request->total3['total3_6'] : '',
                        'rdg_etc4' => isset($request->total4['total4_6']) ? $request->total4['total4_6'] : '',
                        'rdg_etc5' => isset($request->total5['total5_6']) ? $request->total5['total5_6'] : '',
                        'rdg_etc6' => isset($request->total['totalall6']) ? $request->total['totalall6'] : '',
                    ]
                );
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function getSpasysRateData()
    {
        $user = Auth::user();
        try {
            $rate_data = RateData::where('rd_cate_meta1', '보세화물');

            if($user->mb_type == 'spasys'){
                $rate_data = $rate_data->where('co_no', $user->co_no);
            }else if($user->mb_type == 'shop' || $user->mb_type == 'shipper'){
                $rmd = RateMetaData::where('co_no', $user->co_no)->latest('created_at')->first();
                $rate_data = $rate_data->where('rd_co_no', $user->co_no);
                if(isset($rmd->rmd_no)){
                    $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
                }
            }else {
                $rate_data = $rate_data->where('co_no', $user->co_no);
            }

            $rate_data = $rate_data->get();

            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data, 'mb_type' => $user->mb_type], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getSpasysRateData2()
    {
        $user = Auth::user();
        try {
            $rate_data = RateData::where('rd_cate_meta1', '수입풀필먼트');

            if($user->mb_type == 'spasys'){
                $rate_data = $rate_data->where('co_no', $user->co_no);
            }else if($user->mb_type == 'shop' || $user->mb_type == 'shipper'){
                $rmd = RateMetaData::where('co_no', $user->co_no)->latest('created_at')->first();
                $rate_data = $rate_data->where('rd_co_no', $user->co_no);
                if(isset($rmd->rmd_no)){
                    $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
                }
            }else {
                $rate_data = $rate_data->where('co_no', $user->co_no);
            }

            $rate_data = $rate_data->get();

            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getSpasysRateData3()
    {
        $user = Auth::user();
        try {
            $rate_data = RateData::where('rd_cate_meta1', '유통가공');

            if($user->mb_type == 'spasys'){
                $rate_data = $rate_data->where('co_no', $user->co_no);
            }else if($user->mb_type == 'shop' || $user->mb_type == 'shipper'){
                $rmd = RateMetaData::where('co_no', $user->co_no)->latest('created_at')->first();
                $rate_data = $rate_data->where('rd_co_no', $user->co_no);
                if(isset($rmd->rmd_no)){
                    $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
                }
            }else {
                $rate_data = $rate_data->where('co_no', $user->co_no);
            }

            $rate_data = $rate_data->get();

            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getSpasysRateData4(Request $request)
    {

        $user = Auth::user();
        try {

            if(isset($request->rd_cate_meta1) && $request->rd_cate_meta1 == '수입풀필먼트'){
                if(isset($request->rmd_no)){
                    $rate_data = RateData::where('rd_cate_meta1', '수입풀필먼트')->where('rmd_no',$request->rmd_no);
                }else{
                    $rate_data = RateData::where('rd_cate_meta1', '수입풀필먼트');
                    if($user->mb_type == 'spasys'){
                        $rate_data = $rate_data->where('co_no', $user->co_no);
                    }else if($user->mb_type == 'shop'){
                        $rmd = RateMetaData::where('co_no', $user->co_no)->latest('created_at')->first();
                        $rate_data = $rate_data->where('rd_co_no', $user->co_no);
                        if(isset($rmd->rmd_no)){
                            $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
                        }
                    }

                }
            }else{
                if(isset($request->rmd_no)){
                    $rate_data = RateData::where('rd_cate_meta1', '유통가공')->where('rmd_no',$request->rmd_no);
                }else{
                    $rate_data = RateData::where('rd_cate_meta1', '유통가공');
                    if($user->mb_type == 'spasys'){
                        $rate_data = $rate_data->where('co_no', $user->co_no);
                    }else if($user->mb_type == 'shop'){
                        $rmd = RateMetaData::where('co_no', $user->co_no)->latest('created_at')->first();
                        $rate_data = $rate_data->where('rd_co_no', $user->co_no);
                        if(isset($rmd->rmd_no)){
                            $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
                        }
                    }

                }

            }


            $rate_data = $rate_data->get();

            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }


    public function sendMail(RateDataSendMailRequest $request)
    {
        $validated = $request->validated();
        try {
            $content = [
                'content' => $validated['content'],
            ];

            $files = [];
            $urls = [];
            foreach ($validated['files'] as $file) {
                $path = join('/', ['files', 'mails']);
                $url = Storage::disk('public')->put($path, $file);
                $urls[] = public_path('/storage/' . $url);
                $files[] = [
                    'file_table' => 'mail',
                    'file_table_key' => 0,
                    'file_name' => basename($url),
                    'file_name_old' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_extension' => $file->extension(),
                    'file_position' => 0,
                    'file_url' => $url,
                ];
            }
            File::insert($files);

            Mail::send('emails.rate_data', $content, function ($message) use ($validated, $urls) {
                $message->to($validated["recipient_mail"])
                    ->subject($validated["subject"])
                    ->from(env('MAIL_FROM_ADDRESS'), $validated['sender_name']);

                if (!empty($validated['cc'])) {
                    $message->cc($validated['cc']);
                }

                foreach ($urls as $file) {
                    Log::error($file);
                    $message->attach($file);
                }
            });

            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }

    public function registe_rate_data_general(Request $request) {
        try {
            DB::beginTransaction();
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();

            $rdg = RateDataGeneral::updateOrCreate(
                [
                    'rdg_no' => $request->rdg_no,
                    'rdg_bill_type' => $request->bill_type
                ],
                [
                    'w_no' => $rgd->w_no,
                    'rgd_no' => isset($rgd->rgd_no) ? $rgd->rgd_no : null ,
                    'rdg_bill_type' => $request->bill_type,
                    'mb_no' => Auth::user()->mb_no,
                    'rdg_set_type' => $request->rdg_set_type,
                    'rdg_supply_price1' => $request->storageData['supply_price'],
                    'rdg_supply_price2' => $request->workData['supply_price'],
                    'rdg_supply_price3' => $request->total['supply_price'],
                    'rdg_vat1' => $request->storageData['taxes'],
                    'rdg_vat2' => $request->workData['taxes'],
                    'rdg_vat3' => $request->total['taxes'],
                    'rdg_sum1' => $request->storageData['sum'],
                    'rdg_sum2' => $request->workData['sum'],
                    'rdg_sum3' => $request->total['sum'],
                    'rdg_etc1' => $request->storageData['etc'],
                    'rdg_etc2' => $request->workData['etc'],
                    'rdg_etc3' => $request->total['etc'],
                ]
            );

            ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                'rgd_status4' => '예상경비청구서',
                'rgd_bill_type' => $request->bill_type
            ]);

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rate_data_general($rgd_no, $bill_type) {
        try {
            DB::beginTransaction();
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();

            $w_no = $rgd->w_no;

            $warehousing = Warehousing::with(['co_no', 'w_import_parent', 'member'])->where('w_no', $w_no)->first();

            $rdg = RateDataGeneral::where('w_no', $w_no)->where('rgd_no', $rgd_no)->where('rdg_bill_type', $bill_type)->first();


            $co_no = $warehousing->co_no;

            $ag_name = AdjustmentGroup::where('co_no',$co_no)->get();

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
                'warehousing' => $warehousing,
                'ag_name' =>  $ag_name,
                'co_no' => $co_no,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rate_data_general_final($rgd_no) {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::where('rgd_no_final', $rgd_no)->where('rdg_bill_type', 'additional')->first();

            if(!isset($rdg->rdg_no)){
                $rdg = RateDataGeneral::where('rgd_no_expectation', $rgd_no)->where('rdg_bill_type', 'final')->first();
            }

            if(!isset($rdg->rdg_no)){
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'expectation')->first();
            }

            if(!isset($rdg->rdg_no)){
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'expectation_monthly')->first();
            }


            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rate_data_general_final2($rgd_no) {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final')->first();

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function registe_rate_data_general_final(Request $request) {
        try {
            DB::beginTransaction();
            //Check is there already RateDataGeneral with rdg_no yet
            $is_exist = RateDataGeneral::where('rdg_no',  $request->rdg_no)->where('rdg_bill_type', $request->bill_type)->first();

            //Get RecevingGoodsDelivery base on rgd_no
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            $w_no = $rgd->w_no;

            $rdg = RateDataGeneral::updateOrCreate(
                [
                    'rdg_no' => isset($is_exist->rdg_no) ? $request->rdg_no : null,
                    'rdg_bill_type' => $request->bill_type
                ],
                [
                    'w_no' => $w_no,
                    'rdg_bill_type' => $request->bill_type,
                    'rgd_no_expectation' => $request->type  == 'edit_final' ? $is_exist->rgd_no_expectation : (str_contains($request->bill_type, 'final') ? $request->rgd_no : null),
                    'rgd_no_final' => $request->type  == 'edit_additional' ? $is_exist->rgd_no_final : (str_contains($request->bill_type, 'additional') ? $request->rgd_no : null),
                    'mb_no' => Auth::user()->mb_no,
                    'rdg_set_type' => $request->rdg_set_type,
                    'rdg_supply_price1' => $request->storageData['supply_price'],
                    'rdg_supply_price2' => $request->workData['supply_price'],
                    'rdg_supply_price3' => $request->total['supply_price'],
                    'rdg_vat1' => $request->storageData['taxes'],
                    'rdg_vat2' => $request->workData['taxes'],
                    'rdg_vat3' => $request->total['taxes'],
                    'rdg_sum1' => $request->storageData['sum'],
                    'rdg_sum2' => $request->workData['sum'],
                    'rdg_sum3' => $request->total['sum'],
                    'rdg_etc1' => $request->storageData['etc'],
                    'rdg_etc2' => $request->workData['etc'],
                    'rdg_etc3' => $request->total['etc'],
                ]
            );

            $previous_rgd = ReceivingGoodsDelivery::where('w_no', $w_no)->where('rgd_bill_type', '=' , $request->previous_bill_type)->first();

            if(!isset($is_exist->rdg_no) && isset($request->previous_bill_type)){
                $final_rgd = $previous_rgd->replicate();
                $final_rgd->rgd_bill_type = $request->bill_type; // the new project_id
                $final_rgd->rgd_status4 = $request->status;
                $final_rgd->save();

                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $final_rgd->rgd_no
                ]);
            }else {
                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' =>  $is_exist ?  $is_exist->rgd_no : $rgd->rgd_no
                ]);
            }

            if($request->bill_type == 'expectation' || $request->bill_type == 'expectation_monthly'){
                ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                    'rgd_status4' => $request->status,
                    'rgd_bill_type' => $request->bill_type,
                    'rgd_settlement_number' => $request->settlement_number ? $request->settlement_number : null,
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
                // 'final_rgd' => $final_rgd
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function registe_rate_data_general_additional(Request $request) {
        try {
            DB::beginTransaction();
            $is_new = RateDataGeneral::where('rdg_no',  $request->rdg_no)->where('rdg_bill_type', 'additional')->first();

            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            $w_no = $rgd->w_no;

            $rdg = RateDataGeneral::updateOrCreate(
                [
                    'rdg_no' => !isset($is_new->rdg_no) ? null :  $request->rdg_no,
                    'rdg_bill_type' => 'additional'
                ],
                [
                    'w_no' => $w_no,
                    'rdg_bill_type' => 'additional',
                    'rgd_no_final' => $request->rgd_no,
                    'mb_no' => Auth::user()->mb_no,
                    'rdg_set_type' => $request->rdg_set_type,
                    'rdg_supply_price1' => $request->storageData['supply_price'],
                    'rdg_supply_price2' => $request->workData['supply_price'],
                    'rdg_supply_price3' => $request->total['supply_price'],
                    'rdg_vat1' => $request->storageData['taxes'],
                    'rdg_vat2' => $request->workData['taxes'],
                    'rdg_vat3' => $request->total['taxes'],
                    'rdg_sum1' => $request->storageData['sum'],
                    'rdg_sum2' => $request->workData['sum'],
                    'rdg_sum3' => $request->total['sum'],
                    'rdg_etc1' => $request->storageData['etc'],
                    'rdg_etc2' => $request->workData['etc'],
                    'rdg_etc3' => $request->total['etc'],
                ]
            );

            $expectation_rgd = ReceivingGoodsDelivery::where('w_no', $w_no)->where('rgd_bill_type', 'final')->first();

            if(!isset($is_new->rdg_no)){
                $final_rgd = $expectation_rgd->replicate();
                $final_rgd->rgd_bill_type = 'additional'; // the new project_id
                $final_rgd->rgd_status4 = $request->status;
                $final_rgd->save();

                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $final_rgd->rgd_no
                ]);

            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function registe_rate_data_general_additional2(Request $request) {
        try {
            DB::beginTransaction();
            $is_new = RateDataGeneral::where('rdg_no',  $request->rdg_no)->where('rdg_bill_type', 'additional')->first();

            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            $w_no = $rgd->w_no;

            $rdg = RateDataGeneral::updateOrCreate(
                [
                    'rdg_no' => !isset($is_new->rdg_no) ? null :  $request->rdg_no,
                    'rdg_bill_type' => 'additional'
                ],
                [
                    'w_no' => $w_no,
                    'rdg_bill_type' => 'additional',
                    'mb_no' => Auth::user()->mb_no,
                    'rdg_set_type' => $request->rdg_set_type,
                    'rdg_supply_price1' => $request->storageData['supply_price'],
                    'rdg_supply_price2' => $request->workData['supply_price'],
                    'rdg_supply_price3' => $request->total['supply_price'],
                    'rdg_vat1' => $request->storageData['taxes'],
                    'rdg_vat2' => $request->workData['taxes'],
                    'rdg_vat3' => $request->total['taxes'],
                    'rdg_sum1' => $request->storageData['sum'],
                    'rdg_sum2' => $request->workData['sum'],
                    'rdg_sum3' => $request->total['sum'],
                    'rdg_etc1' => $request->storageData['etc'],
                    'rdg_etc2' => $request->workData['etc'],
                    'rdg_etc3' => $request->total['etc'],
                ]
            );

            $expectation_rgd = ReceivingGoodsDelivery::where('w_no', $w_no)->where('rgd_bill_type', 'final')->first();

            if(!isset($is_new->rdg_no)){
                $final_rgd = $expectation_rgd->replicate();
                $final_rgd->rgd_bill_type = 'additional'; // the new project_id
                $final_rgd->rgd_status4 = '확정청구서';
                $final_rgd->save();

                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $final_rgd->rgd_no
                ]);

            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
                'final_rgd' => $final_rgd
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rate_data_general_additional($rgd_no) {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final')->first();

            if(!isset($rdg->rdg_no)){
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final')->first();
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
    public function get_rate_data_general_additional2($rgd_no) {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'additional')->first();

            if(!isset($rdg->rdg_no)){
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final')->first();
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rate_data_general_additional3($rgd_no) {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::where('rgd_no_final', $rgd_no)->where('rdg_bill_type', 'additional')->first();

            // if(!isset($rdg->rdg_no)){
            //     $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final')->first();
            // }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function monthly_bill_list($rgd_no, $bill_type) {
        try {
            DB::beginTransaction();
            $rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $rgd_no)->first();
            $co_no = $rgd->warehousing->co_no;

            $updated_at = Carbon::createFromFormat('Y.m.d H:i:s',  $rgd->updated_at->format('Y.m.d H:i:s'));

            $start_date = $updated_at->startOfMonth()->toDateString();
            $end_date = $updated_at->endOfMonth()->toDateString();

            $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general'])
            ->whereHas('w_no', function($q) use($co_no){
                $q->where('co_no', $co_no);
            })
            ->where('updated_at', '>=', date('Y-m-d 00:00:00', strtotime($start_date)))
            ->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($end_date)))
            ->where('rgd_status1', '=', '출고')
            ->where('rgd_status2', '=', '작업완료')
            ->where('rgd_bill_type', $bill_type)
            ->get();

            $rdgs = [];
            foreach($rgds as $rgd){
                $rdg = RateDataGeneral::where('rgd_no_expectation', $rgd->rgd_no)
                ->where('rdg_bill_type', 'final_monthly')->first();
                $rdgs[] = $rdg;
            }
            

            return response()->json([
                'rgds' => $rgds,
                'rdgs' => $rdgs
            ], 201);

            // if (isset($validated['from_date'])) {
            //     $notices->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            // }

            // if (isset($validated['to_date'])) {
            //     $notices->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            // }


            // if(!isset($rdg->rdg_no)){
            //     $rdg = RateDataGeneral::where('rgd_no', $w_no)->where('rdg_bill_type', 'final')->first();
            // }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }


    public function registe_rate_data_general_monthly_final(Request $request) {
        try {
            DB::beginTransaction();

            foreach($request->rgds as $key=>$rgd){
                $is_exist = RateDataGeneral::where('w_no',$rgd['w_no']['w_no'])->where('rdg_bill_type', 'final_monthly')->first();
                if(!$is_exist){
                    $is_exist = RateDataGeneral::where('w_no', $rgd['w_no']['w_no'])->where('rdg_bill_type', 'expectation_monthly')->first();

                    $final_rdg = $is_exist->replicate();
                    $final_rdg->rdg_bill_type = $request->bill_type; // the new project_id
                    $final_rdg->save();
                }else {
                    $final_rdg = $is_exist;
                }

                $expectation_rgd = ReceivingGoodsDelivery::where('w_no', $rgd['w_no']['w_no'])->where('rgd_bill_type', 'expectation_monthly')->first();
                $final_rgd = ReceivingGoodsDelivery::where('w_no', $rgd['w_no']['w_no'])->where('rgd_bill_type', 'final_monthly')->first();

                if(!$final_rgd){
                    $final_rgd = $expectation_rgd->replicate();
                    $final_rgd->rgd_bill_type = $request->bill_type; // the new project_id
                    $final_rgd->rgd_status4 = '확정청구서';
                    $final_rgd->rgd_settlement_number =  $request->settlement_number;
                    $final_rgd->save();

                    RateDataGeneral::where('rdg_no', $final_rdg->rdg_no)->update([
                        'rgd_no' => $final_rgd->rgd_no,
                        'rgd_no_expectation' => $expectation_rgd->rgd_no,
                        'rdg_set_type' => $request->rdg_set_type
                    ]);

                }else {
                    RateDataGeneral::where('rdg_no', $final_rdg->rdg_no)->update([
                        'rgd_no' => $final_rgd->rgd_no,
                        'rgd_no_expectation' => $expectation_rgd->rgd_no
                    ]);
                }
            }

            // $is_new = RateDataGeneral::where('rdg_no',  $request->rdg_no)->where('rdg_bill_type', $request->bill_type)->first();

            // $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            // $w_no = $rgd->w_no;

            // $rdg = RateDataGeneral::updateOrCreate(
            //     [
            //         'rdg_no' => isset($is_new->rdg_no) ? $request->rdg_no : null,
            //         'rdg_bill_type' => $request->bill_type
            //     ],
            //     [
            //         'w_no' => $w_no,
            //         'rdg_bill_type' => $request->bill_type,
            //         'rgd_no_expectation' => $request->rgd_no,
            //         'mb_no' => Auth::user()->mb_no,
            //         // 'rdg_set_type' => $request->rdg_set_type,
            //         'rdg_supply_price3' => $request->supply_price,
            //         'rdg_vat3' => $request->vat,
            //         'rdg_sum3' => $request->sum,
            //     ]
            // );

            // $expectation_rgd = ReceivingGoodsDelivery::where('w_no', $w_no)->where('rgd_bill_type', 'expectation_monthly')->first();

            // if(!isset($is_new->rdg_no)){
            //     $final_rgd = $expectation_rgd->replicate();
            //     $final_rgd->rgd_bill_type = $request->bill_type; // the new project_id
            //     $final_rgd->rgd_status4 = '확정청구서';
            //     $final_rgd->save();

            //     RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
            //         'rgd_no' => $final_rgd->rgd_no
            //     ]);

            // }

            DB::commit();

            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $is_exist,
                // 'final_rgd' => $final_rgd
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rate_data_general_monthly_final($rgd_no) {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::where('rgd_no_expectation', $rgd_no)->where('rdg_bill_type', 'final_monthly')->first();

            if(!isset($rdg->rdg_no)){
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'expectation_monthly')->first();
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
    public function get_rate_data_general_monthly_final2($rgd_no) {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final_monthly')->first();

            if(!isset($rdg->rdg_no)){
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'expectation_monthly')->first();
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rate_data_general_monthly_additional($rgd_no) {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::where('rgd_no_final', $rgd_no)->where('rdg_bill_type', 'additional_monthly')->first();

            // if(!isset($rdg->rdg_no)){
            //     $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final_monthly')->first();
            // }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function registe_rate_data_general_final_service2(Request $request) {
        try {
            DB::beginTransaction();
            //Check is there already RateDataGeneral with rdg_no yet
            $is_exist = RateDataGeneral::where('rdg_no',  $request->rdg_no)->where('rdg_bill_type', $request->bill_type)->first();

            //Get RecevingGoodsDelivery base on rgd_no
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            $w_no = $rgd->w_no;

            $rdg = RateDataGeneral::updateOrCreate(
                [
                    'rdg_no' => isset($is_exist->rdg_no) ? $request->rdg_no : null,
                    'rdg_bill_type' => $request->bill_type
                ],
                [
                    'w_no' => $w_no,
                    'rdg_bill_type' => $request->bill_type,
                    'rgd_no_expectation' => $request->type  == 'edit_final' ? $is_exist->rgd_no_expectation : (str_contains($request->bill_type, 'final') ? $request->rgd_no : null),
                    'rgd_no_final' => $request->type  == 'edit_additional' ? $is_exist->rgd_no_final : (str_contains($request->bill_type, 'additional') ? $request->rgd_no : null),
                    'mb_no' => Auth::user()->mb_no,
                    'rdg_set_type' => $request->rdg_set_type,
                    'rdg_supply_price1' => $request->fulfill1['supply_price'],
                    'rdg_supply_price2' => $request->fulfill2['supply_price'],
                    'rdg_supply_price3' => $request->fulfill3['supply_price'],
                    'rdg_supply_price4' => $request->fulfill4['supply_price'],
                    'rdg_supply_price5' => $request->fulfill5['supply_price'],
                    'rdg_supply_price6' => $request->total['supply_price'],
                    'rdg_vat1' => $request->fulfill1['taxes'],
                    'rdg_vat2' => $request->fulfill2['taxes'],
                    'rdg_vat3' => $request->fulfill3['taxes'],
                    'rdg_vat4' => $request->fulfill4['taxes'],
                    'rdg_vat5' => $request->fulfill5['taxes'],
                    'rdg_vat6' => $request->total['taxes'],
                    'rdg_sum1' => $request->fulfill1['sum'],
                    'rdg_sum2' => $request->fulfill2['sum'],
                    'rdg_sum3' => $request->fulfill3['sum'],
                    'rdg_sum4' => $request->fulfill4['sum'],
                    'rdg_sum5' => $request->fulfill5['sum'],
                    'rdg_sum6' => $request->total['sum'],
                    'rdg_etc1' => $request->fulfill1['etc'],
                    'rdg_etc2' => $request->fulfill2['etc'],
                    'rdg_etc3' => $request->fulfill3['etc'],
                    'rdg_etc4' => $request->fulfill4['etc'],
                    'rdg_etc5' => $request->fulfill5['etc'],
                    'rdg_etc6' => $request->total['etc'],
                ]
            );

            $previous_rgd = ReceivingGoodsDelivery::where('w_no', $w_no)->where('rgd_bill_type', '=' , $request->previous_bill_type)->first();

            if(!isset($is_exist->rdg_no) && isset($request->previous_bill_type)){
                $final_rgd = $previous_rgd->replicate();
                $final_rgd->rgd_bill_type = $request->bill_type; // the new project_id
                $final_rgd->rgd_status4 = $request->status;
                $final_rgd->save();

                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $final_rgd->rgd_no
                ]);
            }else {
                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' =>  $is_exist ?  $is_exist->rgd_no : $rgd->rgd_no
                ]);
            }

            if($request->bill_type == 'final' || $request->bill_type == 'final_monthly'){
                ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                    'rgd_status4' => $request->status,
                    'rgd_bill_type' => $request->bill_type
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
                // 'final_rgd' => $final_rgd
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rmd_no_fulfill($rgd_no, $type, $pretype){
        $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();
        $w_no = $rgd->w_no;


        $rmd = RateMetaData::where(
            [
                'w_no' => $w_no,
                'rgd_no' => $rgd_no,
                'set_type' => $type
            ]
        )->first();
        if(empty($rmd)){
            $rmd = RateMetaData::where(
                [
                    'w_no' => $w_no,
                    'rgd_no' => $rgd_no,
                    'set_type' => $pretype
                ]
            )->first();
        }


        return response()->json([
            'rmd_no' => $rmd ?  $rmd->rmd_no : null,
        ], 200);
    }
}

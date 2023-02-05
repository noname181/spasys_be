<?php

namespace App\Http\Controllers\RateData;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBill\CancelBillRequest;
use App\Http\Requests\RateData\RateDataRequest;
use App\Http\Requests\RateData\RateDataSendMailRequest;
use App\Models\AdjustmentGroup;
use App\Models\CancelBillHistory;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Export;
use App\Models\Import;
use App\Models\RateData;
use App\Models\RateDataGeneral;
use App\Models\RateMeta;
use App\Models\RateMetaData;
use App\Models\ReceivingGoodsDelivery;
use App\Models\Warehousing;
use App\Utils\CommonFunc;
use App\Utils\Messages;
use App\Models\TaxInvoiceDivide;
use App\Models\ImportExpected;
use Carbon\Carbon;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

            if(isset($validated['create_new'])){
                $create_new = $validated['create_new'];
            }else {
                $create_new = false;
            }

            if (!isset($validated['rmd_no']) && isset($validated['rm_no'])) {

                $index = RateMetaData::where('rm_no', $validated['rm_no'])->get()->count() + 1;
                $rmd_no = RateMetaData::insertGetId(
                    [
                        'mb_no' => Auth::user()->mb_no,
                        'rm_no' => $validated['rm_no'],
                        'rmd_number' => CommonFunc::generate_rmd_number($validated['rm_no'], $index),
                        'rmd_mail_detail1a' => isset($validated['rmd_mail_detail1a']) ? $validated['rmd_mail_detail1a'] : '',
                        'rmd_mail_detail1b' => isset($validated['rmd_mail_detail1b']) ? $validated['rmd_mail_detail1b'] : '',
                        'rmd_mail_detail1c' => isset($validated['rmd_mail_detail1c']) ? $validated['rmd_mail_detail1c'] : '',
                        'rmd_mail_detail2' => isset($validated['rmd_mail_detail2']) ? $validated['rmd_mail_detail2'] : '',
                        'rmd_mail_detail3' => isset($validated['rmd_mail_detail3']) ? $validated['rmd_mail_detail3'] : '',

                    ]
                );
            } else if (!isset($validated['rmd_no']) && isset($validated['co_no'])) {
                $index = RateMetaData::where('co_no', $validated['co_no'])->get()->count() + 1;
                $rmd_no = RateMetaData::insertGetId(
                    [
                        'mb_no' => Auth::user()->mb_no,
                        'co_no' => $validated['co_no'],
                        'rmd_number' => CommonFunc::generate_rmd_number($validated['co_no'], $index),
                        'rmd_mail_detail1a' => isset($validated['rmd_mail_detail1a']) ? $validated['rmd_mail_detail1a'] : '',
                        'rmd_mail_detail1b' => isset($validated['rmd_mail_detail1b']) ? $validated['rmd_mail_detail1b'] : '',
                        'rmd_mail_detail1c' => isset($validated['rmd_mail_detail1c']) ? $validated['rmd_mail_detail1c'] : '',
                        'rmd_mail_detail2' => isset($validated['rmd_mail_detail2']) ? $validated['rmd_mail_detail2'] : '',
                        'rmd_mail_detail3' => isset($validated['rmd_mail_detail3']) ? $validated['rmd_mail_detail3'] : '',
                    ]
                );
            } else if (isset($validated['rmd_no']) && isset($validated['rm_no'])) {
                $rmd = RateMetaData::where('rmd_no', $validated['rmd_no'])->first();
                $index = RateMetaData::where('rm_no', $validated['rm_no'])->get()->count() + 1;
                $rmd_no = RateMetaData::insertGetId(
                    [
                        'mb_no' => Auth::user()->mb_no,
                        'rm_no' => $validated['rm_no'],
                        'rmd_number' => CommonFunc::generate_rmd_number($validated['rm_no'], $index),
                        'rmd_parent_no'=> $rmd->rmd_parent_no ? $rmd->rmd_parent_no : $validated['rmd_no'],
                        'rmd_mail_detail1a' => isset($validated['rmd_mail_detail1a']) ? $validated['rmd_mail_detail1a'] : '',
                        'rmd_mail_detail1b' => isset($validated['rmd_mail_detail1b']) ? $validated['rmd_mail_detail1b'] : '',
                        'rmd_mail_detail1c' => isset($validated['rmd_mail_detail1c']) ? $validated['rmd_mail_detail1c'] : '',
                        'rmd_mail_detail2' => isset($validated['rmd_mail_detail2']) ? $validated['rmd_mail_detail2'] : '',
                        'rmd_mail_detail3' => isset($validated['rmd_mail_detail3']) ? $validated['rmd_mail_detail3'] : '',
                    ]
                );

                $rmd_no_new = $rmd_no;
                $rmd_arr = RateMetaData::where('rmd_number', $rmd->rmd_number)->orderBy('rmd_no', 'DESC')->get();
            }  else if (isset($validated['rmd_no']) && isset($validated['co_no']) && $create_new == true) {
                $rmd = RateMetaData::where('rmd_no', $validated['rmd_no'])->first();
                $index = RateMetaData::where('co_no', $validated['co_no'])->get()->count() + 1;
                $rmd_no = RateMetaData::insertGetId(
                    [
                        'mb_no' => Auth::user()->mb_no,
                        'co_no' => $validated['co_no'],
                        'rmd_number' => CommonFunc::generate_rmd_number($validated['co_no'], $index),
                        'rmd_parent_no'=> $rmd->rmd_parent_no ? $rmd->rmd_parent_no : $validated['rmd_no'],
                        'rmd_mail_detail1a' => isset($validated['rmd_mail_detail1a']) ? $validated['rmd_mail_detail1a'] : '',
                        'rmd_mail_detail1b' => isset($validated['rmd_mail_detail1b']) ? $validated['rmd_mail_detail1b'] : '',
                        'rmd_mail_detail1c' => isset($validated['rmd_mail_detail1c']) ? $validated['rmd_mail_detail1c'] : '',
                        'rmd_mail_detail2' => isset($validated['rmd_mail_detail2']) ? $validated['rmd_mail_detail2'] : '',
                        'rmd_mail_detail3' => isset($validated['rmd_mail_detail3']) ? $validated['rmd_mail_detail3'] : '',
                    ]
                );

                $rmd_no_new = $rmd_no;
                $rmd_arr = RateMetaData::where('rmd_number', $rmd->rmd_number)->orderBy('rmd_no', 'DESC')->get();
            }



            foreach ($validated['rate_data'] as $val) {
                Log::error($val);
                $rd_no = RateData::updateOrCreate(
                    [
                        'rd_no' => isset($rmd_no_new) || $create_new == false ? null : $val['rd_no'],
                        'rmd_no' => isset($rmd_no) ? $rmd_no : $validated['rmd_no'],

                    ],
                    [
                        'rm_no' => isset($validated['rm_no']) ? $validated['rm_no'] : null,
                        'rd_co_no' => isset($validated['co_no']) ? $validated['co_no'] : null,
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
            if($create_new == true)
            $update_rate_meta_data = RateMetaData::where('rmd_no', isset($rmd_no) ? $rmd_no : $validated['rmd_no'])->update([
                'rmd_mail_detail1a' => isset($validated['rmd_mail_detail1a']) ? $validated['rmd_mail_detail1a'] : '',
                'rmd_mail_detail1b' => isset($validated['rmd_mail_detail1b']) ? $validated['rmd_mail_detail1b'] : '',
                'rmd_mail_detail1c' => isset($validated['rmd_mail_detail1c']) ? $validated['rmd_mail_detail1c'] : '',
                'rmd_mail_detail2' => isset($validated['rmd_mail_detail2']) ? $validated['rmd_mail_detail2'] : '',
                'rmd_mail_detail3' => isset($validated['rmd_mail_detail3']) ? $validated['rmd_mail_detail3'] : '',
            ]);

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rmd_no' => isset($rmd_no) ? $rmd_no : $validated['rmd_no'],
                'rmd_arr' => isset($rmd_arr) ? $rmd_arr : null,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;

            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function register_set_data(RateDataRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            if (isset($validated['rgd_no'])) {
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $validated['rgd_no'])->first();
                $w_no = $rgd->w_no;
            } else {

                $w_no = null;
            }

            if (isset($validated['type'])) {
                if (
                    $validated['type'] == 'domestic_additional_edit' ||
                    $validated['type'] == 'work_additional_edit' ||
                    $validated['type'] == 'storage_additional_edit' ||
                    $validated['type'] == 'work_monthly_additional_edit' ||
                    $validated['type'] == 'storage_monthly_additional_edit' ||
                    $validated['type'] == 'domestic_monthly_additional_edit' ||
                    $validated['type'] == 'edit' ||
                    $validated['set_type'] == 'work_final_edit' ||
                    $validated['set_type'] == 'storage_final_edit'
                ) {
                    $validated['rgd_no'] = $rgd->rgd_parent_no;
                }
            }

            if (isset($w_no)) {
                $is_new = RateMetaData::where(['rgd_no' => $validated['rgd_no'],
                    'set_type' => $validated['set_type']])->first();

                $rmd = RateMetaData::updateOrCreate(
                    [
                        'rgd_no' => $validated['rgd_no'],
                        'set_type' => $validated['set_type'],
                    ],
                    [
                        'mb_no' => Auth::user()->mb_no,
                    ]
                );
            }

            $index = 0;
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
                        'rd_index' => $index,
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
                $index++;
            }

            //ONLY FOR 보세화물
            if (isset($validated['storage_days']) && isset($validated['rgd_no'])) {
                ReceivingGoodsDelivery::where('rgd_no', $validated['rgd_no'])->update([
                    'rgd_storage_days' => $validated['storage_days'],
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rmd_no' => isset($rmd) ? $rmd->rmd_no : null,
                'w_no' => isset($w_no) ? $w_no : null,
                'rgd_no' => $rgd,
                'validated' => $validated,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function register_set_data_precalculate(request $request)
    {
        try {
            DB::beginTransaction();

            if (!isset($request->rmd_no)) {
                $index = RateMetaData::where('rm_no', $request['co_no'])->get()->count() + 1;
                $rmd_no = RateMetaData::insertGetId(
                    [
                        'co_no' => $request['co_no'],
                        'set_type' => $request['set_type'],
                        'rmd_number' => CommonFunc::generate_rmd_number($request['co_no'], $index),
                        'mb_no' => Auth::user()->mb_no,
                    ]
                );
            }

            foreach ($request['rate_data'] as $val) {
                Log::error($val);
                $rd_no = RateData::updateOrCreate(
                    [
                        'rd_no' => isset($val['rd_no']) ? $val['rd_no'] : null,
                        'rmd_no' => isset($rmd_no) ? $rmd_no : ($request->rmd_no ? $request->rmd_no : null),
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
                'rmd_no' => isset($rmd_no) ? $rmd_no : ($request->rmd_no ? $request->rmd_no : null),
                'request' => $request,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function get_rmd_no($rgd_no, $set_type)
    {
        //FOR ONLY PRECALCULATE PAGE
        if ($set_type == 'precalculate') {
            $rmd = RateMetaData::where(
                [
                    'co_no' => $rgd_no,
                    'set_type' => $set_type,
                ]
            )->first();

            return response()->json([
                'rmd_no' => $rmd ? $rmd->rmd_no : null,
                'co_no' => $rgd_no,
            ], 200);
        }

        $user = Auth::user();
        $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();
        $previous_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();

        $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();

        $rmd = RateMetaData::where(
            [
                'rgd_no' => $rgd->rgd_parent_no,
                'set_type' => $set_type,
            ]
        )->first();
        if (!isset($rmd->rmd_no)){
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => $set_type,
                ]
            )->first();
        }

        if (!isset($rmd->rmd_no) && $set_type == 'work_final') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => $user->mb_type == 'spasys' ? 'work_spasys' : 'work_shop',
                    ]
                )->first();
            }

            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'work_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => $user->mb_type == 'spasys' ? 'work_spasys' : 'work_shop',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($previous_rgd_parent)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => $user->mb_type == 'spasys' ? 'work_spasys' : 'work_shop',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($previous_rgd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $previous_rgd->rgd_parent_no,
                        'set_type' => $user->mb_type == 'spasys' ? 'work_spasys' : 'work_shop',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'storage_final') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => $user->mb_type == 'spasys' ? 'storage_spasys' : 'storage_shop',
                    ]
                )->first();
            }

            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'storage_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => $user->mb_type == 'spasys' ? 'storage_spasys' : 'storage_shop',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($previous_rgd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $previous_rgd->rgd_parent_no,
                        'set_type' => $user->mb_type == 'spasys' ? 'storage_spasys' : 'storage_shop',
                    ]
                )->first();
            }

        } else if (!isset($rmd->rmd_no) && $set_type == 'domestic_final') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => $user->mb_type == 'spasys' ? 'domestic_spasys' : 'domestic_shop',
                    ]
                )->first();
            }

            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'domestic_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => $user->mb_type == 'spasys' ? 'domestic_spasys' : 'domestic_shop',
                    ]
                )->first();
            }

            if (empty($rmd) && !empty($previous_rgd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $previous_rgd->rgd_parent_no,
                        'set_type' => $user->mb_type == 'spasys' ? 'domestic_spasys' : 'domestic_shop',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'work_final_check') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => $user->mb_type == 'shop' ? 'work_spasys' : 'work_shop',
                    ]
                )->first();
            }

            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'work_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => $user->mb_type == 'shop' ? 'work_spasys' : 'work_shop',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($previous_rgd_parent)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => $user->mb_type == 'shop' ? 'work_spasys' : 'work_shop',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($previous_rgd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $previous_rgd->rgd_parent_no,
                        'set_type' => $user->mb_type == 'shop' ? 'work_spasys' : 'work_shop',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'storage_final_check') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => $user->mb_type == 'shop' ? 'storage_spasys' : 'storage_shop',
                    ]
                )->first();
            }

            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'storage_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => $user->mb_type == 'shop' ? 'storage_spasys' : 'storage_shop',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($previous_rgd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $previous_rgd->rgd_parent_no,
                        'set_type' => $user->mb_type == 'shop' ? 'storage_spasys' : 'storage_shop',
                    ]
                )->first();
            }

        } else if (!isset($rmd->rmd_no) && $set_type == 'domestic_final_check') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => $user->mb_type == 'shop' ? 'domestic_spasys' : 'domestic_shop',
                    ]
                )->first();
            }

            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'domestic_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => $user->mb_type == 'shop' ? 'domestic_spasys' : 'domestic_shop',
                    ]
                )->first();
            }

            if (empty($rmd) && !empty($previous_rgd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $previous_rgd->rgd_parent_no,
                        'set_type' => $user->mb_type == 'shop' ? 'domestic_spasys' : 'domestic_shop',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'work_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rdg->rgd_no_final,
                    'set_type' => 'work_additional',
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'work_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'work_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'work',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'storage_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rdg->rgd_no_final,
                    'set_type' => 'storage_additional',
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'storage_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'storage_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'storage',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'domestic_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rdg->rgd_no_final,
                    'set_type' => 'domestic_additional',
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'domestic_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'domestic_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'domestic',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'work_additional2') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => 'work_additional',
                ]
            )->first();

            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_final,
                        'set_type' => 'work_additional',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'storage_additional2') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => 'storage_additional',
                ]
            )->first();
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_final,
                        'set_type' => 'storage_additional',
                    ]
                )->first();
            }

        } else if (!isset($rmd->rmd_no) && $set_type == 'domestic_additional2') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => 'domestic_additional',
                ]
            )->first();
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_final,
                        'set_type' => 'domestic_additional',
                    ]
                )->first();
            }

        } else if (!isset($rmd->rmd_no) && $set_type == 'work_monthly_final') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'work_monthly',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'work_monthly_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'work_monthly',
                    ]
                )->first();
            }

        } else if (!isset($rmd->rmd_no) && $set_type == 'storage_monthly_final') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'storage_monthly',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'storage_monthly_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'storage_monthly',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'domestic_monthly_final') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'domestic_monthly',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'domestic_monthly_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'domestic_monthly',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'work_monthly_additional') {
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_final,
                        'set_type' => 'work_monthly_additional',
                    ]
                )->first();
            }

        } else if (!isset($rmd->rmd_no) && $set_type == 'storage_monthly_additional') {
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_final,
                        'set_type' => 'storage_monthly_additional',
                    ]
                )->first();
            }

        } else if (!isset($rmd->rmd_no) && $set_type == 'domestic_monthly_additional') {
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_final,
                        'set_type' => 'domestic_monthly_additional',
                    ]
                )->first();
            }

        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded1_final') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded1_spasys',
                    ]
                )->orwhere(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded1_shop',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded1',
                    ]
                )->orwhere(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded1_shop',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded2_final') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded2_spasys',
                    ]
                )->orwhere(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded2_shop',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded2_spasys',
                    ]
                )->orwhere(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded2_shop',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded3_final') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded3_spasys',
                    ]
                )->orwhere(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded3_shop',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded3_spasys',
                    ]
                )->orwhere(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded3_shop',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded4_final') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded4_spasys',
                    ]
                )->orwhere(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded4_shop',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded4_spasys',
                    ]
                )->orwhere(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded4_shop',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded5_final') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded5_spasys',
                    ]
                )->orwhere(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded5_shop',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded5_spasys',
                    ]
                )->orwhere(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded5_shop',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded6_final') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded6_spasys',
                    ]
                )->orwhere(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded6_shop',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded6_spasys',
                    ]
                )->orwhere(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded6_shop',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded1_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();

        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded2_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();

        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded3_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();

        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded4_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();

        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded5_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();

        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded6_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();

        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded1_final_monthly') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded1_monthly',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded1_monthly',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded2_final_monthly') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded2_monthly',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded2_monthly',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded3_final_monthly') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded3_monthly',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded3_monthly',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded4_final_monthly') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded4_monthly',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded4_monthly',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded5_final_monthly') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded5_monthly',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded5_monthly',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded6_final_monthly') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded6_monthly',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded6_monthly',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded1_additional_monthly') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => $set_type,
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded2_additional_monthly') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => $set_type,
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded3_additional_monthly') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => $set_type,
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded4_additional_monthly') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => $set_type,
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded5_additional_monthly') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => $set_type,
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded6_additional_monthly') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_no,
                    'set_type' => $set_type,
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => $set_type,
                    ]
                )->first();
            }
        }

        return response()->json([
            'rmd_no' => $rmd ? $rmd->rmd_no : null,
            'rgd_no' => $rgd_no,
        ], 200);
    }

    public function get_set_data($rmd_no)
    {
        try {
            $rate_data = RateData::where('rmd_no', $rmd_no)->where(function ($q) {
                $q->where('rd_cate_meta1', '유통가공')
                    ->orWhere('rd_cate_meta1', '수입풀필먼트')
                    ->orWhere('rd_cate_meta1', '보세화물');
            })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();
            $w_no = $rate_data[0]->w_no;
            $warehousing = Warehousing::with(['co_no', 'w_import_parent', 'w_ew'])->where('w_no', $w_no)->first();
            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data, 'warehousing' => $warehousing], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_set_data_precalculate($rmd_no, $meta_cate)
    {
        try {
            $rate_data = RateData::where('rmd_no', $rmd_no)->where(function ($q) use ($meta_cate) {
                $q->where('rd_cate_meta1', $meta_cate);
            })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_data_general_precalculate($rmd_no)
    {
        try {
            $rate_data_general = RateDataGeneral::where('rmd_no', $rmd_no)->where('rdg_set_type', 'bonded_estimated_costs')->first();

            return response()->json(['message' => Messages::MSG_0007, 'rate_data_general' => $rate_data_general], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function register_data_general_precalculate(Request $request)
    {
        try {
            $user = Auth::user();

            $rmd = RateMetaData::updateOrCreate(
                [
                    'rmd_no' => isset($request->rmd_no) ? $request->rmd_no : null,
                    'set_type' => 'precalculate',
                    'co_no' => isset($request->co_no) ? $request->co_no : null,
                ],
                [
                    'mb_no' => $user->mb_no,
                    'rmd_number' => isset($request->activeTab2) ? $request->activeTab2 : null,
                    'rmd_service' => isset($request->activeTab2) ? $request->activeTab2 : null,
                    'rmd_tab_child' => isset($request->rmd_tab_child) ? $request->rmd_tab_child : null,

                ]
            );

            $rate_data_general = RateDataGeneral::updateOrCreate(
                [
                    'rmd_no' => $rmd->rmd_no,
                ],
                [
                    'rdg_set_type' => 'bonded_estimated_costs',
                    'rdg_sum1' => $request->data1,
                    'rdg_sum2' => $request->data2,
                    'rdg_sum3' => $request->data3,
                    'rdg_sum4' => $request->data4,
                    'rdg_sum5' => $request->data5,

                    'rdg_supply_price1' => $request->data6,
                    'rdg_supply_price2' => $request->data7,
                    'rdg_supply_price3' => $request->data8,
                    'rdg_supply_price4' => $request->data9,
                    'rdg_supply_price5' => $request->data10,

                    'rdg_vat1' => $request->data11,
                    'rdg_vat2' => $request->data12,
                    'rdg_vat3' => $request->data13,
                    'rdg_vat4' => $request->data14,
                    'rdg_vat5' => $request->data15,
                    'mb_no' => $user->mb_no,
                ]
            );

            return response()->json(['message' => Messages::MSG_0007, 'rmd_no' => $rmd->rmd_no, 'rate_data_general' => $rate_data_general , '$request->activeTab2' => $request->activeTab2], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_set_data_raw($rmd_no)
    {
        try {
            $rate_data = RateData::where('rmd_no', $rmd_no)->where(function ($q) {
                $q->where('rd_cate_meta1', '유통가공')
                    ->orWhere('rd_cate_meta1', '수입풀필먼트')
                    ->orWhere('rd_cate_meta1', '보세화물');
            })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();
            $w_no = $rate_data[0]->w_no;
            $warehousing = Warehousing::with(['co_no', 'w_import_parent'])->where('w_no', $w_no)->first();
            return !empty($rate_data) ? $rate_data : array();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_set_data_mobile($bill_type, $rmd_no)
    {
        try {
            $rate_data = RateData::where('rmd_no', $rmd_no)->where('rd_cate_meta1', '유통가공')->get();
            $w_no = $rate_data[0]->w_no;
            $warehousing = Warehousing::with(['co_no', 'w_import_parent', 'w_ew'])->where('w_no', $w_no)->first();
            $rdg = RateDataGeneral::where('w_no', $w_no)->where('rdg_bill_type', $bill_type)->first();
            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data, 'rdg' => $rdg, 'warehousing' => $warehousing], 200);
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
            $co_rate_data1 = RateData::where('rd_cate_meta1', '보세화물');
            $co_rate_data2 = RateData::where('rd_cate_meta1', '수입풀필먼트');
            $co_rate_data3 = RateData::where('rd_cate_meta1', '유통가공');

            if (Auth::user()->mb_type == 'spasys') {
                $co_rate_data1 = $co_rate_data1->where('co_no', $co_no)->get();
                $co_rate_data2 = $co_rate_data2->where('co_no', $co_no)->get();
                $co_rate_data3 = $co_rate_data3->where('co_no', $co_no)->get();
            } else if (Auth::user()->mb_type == 'shop') {
                $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
                $co_rate_data1 = $co_rate_data1->where('rd_co_no', $co_no);
                $co_rate_data2 = $co_rate_data2->where('rd_co_no', $co_no);
                $co_rate_data3 = $co_rate_data3->where('rd_co_no', $co_no);
                if (isset($rmd->rmd_no)) {
                    $co_rate_data1 = $co_rate_data1->where('rmd_no', $rmd->rmd_no)->get();
                    $co_rate_data2 = $co_rate_data2->where('rmd_no', $rmd->rmd_no)->get();
                    $co_rate_data3 = $co_rate_data3->where('rmd_no', $rmd->rmd_no)->get();
                }else {
                    $co_rate_data1 = [];
                    $co_rate_data2 = [];
                    $co_rate_data3 = [];
                }
            }

            if($rmd_no){
                $rmd = RateMetaData::where('rmd_no', $rmd_no)->first();
                if(isset($rmd->rmd_parent_no)){
                    $rmd_arr = RateMetaData::where('rmd_no', $rmd->rmd_parent_no)->orWhere('rmd_parent_no', $rmd->rmd_parent_no)->orderBy('rmd_no', 'DESC')->get();
                }else {
                    $rmd_arr = RateMetaData::where('rmd_no', $rmd_no)->orWhere('rmd_parent_no', $rmd_no)->orderBy('rmd_no', 'DESC')->get();
                }
            }




            return response()->json([
                'message' => Messages::MSG_0007,
                'rate_data1' => $rate_data1,
                'rate_data2' => $rate_data2,
                'rate_data3' => $rate_data3,
                'co_rate_data1' => $co_rate_data1,
                'co_rate_data2' => $co_rate_data2,
                'co_rate_data3' => $co_rate_data3,
                'rmd_arr' => isset($rmd_arr) ? $rmd_arr : null
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            // return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getRateDataRaw($rm_no, $rmd_no)
    {
        $co_no = Auth::user()->co_no;
        try {
            $rate_data1 = RateData::where('rm_no', $rm_no)->where('rmd_no', $rmd_no)->where('rd_cate_meta1', '보세화물')->get();
            $rate_data2 = RateData::where('rm_no', $rm_no)->where('rmd_no', $rmd_no)->where('rd_cate_meta1', '수입풀필먼트')->get();
            $rate_data3 = RateData::where('rm_no', $rm_no)->where('rmd_no', $rmd_no)->where('rd_cate_meta1', '유통가공')->get();
            $co_rate_data1 = RateData::where('rd_cate_meta1', '보세화물');
            $co_rate_data2 = RateData::where('rd_cate_meta1', '수입풀필먼트');
            $co_rate_data3 = RateData::where('rd_cate_meta1', '유통가공');

            if (Auth::user()->mb_type == 'spasys') {
                $co_rate_data1 = $co_rate_data1->where('co_no', $co_no)->get();
                $co_rate_data2 = $co_rate_data2->where('co_no', $co_no)->get();
                $co_rate_data3 = $co_rate_data3->where('co_no', $co_no)->get();
            } else if (Auth::user()->mb_type == 'shop') {
                $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
                $co_rate_data1 = $co_rate_data1->where('rd_co_no', $co_no);
                $co_rate_data2 = $co_rate_data2->where('rd_co_no', $co_no);
                $co_rate_data3 = $co_rate_data3->where('rd_co_no', $co_no);
                if (isset($rmd->rmd_no)) {
                    $co_rate_data1 = $co_rate_data1->where('rmd_no', $rmd->rmd_no)->get();
                    $co_rate_data2 = $co_rate_data2->where('rmd_no', $rmd->rmd_no)->get();
                    $co_rate_data3 = $co_rate_data3->where('rmd_no', $rmd->rmd_no)->get();
                }else {
                    $co_rate_data1 = [];
                    $co_rate_data2 = [];
                    $co_rate_data3 = [];
                }
            }

            return [
                'message' => Messages::MSG_0007,
                'rate_data1' => $rate_data1,
                'rate_data2' => $rate_data2,
                'rate_data3' => $rate_data3,
                'co_rate_data1' => $co_rate_data1,
                'co_rate_data2' => $co_rate_data2,
                'co_rate_data3' => $co_rate_data3,
            ];

        } catch (\Exception $e) {
            DB::rollback();
            return [];
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

            if (Auth::user()->mb_type == 'spasys') {
                $co_rate_data1 = $co_rate_data1->where('co_no', $co_no)->get();
                $co_rate_data2 = $co_rate_data2->where('co_no', $co_no)->get();
                $co_rate_data3 = $co_rate_data3->where('co_no', $co_no)->get();
            } else if (Auth::user()->mb_type == 'shop') {
                $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
                $co_rate_data1 = $co_rate_data1->where('rd_co_no', $co_no);
                $co_rate_data2 = $co_rate_data2->where('rd_co_no', $co_no);
                $co_rate_data3 = $co_rate_data3->where('rd_co_no', $co_no);
                if (isset($rmd->rmd_no)) {
                    $co_rate_data1 = $co_rate_data1->where('rmd_no', $rmd->rmd_no)->get();
                    $co_rate_data2 = $co_rate_data2->where('rmd_no', $rmd->rmd_no)->get();
                    $co_rate_data3 = $co_rate_data3->where('rmd_no', $rmd->rmd_no)->get();
                }else {
                    $co_rate_data1 = [];
                    $co_rate_data2 = [];
                    $co_rate_data3 = [];
                }
            }

            $rate_meta_data = RateMetaData::where('rmd_no', $rmd_no)->first();

            if($rmd_no){
                $rmd = RateMetaData::where('rmd_no', $rmd_no)->first();
                if(isset($rmd->rmd_parent_no)){
                    $rmd_arr = RateMetaData::where('rmd_no', $rmd->rmd_parent_no)->orWhere('rmd_parent_no', $rmd->rmd_parent_no)->orderBy('rmd_no', 'DESC')->get();
                }else {
                    $rmd_arr = RateMetaData::where('rmd_no', $rmd_no)->orWhere('rmd_parent_no', $rmd_no)->orderBy('rmd_no', 'DESC')->get();
                }
            }

            return response()->json([
                'message' => Messages::MSG_0007,
                'rate_data1' => $rate_data1,
                'rate_data2' => $rate_data2,
                'rate_data3' => $rate_data3,
                'co_rate_data1' => $co_rate_data1,
                'co_rate_data2' => $co_rate_data2,
                'co_rate_data3' => $co_rate_data3,
                'rate_meta_data' => $rate_meta_data,
                'rmd_arr' => isset($rmd_arr) ? $rmd_arr : null
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getRateDataByRmdNo($rmd_no)
    {
        $co_no = Auth::user()->co_no;
        try {

            $co_rate_data1 = RateData::where('rd_cate_meta1', '보세화물')->where('rmd_no', $rmd_no);
            $co_rate_data2 = RateData::where('rd_cate_meta1', '수입풀필먼트')->where('rmd_no', $rmd_no);
            $co_rate_data3 = RateData::where('rd_cate_meta1', '유통가공')->where('rmd_no', $rmd_no);


            $co_rate_data1 = $co_rate_data1->get();
            $co_rate_data2 = $co_rate_data2->get();
            $co_rate_data3 = $co_rate_data3->get();

            return response()->json([
                'message' => Messages::MSG_0007,
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
            $index = RateMetaData::where('co_no', $co_no)->get()->count() + 1;

            if(isset($validated['rmd_mail_detail1a']) || isset($validated['rmd_mail_detail1b']) || isset($validated['rmd_mail_detail1c']) || isset($validated['rmd_mail_detail2']) || isset($validated['rmd_mail_detail3'])){
                $rmd = RateMetaData::where('co_no', $co_no)->first();
                RateMetaData::updateOrCreate(
                    [
                        'co_no' => $co_no,
                        'mb_no' => Auth::user()->mb_no,
                    ],
                    [
                        'rmd_number' => CommonFunc::generate_rmd_number($co_no, $index),
                        'rmd_mail_detail1a' => isset($validated['rmd_mail_detail1a']) ? $validated['rmd_mail_detail1a'] : ($rmd ? $rmd->rmd_mail_detail1a : ''),
                        'rmd_mail_detail1b' => isset($validated['rmd_mail_detail1b']) ? $validated['rmd_mail_detail1b'] : ($rmd ? $rmd->rmd_mail_detail1b : ''),
                        'rmd_mail_detail1c' => isset($validated['rmd_mail_detail1c']) ? $validated['rmd_mail_detail1c'] : ($rmd ? $rmd->rmd_mail_detail1c : ''),
                        'rmd_mail_detail2' => isset($validated['rmd_mail_detail2']) ? $validated['rmd_mail_detail2'] : ($rmd ? $rmd->rmd_mail_detail2 : ''),
                        'rmd_mail_detail3' => isset($validated['rmd_mail_detail3']) ? $validated['rmd_mail_detail3'] : ($rmd ? $rmd->rmd_mail_detail3 : ''),
                    ]
                );
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'co_no' => $co_no
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
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
                $i = 0;
                foreach ($request->rate_data as $val) {
                    $update_or_create = null;
                    if (isset($val['rmd_no']) && isset($val['rd_no'])) {
                        if ($val['rmd_no'] == $request->rmd_no) {
                            $update_or_create = $request->rmd_no;
                        }
                    }
                    $i++;
                    RateData::updateOrCreate(
                        [
                            'rmd_no' => $request->rmd_no,
                            'rd_no' => $update_or_create ? $val['rd_no'] : null,
                        ],
                        [

                            'rd_co_no' => $request->co_no,
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

                $rdg = RateDataGeneral::updateOrCreate(
                    [
                        'rmd_no' => isset($val['rmd_no']) && isset($val['rd_no']) ? $val['rmd_no'] : $request->rmd_no,
                        'rdg_set_type' => 'estimated_costs',
                    ],
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
            } else {
                if (isset($request->co_no)) {
                    $index = RateMetaData::where('rm_no', $request['co_no'])->get()->count() + 1;
                    $rmd_no = RateMetaData::insertGetId([
                        'co_no' => $request->co_no,
                        'mb_no' => Auth::user()->mb_no,
                        'rmd_number' => CommonFunc::generate_rmd_number($request['co_no'], $index),
                        'set_type' => 'estimated_costs',
                        'rmd_service' => $request->activeTab2,
                        'rmd_tab_child' => $request->rmd_tab_child,
                    ]);
                }

                foreach ($request->rate_data as $val) {
                    RateData::insertGetId(
                        [
                            'rmd_no' => $rmd_no,
                            'rd_co_no' => $request->co_no,
                            'rd_cate_meta1' => isset($val['rd_cate_meta1']) ? $val['rd_cate_meta1'] : '' ,
                            'rd_cate_meta2' => isset($val['rd_cate_meta2']) ? $val['rd_cate_meta2'] : '',
                            'rd_cate1' => isset($val['rd_cate1']) ? $val['rd_cate1'] : '',
                            'rd_cate2' => isset($val['rd_cate2']) ? $val['rd_cate2'] : '',
                            'rd_cate3' => '',
                            'rd_data1' => isset($val['rd_data1']) ? $val['rd_data1'] : '',
                            'rd_data2' => isset($val['rd_data2']) ? $val['rd_data2'] : '',
                            'rd_data3' => isset($val['rd_data3']) ? $val['rd_data3'] : '',
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
                        'rdg_supply_price1' => isset($request->total1['total1_3']) ? $request->total1['total1_3'] : '',
                        'rdg_supply_price2' => isset($request->total2['total2_3']) ? $request->total2['total2_3'] : '',
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
                '$request->rate_data' => $request->rate_data,
                'rmd_no' => isset($request->rmd_no) ? $request->rmd_no : $rmd_no,
                'i' => isset($i) ? $i : '',
            ], 201);
        } catch (\Exception $e) {
            //return $e;
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }
    public function getspasys1fromte(Request $request)
    {

        try {
            $user = Auth::user();
            if($request->type_page == "te_carry_out_number"){
                $export = Export::with(['import', 'import_expected'])->where('te_carry_out_number', $request->is_no)->first();
                $company = Company::with(['co_parent'])->where('co_license', $export->import_expected->tie_co_license)->first();
            }else if($request->type_page == "ti_carry_in_number") {
                $import = Import::with(['import_expect', 'export_confirm'])->where('ti_carry_in_number', $request->is_no)->first();
                $company = Company::with(['co_parent'])->where('co_license', $import->import_expect->tie_co_license)->first();
            }else {
                $import_expected = ImportExpected::where('tie_logistic_manage_number', $request->is_no)->first();
                $company = Company::with(['co_parent'])->where('co_license', $import_expected->tie_co_license)->first();
            }

            //$company = Company::with(['co_parent'])->where('co_license', $export->import_expected->tie_co_license)->first();
            $rate_data = RateData::where('rd_cate_meta1', '보세화물');

            if ($user->mb_type == 'spasys') {
                $co_no = $company->co_no;
                $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
                $rate_data = $rate_data->where('rd_co_no', $co_no);
                if (isset($rmd->rmd_no)) {
                    $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
                }else {
                    $rate_data = [];
                }
            } else if ($user->mb_type == 'shop') {
                $co_no = $company->co_no;
                $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
                $rate_data = $rate_data->where('rd_co_no', $co_no);
                if (isset($rmd->rmd_no)) {
                    $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
                }else {
                    $rate_data = [];
                }
            } else {
                $co_no = $company->co_no;
                $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
                $rate_data = $rate_data->where('rd_co_no', $co_no);
                if (isset($rmd->rmd_no)) {
                    $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
                }else {
                    $rate_data = [];
                }
            }

            $adjustment_group = AdjustmentGroup::where('co_no', $co_no)->first();

            return response()->json([
                'message' => Messages::MSG_0007,
                'company' => $company,
                'rate_data' => $rate_data,
                'adjustment_group' => $adjustment_group,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
    public function getspasys2fromte($is_no)
    {

        try {
            $export = Export::with(['import'])->where('te_carry_out_number', $is_no)->first();
            $company = Company::where('co_license', $export->import->ti_co_license)->first();
            $rate_data = RateData::where('rd_cate_meta1', '수입풀필먼트')->where('co_no', $company->co_no);
            $rate_data = $rate_data->get();
            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
    public function getspasys3fromte($is_no)
    {
        $user = Auth::user();
        try {
            $export = Export::with(['import'])->where('te_carry_out_number', $is_no)->first();
            $company = Company::where('co_license', $export->import->ti_co_license)->first();
            $rate_data = RateData::where('rd_cate_meta1', '유통가공')->where('co_no', $company->co_no);
            $rate_data = $rate_data->get();
            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getspasys1fromlogisticnumber($is_no)
    {

        try {
            $user = Auth::user();

            $rgd = ReceivingGoodsDelivery::with(['warehousing', 't_import', 't_export', 't_import_expected'])->where('rgd_no', $is_no)->first();



            $import = Import::with(['export_confirm', 'export', 'import_expect'])->where('ti_carry_in_number', $rgd->rgd_ti_carry_in_number)->first();


            $company = Company::where('co_no', $rgd->warehousing->co_no)->first();

            $company_shipper = $company;



            $rate_data = RateData::where('rd_cate_meta1', '보세화물');

            $rmd = RateMetaData::where('co_no', $user->mb_type == 'spasys' ? $company->co_parent_no : $company->co_no)->whereNull('set_type')->orderBy('rmd_no', 'DESC')->first();
            $rate_data = $rate_data->where('rd_co_no', $user->mb_type == 'spasys' ? $company->co_parent_no : $company->co_no);
            if (isset($rmd->rmd_no)) {
                $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
            }

            $rate_data = $rate_data->get();

            $adjustment_group = AdjustmentGroup::where('co_no', '=', $user->mb_type == 'spasys' ? $company->co_parent_no : $company->co_no)->first();
            $adjustment_group_all = AdjustmentGroup::where('co_no', '=', $user->mb_type == 'spasys' ? $company->co_parent_no : $company->co_no)->get();

            $export = Export::with(['import', 'import_expected', 't_export_confirm'])->where('te_carry_out_number', $rgd->rgd_te_carry_out_number)->first();

            if (empty($export)) {
                $export = [
                    'import' => $import,
                    'import_expected' => $import->import_expect,

                ];
            }
            $rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_tracking_code', $is_no)->first();
            return response()->json([
                'message' => Messages::MSG_0007,
                'company' => $company,
                'adjustment_group_all' => $adjustment_group_all,
                'rate_data' => $rate_data,
                'rgd'=>$rgd,
                'export' => $export,
                'company_shipper' => $company_shipper,
                'adjustment_group' => $adjustment_group,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getSpasysRateData($co_no)
    {
        $user = Auth::user();

        $service_korean_name = '보세화물';

        try {
            $rate_data = RateData::where('rd_cate_meta1', $service_korean_name);

           if ($user->mb_type == 'spasys') {
                $rate_data = $rate_data->where('co_no', $co_no)->get();
            } else if ($user->mb_type == 'shop' || $user->mb_type == 'shipper') {
                $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
                $rate_data = $rate_data->where('rd_co_no', $co_no);
                if (isset($rmd->rmd_no)) {
                    $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
                }else {
                    $rate_data = [];
                }
            } else {
                $rate_data = $rate_data->where('co_no', $co_no)->get();
            }

            $rate_meta_data = RateMetaData::where('co_no', $co_no)->latest('created_at')->first();

            return response()->json([
                'message' => Messages::MSG_0007,
                'rate_data' => $rate_data,
                'co_no' => $co_no,
                'rate_meta_data' => $rate_meta_data

                ], 200);
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

            if ($user->mb_type == 'spasys') {
                $rate_data = $rate_data->where('co_no', $user->co_no);
            } else if ($user->mb_type == 'shop' || $user->mb_type == 'shipper') {
                $rmd = RateMetaData::where('co_no', $user->co_no)->whereNull('set_type')->latest('created_at')->first();
                $rate_data = $rate_data->where('rd_co_no', $user->co_no);
                if (isset($rmd->rmd_no)) {
                    $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
                }
            } else {
                $rate_data = $rate_data->where('co_no', $user->co_no);
            }

            $rate_meta_data = RateMetaData::where('co_no', $user->co_no)->latest('created_at')->first();

            $rate_data = $rate_data->get();

            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data, 'co_no' => $user->co_no, 'rate_meta_data' => $rate_meta_data], 200);
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

            if ($user->mb_type == 'spasys') {
                $rate_data = $rate_data->where('co_no', $user->co_no);
            } else if ($user->mb_type == 'shop' || $user->mb_type == 'shipper') {
                $rmd = RateMetaData::where('co_no', $user->co_no)->whereNull('set_type')->latest('created_at')->first();
                $rate_data = $rate_data->where('rd_co_no', $user->co_no);
                if (isset($rmd->rmd_no)) {
                    $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
                }
            } else {
                $rate_data = $rate_data->where('co_no', $user->co_no);
            }

            $rate_data = $rate_data->get();

            $rate_meta_data = RateMetaData::where('co_no', $user->co_no)->latest('created_at')->first();

            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data, 'rate_meta_data' => $rate_meta_data], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getRateDataByRgd($rgd_no, $service)
    {
        $user = Auth::user();
        $rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $rgd_no)->first();

        $service_korean_name = $service == 'distribution' ? '유통가공' : ($service == 'fulfillment' ? '수입풀필먼트' : '보세화물');

        try {
            $rate_data = RateData::where('rd_cate_meta1', $service_korean_name);

            if ($user->mb_type == 'spasys') {
                $co_no = ($service == 'distribution' || $service == 'bonded') ? $rgd->warehousing->company->co_parent->co_no : $rgd->warehousing->co_no;
            } else if ($user->mb_type == 'shop') {
                $co_no = $rgd->warehousing->company->co_no;
            } else {
                $co_no = $user->co_no;
            }

            $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
            $rate_data = $rate_data->where('rd_co_no', $co_no);
            if (isset($rmd->rmd_no)) {
                $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
            }else {
                $rate_data = [];
            }

            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data, 'co_no' => $co_no], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getRateDataByConoService($co_no, $service)
    {
        $user = Auth::user();
        try {
            if($service == 'bonded'){
                $service = '보세화물';
            }else if($service == 'fulfillment'){
                $service = '수입풀필먼트';
            }else if($service == 'distribution'){
                $service = '유통가공';
            }
            $rate_data = RateData::where('rd_cate_meta1', $service);


            $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->orderBy('rmd_no', 'DESC')->first();
            $rate_data = $rate_data->where('rd_co_no', $co_no);
            if (isset($rmd->rmd_no)) {
                $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
            }else {
                $rate_data = [];
            }

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

            if (isset($request->rd_cate_meta1) && $request->rd_cate_meta1 == '수입풀필먼트') {
                if (isset($request->rmd_no)) {
                    $rate_data = RateData::where('rd_cate_meta1', '수입풀필먼트')->where('rmd_no', $request->rmd_no);
                } else {
                    $rate_data = RateData::where('rd_cate_meta1', '수입풀필먼트');
                    if ($user->mb_type == 'spasys') {
                        $rate_data = $rate_data->where('co_no', $request->co_no);
                    } else if ($user->mb_type == 'shop') {
                        $rmd = RateMetaData::where('co_no', $request->co_no)->latest('created_at')->first();
                        $rate_data = $rate_data->where('rd_co_no', $request->co_no);
                        if (isset($rmd->rmd_no)) {
                            $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
                        }
                    }

                }
            } else {
                if (isset($request->rmd_no)) {
                    $rate_data = RateData::where('rd_cate_meta1', '유통가공')->where('rmd_no', $request->rmd_no);
                } else {
                    $rate_data = RateData::where('rd_cate_meta1', '유통가공');
                    if ($user->mb_type == 'spasys') {
                        $rate_data = $rate_data->where('co_no', $request->co_no);
                    } else if ($user->mb_type == 'shop') {
                        $rmd = RateMetaData::where('co_no', $request->co_no)->latest('created_at')->first();
                        $rate_data = $rate_data->where('rd_co_no', $request->co_no);
                        if (isset($rmd->rmd_no)) {
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

    public function registe_rate_data_general(Request $request)
    {
        try {
            DB::beginTransaction();
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            $ag = AdjustmentGroup::where('ag_no', $request->rdg_set_type)->first();

            $rdg = RateDataGeneral::updateOrCreate(
                [
                    'rdg_no' => $request->rdg_no,
                    'rdg_bill_type' => $request->bill_type,
                ],
                [
                    'w_no' => $rgd->w_no,
                    'rgd_no' => isset($rgd->rgd_no) ? $rgd->rgd_no : null,
                    'rdg_bill_type' => $request->bill_type,
                    'mb_no' => Auth::user()->mb_no,
                    'rdg_set_type' => isset($ag->ag_name) ? $ag->ag_name : null,
                    'ag_no' => isset($ag->ag_no) ? $ag->ag_no : null,
                    'rdg_supply_price1' => $request->storageData['supply_price'],
                    'rdg_supply_price2' => $request->workData['supply_price'],
                    'rdg_supply_price3' => $request->domesticData['supply_price'],
                    'rdg_supply_price4' => $request->total['supply_price'],
                    'rdg_vat1' => $request->storageData['taxes'],
                    'rdg_vat2' => $request->workData['taxes'],
                    'rdg_vat3' => $request->domesticData['taxes'],
                    'rdg_vat4' => $request->total['taxes'],
                    'rdg_sum1' => $request->storageData['sum'],
                    'rdg_sum2' => $request->workData['sum'],
                    'rdg_sum3' => $request->domesticData['sum'],
                    'rdg_sum4' => $request->total['sum'],
                    'rdg_etc1' => $request->storageData['etc'],
                    'rdg_etc2' => $request->workData['etc'],
                    'rdg_etc3' => $request->domesticData['etc'],
                    'rdg_etc4' => $request->total['etc'],
                ]
            );

            ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                'rgd_status4' => '예상경비청구서',
                'rgd_issue_date' => Carbon::now()->toDateTimeString(),
                'rgd_bill_type' => $request->bill_type,
            ]);

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rate_data_general($rgd_no, $bill_type)
    {
        try {
            DB::beginTransaction();
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();

            $w_no = $rgd->w_no;
            $user = Auth::user();
            $warehousing = Warehousing::with(['w_ew_many' => function ($q) {

                $q->withCount([
                    'warehousing_item as bonusQuantity' => function ($query) {

                        $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                    },
                ]);

            }, 'w_ew', 'co_no', 'w_import_parent', 'member', 'warehousing_request', 'warehousing_item'])->where('w_no', $w_no)->first();

            $rdg = RateDataGeneral::where('w_no', $w_no)->where('rgd_no', $rgd_no)->where('rdg_bill_type', $bill_type)->first();

            if (empty($rdg)) {
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', $bill_type)->first();
            }
            if ($user->mb_type == 'spasys') {
                $co_no = Company::with(['co_parent'])->where('co_no', $warehousing->co_no)->first();
                $co_no = $co_no->co_parent->co_no;
            } else if ($user->mb_type == 'shop') {
                $co_no = $warehousing->co_no;
            }else {
                $co_no = $warehousing->co_no;
            }

            $ag_name = AdjustmentGroup::where('co_no', $co_no)->get();

            DB::commit();

            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
                'rgd' => $rgd,
                'warehousing' => $warehousing,
                'ag_name' => $ag_name,
                'co_no' => $co_no,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rate_data_general_final($rgd_no)
    {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::with(['warehousing'])->where('rgd_no_final', $rgd_no)->where('rdg_bill_type', 'additional')->first();

            if (!isset($rdg->rdg_no)) {
                $rdg = RateDataGeneral::with(['warehousing'])->where('rgd_no_expectation', $rgd_no)->where('rdg_bill_type', 'final')->first();
            }

            if (!isset($rdg->rdg_no)) {
                $rdg = RateDataGeneral::with(['warehousing'])->where('rgd_no', $rgd_no)->where('rdg_bill_type', 'expectation')->first();
            }

            if (!isset($rdg->rdg_no)) {
                $rdg = RateDataGeneral::with(['warehousing'])->where('rgd_no', $rgd_no)->where('rdg_bill_type', 'expectation_monthly')->first();
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rate_data_general_final2($rgd_no)
    {
        try {
            DB::beginTransaction();
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();

            $rdg = RateDataGeneral::with(['warehousing'])->where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final')->first();
            if (empty($rdg)) {
                $rdg = RateDataGeneral::with(['warehousing'])->where('rgd_no', $rgd_no)->where(function($q){
                    $q->where('rdg_bill_type', 'final_spasys')
                    ->orWhere('rdg_bill_type', 'final_shop');
                })->first();
            }
            if (empty($rdg)) {
                $rdg = RateDataGeneral::with(['warehousing'])->where('rgd_no_expectation', $rgd->rgd_parent_no)->where(function($q){
                    $q->where('rdg_bill_type', 'final_spasys')
                    ->orWhere('rdg_bill_type', 'final_shop');
                })->first();
            }
            if (empty($rdg)) {
                $rdg = RateDataGeneral::with(['warehousing'])->where('rgd_no', $rgd_no)->where('rdg_bill_type', 'additional')->first();
            }


            $user = Auth::user();

            $contract = Contract::where('co_no',  $user->co_no)->first();
            if(isset($contract->c_calculate_deadline_yn)){
                $rgd['c_calculate_deadline_yn'] = $contract->c_calculate_deadline_yn;
            }else {
                $rgd['c_calculate_deadline_yn'] = 'n';
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
                'rgd' => $rgd,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function registe_rate_data_general_final(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();

            //Check is there already RateDataGeneral with rdg_no and bill_type from request yet
            $is_exist = RateDataGeneral::where('rgd_no', $request->rgd_no)->where('rdg_bill_type', $request->bill_type)->first();

            //Get RecevingGoodsDelivery base on rgd_no
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();

            //Get adjustmentgroup with rdg_set_type request
            $ag = AdjustmentGroup::where('ag_no', $request->rdg_set_type)->first();

            $rdg = RateDataGeneral::updateOrCreate(
                [
                    'rdg_no' => isset($is_exist->rdg_no) ? $request->rdg_no : null,
                    'rdg_bill_type' => $request->bill_type,
                ],
                [
                    'w_no' => $rgd->w_no,
                    'rdg_bill_type' => isset($request->bill_type) ? $request->bill_type : '',
                    'rgd_no_expectation' => $request->type == 'edit_final' ? $is_exist->rgd_no_expectation : (str_contains($request->bill_type, 'final') ? $request->rgd_no : null),
                    'rgd_no_final' => $request->type == 'edit_additional' ? $is_exist->rgd_no_final : (str_contains($request->bill_type, 'additional') ? $request->rgd_no : null),
                    'mb_no' => Auth::user()->mb_no,
                    'rdg_set_type' => isset($ag->ag_name) ? $ag->ag_name : null,
                    'ag_no' => isset($ag->ag_no) ? $ag->ag_no : null,
                    'rdg_supply_price1' => isset($request->storageData['supply_price']) ? $request->storageData['supply_price'] : 0,
                    'rdg_supply_price2' => isset($request->workData['supply_price']) ? $request->workData['supply_price'] : 0,
                    'rdg_supply_price3' => isset($request->domesticData['supply_price']) ? $request->domesticData['supply_price'] : 0,
                    'rdg_supply_price4' => isset($request->total['supply_price']) ? $request->total['supply_price'] : 0,
                    'rdg_vat1' => isset($request->storageData['taxes']) ? $request->storageData['taxes'] : 0,
                    'rdg_vat2' => isset($request->workData['taxes']) ? $request->workData['taxes'] : 0,
                    'rdg_vat3' => isset($request->domesticData['taxes']) ? $request->domesticData['taxes'] : 0,
                    'rdg_vat4' => isset($request->total['taxes']) ? $request->total['taxes'] : 0,
                    'rdg_sum1' => isset($request->storageData['sum']) ? $request->storageData['sum'] : 0,
                    'rdg_sum2' => isset($request->workData['sum']) ? $request->workData['sum'] : 0,
                    'rdg_sum3' => isset($request->domesticData['sum']) ? $request->domesticData['sum'] : 0,
                    'rdg_sum4' => isset($request->total['sum']) ? $request->total['sum'] : 0,
                    'rdg_etc1' => isset($request->storageData['etc']) ? $request->storageData['etc'] : 0,
                    'rdg_etc2' => isset($request->workData['etc']) ? $request->workData['etc'] : 0,
                    'rdg_etc3' => isset($request->domesticData['etc']) ? $request->domesticData['etc'] : 0,
                    'rdg_etc4' => isset($request->total['etc']) ? $request->total['etc'] : 0,
                    'rdg_count_work' => isset($request->workData['count_work']) ? $request->workData['count_work'] : 0,
                ]
            );

            if (!isset($is_exist->rdg_no) && isset($request->previous_bill_type)) {
                if ($rgd->rgd_bill_type == null) {
                    $rgd->rgd_status4 = $user->mb_type == 'shop' ? 'issued' : $rgd->rgd_status4;
                    $rgd->rgd_status5 = $user->mb_type == 'spasys' ? 'issued' : $rgd->rgd_status5;
                    $rgd->save();
                }else if ($rgd->rgd_bill_type != 'expectation_monthly') {
                    $rgd->rgd_status5 = 'issued';
                    $rgd->save();
                }


                $final_rgd = $rgd->replicate();
                $final_rgd->rgd_bill_type = $request->bill_type; // the new project_id
                $final_rgd->rgd_status4 = $request->status;
                $final_rgd->rgd_issue_date = Carbon::now()->toDateTimeString();
                $final_rgd->rgd_is_show = $request->bill_type == 'final_monthly' ? 'n' : 'y';
                $final_rgd->rgd_parent_no = $rgd->rgd_no;
                $final_rgd->rgd_status5 = null;
                $final_rgd->rgd_status6 = null;
                $final_rgd->rgd_status7 = null;
                $final_rgd->rgd_confirmed_date = null;
                $final_rgd->rgd_paid_date = null;
                $final_rgd->rgd_tax_invoice_date = null;
                $final_rgd->rgd_tax_invoice_number = null;
                $final_rgd->rgd_calculate_deadline_yn = $request->rgd_calculate_deadline_yn ? $request->rgd_calculate_deadline_yn : $rgd->rgd_calculate_deadline_yn;
                $final_rgd->rgd_settlement_number = $request->settlement_number ? $request->settlement_number : $rgd->rgd_settlement_number;
                $final_rgd->mb_no = Auth::user()->mb_no;
                $final_rgd->save();

                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $final_rgd->rgd_no,
                ]);

                if ($request->bill_type == 'final') {
                    $settlement_number = explode('_', $final_rgd->rgd_settlement_number);
                    $settlement_number[2] = str_replace("C", "CF", $settlement_number[2]);
                    $final_rgd->rgd_settlement_number = implode("_", $settlement_number);
                    $final_rgd->save();
                } else if ($request->bill_type == 'final_monthly') {
                    $settlement_number = explode('_', $final_rgd->rgd_settlement_number);
                    $settlement_number[2] = str_replace("M", "MF", $settlement_number[2]);
                    $final_rgd->rgd_settlement_number = implode("_", $settlement_number);
                    $final_rgd->save();
                } else if ($request->bill_type == 'additional_monthly') {
                    $settlement_number = explode('_', $final_rgd->rgd_settlement_number);
                    $settlement_number[2] = str_replace("MF", "MA", $settlement_number[2]);
                    $final_rgd->rgd_settlement_number = implode("_", $settlement_number);
                    $final_rgd->save();
                }

            } else {
                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $is_exist ? $is_exist->rgd_no : $rgd->rgd_no,
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

    public function registe_rate_data_general_additional(Request $request)
    {
        try {
            DB::beginTransaction();
            $is_new = RateDataGeneral::where('rdg_no', $request->rdg_no)->where('rdg_bill_type', 'additional')->first();

            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            $w_no = $rgd->w_no;
            $ag = AdjustmentGroup::where('ag_no', $request->rdg_set_type)->first();

            $rdg = RateDataGeneral::updateOrCreate(
                [
                    'rdg_no' => !isset($is_new->rdg_no) ? null : $request->rdg_no,
                    'rdg_bill_type' => 'additional',
                ],
                [
                    'w_no' => $w_no,
                    'rdg_bill_type' => 'additional',
                    'rgd_no_final' => $request->rgd_no,
                    'mb_no' => Auth::user()->mb_no,
                    'rdg_set_type' => isset($ag->ag_name) ? $ag->ag_name : null,
                    'ag_no' => isset($ag->ag_no) ? $ag->ag_no : null,
                    'rdg_supply_price1' => $request->storageData['supply_price'],
                    'rdg_supply_price2' => $request->workData['supply_price'],
                    'rdg_supply_price3' => $request->domesticData['supply_price'],
                    'rdg_supply_price4' => $request->total['supply_price'],
                    'rdg_vat1' => $request->storageData['taxes'],
                    'rdg_vat2' => $request->workData['taxes'],
                    'rdg_vat3' => $request->domesticData['taxes'],
                    'rdg_vat4' => $request->total['taxes'],
                    'rdg_sum1' => $request->storageData['sum'],
                    'rdg_sum2' => $request->workData['sum'],
                    'rdg_sum3' => $request->domesticData['sum'],
                    'rdg_sum4' => $request->total['sum'],
                    'rdg_etc1' => $request->storageData['etc'],
                    'rdg_etc2' => $request->workData['etc'],
                    'rdg_etc3' => $request->domesticData['etc'],
                    'rdg_etc4' => $request->total['etc'],
                ]
            );

            if (!isset($is_new->rdg_no)) {
                $rgd->rgd_status5 = 'issued';
                $rgd->save();

                $final_rgd = $rgd->replicate();
                $final_rgd->rgd_bill_type = 'additional'; // the new project_id
                $final_rgd->rgd_status4 = $request->status;
                $final_rgd->rgd_issue_date = Carbon::now()->toDateTimeString();
                $final_rgd->rgd_status5 = null;
                $final_rgd->rgd_status6 = null;
                $final_rgd->rgd_status7 = null;
                $final_rgd->rgd_confirmed_date = null;
                $final_rgd->rgd_paid_date = null;
                $final_rgd->rgd_tax_invoice_date = null;
                $final_rgd->rgd_tax_invoice_number = null;
                $final_rgd->rgd_parent_no = $rgd->rgd_no;
                $final_rgd->save();

                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $final_rgd->rgd_no,
                ]);

                $settlement_number = explode('_', $final_rgd->rgd_settlement_number);
                $settlement_number[2] = str_replace("CF", "CA", $settlement_number[2]);
                $final_rgd->rgd_settlement_number = implode("_", $settlement_number);
                $final_rgd->save();

            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function registe_rate_data_general_additional2(Request $request)
    {
        try {
            DB::beginTransaction();
            $is_new = RateDataGeneral::where('rdg_no', $request->rdg_no)->where('rdg_bill_type', 'additional')->first();

            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            $w_no = $rgd->w_no;
            $ag = AdjustmentGroup::where('ag_no', $request->rdg_set_type)->first();

            $rdg = RateDataGeneral::updateOrCreate(
                [
                    'rdg_no' => !isset($is_new->rdg_no) ? null : $request->rdg_no,
                    'rdg_bill_type' => 'additional',
                ],
                [
                    'w_no' => $w_no,
                    'rdg_bill_type' => 'additional',
                    'mb_no' => Auth::user()->mb_no,
                    'rdg_set_type' => isset($ag->ag_name) ? $ag->ag_name : null,
                    'ag_no' => isset($ag->ag_no) ? $ag->ag_no : null,
                    'rdg_supply_price1' => $request->storageData['supply_price'],
                    'rdg_supply_price2' => $request->workData['supply_price'],
                    'rdg_supply_price3' => $request->domesticData['supply_price'],
                    'rdg_supply_price4' => $request->total['supply_price'],
                    'rdg_vat1' => $request->storageData['taxes'],
                    'rdg_vat2' => $request->workData['taxes'],
                    'rdg_vat3' => $request->domesticData['taxes'],
                    'rdg_vat4' => $request->total['taxes'],
                    'rdg_sum1' => $request->storageData['sum'],
                    'rdg_sum2' => $request->workData['sum'],
                    'rdg_sum3' => $request->domesticData['sum'],
                    'rdg_sum4' => $request->total['sum'],
                    'rdg_etc1' => $request->storageData['etc'],
                    'rdg_etc2' => $request->workData['etc'],
                    'rdg_etc3' => $request->domesticData['etc'],
                    'rdg_etc4' => $request->total['etc'],
                ]
            );

            $expectation_rgd = ReceivingGoodsDelivery::where('w_no', $w_no)->where('rgd_bill_type', 'final')->first();

            if (!isset($is_new->rdg_no)) {
                $expectation_rgd->rgd_status5 = 'issued';
                $expectation_rgd->save();

                $final_rgd = $expectation_rgd->replicate();
                $final_rgd->rgd_bill_type = 'additional'; // the new project_id
                $final_rgd->rgd_status4 = '확정청구서';
                $final_rgd->rgd_issue_date = Carbon::now()->toDateTimeString();
                $final_rgd->rgd_status5 = null;
                $final_rgd->rgd_status6 = null;
                $final_rgd->rgd_status7 = null;
                $final_rgd->rgd_confirmed_date = null;
                $final_rgd->rgd_paid_date = null;
                $final_rgd->rgd_tax_invoice_date = null;
                $final_rgd->rgd_tax_invoice_number = null;
                $final_rgd->save();

                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $final_rgd->rgd_no,
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

    public function get_rate_data_general_additional($rgd_no)
    {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final')->first();

            if (!isset($rdg->rdg_no)) {
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final')->first();
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
    public function get_rate_data_general_additional2($rgd_no)
    {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'additional')->first();

            if (!isset($rdg->rdg_no)) {
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final')->first();
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rate_data_general_additional3($rgd_no)
    {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::where('rgd_no_final', $rgd_no)->where('rdg_bill_type', 'additional')->first();

            // if(!isset($rdg->rdg_no)){
            //     $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final')->first();
            // }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function monthly_bill_list($rgd_no, $bill_type)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $rgd = ReceivingGoodsDelivery::with(['warehousing', 'rate_data_general'])->where('rgd_no', $rgd_no)->first();

            if($user->mb_type == 'spasys'){
                $co_no = $rgd->warehousing->company->co_parent->co_no;
            }else {
                $co_no = $rgd->warehousing->co_no;
            }

            $adjustmentgroupall = AdjustmentGroup::where('co_no', $co_no)->get();
            $created_at = Carbon::createFromFormat('Y.m.d H:i:s', $rgd->created_at->format('Y.m.d H:i:s'));

            $start_date = $created_at->startOfMonth()->toDateString();
            $end_date = $created_at->endOfMonth()->toDateString();

            $rgds = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general', 'rgd_child', 'rate_meta_data', 'rate_meta_data_parent'])
                ->whereHas('w_no', function ($q) use ($rgd) {
                    $q->where('co_no', $rgd->warehousing->co_no)
                        ->where('w_category_name', '유통가공');
                })->whereHas('mb_no', function ($q) {
                if (Auth::user()->mb_type == 'spasys') {
                    $q->where('mb_type', 'spasys');
                } else if (Auth::user()->mb_type == 'shop') {
                    $q->where('mb_type', 'shop');
                }

            })
            // ->doesntHave('rgd_child')
                ->whereHas('rate_data_general', function($q) use($rgd){
                    $q->where('ag_no', $rgd->rate_data_general->ag_no);
                })
                ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($start_date)))
                ->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($end_date)))
                ->where('rgd_status1', '=', '입고')

                ->where('rgd_bill_type', $bill_type)
                ->where(function ($q) {
                    $q->where('rgd_status5', '!=', 'cancel')
                        ->orWhereNull('rgd_status5');
                })
                ->where(function ($q) {
                    $q->where('rgd_status5', '!=', 'issued')
                        ->orWhereNull('rgd_status5');
                })
                ->whereDoesntHave('rgd_child')
                ->get();

            $rdgs = [];
            foreach ($rgds as $rgd) {
                $rdg = RateDataGeneral::where('rgd_no_expectation', $rgd->rgd_no)
                    ->where('rdg_bill_type', 'final_monthly')->first();
                $rdgs[] = $rdg;
            }

            $rdgs2 = [];

            foreach ($rgds as $rgd2) {
                $rdg2 = RateDataGeneral::where('rgd_no', $rgd2->rgd_no)
                    ->where('rdg_bill_type', 'expectation_monthly')->first();
                $rdgs2[] = $rdg2;
            }

            $adjustment_group_choose = [];

            if (!empty($rgds) && isset($rdgs[0])) {
                if ($rdgs[0] != null) {
                    $adjustment_group_choose = AdjustmentGroup::where('co_no', '=', $co_no)->where('ag_name', '=', $rdgs[0]->rdg_set_type)->first();
                } else if ($rdgs2[0] != null) {
                    $adjustment_group_choose = AdjustmentGroup::where('co_no', '=', $co_no)->where('ag_name', '=', $rdgs2[0]->rdg_set_type)->first();
                }
            }

            return response()->json([
                'rgds' => $rgds,
                'rdgs' => $rdgs,
                'adjustmentgroupall' => $adjustmentgroupall,
                'adjustment_group_choose' => $adjustment_group_choose,
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
                'rdg' => $rdg,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
    public function monthly_bill_list_edit($rgd_no, $bill_type)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $rgd_no)->first();
            $co_no = $rgd->warehousing->co_no;
            $adjustmentgroupall = AdjustmentGroup::where('co_no', $co_no)->get();
            $created_at = Carbon::createFromFormat('Y.m.d H:i:s', $rgd->created_at->format('Y.m.d H:i:s'));

            $start_date = $created_at->startOfMonth()->toDateString();
            $end_date = $created_at->endOfMonth()->toDateString();

            $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general', 'rgd_child', 'rate_meta_data', 'rate_meta_data_parent'])
                ->whereHas('w_no', function ($q) use ($co_no) {
                    $q->where('co_no', $co_no)
                        ->where('w_category_name', '유통가공');
                })
            // ->doesntHave('rgd_child')
                ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($start_date)))
                ->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($end_date)))
                ->where('rgd_status1', '=', '입고')
                ->where('rgd_settlement_number', $rgd->rgd_settlement_number)
                ->get();

            $rdgs = [];
            foreach ($rgds as $index => $rgd) {
                $child_rgd = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general', 'rgd_child', 'rate_meta_data', 'rate_meta_data_parent'])->where('rgd_no', $rgd['rgd_parent_no'])->first();
                $rgds[$index] = $child_rgd;
                $rdg = RateDataGeneral::where('rgd_no_expectation', $rgd->rgd_no)
                    ->where('rdg_bill_type', 'final_monthly')->first();
                $rdgs[] = $rdg;
            }

            $rdgs2 = [];

            foreach ($rgds as $rgd2) {
                $rdg2 = RateDataGeneral::where('rgd_no', $rgd2->rgd_no)
                    ->where('rdg_bill_type', 'expectation_monthly')->first();
                $rdgs2[] = $rdg2;

                $rmd = RateMetaData::where('rgd_no', $rgd2['rgd_parent_no'])->where('set_type', 'work_monthly')->first();
                if(isset($rmd->rmd_no)){
                    $work_sum = RateData::where('rmd_no', $rmd->rmd_no)->sum('rd_data4');
                    $rgd2['work_sum'] = $work_sum;
                }else {
                    $rgd2['work_sum'] = 0;
                }
            }


            $adjustment_group_choose = [];

            if ($rgds->count() != 0) {
                if ($rdgs[0] != null) {
                    $adjustment_group_choose = AdjustmentGroup::where('co_no', '=', $co_no)->where('ag_name', '=', $rdgs[0]->rdg_set_type)->first();
                } else if ($rdgs2[0] != null) {
                    $adjustment_group_choose = AdjustmentGroup::where('co_no', '=', $co_no)->where('ag_name', '=', $rdgs2[0]->rdg_set_type)->first();
                }
            }

            return response()->json([
                'rgds' => $rgds,
                'rdgs' => $rdgs,
                'adjustmentgroupall' => $adjustmentgroupall,
                'adjustment_group_choose' => $adjustment_group_choose,
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
                'rdg' => $rdg,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function bonded_monthly_bill_list($rgd_no, $bill_type)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $rgd = ReceivingGoodsDelivery::with(['warehousing', 'rate_data_general'])->where('rgd_no', $rgd_no)->first();
            $co_no = $rgd->warehousing->co_no;
            $adjustmentgroupall = AdjustmentGroup::where('co_no', $co_no)->get();
            $created_at = Carbon::createFromFormat('Y.m.d H:i:s', $rgd->created_at->format('Y.m.d H:i:s'));

            $start_date = $created_at->startOfMonth()->toDateString();
            $end_date = $created_at->endOfMonth()->toDateString();

            $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general', 'rgd_child', 'rate_meta_data', 'rate_meta_data_parent'])
                ->whereHas('w_no', function ($q) use ($co_no) {
                    $q->where('co_no', $co_no)
                        ->where('w_category_name', '보세화물');
                })
                ->whereHas('rate_data_general', function($q) use($rgd){
                    $q->where('ag_no', $rgd->rate_data_general->ag_no);
                })
                ->where('rgd_status1', '=', '입고')
                ->where('rgd_bill_type', $bill_type)
                ->where(function ($q) {
                    $q->where('rgd_status5', '!=', 'cancel')
                        ->orWhereNull('rgd_status5');
                })
                ->where(function ($q) {
                    $q->where('rgd_status5', '!=', 'issued')
                        ->orWhereNull('rgd_status5');
                })
                ->whereDoesntHave('rgd_child')
                ->get();

            $rdgs = [];
            foreach ($rgds as $rgd) {
                $rdg = RateDataGeneral::where('rgd_no_expectation', $rgd->rgd_no)
                    ->where('rdg_bill_type', 'final_monthly')->first();
                $rdgs[] = $rdg;
            }

            $rdgs2 = [];

            foreach ($rgds as $rgd2) {
                $rdg2 = RateDataGeneral::where('rgd_no', $rgd2->rgd_no)
                    ->where('rdg_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();
                $rdgs2[] = $rdg2;
            }
            $time = str_replace('-', '.', $start_date) . ' ~ ' . str_replace('-', '.', $end_date);

            DB::commit();

            return response()->json([
                'rgds' => $rgds,
                'rdgs' => $rdgs,
                'time' => $time,
                'adjustmentgroupall' => $adjustmentgroupall,
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
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function bonded_monthly_bill_list_edit($rgd_no, $bill_type)
    {
        try {
            DB::beginTransaction();
            $rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $rgd_no)->first();
            $co_no = $rgd->warehousing->co_no;
            $adjustmentgroupall = AdjustmentGroup::where('co_no', $co_no)->get();
            $created_at = Carbon::createFromFormat('Y.m.d H:i:s', $rgd->created_at->format('Y.m.d H:i:s'));

            $start_date = $created_at->startOfMonth()->toDateString();
            $end_date = $created_at->endOfMonth()->toDateString();

            $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general', 'rgd_child', 'rate_meta_data', 'rate_meta_data_parent', 't_export'])
                ->whereHas('w_no', function ($q) use ($co_no) {
                    $q->where('co_no', $co_no)
                        ->where('w_category_name', '보세화물');
                })
            // ->doesntHave('rgd_child')
                ->where('rgd_settlement_number', $rgd->rgd_settlement_number)
            // ->whereDoesntHave('rgd_child')
                ->get();

            $rdgs = [];
            foreach ($rgds as $index => $rgd) {
                $child_rgd = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general', 'rgd_child', 'rate_meta_data', 'rate_meta_data_parent', 't_export'])->where('rgd_no', $rgd['rgd_parent_no'])->first();
                $rgds[$index] = $child_rgd;

                $rdg = RateDataGeneral::where('rgd_no_expectation', $rgd->rgd_no)
                    ->where('rdg_bill_type', 'final_monthly')->first();
                $rdgs[] = $rdg;
            }

            $rdgs2 = [];

            foreach ($rgds as $rgd2) {
                $rdg2 = RateDataGeneral::where('rgd_no', $rgd2->rgd_no)
                    ->where('rdg_bill_type', 'expectation_monthly')->first();
                $rdgs2[] = $rdg2;
            }

            $time = str_replace('-', '.', $start_date) . ' ~ ' . str_replace('-', '.', $end_date);

            DB::commit();

            return response()->json([
                'rgds' => $rgds,
                'rdgs' => $rdgs,
                'time' => $time,
                'adjustmentgroupall' => $adjustmentgroupall,
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
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function registe_rate_data_general_monthly_final(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            if ($request->is_edit == 'edit') {
                $i = 0;
                foreach ($request->rgds as $key => $rgd) {
                    $is_exist = RateDataGeneral::where('rgd_no', $rgd['rgd_no'])->where('rdg_bill_type', 'final_monthly')->first();
                }
            } else {
                $i = 0;
                $final_rgds = [];

                $rdg_supply_price1 = 0;
                $rdg_supply_price2 = 0;
                $rdg_supply_price3 = 0;
                $rdg_supply_price4 = 0;
                $rdg_supply_price5 = 0;
                $rdg_supply_price6 = 0;
                $rdg_supply_price7 = 0;

                $rdg_vat1 = 0;
                $rdg_vat2 = 0;
                $rdg_vat3 = 0;
                $rdg_vat4 = 0;
                $rdg_vat5 = 0;
                $rdg_vat6 = 0;
                $rdg_vat7 = 0;

                $rdg_sum1 = 0;
                $rdg_sum2 = 0;
                $rdg_sum3 = 0;
                $rdg_sum4 = 0;
                $rdg_sum5 = 0;
                $rdg_sum6 = 0;
                $rdg_sum7 = 0;

                foreach ($request->rgds as $key => $rgd) {
                    $is_exist = RateDataGeneral::where('rgd_no', $rgd['rgd_no'])->where('rdg_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();
                    $rdg_supply_price1 = $rdg_supply_price1 + $is_exist['rdg_supply_price1'];
                    $rdg_supply_price2 = $rdg_supply_price2 + $is_exist['rdg_supply_price2'];
                    $rdg_supply_price3 = $rdg_supply_price3 + $is_exist['rdg_supply_price3'];
                    $rdg_supply_price4 = $rdg_supply_price4 + $is_exist['rdg_supply_price4'];
                    $rdg_supply_price5 = $rdg_supply_price5 + $is_exist['rdg_supply_price5'];
                    $rdg_supply_price6 = $rdg_supply_price6 + $is_exist['rdg_supply_price6'];
                    $rdg_supply_price7 = $rdg_supply_price7 + $is_exist['rdg_supply_price7'];

                    $rdg_vat1 = $rdg_vat1 + $is_exist['rdg_vat1'];
                    $rdg_vat2 = $rdg_vat2 + $is_exist['rdg_vat2'];
                    $rdg_vat3 = $rdg_vat3 + $is_exist['rdg_vat3'];
                    $rdg_vat4 = $rdg_vat4 + $is_exist['rdg_vat4'];
                    $rdg_vat5 = $rdg_vat5 + $is_exist['rdg_vat5'];
                    $rdg_vat6 = $rdg_vat6 + $is_exist['rdg_vat6'];
                    $rdg_vat7 = $rdg_vat7 + $is_exist['rdg_vat7'];

                    $rdg_sum1 = $rdg_sum1 + $is_exist['rdg_sum1'];
                    $rdg_sum2 = $rdg_sum2 + $is_exist['rdg_sum2'];
                    $rdg_sum3 = $rdg_sum3 + $is_exist['rdg_sum3'];
                    $rdg_sum4 = $rdg_sum4 + $is_exist['rdg_sum4'];
                    $rdg_sum5 = $rdg_sum5 + $is_exist['rdg_sum5'];
                    $rdg_sum6 = $rdg_sum6 + $is_exist['rdg_sum6'];
                    $rdg_sum7 = $rdg_sum7 + $is_exist['rdg_sum7'];
                }

                foreach ($request->rgds as $key => $rgd) {
                    $is_exist = RateDataGeneral::where('rgd_no_expectation', $rgd['rgd_no'])->where('rdg_bill_type', 'final_monthly')->first();

                    if (!$is_exist) {
                        $is_exist = RateDataGeneral::where('rgd_no', $rgd['rgd_no'])->where('rdg_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();

                        $final_rdg = $is_exist->replicate();
                        $final_rdg->rdg_bill_type = $request->bill_type; // the new project_id

                        $final_rdg->rdg_supply_price1 = $rdg_supply_price1;
                        $final_rdg->rdg_supply_price2 = $rdg_supply_price2;
                        $final_rdg->rdg_supply_price3 = $rdg_supply_price3;
                        $final_rdg->rdg_supply_price4 = $rdg_supply_price4;
                        $final_rdg->rdg_supply_price5 = $rdg_supply_price5;
                        $final_rdg->rdg_supply_price6 = $rdg_supply_price6;
                        $final_rdg->rdg_supply_price7 = $rdg_supply_price7;

                        $final_rdg->rdg_vat1 = $rdg_vat1;
                        $final_rdg->rdg_vat2 = $rdg_vat2;
                        $final_rdg->rdg_vat3 = $rdg_vat3;
                        $final_rdg->rdg_vat4 = $rdg_vat4;
                        $final_rdg->rdg_vat5 = $rdg_vat5;
                        $final_rdg->rdg_vat6 = $rdg_vat6;
                        $final_rdg->rdg_vat7 = $rdg_vat7;

                        $final_rdg->rdg_sum1 = $rdg_sum1;
                        $final_rdg->rdg_sum2 = $rdg_sum2;
                        $final_rdg->rdg_sum3 = $rdg_sum3;
                        $final_rdg->rdg_sum4 = $rdg_sum4;
                        $final_rdg->rdg_sum5 = $rdg_sum5;
                        $final_rdg->rdg_sum6 = $rdg_sum6;
                        $final_rdg->rdg_sum7 = $rdg_sum7;

                        $final_rdg->save();
                    } else {
                        $final_rgd = ReceivingGoodsDelivery::where('rgd_no', $is_exist['rgd_no'])->first();
                        if($final_rgd->rgd_status5 == 'cancel') {
                            $is_exist = RateDataGeneral::where('rgd_no', $rgd['rgd_no'])->where('rdg_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();

                            $final_rdg = $is_exist->replicate();
                            $final_rdg->rdg_bill_type = $request->bill_type; // the new project_id

                            $final_rdg->rdg_supply_price1 = $rdg_supply_price1;
                            $final_rdg->rdg_supply_price2 = $rdg_supply_price2;
                            $final_rdg->rdg_supply_price3 = $rdg_supply_price3;
                            $final_rdg->rdg_supply_price4 = $rdg_supply_price4;
                            $final_rdg->rdg_supply_price5 = $rdg_supply_price5;
                            $final_rdg->rdg_supply_price6 = $rdg_supply_price6;
                            $final_rdg->rdg_supply_price7 = $rdg_supply_price7;

                            $final_rdg->rdg_vat1 = $rdg_vat1;
                            $final_rdg->rdg_vat2 = $rdg_vat2;
                            $final_rdg->rdg_vat3 = $rdg_vat3;
                            $final_rdg->rdg_vat4 = $rdg_vat4;
                            $final_rdg->rdg_vat5 = $rdg_vat5;
                            $final_rdg->rdg_vat6 = $rdg_vat6;
                            $final_rdg->rdg_vat7 = $rdg_vat7;

                            $final_rdg->rdg_sum1 = $rdg_sum1;
                            $final_rdg->rdg_sum2 = $rdg_sum2;
                            $final_rdg->rdg_sum3 = $rdg_sum3;
                            $final_rdg->rdg_sum4 = $rdg_sum4;
                            $final_rdg->rdg_sum5 = $rdg_sum5;
                            $final_rdg->rdg_sum6 = $rdg_sum6;
                            $final_rdg->rdg_sum7 = $rdg_sum7;

                            $final_rdg->save();
                        }else
                            $final_rdg = $is_exist;
                    }

                    $expectation_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->where('rgd_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();
                    $final_rgd = ReceivingGoodsDelivery::where('rgd_parent_no', $rgd['rgd_no'])->where('rgd_bill_type', 'final_monthly')->where('rgd_status5', '!=', 'cancel')->first();

                    if (!$final_rgd) {
                        $expectation_rgd->rgd_status5 = 'issued';
                        $expectation_rgd->save();

                        $final_rgd = $expectation_rgd->replicate();
                        $final_rgd->rgd_bill_type = $request->bill_type; // the new project_id
                        $final_rgd->rgd_status4 = '확정청구서';
                        $final_rgd->rgd_issue_date = Carbon::now()->toDateTimeString();
                        $final_rgd->rgd_status5 = null;
                        $final_rgd->rgd_status6 = null;
                        $final_rgd->rgd_status7 = null;
                        $final_rgd->rgd_is_show = ($i == 0 ? 'y' : 'n');
                        $final_rgd->rgd_parent_no = $expectation_rgd->rgd_no;
                        $final_rgd->rgd_settlement_number = $request->settlement_number;
                        $final_rgd->save();

                        $ag = AdjustmentGroup::where('ag_no', $request->rdg_set_type)->first();

                        RateDataGeneral::where('rdg_no', $final_rdg->rdg_no)->update([
                            'rgd_no' => $final_rgd->rgd_no,
                            'rgd_no_expectation' => $expectation_rgd->rgd_no,
                            'rdg_set_type' => isset($ag->ag_name) ? $ag->ag_name : null,
                            'ag_no' => isset($ag->ag_no) ? $ag->ag_no : null,
                        ]);
                        RateMetaData::where('rgd_no', $request->rgd_no)->where(function ($q) {
                            $q->where('set_type', 'storage_monthly_final')
                                ->orWhere('set_type', 'work_monthly_final');
                        })->update([
                            'rgd_no' => $final_rgd->rgd_no,
                        ]);

                    } else {
                        $expectation_rgd->rgd_status5 = 'issued';
                        $expectation_rgd->save();
                        if ($i == 0) {
                            $final_rgd->rgd_is_show = 'y';
                            $final_rgd->rgd_settlement_number = $request->settlement_number;
                            $final_rgd->save();
                        }

                        RateDataGeneral::where('rdg_no', $final_rdg->rdg_no)->update([
                            'rgd_no' => $final_rgd->rgd_no,
                            'rgd_no_expectation' => $expectation_rgd->rgd_no,
                        ]);
                    }
                    $i++;
                }
            }

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

    public function registe_rate_data_general_monthly_final_bonded(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            if ($request->is_edit == 'edit') {
                $i = 0;
                foreach ($request->rgds as $key => $rgd) {
                    $is_exist = RateDataGeneral::where('rgd_no', $rgd['rgd_no'])->where('rdg_bill_type', 'final_monthly')->first();
                }
            } else {
                $i = 0;
                $final_rgds = [];

                $rdg_supply_price1 = 0;
                $rdg_supply_price2 = 0;
                $rdg_supply_price3 = 0;
                $rdg_supply_price4 = 0;
                $rdg_supply_price5 = 0;
                $rdg_supply_price6 = 0;
                $rdg_supply_price7 = 0;

                $rdg_vat1 = 0;
                $rdg_vat2 = 0;
                $rdg_vat3 = 0;
                $rdg_vat4 = 0;
                $rdg_vat5 = 0;
                $rdg_vat6 = 0;
                $rdg_vat7 = 0;

                $rdg_sum1 = 0;
                $rdg_sum2 = 0;
                $rdg_sum3 = 0;
                $rdg_sum4 = 0;
                $rdg_sum5 = 0;
                $rdg_sum6 = 0;
                $rdg_sum7 = 0;

                foreach ($request->rgds as $key => $rgd) {
                    $is_exist = RateDataGeneral::where('rgd_no', $rgd['rgd_no'])->where('rdg_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();
                    $rdg_supply_price1 = $rdg_supply_price1 + $is_exist['rdg_supply_price1'];
                    $rdg_supply_price2 = $rdg_supply_price2 + $is_exist['rdg_supply_price2'];
                    $rdg_supply_price3 = $rdg_supply_price3 + $is_exist['rdg_supply_price3'];
                    $rdg_supply_price4 = $rdg_supply_price4 + $is_exist['rdg_supply_price4'];
                    $rdg_supply_price5 = $rdg_supply_price5 + $is_exist['rdg_supply_price5'];
                    $rdg_supply_price6 = $rdg_supply_price6 + $is_exist['rdg_supply_price6'];
                    $rdg_supply_price7 = $rdg_supply_price7 + $is_exist['rdg_supply_price7'];

                    $rdg_vat1 = $rdg_vat1 + $is_exist['rdg_vat1'];
                    $rdg_vat2 = $rdg_vat2 + $is_exist['rdg_vat2'];
                    $rdg_vat3 = $rdg_vat3 + $is_exist['rdg_vat3'];
                    $rdg_vat4 = $rdg_vat4 + $is_exist['rdg_vat4'];
                    $rdg_vat5 = $rdg_vat5 + $is_exist['rdg_vat5'];
                    $rdg_vat6 = $rdg_vat6 + $is_exist['rdg_vat6'];
                    $rdg_vat7 = $rdg_vat7 + $is_exist['rdg_vat7'];

                    $rdg_sum1 = $rdg_sum1 + $is_exist['rdg_sum1'];
                    $rdg_sum2 = $rdg_sum2 + $is_exist['rdg_sum2'];
                    $rdg_sum3 = $rdg_sum3 + $is_exist['rdg_sum3'];
                    $rdg_sum4 = $rdg_sum4 + $is_exist['rdg_sum4'];
                    $rdg_sum5 = $rdg_sum5 + $is_exist['rdg_sum5'];
                    $rdg_sum6 = $rdg_sum6 + $is_exist['rdg_sum6'];
                    $rdg_sum7 = $rdg_sum7 + $is_exist['rdg_sum7'];
                }

                foreach ($request->rgds as $key => $rgd) {
                    $is_exist = RateDataGeneral::where('rgd_no_expectation', $rgd['rgd_no'])->where('rdg_bill_type', 'final_monthly')->first();
                    if (!$is_exist) {
                        $is_exist = RateDataGeneral::where('rgd_no', $rgd['rgd_no'])->where('rdg_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();

                        $final_rdg = $is_exist->replicate();
                        $final_rdg->rdg_bill_type = $request->bill_type; // the new project_id

                        $final_rdg->rdg_supply_price1 = $rdg_supply_price1;
                        $final_rdg->rdg_supply_price2 = $rdg_supply_price2;
                        $final_rdg->rdg_supply_price3 = $rdg_supply_price3;
                        $final_rdg->rdg_supply_price4 = $rdg_supply_price4;
                        $final_rdg->rdg_supply_price5 = $rdg_supply_price5;
                        $final_rdg->rdg_supply_price6 = $rdg_supply_price6;
                        $final_rdg->rdg_supply_price7 = $rdg_supply_price7;

                        $final_rdg->rdg_vat1 = $rdg_vat1;
                        $final_rdg->rdg_vat2 = $rdg_vat2;
                        $final_rdg->rdg_vat3 = $rdg_vat3;
                        $final_rdg->rdg_vat4 = $rdg_vat4;
                        $final_rdg->rdg_vat5 = $rdg_vat5;
                        $final_rdg->rdg_vat6 = $rdg_vat6;
                        $final_rdg->rdg_vat7 = $rdg_vat7;

                        $final_rdg->rdg_sum1 = $rdg_sum1;
                        $final_rdg->rdg_sum2 = $rdg_sum2;
                        $final_rdg->rdg_sum3 = $rdg_sum3;
                        $final_rdg->rdg_sum4 = $rdg_sum4;
                        $final_rdg->rdg_sum5 = $rdg_sum5;
                        $final_rdg->rdg_sum6 = $rdg_sum6;
                        $final_rdg->rdg_sum7 = $rdg_sum7;

                        $final_rdg->save();
                    } else {
                        $final_rdg = $is_exist;
                    }

                    $expectation_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->where('rgd_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();
                    $final_rgd = ReceivingGoodsDelivery::where('rgd_parent_no', $rgd['rgd_no'])->where('rgd_bill_type', 'final_monthly')->first();

                    $final_rgds[] = $final_rgd;

                    if (!$final_rgd) {
                        $expectation_rgd->rgd_status5 = 'issued';
                        $expectation_rgd->save();

                        $final_rgd = $expectation_rgd->replicate();
                        $final_rgd->rgd_bill_type = $request->bill_type; // the new project_id
                        $final_rgd->rgd_status4 = '확정청구서';
                        $final_rgd->rgd_issue_date = Carbon::now()->toDateTimeString();
                        $final_rgd->rgd_status5 = null;
                        $final_rgd->rgd_status6 = null;
                        $final_rgd->rgd_status7 = null;
                        $final_rgd->rgd_is_show = ($i == 0 ? 'y' : 'n');
                        $final_rgd->rgd_parent_no = $expectation_rgd->rgd_no;
                        $final_rgd->rgd_settlement_number = $request->settlement_number;
                        $final_rgd->save();

                        $ag = AdjustmentGroup::where('ag_no', $request->rdg_set_type)->first();

                        RateDataGeneral::where('rdg_no', $final_rdg->rdg_no)->update([
                            'rgd_no' => $final_rgd->rgd_no,
                            'rgd_no_expectation' => $expectation_rgd->rgd_no,
                            'rdg_set_type' => isset($ag->ag_name) ? $ag->ag_name : $is_exist->rdg_set_type,
                            'ag_no' => isset($ag->ag_no) ? $ag->ag_no : $is_exist->ag_no,
                        ]);
                        RateMetaData::where('rgd_no', $request->rgd_no)->where(function ($q) {
                            $q->where('set_type', 'bonded1_final_monthly')
                                ->orWhere('set_type', 'bonded2_final_monthly')
                                ->orWhere('set_type', 'bonded3_final_monthly')
                                ->orWhere('set_type', 'bonded4_final_monthly')
                                ->orWhere('set_type', 'bonded5_final_monthly')
                                ->orWhere('set_type', 'bonded6_final_monthly');
                        })->update([
                            'rgd_no' => $final_rgd->rgd_no,
                        ]);

                    } else {
                        $expectation_rgd->rgd_status5 = 'issued';
                        $expectation_rgd->save();

                        $final_rgd->rgd_settlement_number = $request->settlement_number;
                        if ($i == 0) {
                            $final_rgd->rgd_is_show = 'y';
                        } else {
                            $final_rgd->rgd_is_show = 'n';
                        }
                        $final_rgd->save();

                        RateDataGeneral::where('rdg_no', $final_rdg->rdg_no)->update([
                            'rgd_no' => $final_rgd->rgd_no,
                            'rgd_no_expectation' => $expectation_rgd->rgd_no,
                        ]);
                    }
                    $i++;
                }
            }

            DB::commit();

            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $is_exist,
                'final_rgds' => $final_rgds,
                // 'final_rgd' => $final_rgd
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rate_data_general_monthly_final($rgd_no)
    {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::where('rgd_no_expectation', $rgd_no)->where('rdg_bill_type', 'final_monthly')->first();

            if (!isset($rdg->rdg_no)) {
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'expectation_monthly')->first();
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
    public function get_rate_data_general_monthly_final2($rgd_no)
    {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final_monthly')->first();

            if (!isset($rdg->rdg_no)) {
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'expectation_monthly')->first();
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rate_data_general_monthly_additional($rgd_no)
    {
        try {
            DB::beginTransaction();
            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'additional_monthly')->first();

            if (!isset($rdg->rdg_no)) {
                $rdg = RateDataGeneral::where('rgd_no_final', $rgd_no)->where('rdg_bill_type', 'additional_monthly')->first();
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function registe_rate_data_general_final_service2(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            //Check is there already RateDataGeneral with rdg_no yet
            $is_exist = RateDataGeneral::where('rgd_no', $request->rgd_no)->where('rdg_bill_type', $request->bill_type)->first();

            //Get RecevingGoodsDelivery base on rgd_no
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            $w_no = $rgd->w_no;
            $ag = AdjustmentGroup::where('ag_no', $request->rdg_set_type)->first();

            $rdg = RateDataGeneral::updateOrCreate(
                [
                    'rdg_no' => isset($is_exist->rdg_no) ? $is_exist->rdg_no : null,
                    'rdg_bill_type' => $request->bill_type,
                ],
                [
                    'w_no' => $w_no,
                    'rdg_bill_type' => $request->bill_type,
                    'rgd_no_expectation' => $request->type == 'edit_final' ? $is_exist->rgd_no_expectation : (str_contains($request->bill_type, 'final') ? $request->rgd_no : null),
                    'rgd_no_final' => $request->type == 'edit_additional' ? $is_exist->rgd_no_final : (str_contains($request->bill_type, 'additional') ? $request->rgd_no : null),
                    'mb_no' => Auth::user()->mb_no,
                    'rdg_set_type' => isset($ag->ag_name) ? $ag->ag_name : null,
                    'ag_no' => isset($ag->ag_no) ? $ag->ag_no : null,
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

            $previous_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_no)->where('rgd_bill_type', '=', $request->previous_bill_type)->first();

            if ($request->bill_type == 'final_spasys' || $request->bill_type == 'final_shop') {
                $previous_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_no)->first();

                $previous_rgd->rgd_status4 = $user->mb_type == 'shop' ? 'issued' : $previous_rgd->rgd_status4;
                $previous_rgd->rgd_status5 = $user->mb_type == 'spasys' ? 'issued' : $previous_rgd->rgd_status5;
                $previous_rgd->save();

                $final_rgd = $previous_rgd->replicate();
                $final_rgd->rgd_bill_type = $request->bill_type; // the new project_id
                $final_rgd->rgd_status3 = null;
                $final_rgd->rgd_status4 = $request->status;
                $final_rgd->rgd_issue_date = Carbon::now()->toDateTimeString();
                $final_rgd->rgd_status5 = null;
                $final_rgd->rgd_status6 = null;
                $final_rgd->rgd_status7 = null;
                $final_rgd->rgd_confirmed_date = null;
                $final_rgd->rgd_paid_date = null;
                $final_rgd->rgd_tax_invoice_date = null;
                $final_rgd->rgd_tax_invoice_number = null;
                $final_rgd->rgd_parent_no = $previous_rgd->rgd_no;
                $final_rgd->rgd_settlement_number = $request->settlement_number;
                $final_rgd->rgd_calculate_deadline_yn = $request->rgd_calculate_deadline_yn ? $request->rgd_calculate_deadline_yn : $previous_rgd->rgd_calculate_deadline_yn;
                $final_rgd->save();

                RateDataGeneral::where('rgd_no_expectation', $previous_rgd->rgd_no)->update([
                    'rgd_no' => $final_rgd->rgd_no,
                ]);

                // ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                //     'rgd_is_show' => 'y',
                //     'rgd_status4' => $request->status,
                //     'rgd_issue_date' => Carbon::now()->toDateTimeString(),
                //     'rgd_bill_type' => $request->bill_type,
                //     'rgd_settlement_number' => $request->settlement_number,
                //     'rgd_calculate_deadline_yn' => $request->rgd_calculate_deadline_yn ? $request->rgd_calculate_deadline_yn : '',
                // ]);
            } else if ($request->bill_type == 'additional' && $request->type != 'edit_additional') {
                $previous_rgd->rgd_status5 = 'issued';
                $previous_rgd->save();

                $final_rgd = $previous_rgd->replicate();
                $final_rgd->rgd_bill_type = $request->bill_type; // the new project_id
                $final_rgd->rgd_status3 = null;
                $final_rgd->rgd_status4 = $request->status;
                $final_rgd->rgd_issue_date = Carbon::now()->toDateTimeString();
                $final_rgd->rgd_status5 = null;
                $final_rgd->rgd_status6 = null;
                $final_rgd->rgd_status7 = null;
                $final_rgd->rgd_confirmed_date = null;
                $final_rgd->rgd_paid_date = null;
                $final_rgd->rgd_tax_invoice_date = null;
                $final_rgd->rgd_tax_invoice_number = null;
                $final_rgd->rgd_parent_no = $previous_rgd->rgd_no;
                $final_rgd->save();

                $settlement_number = explode('_', $final_rgd->rgd_settlement_number);
                $settlement_number[2] = str_replace("MF", "MA", $settlement_number[2]);
                $final_rgd->rgd_settlement_number = implode("_", $settlement_number);
                $final_rgd->save();

                RateDataGeneral::where('rgd_no_final', $previous_rgd->rgd_no)->update([
                    'rgd_no' => $final_rgd->rgd_no,
                ]);
            } else if ($request->type == 'edit_additional') {

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
    public function registe_rate_data_general_final_service2_mobile(Request $request)
    {
        try {
            DB::beginTransaction();

            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            $w_no = $rgd->w_no;
            $rdg = RateDataGeneral::where('rgd_no', $request->rgd_no)
                ->where('rdg_bill_type', $request->bill_type)
                ->update([
                    'rdg_set_type' => $request->ag_name,
                ]);
            //  return DB::getQueryLog();
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rdg' => $rdg,
                'sql' => DB::getQueryLog(),
                'rgd_no' => $request->rdg_no,
                'rdg_bill_type' => $request->bill_type,
                // 'final_rgd' => $final_rgd
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function registe_rate_data_general_service1(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            //Check is there already RateDataGeneral with rdg_no yet
            $is_exist = RateDataGeneral::where('rgd_no', $request->rgd_no)->where('rdg_bill_type', $request->bill_type)->first();

            //Get RecevingGoodsDelivery base on rgd_no
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            $w_no = $rgd->w_no;
            $ag = AdjustmentGroup::where('ag_no', $request->rdg_set_type)->first();

            $rdg = RateDataGeneral::updateOrCreate(
                [
                    'rdg_no' => isset($is_exist->rdg_no) ? $is_exist->rdg_no : null,
                    'rdg_bill_type' => $request->bill_type,
                ],
                [
                    'w_no' => $w_no,
                    'rdg_bill_type' => $request->bill_type,
                    'rgd_no_expectation' => $request->type == 'edit_final' ? $is_exist->rgd_no_expectation : (str_contains($request->bill_type, 'final') ? $request->rgd_no : null),
                    'rgd_no_final' => $request->type == 'edit_additional' ? $is_exist->rgd_no_final : (str_contains($request->bill_type, 'additional') ? $request->rgd_no : null),
                    'mb_no' => Auth::user()->mb_no,
                    'rdg_set_type' => isset($ag->ag_name) ? $ag->ag_name : NULL,
                    'ag_no' => isset($ag->ag_no) ? $ag->ag_no : NULL,
                    'rdg_supply_price1' => $request->bonded1['supply_price'],
                    'rdg_supply_price2' => $request->bonded2['supply_price'],
                    'rdg_supply_price3' => $request->bonded3['supply_price'],
                    'rdg_supply_price4' => $request->bonded4['supply_price'],
                    'rdg_supply_price5' => $request->bonded5['supply_price'],
                    'rdg_supply_price6' => $request->bonded6['supply_price'],
                    'rdg_supply_price7' => $request->total['supply_price'],
                    'rdg_vat1' => $request->bonded1['taxes'],
                    'rdg_vat2' => $request->bonded2['taxes'],
                    'rdg_vat3' => $request->bonded3['taxes'],
                    'rdg_vat4' => $request->bonded4['taxes'],
                    'rdg_vat5' => $request->bonded5['taxes'],
                    'rdg_vat6' => $request->bonded6['taxes'],
                    'rdg_vat7' => $request->total['taxes'],
                    'rdg_sum1' => $request->bonded1['sum'],
                    'rdg_sum2' => $request->bonded2['sum'],
                    'rdg_sum3' => $request->bonded3['sum'],
                    'rdg_sum4' => $request->bonded4['sum'],
                    'rdg_sum5' => $request->bonded5['sum'],
                    'rdg_sum6' => $request->bonded6['sum'],
                    'rdg_sum7' => $request->total['sum'],
                    'rdg_etc1' => $request->bonded1['etc'],
                    'rdg_etc2' => $request->bonded2['etc'],
                    'rdg_etc3' => $request->bonded3['etc'],
                    'rdg_etc4' => $request->bonded4['etc'],
                    'rdg_etc5' => $request->bonded5['etc'],
                    'rdg_etc6' => $request->bonded6['etc'],
                    'rdg_etc7' => $request->total['etc'],
                ]
            );
            $previous_rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->where('rgd_bill_type', '=', $request->previous_bill_type)->first();
            if ($request->type == 'create_expectation' || $request->type == 'create_expectation_monthly') {
                $previous_rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();

                $previous_rgd->rgd_status4 = $user->mb_type == 'shop' ? 'issued' : $previous_rgd->rgd_status4;
                $previous_rgd->rgd_status5 = $user->mb_type == 'spasys' ? 'issued' : $previous_rgd->rgd_status5;
                $previous_rgd->save();

                $final_rgd = $previous_rgd->replicate();
                $final_rgd->rgd_bill_type = $request->bill_type; // the new project_id
                $final_rgd->rgd_status3 = null;
                $final_rgd->rgd_status4 = $request->status;
                $final_rgd->rgd_issue_date = Carbon::now()->toDateTimeString();
                $final_rgd->rgd_settlement_number = $request->rgd_settlement_number;
                $final_rgd->rgd_status5 = null;
                $final_rgd->rgd_status6 = null;
                $final_rgd->rgd_status7 = null;
                $final_rgd->rgd_confirmed_date = null;
                $final_rgd->rgd_paid_date = null;
                $final_rgd->rgd_tax_invoice_date = null;
                $final_rgd->rgd_tax_invoice_number = null;
                $final_rgd->mb_no = Auth::user()->mb_no;
                $final_rgd->rgd_parent_no = $previous_rgd->rgd_no;
                $final_rgd->rgd_storage_days = $request->storage_days;
                $final_rgd->rgd_integrated_calculate_yn = $request->rgd_integrated_calculate_yn;
                $final_rgd->rgd_calculate_deadline_yn = $request->rgd_calculate_deadline_yn ? $request->rgd_calculate_deadline_yn : $previous_rgd->rgd_calculate_deadline_yn;
                $final_rgd->save();

                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $final_rgd->rgd_no,
                ]);

                // ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                //     'rgd_status4' => '예상경비청구서',
                //     'rgd_issue_date' => Carbon::now()->toDateTimeString(),
                //     'rgd_bill_type' => $request->bill_type,
                //     'rgd_storage_days' => $request->storage_days,
                //     'rgd_settlement_number' => $request->rgd_settlement_number,
                //     'rgd_integrated_calculate_yn'=> $request->rgd_integrated_calculate_yn,
                //     'rgd_calculate_deadline_yn'=> $request->rgd_calculate_deadline_yn,
                //     'mb_no' => Auth::user()->mb_no,
                // ]);
            } else if (!isset($is_exist->rdg_no) && isset($request->previous_bill_type) && !empty($previous_rgd)) {


                $previous_rgd->rgd_status5 = 'issued';
                $previous_rgd->save();

                $final_rgd = $previous_rgd->replicate();
                $final_rgd->rgd_bill_type = $request->bill_type; // the new project_id
                $final_rgd->rgd_status3 = null;
                $final_rgd->rgd_status4 = $request->status;
                $final_rgd->rgd_issue_date = Carbon::now()->toDateTimeString();
                $final_rgd->rgd_settlement_number = $request->rgd_settlement_number;
                $final_rgd->rgd_status5 = null;
                $final_rgd->rgd_status6 = null;
                $final_rgd->rgd_status7 = null;
                $final_rgd->rgd_confirmed_date = null;
                $final_rgd->rgd_paid_date = null;
                $final_rgd->rgd_tax_invoice_date = null;
                $final_rgd->rgd_tax_invoice_number = null;
                $final_rgd->mb_no = Auth::user()->mb_no;
                $final_rgd->rgd_parent_no = $previous_rgd->rgd_no;
                $final_rgd->save();

                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $final_rgd->rgd_no,
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

    public function registe_rate_data_general_service1_final(Request $request)
    {
        try {
            DB::beginTransaction();
            //Check is there already RateDataGeneral with rdg_no yet
            $is_exist = RateDataGeneral::where('rgd_no_expectation', $request->rgd_no)->where('rdg_bill_type', $request->bill_type)->first();

            //Get RecevingGoodsDelivery base on rgd_no
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            $w_no = $rgd->w_no;
            $ag = AdjustmentGroup::where('ag_no', $request->rdg_set_type)->first();

            $rdg = RateDataGeneral::updateOrCreate(
                [
                    'rdg_no' => isset($is_exist->rdg_no) ? $is_exist->rdg_no : null,
                    'rdg_bill_type' => $request->bill_type,
                ],
                [
                    'w_no' => $w_no,
                    'rdg_bill_type' => $request->bill_type,
                    'rgd_no_expectation' => $request->type == 'edit_final' ? $is_exist->rgd_no_expectation : (str_contains($request->bill_type, 'final') ? $request->rgd_no : null),
                    'rgd_no_final' => $request->type == 'edit_additional' ? $is_exist->rgd_no_final : (str_contains($request->bill_type, 'additional') ? $request->rgd_no : null),
                    'mb_no' => Auth::user()->mb_no,
                    'rdg_set_type' => isset($ag->ag_name) ? $ag->ag_name : null,
                    'ag_no' => isset($ag->ag_no) ? $ag->ag_no : null,
                    'rdg_supply_price1' => $request->bonded1['supply_price'],
                    'rdg_supply_price2' => $request->bonded2['supply_price'],
                    'rdg_supply_price3' => $request->bonded3['supply_price'],
                    'rdg_supply_price4' => $request->bonded4['supply_price'],
                    'rdg_supply_price5' => $request->bonded5['supply_price'],
                    'rdg_supply_price6' => $request->bonded6['supply_price'],
                    'rdg_supply_price7' => $request->total['supply_price'],
                    'rdg_vat1' => $request->bonded1['taxes'],
                    'rdg_vat2' => $request->bonded2['taxes'],
                    'rdg_vat3' => $request->bonded3['taxes'],
                    'rdg_vat4' => $request->bonded4['taxes'],
                    'rdg_vat5' => $request->bonded5['taxes'],
                    'rdg_vat6' => $request->bonded6['taxes'],
                    'rdg_vat7' => $request->total['taxes'],
                    'rdg_sum1' => $request->bonded1['sum'],
                    'rdg_sum2' => $request->bonded2['sum'],
                    'rdg_sum3' => $request->bonded3['sum'],
                    'rdg_sum4' => $request->bonded4['sum'],
                    'rdg_sum5' => $request->bonded5['sum'],
                    'rdg_sum6' => $request->bonded6['sum'],
                    'rdg_sum7' => $request->total['sum'],
                    'rdg_etc1' => $request->bonded1['etc'],
                    'rdg_etc2' => $request->bonded2['etc'],
                    'rdg_etc3' => $request->bonded3['etc'],
                    'rdg_etc4' => $request->bonded4['etc'],
                    'rdg_etc5' => $request->bonded5['etc'],
                    'rdg_etc6' => $request->bonded6['etc'],
                    'rdg_etc7' => $request->total['etc'],
                ]
            );

            if ($request->type == 'create_expectation' || $request->type == 'create_expectation_monthly') {
                ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                    'rgd_status4' => '예상경비청구서',
                    'rgd_issue_date' => Carbon::now()->toDateTimeString(),
                    'rgd_bill_type' => $request->bill_type,
                    'rgd_storage_days' => $request->storage_days,
                    'rgd_settlement_number' => $request->rgd_settlement_number,
                    'mb_no' => Auth::user()->mb_no,
                ]);
            }

            $previous_rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->where('rgd_bill_type', '=', $request->previous_bill_type)->first();

            if (!isset($is_exist->rdg_no) && isset($request->previous_bill_type) && !empty($previous_rgd)) {
                $previous_rgd->save();

                $final_rgd = $previous_rgd->replicate();
                $final_rgd->rgd_bill_type = $request->bill_type; // the new project_id
                $final_rgd->rgd_status3 = null;
                $final_rgd->rgd_status4 = $request->status;
                $final_rgd->rgd_issue_date = Carbon::now()->toDateTimeString();
                $final_rgd->rgd_status5 = null;
                $final_rgd->rgd_status6 = null;
                $final_rgd->rgd_status7 = null;
                $final_rgd->rgd_confirmed_date = null;
                $final_rgd->rgd_paid_date = null;
                $final_rgd->rgd_tax_invoice_date = null;
                $final_rgd->rgd_tax_invoice_number = null;
                $final_rgd->rgd_is_show = 'n';
                $final_rgd->rgd_settlement_number = null;
                $final_rgd->rgd_parent_no = $previous_rgd->rgd_no;
                $final_rgd->mb_no = Auth::user()->mb_no;
                $final_rgd->save();

                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $final_rgd->rgd_no,
                ]);
            } else {
                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $is_exist ? $is_exist->rgd_no : $rgd->rgd_no,
                ]);
            }

            if ($request->bill_type == 'final' || $request->bill_type == 'final_monthly') {
                if ($request->type != 'create_final_monthly') {
                    ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                        'rgd_status4' => $request->status,
                        'rgd_issue_date' => Carbon::now()->toDateTimeString(),
                        'rgd_bill_type' => $request->bill_type,
                        'mb_no' => Auth::user()->mb_no,
                    ]);
                }

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

    public function update_storage_days(Request $request)
    {
        try {
            DB::beginTransaction();
            //Check is there already RateDataGeneral with rdg_no yet
            $company = Company::where('co_no', $request->co_no)->first();
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                'rgd_storage_days' => $request->storage_days,
            ]);

            Import::where('ti_logistic_manage_number', $request->ti_logistic_manage_number)->update([
                'ti_co_license' => isset($company->co_license) ? $company->co_license : null,
                'ti_logistic_type' => isset($request->te_e_price) ? $request->ti_logistic_type : null,
                'ti_i_storeday' => $request->storage_days ? $request->storage_days : $request->storagedays,
            ]);

            ImportExpected::where('tie_logistic_manage_number', $request->ti_logistic_manage_number)->update([
                'tie_co_license' => isset($company->co_license) ? $company->co_license : null,
            ]);

            Export::where('te_logistic_manage_number', $request->ti_logistic_manage_number)->update([
                'te_e_price' => isset($request->te_e_price) ? $request->te_e_price : null,
            ]);

            Warehousing::where('logistic_manage_number', $request->ti_logistic_manage_number)->update([
                'co_no' => isset($request->co_no) ? $request->co_no : null,
            ]);

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rmd_no_fulfill($rgd_no, $type, $pretype)
    {
        try {
        $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();
        $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();
        $previous_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();

        $rmd = RateMetaData::where(
            [
                'rgd_no' => $rgd_no,
                'set_type' => $type,
            ]
        )->first();
        if (empty($rmd) && !empty($rdg)) {
            $rmd = RateMetaData::where(
                [

                    'rgd_no' => $rdg->rgd_no_final,
                    'set_type' => $type,
                ]
            )->first();
        }
        if (empty($rmd) && !empty($previous_rgd)) {
            $rmd = RateMetaData::where(
                [

                    'rgd_no' => $previous_rgd->rgd_no,
                    'set_type' => $type,
                ]
            )->first();
        }
        if (empty($rmd)) {
            $rmd = RateMetaData::where(
                [

                    'rgd_no' => $rgd_no,
                    'set_type' => $pretype,
                ]
            )->first();
        }


        return response()->json([
            'rmd_no' => $rmd ? $rmd->rmd_no : null,
        ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function get_rmd_no_fulfill_raw($rgd_no, $type, $pretype)
    {
        $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();
        $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();

        $rmd = RateMetaData::where(
            [
                'rgd_no' => $rgd_no,
                'set_type' => $type,
            ]
        )->first();
        if (empty($rmd) && !empty($rdg)) {
            $rmd = RateMetaData::where(
                [

                    'rgd_no' => $rdg->rgd_no_final,
                    'set_type' => $type,
                ]
            )->first();
        }
        if (empty($rmd)) {
            $rmd = RateMetaData::where(
                [

                    'rgd_no' => $rgd_no,
                    'set_type' => $pretype,
                ]
            )->first();
        }

        return $rmd ? $rmd->rmd_no : null;
    }
    public function download_final_month_bill_issue(Request $request)
    {
        return response()->json([
            'message' => 'No data',
            'status' => 1,
        ], 201);
    }
    public function deleteRowRateData($rd_no)
    {
        try {

            $rateData = RateData::where('rd_no', $rd_no)->delete();
            return response()->json([
                'message' => $rateData,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0006], 500);
        }
    }
    //DELETE SET RATE DATA FOR BONDED SERVICE
    public function deleteSetRateData($rd_no)
    {
        try {

            $rate_data = RateData::where('rd_no', $rd_no)->first();

            RateData::where(['rmd_no' => $rate_data->rmd_no, 'rd_cate1' => $rate_data->rd_cate1])->delete();

            return response()->json([
                'message' => $rate_data,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0006], 500);
        }
    }
    public function get_rate_data_raw($rmd_no)
    {
        $rate_data = RateData::where('rmd_no', $rmd_no)->where(function ($q) {
            $q->where('rd_cate_meta1', '유통가공')
                ->orWhere('rd_cate_meta1', '수입풀필먼트');
        })->get();
        return $rate_data;
    }
    public function get_rmd_no_raw($rgd_no, $set_type)
    {

        $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();
        $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();
        $w_no = $rgd->w_no;

        $rmd = RateMetaData::where(
            [
                'rgd_no' => $rgd_no,
                'set_type' => $set_type,
            ]
        )->first();

        if (!isset($rmd->rmd_no) && $set_type == 'work_final') {

            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => 'work_final',
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'work',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'work_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'work',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'storage_final') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => 'storage_final',
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'storage',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'storage_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'storage',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'domestic_final') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => 'domestic_final',
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'domestic',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'domestic_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'domestic',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'work_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rdg->rgd_no_final,
                    'set_type' => 'work_additional',
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'work_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'work_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'work',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'storage_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rdg->rgd_no_final,
                    'set_type' => 'storage_additional',
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'storage_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'storage_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'storage',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'domestic_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rdg->rgd_no_final,
                    'set_type' => 'domestic_additional',
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'domestic_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'domestic_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'domestic',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'work_additional2') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => 'work_additional',
                ]
            )->first();

            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_final,
                        'set_type' => 'work_additional',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'storage_additional2') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => 'storage_additional',
                ]
            )->first();
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_final,
                        'set_type' => 'storage_additional',
                    ]
                )->first();
            }

        } else if (!isset($rmd->rmd_no) && $set_type == 'work_monthly_final') {

            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => 'work_monthly_final',
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'work_monthly',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'work_monthly_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'work_monthly',
                    ]
                )->first();
            }

        } else if (!isset($rmd->rmd_no) && $set_type == 'storage_monthly_final') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => 'storage_monthly_final',
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'storage_monthly',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'storage_monthly_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'storage_monthly',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'domestic_monthly_final') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => 'domestic_monthly_final',
                ]
            )->first();
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd_no,
                        'set_type' => 'domestic_monthly',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'domestic_monthly_final',
                    ]
                )->first();
            }
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_expectation,
                        'set_type' => 'domestic_monthly',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'work_monthly_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => 'work_monthly_additional',
                ]
            )->first();
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_final,
                        'set_type' => 'work_monthly_additional',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'storage_monthly_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => 'storage_monthly_additional',
                ]
            )->first();
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_final,
                        'set_type' => 'storage_monthly_additional',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'domestic_monthly_additional') {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd_no,
                    'set_type' => 'domestic_monthly_additional',
                ]
            )->first();
            if (empty($rmd) && !empty($rdg)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rdg->rgd_no_final,
                        'set_type' => 'domestic_monthly_additional',
                    ]
                )->first();
            }
        }
        return $rmd ? $rmd->rmd_no : null;
    }

    public function download_data_general($rgd_no)
    {
        $data = array();

        DB::beginTransaction();
        $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();
        $w_no = $rgd->w_no;
        $rdg = RateDataGeneral::where('w_no', $w_no)->where('rgd_no', $rgd_no)->where('rdg_bill_type', 'expectation')->first();

        if (empty($rdg)) {
            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'expectation')->first();
        }
        $user = Auth::user();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);
        $sheet->setTitle('종합');

        $sheet->setCellValue('A1', '종합');
        $sheet->setCellValue('B1', '공급가');
        $sheet->setCellValue('C1', '부가세');
        $sheet->setCellValue('D1', '합계');
        $sheet->setCellValue('E1', '비고');

        $sheet->setCellValue('A2', '유통가공 작업료');
        $sheet->setCellValue('B2', $rdg['rdg_supply_price2']);
        $sheet->setCellValue('C2', $rdg['rdg_vat2']);
        $sheet->setCellValue('D2', $rdg['rdg_sum2']);
        $sheet->setCellValue('E2', $rdg['rdg_etc2']);

        $sheet->setCellValue('A3', '부자재 보관료');
        $sheet->setCellValue('B3', $rdg['rdg_supply_price1']);
        $sheet->setCellValue('C3', $rdg['rdg_vat1']);
        $sheet->setCellValue('D3', $rdg['rdg_sum1']);
        $sheet->setCellValue('E3', $rdg['rdg_etc1']);

        $sheet->setCellValue('A4', '국내운송료');
        $sheet->setCellValue('B4', $rdg['rdg_supply_price3']);
        $sheet->setCellValue('C4', $rdg['rdg_vat3']);
        $sheet->setCellValue('D4', $rdg['rdg_sum3']);
        $sheet->setCellValue('E4', $rdg['rdg_etc3']);

        $sheet->setCellValue('A5', '합계');
        $sheet->setCellValue('B5', $rdg['rdg_supply_price4']);
        $sheet->setCellValue('C5', $rdg['rdg_vat4']);
        $sheet->setCellValue('D5', $rdg['rdg_sum4']);
        $sheet->setCellValue('E5', $rdg['rdg_etc4']);

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);
        $rmd_no = $this->get_rmd_no_raw($rgd_no, 'work');
        $data_sheet2 = $rate_data = array();
        if ($rmd_no) {
            $rate_data = $this->get_rate_data_raw($rmd_no);
        }
        $sheet2->setTitle('작업료');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('A1', '항목');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', '건수');
        $sheet2->setCellValue('F1', '공급가');
        $sheet2->setCellValue('G1', '부가세');
        $sheet2->setCellValue('H1', '합계');
        $sheet2->setCellValue('I1', '비고');

        $row_2 = 2;
        if (!empty($rate_data)) {
            $data_sheet2 = json_decode($rate_data, 1);
            foreach ($data_sheet2 as $dt2) {
                $sheet2->setCellValue('A' . $row_2, $dt2['rd_cate1']);
                $sheet2->setCellValue('B' . $row_2, $dt2['rd_cate2']);
                $sheet2->setCellValue('C' . $row_2, $dt2['rd_data1']);
                $sheet2->setCellValue('D' . $row_2, $dt2['rd_data2']);
                $sheet2->setCellValue('E' . $row_2, $dt2['rd_data4']);
                $sheet2->setCellValue('F' . $row_2, $dt2['rd_data5']);
                $sheet2->setCellValue('G' . $row_2, $dt2['rd_data6']);
                $sheet2->setCellValue('H' . $row_2, $dt2['rd_data7']);
                $sheet2->setCellValue('I' . $row_2, '');
                $row_2++;
            }
        }

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);
        $data_sheet3 = array();
        if ($rgd_no) {
            $rmd_no_storage = $this->get_rmd_no_raw($rgd_no, 'storage');
            $rate_data_storage = $this->get_rate_data_raw($rmd_no_storage);
        }

        $sheet3->setTitle('보관료');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('A1', '항목');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', '건수');
        $sheet3->setCellValue('F1', '공급가');
        $sheet3->setCellValue('G1', '부가세');
        $sheet3->setCellValue('H1', '합계');
        $sheet3->setCellValue('I1', '비고');
        $row_3 = 2;
        if (!empty($rate_data_storage)) {
            $data_sheet3 = json_decode($rate_data_storage, 1);
            foreach ($data_sheet3 as $dt3) {
                $sheet3->setCellValue('A' . $row_3, $dt3['rd_cate1']);
                $sheet3->setCellValue('B' . $row_3, $dt3['rd_cate2']);
                $sheet3->setCellValue('C' . $row_3, $dt3['rd_data1']);
                $sheet3->setCellValue('D' . $row_3, $dt3['rd_data2']);
                $sheet3->setCellValue('E' . $row_3, $dt3['rd_data4']);
                $sheet3->setCellValue('F' . $row_3, $dt3['rd_data5']);
                $sheet3->setCellValue('G' . $row_3, $dt3['rd_data6']);
                $sheet3->setCellValue('H' . $row_3, $dt3['rd_data7']);
                $sheet3->setCellValue('I' . $row_3, '');
                $row_3++;
            }
        }

        $spreadsheet->createSheet();
        $sheet4 = $spreadsheet->getSheet(3);
        $data_sheet4 = array();
        if ($rgd_no) {
            $rmd_no_domestic = $this->get_rmd_no_raw($rgd_no, 'domestic');
            $rate_data_domestic = $this->get_rate_data_raw($rmd_no_domestic);
        }

        $sheet4->setTitle('국내운송료');
        $sheet4->mergeCells('A1:B1');
        $sheet4->setCellValue('A1', '항목');
        $sheet4->setCellValue('C1', '단위');
        $sheet4->setCellValue('D1', '단가');
        $sheet4->setCellValue('E1', '건수');
        $sheet4->setCellValue('F1', '공급가');
        $sheet4->setCellValue('G1', '부가세');
        $sheet4->setCellValue('H1', '합계');
        $sheet4->setCellValue('I1', '비고');

        $row_4 = 2;
        if (!empty($rate_data_domestic)) {
            $data_sheet4 = json_decode($rate_data_domestic, 1);
            foreach ($data_sheet4 as $dt4) {
                $sheet4->setCellValue('A' . $row_4, $dt4['rd_cate1']);
                $sheet4->setCellValue('B' . $row_4, $dt4['rd_cate2']);
                $sheet4->setCellValue('C' . $row_4, $dt4['rd_data1']);
                $sheet4->setCellValue('D' . $row_4, $dt4['rd_data2']);
                $sheet4->setCellValue('E' . $row_4, $dt4['rd_data4']);
                $sheet4->setCellValue('F' . $row_4, $dt4['rd_data5']);
                $sheet4->setCellValue('G' . $row_4, $dt4['rd_data6']);
                $sheet4->setCellValue('H' . $row_4, $dt4['rd_data7']);
                $sheet4->setCellValue('I' . $row_4, '');
                $row_4++;
            }
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Rate-Data-General-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Rate-Data-General-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();

    }

    public function download_data_casebill_edit($rgd_no)
    {
        Log::error($rgd_no);
        DB::beginTransaction();
        $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'additional')->first();

        Log::error($rgd_no);
        Log::error($rdg);
        if (!isset($rdg->rdg_no)) {
            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final')->first();
        }
        $user = Auth::user();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);
        $sheet->setTitle('종합');

        $sheet->setCellValue('A1', '종합');
        $sheet->setCellValue('B1', '공급가');
        $sheet->setCellValue('C1', '부가세');
        $sheet->setCellValue('D1', '합계');
        $sheet->setCellValue('E1', '비고');

        $sheet->setCellValue('A2', '유통가공 작업료');
        $sheet->setCellValue('B2', $rdg['rdg_supply_price2']);
        $sheet->setCellValue('C2', $rdg['rdg_vat2']);
        $sheet->setCellValue('D2', $rdg['rdg_sum2']);
        $sheet->setCellValue('E2', $rdg['rdg_etc2']);

        $sheet->setCellValue('A3', '부자재 보관료');
        $sheet->setCellValue('B3', $rdg['rdg_supply_price1']);
        $sheet->setCellValue('C3', $rdg['rdg_vat1']);
        $sheet->setCellValue('D3', $rdg['rdg_sum1']);
        $sheet->setCellValue('E3', $rdg['rdg_etc1']);

        $sheet->setCellValue('A4', '국내운송료');
        $sheet->setCellValue('B4', $rdg['rdg_supply_price3']);
        $sheet->setCellValue('C4', $rdg['rdg_vat3']);
        $sheet->setCellValue('D4', $rdg['rdg_sum3']);
        $sheet->setCellValue('E4', $rdg['rdg_etc3']);

        $sheet->setCellValue('A5', '합계');
        $sheet->setCellValue('B5', $rdg['rdg_supply_price4']);
        $sheet->setCellValue('C5', $rdg['rdg_vat4']);
        $sheet->setCellValue('D5', $rdg['rdg_sum4']);
        $sheet->setCellValue('E5', $rdg['rdg_etc4']);

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);
        $rmd_no = $this->get_rmd_no_raw($rgd_no, 'work_additional');
        $data_sheet2 = $rate_data = array();
        if ($rmd_no) {
            $rate_data = $this->get_rate_data_raw($rmd_no);
        }
        $sheet2->setTitle('작업료');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('A1', '항목');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', '건수');
        $sheet2->setCellValue('F1', '공급가');
        $sheet2->setCellValue('G1', '부가세');
        $sheet2->setCellValue('H1', '합계');
        $sheet2->setCellValue('I1', '비고');

        $row_2 = 2;
        if (!empty($rate_data)) {
            $data_sheet2 = json_decode($rate_data, 1);
            foreach ($data_sheet2 as $dt2) {
                $sheet2->setCellValue('A' . $row_2, $dt2['rd_cate1']);
                $sheet2->setCellValue('B' . $row_2, $dt2['rd_cate2']);
                $sheet2->setCellValue('C' . $row_2, $dt2['rd_data1']);
                $sheet2->setCellValue('D' . $row_2, $dt2['rd_data2']);
                $sheet2->setCellValue('E' . $row_2, $dt2['rd_data4']);
                $sheet2->setCellValue('F' . $row_2, $dt2['rd_data5']);
                $sheet2->setCellValue('G' . $row_2, $dt2['rd_data6']);
                $sheet2->setCellValue('H' . $row_2, $dt2['rd_data7']);
                $sheet2->setCellValue('I' . $row_2, '');
                $row_2++;
            }
        }

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);
        $data_sheet3 = array();
        if ($rgd_no) {
            $rmd_no_storage = $this->get_rmd_no_raw($rgd_no, 'storage_additional');
            $rate_data_storage = $this->get_rate_data_raw($rmd_no_storage);
        }

        $sheet3->setTitle('보관료');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('A1', '항목');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', '건수');
        $sheet3->setCellValue('F1', '공급가');
        $sheet3->setCellValue('G1', '부가세');
        $sheet3->setCellValue('H1', '합계');
        $sheet3->setCellValue('I1', '비고');
        $row_3 = 2;
        if (!empty($rate_data_storage)) {
            $data_sheet3 = json_decode($rate_data_storage, 1);
            foreach ($data_sheet3 as $dt3) {
                $sheet3->setCellValue('A' . $row_3, $dt3['rd_cate1']);
                $sheet3->setCellValue('B' . $row_3, $dt3['rd_cate2']);
                $sheet3->setCellValue('C' . $row_3, $dt3['rd_data1']);
                $sheet3->setCellValue('D' . $row_3, $dt3['rd_data2']);
                $sheet3->setCellValue('E' . $row_3, $dt3['rd_data4']);
                $sheet3->setCellValue('F' . $row_3, $dt3['rd_data5']);
                $sheet3->setCellValue('G' . $row_3, $dt3['rd_data6']);
                $sheet3->setCellValue('H' . $row_3, $dt3['rd_data7']);
                $sheet3->setCellValue('I' . $row_3, '');
                $row_3++;
            }
        }

        $spreadsheet->createSheet();
        $sheet4 = $spreadsheet->getSheet(3);
        $data_sheet4 = array();
        if ($rgd_no) {
            $rmd_no_domestic = $this->get_rmd_no_raw($rgd_no, 'domestic_additional');
            $rate_data_domestic = $this->get_rate_data_raw($rmd_no_domestic);
        }

        $sheet4->setTitle('국내운송료');
        $sheet4->mergeCells('A1:B1');
        $sheet4->setCellValue('A1', '항목');
        $sheet4->setCellValue('C1', '단위');
        $sheet4->setCellValue('D1', '단가');
        $sheet4->setCellValue('E1', '건수');
        $sheet4->setCellValue('F1', '공급가');
        $sheet4->setCellValue('G1', '부가세');
        $sheet4->setCellValue('H1', '합계');
        $sheet4->setCellValue('I1', '비고');

        $row_4 = 2;
        if (!empty($rate_data_domestic)) {
            $data_sheet4 = json_decode($rate_data_domestic, 1);
            foreach ($data_sheet4 as $dt4) {
                $sheet4->setCellValue('A' . $row_4, $dt4['rd_cate1']);
                $sheet4->setCellValue('B' . $row_4, $dt4['rd_cate2']);
                $sheet4->setCellValue('C' . $row_4, $dt4['rd_data1']);
                $sheet4->setCellValue('D' . $row_4, $dt4['rd_data2']);
                $sheet4->setCellValue('E' . $row_4, $dt4['rd_data4']);
                $sheet4->setCellValue('F' . $row_4, $dt4['rd_data5']);
                $sheet4->setCellValue('G' . $row_4, $dt4['rd_data6']);
                $sheet4->setCellValue('H' . $row_4, $dt4['rd_data7']);
                $sheet4->setCellValue('I' . $row_4, '');
                $row_4++;
            }
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Rate-Data-CaseBill-Edit-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Rate-Data-CaseBill-Edit-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }

    public function download_final_case_bill($rgd_no)
    {
        Log::error($rgd_no);
        DB::beginTransaction();
        $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'additional')->first();

        Log::error($rgd_no);
        Log::error($rdg);
        if (!isset($rdg->rdg_no)) {
            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final')->first();
        }
        $user = Auth::user();

        $data_sheet4 = $data_sheet3 = $data_sheet2 = $rate_data = array();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        if ($rgd_no) {
            $rmd_no = $this->get_rmd_no_raw($rgd_no, 'work_final');
            $rate_data = $this->get_rate_data_raw($rmd_no);

            $rmd_no_domestic = $this->get_rmd_no_raw($rgd_no, 'domestic_final');
            $rate_data_domestic = !empty($rmd_no_domestic) ? $this->get_rate_data_raw($rmd_no_domestic) : array();
            $data_sheet4 = !empty($rate_data_domestic) ? json_decode($rate_data_domestic, 1) : array();
            $supply_price = array_sum(array_column($data_sheet4, 'rd_data5'));
            $vat_price = array_sum(array_column($data_sheet4, 'rd_data6'));
            $sum_price = array_sum(array_column($data_sheet4, 'rd_data7'));

            $rmd_no_storage = $this->get_rmd_no_raw($rgd_no, 'storage_final');
            $rate_data_storage = $this->get_rate_data_raw($rmd_no_storage);
        }

        $sheet->setTitle('종합');

        $sheet->setCellValue('A1', '종합');
        $sheet->setCellValue('B1', '공급가');
        $sheet->setCellValue('C1', '부가세');
        $sheet->setCellValue('D1', '합계');
        $sheet->setCellValue('E1', '비고');

        $sheet->setCellValue('A2', '유통가공 작업료');
        $sheet->setCellValue('B2', $rdg['rdg_supply_price2']);
        $sheet->setCellValue('C2', $rdg['rdg_vat2']);
        $sheet->setCellValue('D2', $rdg['rdg_sum2']);
        $sheet->setCellValue('E2', $rdg['rdg_etc2']);

        $sheet->setCellValue('A3', '부자재 보관료');
        $sheet->setCellValue('B3', $rdg['rdg_supply_price1']);
        $sheet->setCellValue('C3', $rdg['rdg_vat1']);
        $sheet->setCellValue('D3', $rdg['rdg_sum1']);
        $sheet->setCellValue('E3', $rdg['rdg_etc1']);

        $sheet->setCellValue('A4', '국내운송료');
        $sheet->setCellValue('B4', $supply_price);
        $sheet->setCellValue('C4', $vat_price);
        $sheet->setCellValue('D4', $sum_price);
        $sheet->setCellValue('E4', $rdg['rdg_etc3']);

        $sheet->setCellValue('A5', '합계');
        $sheet->setCellValue('B5', $rdg['rdg_supply_price4']);
        $sheet->setCellValue('C5', $rdg['rdg_vat4']);
        $sheet->setCellValue('D5', $rdg['rdg_sum4']);
        $sheet->setCellValue('E5', $rdg['rdg_etc4']);

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);

        $sheet2->setTitle('작업료');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('A1', '항목');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', '건수');
        $sheet2->setCellValue('F1', '공급가');
        $sheet2->setCellValue('G1', '부가세');
        $sheet2->setCellValue('H1', '합계');
        $sheet2->setCellValue('I1', '비고');

        $row_2 = 2;
        if (!empty($rate_data)) {
            $data_sheet2 = json_decode($rate_data, 1);
            foreach ($data_sheet2 as $dt2) {
                $sheet2->setCellValue('A' . $row_2, $dt2['rd_cate1']);
                $sheet2->setCellValue('B' . $row_2, $dt2['rd_cate2']);
                $sheet2->setCellValue('C' . $row_2, $dt2['rd_data1']);
                $sheet2->setCellValue('D' . $row_2, $dt2['rd_data2']);
                $sheet2->setCellValue('E' . $row_2, $dt2['rd_data4']);
                $sheet2->setCellValue('F' . $row_2, $dt2['rd_data5']);
                $sheet2->setCellValue('G' . $row_2, $dt2['rd_data6']);
                $sheet2->setCellValue('H' . $row_2, $dt2['rd_data7']);
                $sheet2->setCellValue('I' . $row_2, $dt2['rd_data8']);
                $row_2++;
            }
        }

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);

        $sheet3->setTitle('보관료');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('A1', '항목');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', '건수');
        $sheet3->setCellValue('F1', '공급가');
        $sheet3->setCellValue('G1', '부가세');
        $sheet3->setCellValue('H1', '합계');
        $sheet3->setCellValue('I1', '비고');
        $row_3 = 2;
        if (!empty($rate_data_storage)) {
            $data_sheet3 = json_decode($rate_data_storage, 1);
            foreach ($data_sheet3 as $dt3) {
                $sheet3->setCellValue('A' . $row_3, $dt3['rd_cate1']);
                $sheet3->setCellValue('B' . $row_3, $dt3['rd_cate2']);
                $sheet3->setCellValue('C' . $row_3, $dt3['rd_data1']);
                $sheet3->setCellValue('D' . $row_3, $dt3['rd_data2']);
                $sheet3->setCellValue('E' . $row_3, $dt3['rd_data4']);
                $sheet3->setCellValue('F' . $row_3, $dt3['rd_data5']);
                $sheet3->setCellValue('G' . $row_3, $dt3['rd_data6']);
                $sheet3->setCellValue('H' . $row_3, $dt3['rd_data7']);
                $sheet3->setCellValue('I' . $row_3, $dt3['rd_data8']);
                $row_3++;
            }
        }

        $spreadsheet->createSheet();
        $sheet4 = $spreadsheet->getSheet(3);

        $sheet4->setTitle('국내운송료');
        $sheet4->mergeCells('A1:B1');
        $sheet4->mergeCells('A2:A4');
        $sheet4->setCellValue('A1', '항목');
        $sheet4->setCellValue('A2', '운송');
        $sheet4->setCellValue('C1', '단위');
        $sheet4->setCellValue('D1', '단가');
        $sheet4->setCellValue('E1', '건수');
        $sheet4->setCellValue('F1', '공급가');
        $sheet4->setCellValue('G1', '부가세');
        $sheet4->setCellValue('H1', '합계');
        $sheet4->setCellValue('I1', '비고');

        $row_4 = 2;
        if (!empty($data_sheet4)) {
            foreach ($data_sheet4 as $dt4) {
                if ($row_4 < 5) {
                    switch ($row_4) {
                        case 2:
                            $sheet4->setCellValue('B' . $row_4, '픽업료');
                            break;
                        case 3:
                            $sheet4->setCellValue('B' . $row_4, '배차(내륙운송)');
                            break;
                        case 4:
                            $sheet4->setCellValue('B' . $row_4, '국내 택배료');
                            break;
                        default:
                            break;
                    }
                } else {
                    $sheet4->setCellValue('B' . $row_4, $dt4['rd_cate2']);
                }
                $sheet4->setCellValue('C' . $row_4, $dt4['rd_data1']);
                $sheet4->setCellValue('D' . $row_4, $dt4['rd_data2']);
                $sheet4->setCellValue('E' . $row_4, $dt4['rd_data4']);
                $sheet4->setCellValue('F' . $row_4, $dt4['rd_data5']);
                $sheet4->setCellValue('G' . $row_4, $dt4['rd_data6']);
                $sheet4->setCellValue('H' . $row_4, $dt4['rd_data7']);
                $sheet4->setCellValue('I' . $row_4, $dt4['rd_data8']);
                $row_4++;
            }
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Rate-Data-CaseBill-Final-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Rate-Data-CaseBill-Final-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }

    public function download_final_month_bill($rgd_no)
    {
        DB::beginTransaction();
        $rdg = RateDataGeneral::where('rgd_no_expectation', $rgd_no)->where('rdg_bill_type', 'final_monthly')->first();

        if (!isset($rdg->rdg_no)) {
            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'expectation_monthly')->first();
        }
        DB::commit();
        $user = Auth::user();

        $data_sheet4 = $data_sheet3 = $data_sheet2 = $rate_data = array();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        if ($rgd_no) {
            $rmd_no = $this->get_rmd_no_raw($rgd_no, 'work_monthly_final');
            $rate_data = $this->get_rate_data_raw($rmd_no);

            $rmd_no_domestic = $this->get_rmd_no_raw($rgd_no, 'domestic_monthly_final');
            $rate_data_domestic = !empty($rmd_no_domestic) ? $this->get_rate_data_raw($rmd_no_domestic) : array();
            $data_sheet4 = !empty($rate_data_domestic) ? json_decode($rate_data_domestic, 1) : array();
            $supply_price = array_sum(array_column($data_sheet4, 'rd_data5'));
            $vat_price = array_sum(array_column($data_sheet4, 'rd_data6'));
            $sum_price = array_sum(array_column($data_sheet4, 'rd_data7'));

            $rmd_no_storage = $this->get_rmd_no_raw($rgd_no, 'storage_monthly_final');
            $rate_data_storage = $this->get_rate_data_raw($rmd_no_storage);
        }

        $sheet->setTitle('종합');

        $sheet->setCellValue('A1', '종합');
        $sheet->setCellValue('B1', '공급가');
        $sheet->setCellValue('C1', '부가세');
        $sheet->setCellValue('D1', '합계');
        $sheet->setCellValue('E1', '비고');

        $sheet->setCellValue('A2', '유통가공 작업료');
        $sheet->setCellValue('B2', $rdg['rdg_supply_price2']);
        $sheet->setCellValue('C2', $rdg['rdg_vat2']);
        $sheet->setCellValue('D2', $rdg['rdg_sum2']);
        $sheet->setCellValue('E2', $rdg['rdg_etc2']);

        $sheet->setCellValue('A3', '부자재 보관료');
        $sheet->setCellValue('B3', $rdg['rdg_supply_price1']);
        $sheet->setCellValue('C3', $rdg['rdg_vat1']);
        $sheet->setCellValue('D3', $rdg['rdg_sum1']);
        $sheet->setCellValue('E3', $rdg['rdg_etc1']);

        $sheet->setCellValue('A4', '국내운송료');
        $sheet->setCellValue('B4', $supply_price);
        $sheet->setCellValue('C4', $vat_price);
        $sheet->setCellValue('D4', $sum_price);
        $sheet->setCellValue('E4', $rdg['rdg_etc3']);

        $sheet->setCellValue('A5', '합계');
        $sheet->setCellValue('B5', $rdg['rdg_supply_price4']);
        $sheet->setCellValue('C5', $rdg['rdg_vat4']);
        $sheet->setCellValue('D5', $rdg['rdg_sum4']);
        $sheet->setCellValue('E5', $rdg['rdg_etc4']);

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);

        $sheet2->setTitle('작업료');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('A1', '항목');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', '건수');
        $sheet2->setCellValue('F1', '공급가');
        $sheet2->setCellValue('G1', '부가세');
        $sheet2->setCellValue('H1', '합계');
        $sheet2->setCellValue('I1', '비고');

        $row_2 = 2;
        if (!empty($rate_data)) {
            $data_sheet2 = json_decode($rate_data, 1);
            foreach ($data_sheet2 as $dt2) {
                $sheet2->setCellValue('A' . $row_2, $dt2['rd_cate1']);
                $sheet2->setCellValue('B' . $row_2, $dt2['rd_cate2']);
                $sheet2->setCellValue('C' . $row_2, $dt2['rd_data1']);
                $sheet2->setCellValue('D' . $row_2, $dt2['rd_data2']);
                $sheet2->setCellValue('E' . $row_2, $dt2['rd_data4']);
                $sheet2->setCellValue('F' . $row_2, $dt2['rd_data5']);
                $sheet2->setCellValue('G' . $row_2, $dt2['rd_data6']);
                $sheet2->setCellValue('H' . $row_2, $dt2['rd_data7']);
                $sheet2->setCellValue('I' . $row_2, $dt2['rd_data8']);
                $row_2++;
            }
        }

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);

        $sheet3->setTitle('보관료');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('A1', '항목');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', '건수');
        $sheet3->setCellValue('F1', '공급가');
        $sheet3->setCellValue('G1', '부가세');
        $sheet3->setCellValue('H1', '합계');
        $sheet3->setCellValue('I1', '비고');
        $row_3 = 2;
        if (!empty($rate_data_storage)) {
            $data_sheet3 = json_decode($rate_data_storage, 1);
            foreach ($data_sheet3 as $dt3) {
                $sheet3->setCellValue('A' . $row_3, $dt3['rd_cate1']);
                $sheet3->setCellValue('B' . $row_3, $dt3['rd_cate2']);
                $sheet3->setCellValue('C' . $row_3, $dt3['rd_data1']);
                $sheet3->setCellValue('D' . $row_3, $dt3['rd_data2']);
                $sheet3->setCellValue('E' . $row_3, $dt3['rd_data4']);
                $sheet3->setCellValue('F' . $row_3, $dt3['rd_data5']);
                $sheet3->setCellValue('G' . $row_3, $dt3['rd_data6']);
                $sheet3->setCellValue('H' . $row_3, $dt3['rd_data7']);
                $sheet3->setCellValue('I' . $row_3, $dt3['rd_data8']);
                $row_3++;
            }
        }

        $spreadsheet->createSheet();
        $sheet4 = $spreadsheet->getSheet(3);

        $sheet4->setTitle('국내운송료');
        $sheet4->mergeCells('A1:B1');
        $sheet4->mergeCells('A2:A4');
        $sheet4->setCellValue('A1', '항목');
        $sheet4->setCellValue('A2', '운송');
        $sheet4->setCellValue('C1', '단위');
        $sheet4->setCellValue('D1', '단가');
        $sheet4->setCellValue('E1', '건수');
        $sheet4->setCellValue('F1', '공급가');
        $sheet4->setCellValue('G1', '부가세');
        $sheet4->setCellValue('H1', '합계');
        $sheet4->setCellValue('I1', '비고');

        $row_4 = 2;
        if (!empty($data_sheet4)) {
            foreach ($data_sheet4 as $dt4) {
                if ($row_4 < 5) {
                    switch ($row_4) {
                        case 2:
                            $sheet4->setCellValue('B' . $row_4, '픽업료');
                            break;
                        case 3:
                            $sheet4->setCellValue('B' . $row_4, '배차(내륙운송)');
                            break;
                        case 4:
                            $sheet4->setCellValue('B' . $row_4, '국내 택배료');
                            break;
                        default:
                            break;
                    }
                } else {
                    $sheet4->setCellValue('B' . $row_4, $dt4['rd_cate2']);
                }
                $sheet4->setCellValue('C' . $row_4, $dt4['rd_data1']);
                $sheet4->setCellValue('D' . $row_4, $dt4['rd_data2']);
                $sheet4->setCellValue('E' . $row_4, $dt4['rd_data4']);
                $sheet4->setCellValue('F' . $row_4, $dt4['rd_data5']);
                $sheet4->setCellValue('G' . $row_4, $dt4['rd_data6']);
                $sheet4->setCellValue('H' . $row_4, $dt4['rd_data7']);
                $sheet4->setCellValue('I' . $row_4, $dt4['rd_data8']);
                $row_4++;
            }
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Rate-Data-Final-Month-Bill-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Rate-Data-Final-Month-Bill-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }
    public function download_est_month_bill($rgd_no)
    {
        DB::beginTransaction();
        $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();
        DB::commit();
        $user = Auth::user();

        $data_sheet4 = $data_sheet3 = $data_sheet2 = $rate_data = array();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        if ($rgd_no) {
            $rmd_no = $this->get_rmd_no_raw($rgd_no, 'work_monthly');
            $rate_data = $this->get_rate_data_raw($rmd_no);

            $rmd_no_domestic = $this->get_rmd_no_raw($rgd_no, 'domestic_monthly');
            $rate_data_domestic = !empty($rmd_no_domestic) ? $this->get_rate_data_raw($rmd_no_domestic) : array();
            $data_sheet4 = !empty($rate_data_domestic) ? json_decode($rate_data_domestic, 1) : array();
            $supply_price = array_sum(array_column($data_sheet4, 'rd_data5'));
            $vat_price = array_sum(array_column($data_sheet4, 'rd_data6'));
            $sum_price = array_sum(array_column($data_sheet4, 'rd_data7'));

            $rmd_no_storage = $this->get_rmd_no_raw($rgd_no, 'storage_monthly');
            $rate_data_storage = $this->get_rate_data_raw($rmd_no_storage);
        }

        $sheet->setTitle('종합');

        $sheet->setCellValue('A1', '종합');
        $sheet->setCellValue('B1', '공급가');
        $sheet->setCellValue('C1', '부가세');
        $sheet->setCellValue('D1', '합계');
        $sheet->setCellValue('E1', '비고');

        $sheet->setCellValue('A2', '유통가공 작업료');
        $sheet->setCellValue('B2', $rdg['rdg_supply_price2']);
        $sheet->setCellValue('C2', $rdg['rdg_vat2']);
        $sheet->setCellValue('D2', $rdg['rdg_sum2']);
        $sheet->setCellValue('E2', $rdg['rdg_etc2']);

        $sheet->setCellValue('A3', '부자재 보관료');
        $sheet->setCellValue('B3', $rdg['rdg_supply_price1']);
        $sheet->setCellValue('C3', $rdg['rdg_vat1']);
        $sheet->setCellValue('D3', $rdg['rdg_sum1']);
        $sheet->setCellValue('E3', $rdg['rdg_etc1']);

        $sheet->setCellValue('A4', '국내운송료');
        $sheet->setCellValue('B4', $supply_price);
        $sheet->setCellValue('C4', $vat_price);
        $sheet->setCellValue('D4', $sum_price);
        $sheet->setCellValue('E4', $rdg['rdg_etc3']);

        $sheet->setCellValue('A5', '합계');
        $sheet->setCellValue('B5', $rdg['rdg_supply_price4']);
        $sheet->setCellValue('C5', $rdg['rdg_vat4']);
        $sheet->setCellValue('D5', $rdg['rdg_sum4']);
        $sheet->setCellValue('E5', $rdg['rdg_etc4']);

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);

        $sheet2->setTitle('작업료');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('A1', '항목');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', '건수');
        $sheet2->setCellValue('F1', '공급가');
        $sheet2->setCellValue('G1', '부가세');
        $sheet2->setCellValue('H1', '합계');
        $sheet2->setCellValue('I1', '비고');

        $row_2 = 2;
        if (!empty($rate_data)) {
            $data_sheet2 = json_decode($rate_data, 1);
            foreach ($data_sheet2 as $dt2) {
                $sheet2->setCellValue('A' . $row_2, $dt2['rd_cate1']);
                $sheet2->setCellValue('B' . $row_2, $dt2['rd_cate2']);
                $sheet2->setCellValue('C' . $row_2, $dt2['rd_data1']);
                $sheet2->setCellValue('D' . $row_2, $dt2['rd_data2']);
                $sheet2->setCellValue('E' . $row_2, $dt2['rd_data4']);
                $sheet2->setCellValue('F' . $row_2, $dt2['rd_data5']);
                $sheet2->setCellValue('G' . $row_2, $dt2['rd_data6']);
                $sheet2->setCellValue('H' . $row_2, $dt2['rd_data7']);
                $sheet2->setCellValue('I' . $row_2, $dt2['rd_data8']);
                $row_2++;
            }
        }

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);

        $sheet3->setTitle('보관료');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('A1', '항목');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', '건수');
        $sheet3->setCellValue('F1', '공급가');
        $sheet3->setCellValue('G1', '부가세');
        $sheet3->setCellValue('H1', '합계');
        $sheet3->setCellValue('I1', '비고');
        $row_3 = 2;
        if (!empty($rate_data_storage)) {
            $data_sheet3 = json_decode($rate_data_storage, 1);
            foreach ($data_sheet3 as $dt3) {
                $sheet3->setCellValue('A' . $row_3, $dt3['rd_cate1']);
                $sheet3->setCellValue('B' . $row_3, $dt3['rd_cate2']);
                $sheet3->setCellValue('C' . $row_3, $dt3['rd_data1']);
                $sheet3->setCellValue('D' . $row_3, $dt3['rd_data2']);
                $sheet3->setCellValue('E' . $row_3, $dt3['rd_data4']);
                $sheet3->setCellValue('F' . $row_3, $dt3['rd_data5']);
                $sheet3->setCellValue('G' . $row_3, $dt3['rd_data6']);
                $sheet3->setCellValue('H' . $row_3, $dt3['rd_data7']);
                $sheet3->setCellValue('I' . $row_3, $dt3['rd_data8']);
                $row_3++;
            }
        }

        $spreadsheet->createSheet();
        $sheet4 = $spreadsheet->getSheet(3);

        $sheet4->setTitle('국내운송료');
        $sheet4->mergeCells('A1:B1');
        $sheet4->mergeCells('A2:A4');
        $sheet4->setCellValue('A1', '항목');
        $sheet4->setCellValue('A2', '운송');
        $sheet4->setCellValue('C1', '단위');
        $sheet4->setCellValue('D1', '단가');
        $sheet4->setCellValue('E1', '건수');
        $sheet4->setCellValue('F1', '공급가');
        $sheet4->setCellValue('G1', '부가세');
        $sheet4->setCellValue('H1', '합계');
        $sheet4->setCellValue('I1', '비고');

        $row_4 = 2;
        if (!empty($data_sheet4)) {
            foreach ($data_sheet4 as $dt4) {
                if ($row_4 < 5) {
                    switch ($row_4) {
                        case 2:
                            $sheet4->setCellValue('B' . $row_4, '픽업료');
                            break;
                        case 3:
                            $sheet4->setCellValue('B' . $row_4, '배차(내륙운송)');
                            break;
                        case 4:
                            $sheet4->setCellValue('B' . $row_4, '국내 택배료');
                            break;
                        default:
                            break;
                    }
                } else {
                    $sheet4->setCellValue('B' . $row_4, $dt4['rd_cate2']);
                }
                $sheet4->setCellValue('C' . $row_4, $dt4['rd_data1']);
                $sheet4->setCellValue('D' . $row_4, $dt4['rd_data2']);
                $sheet4->setCellValue('E' . $row_4, $dt4['rd_data4']);
                $sheet4->setCellValue('F' . $row_4, $dt4['rd_data5']);
                $sheet4->setCellValue('G' . $row_4, $dt4['rd_data6']);
                $sheet4->setCellValue('H' . $row_4, $dt4['rd_data7']);
                $sheet4->setCellValue('I' . $row_4, $dt4['rd_data8']);
                $row_4++;
            }
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Rate-Data-Est-Monthbill-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Rate-Data-Est-Monthbill-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }
    public function download_add_month_bill($rgd_no)
    {
        DB::beginTransaction();
        $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();
        DB::commit();
        $user = Auth::user();

        $data_sheet4 = $data_sheet3 = $data_sheet2 = $rate_data = array();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        if ($rgd_no) {
            $rmd_no = $this->get_rmd_no_raw($rgd_no, 'work_monthly_additional');
            $rate_data = $this->get_rate_data_raw($rmd_no);

            $rmd_no_domestic = $this->get_rmd_no_raw($rgd_no, 'domestic_monthly_additional');
            $rate_data_domestic = !empty($rmd_no_domestic) ? $this->get_rate_data_raw($rmd_no_domestic) : array();
            $data_sheet4 = !empty($rate_data_domestic) ? json_decode($rate_data_domestic, 1) : array();
            $supply_price = array_sum(array_column($data_sheet4, 'rd_data5'));
            $vat_price = array_sum(array_column($data_sheet4, 'rd_data6'));
            $sum_price = array_sum(array_column($data_sheet4, 'rd_data7'));

            $rmd_no_storage = $this->get_rmd_no_raw($rgd_no, 'storage_monthly_additional');
            $rate_data_storage = $this->get_rate_data_raw($rmd_no_storage);
        }

        $sheet->setTitle('종합');

        $sheet->setCellValue('A1', '종합');
        $sheet->setCellValue('B1', '공급가');
        $sheet->setCellValue('C1', '부가세');
        $sheet->setCellValue('D1', '합계');
        $sheet->setCellValue('E1', '비고');

        $sheet->setCellValue('A2', '유통가공 작업료');
        $sheet->setCellValue('B2', $rdg['rdg_supply_price2']);
        $sheet->setCellValue('C2', $rdg['rdg_vat2']);
        $sheet->setCellValue('D2', $rdg['rdg_sum2']);
        $sheet->setCellValue('E2', $rdg['rdg_etc2']);

        $sheet->setCellValue('A3', '부자재 보관료');
        $sheet->setCellValue('B3', $rdg['rdg_supply_price1']);
        $sheet->setCellValue('C3', $rdg['rdg_vat1']);
        $sheet->setCellValue('D3', $rdg['rdg_sum1']);
        $sheet->setCellValue('E3', $rdg['rdg_etc1']);

        $sheet->setCellValue('A4', '국내운송료');
        $sheet->setCellValue('B4', $supply_price);
        $sheet->setCellValue('C4', $vat_price);
        $sheet->setCellValue('D4', $sum_price);
        $sheet->setCellValue('E4', $rdg['rdg_etc3']);

        $sheet->setCellValue('A5', '합계');
        $sheet->setCellValue('B5', $rdg['rdg_supply_price4']);
        $sheet->setCellValue('C5', $rdg['rdg_vat4']);
        $sheet->setCellValue('D5', $rdg['rdg_sum4']);
        $sheet->setCellValue('E5', $rdg['rdg_etc4']);

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);

        $sheet2->setTitle('작업료');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('A1', '항목');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', '건수');
        $sheet2->setCellValue('F1', '공급가');
        $sheet2->setCellValue('G1', '부가세');
        $sheet2->setCellValue('H1', '합계');
        $sheet2->setCellValue('I1', '비고');

        $row_2 = 2;
        if (!empty($rate_data)) {
            $data_sheet2 = json_decode($rate_data, 1);
            foreach ($data_sheet2 as $dt2) {
                $sheet2->setCellValue('A' . $row_2, $dt2['rd_cate1']);
                $sheet2->setCellValue('B' . $row_2, $dt2['rd_cate2']);
                $sheet2->setCellValue('C' . $row_2, $dt2['rd_data1']);
                $sheet2->setCellValue('D' . $row_2, $dt2['rd_data2']);
                $sheet2->setCellValue('E' . $row_2, $dt2['rd_data4']);
                $sheet2->setCellValue('F' . $row_2, $dt2['rd_data5']);
                $sheet2->setCellValue('G' . $row_2, $dt2['rd_data6']);
                $sheet2->setCellValue('H' . $row_2, $dt2['rd_data7']);
                $sheet2->setCellValue('I' . $row_2, $dt2['rd_data8']);
                $row_2++;
            }
        }

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);

        $sheet3->setTitle('보관료');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('A1', '항목');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', '건수');
        $sheet3->setCellValue('F1', '공급가');
        $sheet3->setCellValue('G1', '부가세');
        $sheet3->setCellValue('H1', '합계');
        $sheet3->setCellValue('I1', '비고');
        $row_3 = 2;
        if (!empty($rate_data_storage)) {
            $data_sheet3 = json_decode($rate_data_storage, 1);
            foreach ($data_sheet3 as $dt3) {
                $sheet3->setCellValue('A' . $row_3, $dt3['rd_cate1']);
                $sheet3->setCellValue('B' . $row_3, $dt3['rd_cate2']);
                $sheet3->setCellValue('C' . $row_3, $dt3['rd_data1']);
                $sheet3->setCellValue('D' . $row_3, $dt3['rd_data2']);
                $sheet3->setCellValue('E' . $row_3, $dt3['rd_data4']);
                $sheet3->setCellValue('F' . $row_3, $dt3['rd_data5']);
                $sheet3->setCellValue('G' . $row_3, $dt3['rd_data6']);
                $sheet3->setCellValue('H' . $row_3, $dt3['rd_data7']);
                $sheet3->setCellValue('I' . $row_3, $dt3['rd_data8']);
                $row_3++;
            }
        }

        $spreadsheet->createSheet();
        $sheet4 = $spreadsheet->getSheet(3);

        $sheet4->setTitle('국내운송료');
        $sheet4->mergeCells('A1:B1');
        $sheet4->mergeCells('A2:A4');
        $sheet4->setCellValue('A1', '항목');
        $sheet4->setCellValue('A2', '운송');
        $sheet4->setCellValue('C1', '단위');
        $sheet4->setCellValue('D1', '단가');
        $sheet4->setCellValue('E1', '건수');
        $sheet4->setCellValue('F1', '공급가');
        $sheet4->setCellValue('G1', '부가세');
        $sheet4->setCellValue('H1', '합계');
        $sheet4->setCellValue('I1', '비고');

        $row_4 = 2;
        if (!empty($data_sheet4)) {
            foreach ($data_sheet4 as $dt4) {
                if ($row_4 < 5) {
                    switch ($row_4) {
                        case 2:
                            $sheet4->setCellValue('B' . $row_4, '픽업료');
                            break;
                        case 3:
                            $sheet4->setCellValue('B' . $row_4, '배차(내륙운송)');
                            break;
                        case 4:
                            $sheet4->setCellValue('B' . $row_4, '국내 택배료');
                            break;
                        default:
                            break;
                    }
                } else {
                    $sheet4->setCellValue('B' . $row_4, $dt4['rd_cate2']);
                }
                $sheet4->setCellValue('C' . $row_4, $dt4['rd_data1']);
                $sheet4->setCellValue('D' . $row_4, $dt4['rd_data2']);
                $sheet4->setCellValue('E' . $row_4, $dt4['rd_data4']);
                $sheet4->setCellValue('F' . $row_4, $dt4['rd_data5']);
                $sheet4->setCellValue('G' . $row_4, $dt4['rd_data6']);
                $sheet4->setCellValue('H' . $row_4, $dt4['rd_data7']);
                $sheet4->setCellValue('I' . $row_4, $dt4['rd_data8']);
                $row_4++;
            }
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Rate-Data-Add-Month-Bill-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Rate-Data-Add-Month-Bill-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }
    public function download_est_month_check($rgd_no)
    {
        DB::beginTransaction();
        $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();
        DB::commit();
        $user = Auth::user();

        $data_sheet4 = $data_sheet3 = $data_sheet2 = $rate_data = array();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        if ($rgd_no) {
            $rmd_no = $this->get_rmd_no_raw($rgd_no, 'work_monthly');
            $rate_data = $this->get_rate_data_raw($rmd_no);

            $rmd_no_domestic = $this->get_rmd_no_raw($rgd_no, 'domestic_monthly');
            $rate_data_domestic = !empty($rmd_no_domestic) ? $this->get_rate_data_raw($rmd_no_domestic) : array();
            $data_sheet4 = !empty($rate_data_domestic) ? json_decode($rate_data_domestic, 1) : array();
            $supply_price = array_sum(array_column($data_sheet4, 'rd_data5'));
            $vat_price = array_sum(array_column($data_sheet4, 'rd_data6'));
            $sum_price = array_sum(array_column($data_sheet4, 'rd_data7'));

            $rmd_no_storage = $this->get_rmd_no_raw($rgd_no, 'storage_monthly');
            $rate_data_storage = $this->get_rate_data_raw($rmd_no_storage);
        }

        $sheet->setTitle('종합');

        $sheet->setCellValue('A1', '종합');
        $sheet->setCellValue('B1', '공급가');
        $sheet->setCellValue('C1', '부가세');
        $sheet->setCellValue('D1', '합계');
        $sheet->setCellValue('E1', '비고');

        $sheet->setCellValue('A2', '유통가공 작업료');
        $sheet->setCellValue('B2', $rdg['rdg_supply_price2']);
        $sheet->setCellValue('C2', $rdg['rdg_vat2']);
        $sheet->setCellValue('D2', $rdg['rdg_sum2']);
        $sheet->setCellValue('E2', $rdg['rdg_etc2']);

        $sheet->setCellValue('A3', '부자재 보관료');
        $sheet->setCellValue('B3', $rdg['rdg_supply_price1']);
        $sheet->setCellValue('C3', $rdg['rdg_vat1']);
        $sheet->setCellValue('D3', $rdg['rdg_sum1']);
        $sheet->setCellValue('E3', $rdg['rdg_etc1']);

        $sheet->setCellValue('A4', '국내운송료');
        $sheet->setCellValue('B4', $supply_price);
        $sheet->setCellValue('C4', $vat_price);
        $sheet->setCellValue('D4', $sum_price);
        $sheet->setCellValue('E4', $rdg['rdg_etc3']);

        $sheet->setCellValue('A5', '합계');
        $sheet->setCellValue('B5', $rdg['rdg_supply_price4']);
        $sheet->setCellValue('C5', $rdg['rdg_vat4']);
        $sheet->setCellValue('D5', $rdg['rdg_sum4']);
        $sheet->setCellValue('E5', $rdg['rdg_etc4']);

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);

        $sheet2->setTitle('작업료');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('A1', '항목');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', '건수');
        $sheet2->setCellValue('F1', '공급가');
        $sheet2->setCellValue('G1', '부가세');
        $sheet2->setCellValue('H1', '합계');
        $sheet2->setCellValue('I1', '비고');

        $row_2 = 2;
        if (!empty($rate_data)) {
            $data_sheet2 = json_decode($rate_data, 1);
            foreach ($data_sheet2 as $dt2) {
                $sheet2->setCellValue('A' . $row_2, $dt2['rd_cate1']);
                $sheet2->setCellValue('B' . $row_2, $dt2['rd_cate2']);
                $sheet2->setCellValue('C' . $row_2, $dt2['rd_data1']);
                $sheet2->setCellValue('D' . $row_2, $dt2['rd_data2']);
                $sheet2->setCellValue('E' . $row_2, $dt2['rd_data4']);
                $sheet2->setCellValue('F' . $row_2, $dt2['rd_data5']);
                $sheet2->setCellValue('G' . $row_2, $dt2['rd_data6']);
                $sheet2->setCellValue('H' . $row_2, $dt2['rd_data7']);
                $sheet2->setCellValue('I' . $row_2, $dt2['rd_data8']);
                $row_2++;
            }
        }

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);

        $sheet3->setTitle('보관료');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('A1', '항목');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', '건수');
        $sheet3->setCellValue('F1', '공급가');
        $sheet3->setCellValue('G1', '부가세');
        $sheet3->setCellValue('H1', '합계');
        $sheet3->setCellValue('I1', '비고');
        $row_3 = 2;
        if (!empty($rate_data_storage)) {
            $data_sheet3 = json_decode($rate_data_storage, 1);
            foreach ($data_sheet3 as $dt3) {
                $sheet3->setCellValue('A' . $row_3, $dt3['rd_cate1']);
                $sheet3->setCellValue('B' . $row_3, $dt3['rd_cate2']);
                $sheet3->setCellValue('C' . $row_3, $dt3['rd_data1']);
                $sheet3->setCellValue('D' . $row_3, $dt3['rd_data2']);
                $sheet3->setCellValue('E' . $row_3, $dt3['rd_data4']);
                $sheet3->setCellValue('F' . $row_3, $dt3['rd_data5']);
                $sheet3->setCellValue('G' . $row_3, $dt3['rd_data6']);
                $sheet3->setCellValue('H' . $row_3, $dt3['rd_data7']);
                $sheet3->setCellValue('I' . $row_3, $dt3['rd_data8']);
                $row_3++;
            }
        }

        $spreadsheet->createSheet();
        $sheet4 = $spreadsheet->getSheet(3);

        $sheet4->setTitle('국내운송료');
        $sheet4->mergeCells('A1:B1');
        $sheet4->mergeCells('A2:A4');
        $sheet4->setCellValue('A1', '항목');
        $sheet4->setCellValue('A2', '운송');
        $sheet4->setCellValue('C1', '단위');
        $sheet4->setCellValue('D1', '단가');
        $sheet4->setCellValue('E1', '건수');
        $sheet4->setCellValue('F1', '공급가');
        $sheet4->setCellValue('G1', '부가세');
        $sheet4->setCellValue('H1', '합계');
        $sheet4->setCellValue('I1', '비고');

        $row_4 = 2;
        if (!empty($data_sheet4)) {
            foreach ($data_sheet4 as $dt4) {
                if ($row_4 < 5) {
                    switch ($row_4) {
                        case 2:
                            $sheet4->setCellValue('B' . $row_4, '픽업료');
                            break;
                        case 3:
                            $sheet4->setCellValue('B' . $row_4, '배차(내륙운송)');
                            break;
                        case 4:
                            $sheet4->setCellValue('B' . $row_4, '국내 택배료');
                            break;
                        default:
                            break;
                    }
                } else {
                    $sheet4->setCellValue('B' . $row_4, $dt4['rd_cate2']);
                }
                $sheet4->setCellValue('C' . $row_4, $dt4['rd_data1']);
                $sheet4->setCellValue('D' . $row_4, $dt4['rd_data2']);
                $sheet4->setCellValue('E' . $row_4, $dt4['rd_data4']);
                $sheet4->setCellValue('F' . $row_4, $dt4['rd_data5']);
                $sheet4->setCellValue('G' . $row_4, $dt4['rd_data6']);
                $sheet4->setCellValue('H' . $row_4, $dt4['rd_data7']);
                $sheet4->setCellValue('I' . $row_4, $dt4['rd_data8']);
                $row_4++;
            }
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Rate-Data-Est-Month-Bill-Check-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Rate-Data-Est-Month-Bill-Check-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }
    public function download_distribution_final($rgd_no)
    {
        DB::beginTransaction();
        $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();
        DB::commit();
        $user = Auth::user();

        $data_sheet4 = $data_sheet3 = $data_sheet2 = $rate_data = array();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        if ($rgd_no) {
            $rmd_no = $this->get_rmd_no_raw($rgd_no, 'work_final');
            $rate_data = $this->get_rate_data_raw($rmd_no);

            $rmd_no_domestic = $this->get_rmd_no_raw($rgd_no, 'domestic_final');
            $rate_data_domestic = !empty($rmd_no_domestic) ? $this->get_rate_data_raw($rmd_no_domestic) : array();
            $data_sheet4 = !empty($rate_data_domestic) ? json_decode($rate_data_domestic, 1) : array();
            $supply_price = array_sum(array_column($data_sheet4, 'rd_data5'));
            $vat_price = array_sum(array_column($data_sheet4, 'rd_data6'));
            $sum_price = array_sum(array_column($data_sheet4, 'rd_data7'));

            $rmd_no_storage = $this->get_rmd_no_raw($rgd_no, 'storage_final');
            $rate_data_storage = $this->get_rate_data_raw($rmd_no_storage);
        }

        $sheet->setTitle('종합');

        $sheet->setCellValue('A1', '종합');
        $sheet->setCellValue('B1', '공급가');
        $sheet->setCellValue('C1', '부가세');
        $sheet->setCellValue('D1', '합계');
        $sheet->setCellValue('E1', '비고');

        $sheet->setCellValue('A2', '유통가공 작업료');
        $sheet->setCellValue('B2', $rdg['rdg_supply_price2']);
        $sheet->setCellValue('C2', $rdg['rdg_vat2']);
        $sheet->setCellValue('D2', $rdg['rdg_sum2']);
        $sheet->setCellValue('E2', $rdg['rdg_etc2']);

        $sheet->setCellValue('A3', '부자재 보관료');
        $sheet->setCellValue('B3', $rdg['rdg_supply_price1']);
        $sheet->setCellValue('C3', $rdg['rdg_vat1']);
        $sheet->setCellValue('D3', $rdg['rdg_sum1']);
        $sheet->setCellValue('E3', $rdg['rdg_etc1']);

        $sheet->setCellValue('A4', '국내운송료');
        $sheet->setCellValue('B4', $supply_price);
        $sheet->setCellValue('C4', $vat_price);
        $sheet->setCellValue('D4', $sum_price);
        $sheet->setCellValue('E4', $rdg['rdg_etc3']);

        $sheet->setCellValue('A5', '합계');
        $sheet->setCellValue('B5', $rdg['rdg_supply_price4']);
        $sheet->setCellValue('C5', $rdg['rdg_vat4']);
        $sheet->setCellValue('D5', $rdg['rdg_sum4']);
        $sheet->setCellValue('E5', $rdg['rdg_etc4']);

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);

        $sheet2->setTitle('작업료');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('A1', '항목');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', '건수');
        $sheet2->setCellValue('F1', '공급가');
        $sheet2->setCellValue('G1', '부가세');
        $sheet2->setCellValue('H1', '합계');
        $sheet2->setCellValue('I1', '비고');

        $row_2 = 2;
        if (!empty($rate_data)) {
            $data_sheet2 = json_decode($rate_data, 1);
            foreach ($data_sheet2 as $dt2) {
                $sheet2->setCellValue('A' . $row_2, $dt2['rd_cate1']);
                $sheet2->setCellValue('B' . $row_2, $dt2['rd_cate2']);
                $sheet2->setCellValue('C' . $row_2, $dt2['rd_data1']);
                $sheet2->setCellValue('D' . $row_2, $dt2['rd_data2']);
                $sheet2->setCellValue('E' . $row_2, $dt2['rd_data4']);
                $sheet2->setCellValue('F' . $row_2, $dt2['rd_data5']);
                $sheet2->setCellValue('G' . $row_2, $dt2['rd_data6']);
                $sheet2->setCellValue('H' . $row_2, $dt2['rd_data7']);
                $sheet2->setCellValue('I' . $row_2, $dt2['rd_data8']);
                $row_2++;
            }
        }

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);

        $sheet3->setTitle('보관료');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('A1', '항목');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', '건수');
        $sheet3->setCellValue('F1', '공급가');
        $sheet3->setCellValue('G1', '부가세');
        $sheet3->setCellValue('H1', '합계');
        $sheet3->setCellValue('I1', '비고');
        $row_3 = 2;
        if (!empty($rate_data_storage)) {
            $data_sheet3 = json_decode($rate_data_storage, 1);
            foreach ($data_sheet3 as $dt3) {
                $sheet3->setCellValue('A' . $row_3, $dt3['rd_cate1']);
                $sheet3->setCellValue('B' . $row_3, $dt3['rd_cate2']);
                $sheet3->setCellValue('C' . $row_3, $dt3['rd_data1']);
                $sheet3->setCellValue('D' . $row_3, $dt3['rd_data2']);
                $sheet3->setCellValue('E' . $row_3, $dt3['rd_data4']);
                $sheet3->setCellValue('F' . $row_3, $dt3['rd_data5']);
                $sheet3->setCellValue('G' . $row_3, $dt3['rd_data6']);
                $sheet3->setCellValue('H' . $row_3, $dt3['rd_data7']);
                $sheet3->setCellValue('I' . $row_3, $dt3['rd_data8']);
                $row_3++;
            }
        }

        $spreadsheet->createSheet();
        $sheet4 = $spreadsheet->getSheet(3);

        $sheet4->setTitle('국내운송료');
        $sheet4->mergeCells('A1:B1');
        $sheet4->mergeCells('A2:A4');
        $sheet4->setCellValue('A1', '항목');
        $sheet4->setCellValue('A2', '운송');
        $sheet4->setCellValue('C1', '단위');
        $sheet4->setCellValue('D1', '단가');
        $sheet4->setCellValue('E1', '건수');
        $sheet4->setCellValue('F1', '공급가');
        $sheet4->setCellValue('G1', '부가세');
        $sheet4->setCellValue('H1', '합계');
        $sheet4->setCellValue('I1', '비고');

        $row_4 = 2;
        if (!empty($data_sheet4)) {
            foreach ($data_sheet4 as $dt4) {
                if ($row_4 < 5) {
                    switch ($row_4) {
                        case 2:
                            $sheet4->setCellValue('B' . $row_4, '픽업료');
                            break;
                        case 3:
                            $sheet4->setCellValue('B' . $row_4, '배차(내륙운송)');
                            break;
                        case 4:
                            $sheet4->setCellValue('B' . $row_4, '국내 택배료');
                            break;
                        default:
                            break;
                    }
                } else {
                    $sheet4->setCellValue('B' . $row_4, $dt4['rd_cate2']);
                }
                $sheet4->setCellValue('C' . $row_4, $dt4['rd_data1']);
                $sheet4->setCellValue('D' . $row_4, $dt4['rd_data2']);
                $sheet4->setCellValue('E' . $row_4, $dt4['rd_data4']);
                $sheet4->setCellValue('F' . $row_4, $dt4['rd_data5']);
                $sheet4->setCellValue('G' . $row_4, $dt4['rd_data6']);
                $sheet4->setCellValue('H' . $row_4, $dt4['rd_data7']);
                $sheet4->setCellValue('I' . $row_4, $dt4['rd_data8']);
                $row_4++;
            }
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Rate-Data-Est-Month-Bill-Check-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Rate-Data-Est-Month-Bill-Check-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }
    public function download_excel_send_meta($rm_no, $rmd_no)
    {
        DB::beginTransaction();
        $co_no = Auth::user()->co_no;
        $rate_data_send_meta = $this->getRateDataRaw($rm_no, $rmd_no);
        DB::commit();
        $user = Auth::user();

        $data_sheet3 = $data_sheet2 = $rate_data = array();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        $sheet->setTitle('보세화물');

        $sheet->setCellValue('A1', '창고화물');
        $sheet->setCellValue('H1', '온도화물');
        $sheet->setCellValue('O1', '위험물');
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('H1:L1');
        $sheet->mergeCells('O1:S1');

        $sheet->setCellValue('A2', '구분');
        $sheet->setCellValue('B2', '내역');
        $sheet->mergeCells('B2:E2');
        $sheet->setCellValue('H2', '구분');
        $sheet->setCellValue('I2', '내역');
        $sheet->mergeCells('I2:L2');
        $sheet->setCellValue('O2', '구분');
        $sheet->setCellValue('P2', '내역');
        $sheet->mergeCells('P2:S2');

        $sheet->setCellValue('A3', '');
        $sheet->setCellValue('B3', '항목');
        $sheet->setCellValue('C3', '상세');
        $sheet->setCellValue('D3', '기본료');
        $sheet->setCellValue('E3', '단가/KG');

        $sheet->setCellValue('H3', '');
        $sheet->setCellValue('I3', '항목');
        $sheet->setCellValue('J3', '상세');
        $sheet->setCellValue('K3', '기본료');
        $sheet->setCellValue('L3', '단가/KG');

        $sheet->setCellValue('O3', '');
        $sheet->setCellValue('P3', '항목');
        $sheet->setCellValue('Q3', '상세');
        $sheet->setCellValue('R3', '기본료');
        $sheet->setCellValue('S3', '단가/KG');

        if (!empty($rate_data_send_meta['rate_data1'])) {
            $data_sheet1 = $rate_data_send_meta['rate_data1'];
            $sheet_row1 = 4;
            $sheet_row2 = 4;
            $sheet_row3 = 4;
            foreach ($data_sheet1 as $dt1) {
                if ($dt1['rd_cate_meta2'] == '창고화물') {
                    $sheet->setCellValue('A' . $sheet_row1, !empty($dt1['rd_cate1']) ? $dt1['rd_cate1'] : '');
                    $sheet->setCellValue('B' . $sheet_row1, !empty($dt1['rd_cate2']) ? $dt1['rd_cate2'] : '');
                    $sheet->setCellValue('C' . $sheet_row1, !empty($dt1['rd_cate3']) ? $dt1['rd_cate3'] : '');
                    $sheet->setCellValue('D' . $sheet_row1, !empty($dt1['rd_data1']) ? $dt1['rd_data1'] : '');
                    $sheet->setCellValue('E' . $sheet_row1, !empty($dt1['rd_data2']) ? $dt1['rd_data2'] : '');
                    $sheet_row1++;
                } else if ($dt1['rd_cate_meta2'] == '온도화물') {
                    $sheet->setCellValue('H' . $sheet_row2, !empty($dt1['rd_cate1']) ? $dt1['rd_cate1'] : '');
                    $sheet->setCellValue('I' . $sheet_row2, !empty($dt1['rd_cate2']) ? $dt1['rd_cate2'] : '');
                    $sheet->setCellValue('J' . $sheet_row2, !empty($dt1['rd_cate3']) ? $dt1['rd_cate3'] : '');
                    $sheet->setCellValue('K' . $sheet_row2, !empty($dt1['rd_data1']) ? $dt1['rd_data1'] : '');
                    $sheet->setCellValue('L' . $sheet_row2, !empty($dt1['rd_data2']) ? $dt1['rd_data2'] : '');
                    $sheet_row2++;
                } else if ($dt1['rd_cate_meta2'] == '위험물') {
                    $sheet->setCellValue('O' . $sheet_row3, !empty($dt1['rd_cate1']) ? $dt1['rd_cate1'] : '');
                    $sheet->setCellValue('P' . $sheet_row3, !empty($dt1['rd_cate2']) ? $dt1['rd_cate2'] : '');
                    $sheet->setCellValue('Q' . $sheet_row3, !empty($dt1['rd_cate3']) ? $dt1['rd_cate3'] : '');
                    $sheet->setCellValue('R' . $sheet_row3, !empty($dt1['rd_data1']) ? $dt1['rd_data1'] : '');
                    $sheet->setCellValue('S' . $sheet_row3, !empty($dt1['rd_data2']) ? $dt1['rd_data2'] : '');
                    $sheet_row3++;
                }
            }
        }

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);

        $sheet2->setTitle('수입풀필먼트');

        $sheet2->setCellValue('A1', '기준');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', 'ON/OFF');

        $sheet2_row = 2;
        if (!empty($rate_data_send_meta['rate_data2'])) {
            $data_sheet2 = $rate_data_send_meta['rate_data2'];
            foreach ($data_sheet2 as $dt2) {
                $sheet2->setCellValue('A' . $sheet2_row, !empty($dt2['rd_cate1']) ? $dt2['rd_cate1'] : '');
                $sheet2->setCellValue('B' . $sheet2_row, !empty($dt2['rd_cate2']) ? $dt2['rd_cate2'] : '');
                $sheet2->setCellValue('C' . $sheet2_row, !empty($dt2['rd_cate3']) ? $dt2['rd_cate3'] : '');
                $sheet2->setCellValue('D' . $sheet2_row, !empty($dt2['rd_data1']) ? $dt2['rd_data1'] : '');
                $sheet2->setCellValue('E' . $sheet2_row, !empty($dt2['rd_data2']) ? $dt2['rd_data2'] : '');
                $sheet2_row++;
            }
        }

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);

        $sheet3->setTitle('유통가공');

        $sheet3->setCellValue('A1', '기준');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', 'ON/OFF');

        $sheet3_row = 2;
        if (!empty($rate_data_send_meta['rate_data3'])) {
            $data_sheet3 = $rate_data_send_meta['rate_data3'];
            foreach ($data_sheet3 as $dt3) {
                $sheet3->setCellValue('A' . $sheet3_row, !empty($dt3['rd_cate1']) ? $dt3['rd_cate1'] : '');
                $sheet3->setCellValue('B' . $sheet3_row, !empty($dt3['rd_cate2']) ? $dt3['rd_cate2'] : '');
                $sheet3->setCellValue('C' . $sheet3_row, !empty($dt3['rd_cate3']) ? $dt3['rd_cate3'] : '');
                $sheet3->setCellValue('D' . $sheet3_row, !empty($dt3['rd_data1']) ? $dt3['rd_data1'] : '');
                $sheet3->setCellValue('E' . $sheet3_row, !empty($dt3['rd_data2']) ? $dt3['rd_data2'] : '');
                $sheet3_row++;
            }
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Excel-Quotation-Send-Details-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Excel-Quotation-Send-Details-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }

    public function download_final_monthbill_issue(Request $request)
    {
        $datas = $request->all();
        DB::beginTransaction();
        $co_no = Auth::user()->co_no;
        DB::commit();
        $user = Auth::user();

        $data_sheet3 = $data_sheet2 = $rate_data = array();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        $sheet->setTitle('보세화물');

        $sheet->setCellValue('A1', 'NO');
        $sheet->setCellValue('B1', '입고 화물번호');
        $sheet->setCellValue('C1', '출고일자');
        $sheet->setCellValue('D1', '공급가');
        $sheet->setCellValue('E1', '부가세');
        $sheet->setCellValue('F1', '합계');
        $sheet->setCellValue('G1', '작업료');
        $sheet->setCellValue('H1', '보관료');
        $sheet->setCellValue('I1', '국내운송료');
        $sheet->setCellValue('J1', '비고');

        if (!empty($datas)) {
            $sheet_row = 2;

            foreach ($datas as $data) {
                $data = (array) $data;
                $sheet->setCellValue('A' . $sheet_row, !empty($data['key']) ? $data['key'] : '');
                $sheet->setCellValue('B' . $sheet_row, !empty($data['w_schedule_number2']) ? $data['w_schedule_number2'] : '');
                $sheet->setCellValue('C' . $sheet_row, !empty($data['w_completed_day']) ? $data['w_completed_day'] : '');
                $sheet->setCellValue('D' . $sheet_row, !empty($data['rdg_supply_price4']) ? $data['rdg_supply_price4'] : '');
                $sheet->setCellValue('E' . $sheet_row, !empty($data['rdg_vat4']) ? $data['rdg_vat4'] : '');
                $sheet->setCellValue('F' . $sheet_row, !empty($data['rdg_sum4']) ? $data['rdg_sum4'] : '');
                $sheet->setCellValue('G' . $sheet_row, !empty($data['rdg_sum2']) ? $data['rdg_sum2'] : '');
                $sheet->setCellValue('H' . $sheet_row, !empty($data['rdg_sum3']) ? $data['rdg_sum3'] : '');
                $sheet->setCellValue('I' . $sheet_row, !empty($data['rdg_sum1']) ? $data['rdg_sum1'] : '');
                $sheet->setCellValue('J' . $sheet_row, !empty($data['rdg_etc3']) ? $data['rdg_etc3'] : '');
                $sheet_row++;
            }
            $sheet->setCellValue('A' . $sheet_row, '합계');
            $sheet->setCellValue('B' . $sheet_row, '');
            $sheet->setCellValue('C' . $sheet_row, '');
            $sheet->setCellValue('D' . $sheet_row, array_sum(array_column($datas, 'rdg_supply_price4')));
            $sheet->setCellValue('E' . $sheet_row, array_sum(array_column($datas, 'rdg_vat4')));
            $sheet->setCellValue('F' . $sheet_row, array_sum(array_column($datas, 'rdg_sum4')));
            $sheet->setCellValue('G' . $sheet_row, array_sum(array_column($datas, 'rdg_sum2')));
            $sheet->setCellValue('H' . $sheet_row, array_sum(array_column($datas, 'rdg_sum3')));
            $sheet->setCellValue('I' . $sheet_row, array_sum(array_column($datas, 'rdg_sum1')));
            $sheet->setCellValue('J' . $sheet_row, array_sum(array_column($datas, 'rdg_etc3')));
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Excel-Distribution-Final-Monthbill-Issue-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Excel-Distribution-Final-Monthbill-Issue-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }
    public function download_add_casebill_issue($rgd_no)
    {
        DB::beginTransaction();
        $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();
        DB::commit();
        $user = Auth::user();

        $data_sheet4 = $data_sheet3 = $data_sheet2 = $rate_data = array();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        if ($rgd_no) {
            $rmd_no = $this->get_rmd_no_raw($rgd_no, 'work_additional');
            $rate_data = $this->get_rate_data_raw($rmd_no);

            $rmd_no_domestic = $this->get_rmd_no_raw($rgd_no, 'domestic_additional');
            $rate_data_domestic = !empty($rmd_no_domestic) ? $this->get_rate_data_raw($rmd_no_domestic) : array();
            $data_sheet4 = !empty($rate_data_domestic) ? json_decode($rate_data_domestic, 1) : array();
            $supply_price = array_sum(array_column($data_sheet4, 'rd_data5'));
            $vat_price = array_sum(array_column($data_sheet4, 'rd_data6'));
            $sum_price = array_sum(array_column($data_sheet4, 'rd_data7'));

            $rmd_no_storage = $this->get_rmd_no_raw($rgd_no, 'storage_additional');
            $rate_data_storage = $this->get_rate_data_raw($rmd_no_storage);
        }

        $sheet->setTitle('종합');

        $sheet->setCellValue('A1', '종합');
        $sheet->setCellValue('B1', '공급가');
        $sheet->setCellValue('C1', '부가세');
        $sheet->setCellValue('D1', '합계');
        $sheet->setCellValue('E1', '비고');

        $sheet->setCellValue('A2', '유통가공 작업료');
        $sheet->setCellValue('B2', $rdg['rdg_supply_price2']);
        $sheet->setCellValue('C2', $rdg['rdg_vat2']);
        $sheet->setCellValue('D2', $rdg['rdg_sum2']);
        $sheet->setCellValue('E2', $rdg['rdg_etc2']);

        $sheet->setCellValue('A3', '부자재 보관료');
        $sheet->setCellValue('B3', $rdg['rdg_supply_price1']);
        $sheet->setCellValue('C3', $rdg['rdg_vat1']);
        $sheet->setCellValue('D3', $rdg['rdg_sum1']);
        $sheet->setCellValue('E3', $rdg['rdg_etc1']);

        $sheet->setCellValue('A4', '국내운송료');
        $sheet->setCellValue('B4', $supply_price);
        $sheet->setCellValue('C4', $vat_price);
        $sheet->setCellValue('D4', $sum_price);
        $sheet->setCellValue('E4', $rdg['rdg_etc3']);

        /*Total sheet 1*/
        $sheet1_rdg_supply_price = !empty($rdg['rdg_supply_price4']) ? $rdg['rdg_supply_price4'] : ($rdg['rdg_supply_price2'] + $rdg['rdg_supply_price1'] + $supply_price);
        $sheet1_rdg_vat = !empty($rdg['rdg_vat4']) ? $rdg['rdg_vat4'] : ($rdg['rdg_vat2'] + $rdg['rdg_vat1'] + $vat_price);
        $sheet1_rdg_sum = !empty($rdg['rdg_sum4']) ? $rdg['rdg_sum4'] : ($rdg['rdg_sum2'] + $rdg['rdg_sum1'] + $sum_price);
        $sheet1_rdg_etc = !empty($rdg['rdg_etc4']) ? $rdg['rdg_etc4'] : ($rdg['rdg_etc2'] + $rdg['rdg_etc1'] + $rdg['rdg_etc3']);

        $sheet->setCellValue('A5', '합계');
        $sheet->setCellValue('B5', $sheet1_rdg_supply_price);
        $sheet->setCellValue('C5', $sheet1_rdg_vat);
        $sheet->setCellValue('D5', $sheet1_rdg_sum);
        $sheet->setCellValue('E5', $sheet1_rdg_etc);

        /*Sheet 2*/

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);

        $sheet2->setTitle('작업료');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('A1', '항목');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', '건수');
        $sheet2->setCellValue('F1', '공급가');
        $sheet2->setCellValue('G1', '부가세');
        $sheet2->setCellValue('H1', '합계');
        $sheet2->setCellValue('I1', '비고');

        $row_2 = 2;
        if (!empty($rate_data)) {
            $data_sheet2 = json_decode($rate_data, 1);
            foreach ($data_sheet2 as $dt2) {
                $sheet2->setCellValue('A' . $row_2, $dt2['rd_cate1']);
                $sheet2->setCellValue('B' . $row_2, $dt2['rd_cate2']);
                $sheet2->setCellValue('C' . $row_2, $dt2['rd_data1']);
                $sheet2->setCellValue('D' . $row_2, $dt2['rd_data2']);
                $sheet2->setCellValue('E' . $row_2, $dt2['rd_data4']);
                $sheet2->setCellValue('F' . $row_2, $dt2['rd_data5']);
                $sheet2->setCellValue('G' . $row_2, $dt2['rd_data6']);
                $sheet2->setCellValue('H' . $row_2, $dt2['rd_data7']);
                $sheet2->setCellValue('I' . $row_2, $dt2['rd_data8']);
                $row_2++;
            }
            /* Total sheet 2 */
            $sheet2->setCellValue('A' . $row_2, '합계');
            $sheet2->mergeCells('A' . $row_2 . ':C' . $row_2);
            $sheet2->setCellValue('D' . $row_2, '');
            $sheet2->setCellValue('E' . $row_2, '');
            $sheet2->setCellValue('F' . $row_2, array_sum(array_column($data_sheet2, 'rd_data5')));
            $sheet2->setCellValue('G' . $row_2, array_sum(array_column($data_sheet2, 'rd_data6')));
            $sheet2->setCellValue('H' . $row_2, array_sum(array_column($data_sheet2, 'rd_data7')));
            $sheet2->setCellValue('I' . $row_2, array_sum(array_column($data_sheet2, 'rd_data8')));
        }

        /*Sheet 3*/

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);

        $sheet3->setTitle('보관료');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('A1', '항목');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', '건수');
        $sheet3->setCellValue('F1', '공급가');
        $sheet3->setCellValue('G1', '부가세');
        $sheet3->setCellValue('H1', '합계');
        $sheet3->setCellValue('I1', '비고');
        $row_3 = 2;
        if (!empty($rate_data_storage)) {
            $data_sheet3 = json_decode($rate_data_storage, 1);
            foreach ($data_sheet3 as $dt3) {
                $sheet3->setCellValue('A' . $row_3, $dt3['rd_cate1']);
                $sheet3->setCellValue('B' . $row_3, $dt3['rd_cate2']);
                $sheet3->setCellValue('C' . $row_3, $dt3['rd_data1']);
                $sheet3->setCellValue('D' . $row_3, $dt3['rd_data2']);
                $sheet3->setCellValue('E' . $row_3, $dt3['rd_data4']);
                $sheet3->setCellValue('F' . $row_3, $dt3['rd_data5']);
                $sheet3->setCellValue('G' . $row_3, $dt3['rd_data6']);
                $sheet3->setCellValue('H' . $row_3, $dt3['rd_data7']);
                $sheet3->setCellValue('I' . $row_3, $dt3['rd_data8']);
                $row_3++;
            }
            /* Total sheet 3 */
            $sheet3->setCellValue('A' . $row_3, '합계');
            $sheet3->mergeCells('A' . $row_3 . ':C' . $row_3);
            $sheet3->setCellValue('D' . $row_3, '');
            $sheet3->setCellValue('E' . $row_3, '');
            $sheet3->setCellValue('F' . $row_3, array_sum(array_column($data_sheet3, 'rd_data5')));
            $sheet3->setCellValue('G' . $row_3, array_sum(array_column($data_sheet3, 'rd_data6')));
            $sheet3->setCellValue('H' . $row_3, array_sum(array_column($data_sheet3, 'rd_data7')));
            $sheet3->setCellValue('I' . $row_3, array_sum(array_column($data_sheet3, 'rd_data8')));
        }

        $spreadsheet->createSheet();
        $sheet4 = $spreadsheet->getSheet(3);

        $sheet4->setTitle('국내운송료');
        $sheet4->mergeCells('A1:B1');
        $sheet4->mergeCells('A2:A4');
        $sheet4->setCellValue('A1', '항목');
        $sheet4->setCellValue('A2', '운송');
        $sheet4->setCellValue('C1', '단위');
        $sheet4->setCellValue('D1', '단가');
        $sheet4->setCellValue('E1', '건수');
        $sheet4->setCellValue('F1', '공급가');
        $sheet4->setCellValue('G1', '부가세');
        $sheet4->setCellValue('H1', '합계');
        $sheet4->setCellValue('I1', '비고');

        $row_4 = 2;
        if (!empty($data_sheet4)) {
            foreach ($data_sheet4 as $dt4) {
                if ($row_4 < 5) {
                    switch ($row_4) {
                        case 2:
                            $sheet4->setCellValue('B' . $row_4, '픽업료');
                            break;
                        case 3:
                            $sheet4->setCellValue('B' . $row_4, '배차(내륙운송)');
                            break;
                        case 4:
                            $sheet4->setCellValue('B' . $row_4, '국내 택배료');
                            break;
                        default:
                            break;
                    }
                } else {
                    $sheet4->setCellValue('B' . $row_4, $dt4['rd_cate2']);
                }
                $sheet4->setCellValue('C' . $row_4, $dt4['rd_data1']);
                $sheet4->setCellValue('D' . $row_4, $dt4['rd_data2']);
                $sheet4->setCellValue('E' . $row_4, $dt4['rd_data4']);
                $sheet4->setCellValue('F' . $row_4, $dt4['rd_data5']);
                $sheet4->setCellValue('G' . $row_4, $dt4['rd_data6']);
                $sheet4->setCellValue('H' . $row_4, $dt4['rd_data7']);
                $sheet4->setCellValue('I' . $row_4, $dt4['rd_data8']);
                $row_4++;
            }
            /* Total sheet 4 */
            $sheet4->setCellValue('A' . $row_4, '합계');
            $sheet4->mergeCells('A' . $row_4 . ':C' . $row_4);
            $sheet4->setCellValue('D' . $row_4, '');
            $sheet4->setCellValue('E' . $row_4, '');
            $sheet4->setCellValue('F' . $row_4, array_sum(array_column($data_sheet4, 'rd_data5')));
            $sheet4->setCellValue('G' . $row_4, array_sum(array_column($data_sheet4, 'rd_data6')));
            $sheet4->setCellValue('H' . $row_4, array_sum(array_column($data_sheet4, 'rd_data7')));
            $sheet4->setCellValue('I' . $row_4, array_sum(array_column($data_sheet4, 'rd_data8')));
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Excel-Distribution-Add-Casebill-Issue-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Excel-Distribution-Add-Casebill-Issue-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }

    public function download_distribution_monthbill(Request $request)
    {
        $datas = $request->all();
        DB::beginTransaction();
        $co_no = Auth::user()->co_no;
        DB::commit();
        $user = Auth::user();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        $sheet->setTitle('보세화물');

        $sheet->setCellValue('A1', 'NO');
        $sheet->setCellValue('B1', '입고 화물번호');
        $sheet->setCellValue('C1', '출고일자');
        $sheet->setCellValue('D1', '공급가');
        $sheet->setCellValue('E1', '부가세');
        $sheet->setCellValue('F1', '합계');
        $sheet->setCellValue('G1', '작업료');
        $sheet->setCellValue('H1', '보관료');
        $sheet->setCellValue('I1', '국내운송료');
        $sheet->setCellValue('J1', '비고');

        if (!empty($datas)) {
            $sheet_row = 2;

            foreach ($datas as $data) {
                $data = (array) $data;
                $sheet->setCellValue('A' . $sheet_row, !empty($data['key']) ? $data['key'] : '');
                $sheet->setCellValue('B' . $sheet_row, !empty($data['w_schedule_number2']) ? $data['w_schedule_number2'] : '');
                $sheet->setCellValue('C' . $sheet_row, !empty($data['w_completed_day']) ? $data['w_completed_day'] : '');
                $sheet->setCellValue('D' . $sheet_row, !empty($data['rdg_supply_price4']) ? $data['rdg_supply_price4'] : '');
                $sheet->setCellValue('E' . $sheet_row, !empty($data['rdg_vat4']) ? $data['rdg_vat4'] : '');
                $sheet->setCellValue('F' . $sheet_row, !empty($data['rdg_sum4']) ? $data['rdg_sum4'] : '');
                $sheet->setCellValue('G' . $sheet_row, !empty($data['rdg_sum2']) ? $data['rdg_sum2'] : '');
                $sheet->setCellValue('H' . $sheet_row, !empty($data['rdg_sum3']) ? $data['rdg_sum3'] : '');
                $sheet->setCellValue('I' . $sheet_row, !empty($data['rdg_sum1']) ? $data['rdg_sum1'] : '');
                $sheet->setCellValue('J' . $sheet_row, !empty($data['rdg_etc3']) ? $data['rdg_etc3'] : '');
                $sheet_row++;
            }
            $sheet->setCellValue('A' . $sheet_row, '합계');
            $sheet->setCellValue('B' . $sheet_row, '');
            $sheet->setCellValue('C' . $sheet_row, '');
            $sheet->setCellValue('D' . $sheet_row, array_sum(array_column($datas, 'rdg_supply_price4')));
            $sheet->setCellValue('E' . $sheet_row, array_sum(array_column($datas, 'rdg_vat4')));
            $sheet->setCellValue('F' . $sheet_row, array_sum(array_column($datas, 'rdg_sum4')));
            $sheet->setCellValue('G' . $sheet_row, array_sum(array_column($datas, 'rdg_sum2')));
            $sheet->setCellValue('H' . $sheet_row, array_sum(array_column($datas, 'rdg_sum3')));
            $sheet->setCellValue('I' . $sheet_row, array_sum(array_column($datas, 'rdg_sum1')));
            $sheet->setCellValue('J' . $sheet_row, array_sum(array_column($datas, 'rdg_etc3')));
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Excel-Distribution-Add-Monthbill-Issue-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Excel-Distribution-Add-Monthbill-Issue-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }
    public function download_distribution_final_monthbill(Request $request)
    {
        $datas = $request->all();
        DB::beginTransaction();
        $co_no = Auth::user()->co_no;
        DB::commit();
        $user = Auth::user();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        $sheet->setTitle('보세화물');

        $sheet->setCellValue('A1', 'NO');
        $sheet->setCellValue('B1', '입고 화물번호');
        $sheet->setCellValue('C1', '출고일자');
        $sheet->setCellValue('D1', '공급가');
        $sheet->setCellValue('E1', '부가세');
        $sheet->setCellValue('F1', '합계');
        $sheet->setCellValue('G1', '작업료');
        $sheet->setCellValue('H1', '보관료');
        $sheet->setCellValue('I1', '국내운송료');
        $sheet->setCellValue('J1', '비고');

        if (!empty($datas)) {
            $sheet_row = 2;
            foreach ($datas as $data) {
                $data = (array) $data;
                $sheet->setCellValue('A' . $sheet_row, !empty($data['key']) ? $data['key'] : '');
                $sheet->setCellValue('B' . $sheet_row, !empty($data['w_schedule_number2']) ? $data['w_schedule_number2'] : '');
                $sheet->setCellValue('C' . $sheet_row, !empty($data['w_completed_day']) ? $data['w_completed_day'] : '');
                $sheet->setCellValue('D' . $sheet_row, !empty($data['rdg_supply_price4']) ? $data['rdg_supply_price4'] : '');
                $sheet->setCellValue('E' . $sheet_row, !empty($data['rdg_vat4']) ? $data['rdg_vat4'] : '');
                $sheet->setCellValue('F' . $sheet_row, !empty($data['rdg_sum4']) ? $data['rdg_sum4'] : '');
                $sheet->setCellValue('G' . $sheet_row, !empty($data['rdg_sum2']) ? $data['rdg_sum2'] : '');
                $sheet->setCellValue('H' . $sheet_row, !empty($data['rdg_sum3']) ? $data['rdg_sum3'] : '');
                $sheet->setCellValue('I' . $sheet_row, !empty($data['rdg_sum1']) ? $data['rdg_sum1'] : '');
                $sheet->setCellValue('J' . $sheet_row, !empty($data['rdg_etc3']) ? $data['rdg_etc3'] : '');
                $sheet_row++;
            }
            $sheet->setCellValue('A' . $sheet_row, '합계');
            $sheet->setCellValue('B' . $sheet_row, '');
            $sheet->setCellValue('C' . $sheet_row, '');
            $sheet->setCellValue('D' . $sheet_row, array_sum(array_column($datas, 'rdg_supply_price4')));
            $sheet->setCellValue('E' . $sheet_row, array_sum(array_column($datas, 'rdg_vat4')));
            $sheet->setCellValue('F' . $sheet_row, array_sum(array_column($datas, 'rdg_sum4')));
            $sheet->setCellValue('G' . $sheet_row, array_sum(array_column($datas, 'rdg_sum2')));
            $sheet->setCellValue('H' . $sheet_row, array_sum(array_column($datas, 'rdg_sum3')));
            $sheet->setCellValue('I' . $sheet_row, array_sum(array_column($datas, 'rdg_sum1')));
            $sheet->setCellValue('J' . $sheet_row, array_sum(array_column($datas, 'rdg_etc3')));
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Excel-Distribution-Final-Monthbill-Check-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Excel-Distribution-Final-Monthbill-Check-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }

    public function download_fulfillment_final_monthbill($rgd_no)
    {

        $data_fullfill1 = $data_fullfill2 = $data_fullfill3 = $data_fullfill4 = $data_fullfill5 = null;

        $rmd_no1 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill1_final', 'fulfill1');
        $rmd_no2 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill2_final', 'fulfill2');
        $rmd_no3 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill3_final', 'fulfill3');
        $rmd_no4 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill4_final', 'fulfill4');
        $rmd_no5 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill5_final', 'fulfill5');

        $data_fullfill1 = !empty($rmd_no1) ? $this->get_set_data_raw($rmd_no1) : array();
        $data_fullfill2 = !empty($rmd_no2) ? $this->get_set_data_raw($rmd_no2) : array();
        $data_fullfill3 = !empty($rmd_no3) ? $this->get_set_data_raw($rmd_no3) : array();
        $data_fullfill4 = !empty($rmd_no4) ? $this->get_set_data_raw($rmd_no4) : array();
        $data_fullfill5 = !empty($rmd_no5) ? $this->get_set_data_raw($rmd_no5) : array();

        $center_work_supply_price = $center_work_vat = $center_work_sum = 0;
        if (!empty($data_fullfill1)) {
            foreach ($data_fullfill1 as $dt1) {
                $center_work_supply_price += !empty($dt1->rd_data5) ? $dt1->rd_data5 : 0;
                $center_work_vat += !empty($dt1->rd_data6) ? $dt1->rd_data6 : 0;
                $center_work_sum += !empty($dt1->rd_data7) ? $dt1->rd_data7 : 0;
            }
        }

        $domestic_shipping_supply_price = $domestic_shipping_vat = $domestic_shipping_sum = 0;

        if (!empty($data_fullfill2)) {
            foreach ($data_fullfill2 as $dt2) {
                $domestic_shipping_supply_price += !empty($dt2->rd_data5) ? $dt2->rd_data5 : 0;
                $domestic_shipping_vat += !empty($dt2->rd_data6) ? $dt2->rd_data6 : 0;
                $domestic_shipping_sum += !empty($dt2->rd_data7) ? $dt2->rd_data7 : 0;
            }
        }

        $overseas_shipping_supply_price = $overseas_shipping_vat = $overseas_shipping_sum = 0;

        if (!empty($data_fullfill3)) {
            foreach ($data_fullfill3 as $dt3) {
                $overseas_shipping_supply_price += !empty($dt3->rd_data5) ? $dt3->rd_data5 : 0;
                $overseas_shipping_vat += !empty($dt3->rd_data6) ? $dt3->rd_data6 : 0;
                $overseas_shipping_sum += !empty($dt3->rd_data7) ? $dt3->rd_data7 : 0;
            }
        }

        $keep_supply_price = $keep_vat = $keep_sum = 0;

        if (!empty($data_fullfill4)) {
            foreach ($data_fullfill4 as $dt4) {
                $keep_supply_price += !empty($dt4->rd_data5) ? $dt4->rd_data5 : 0;
                $keep_vat += !empty($dt4->rd_data6) ? $dt4->rd_data6 : 0;
                $keep_sum += !empty($dt4->rd_data7) ? $dt4->rd_data7 : 0;
            }
        }

        $subsidiary_supply_price = $subsidiary_supply_vat = $subsidiary_supply_sum = 0;

        if (!empty($data_fullfill5)) {
            foreach ($data_fullfill5 as $dt5) {
                $subsidiary_supply_price += !empty($dt5->rd_data5) ? $dt5->rd_data5 : 0;
                $subsidiary_supply_vat += !empty($dt5->rd_data6) ? $dt5->rd_data6 : 0;
                $subsidiary_supply_sum += !empty($dt5->rd_data7) ? $dt5->rd_data7 : 0;
            }
        }

        $total_supply_price = $center_work_supply_price + $domestic_shipping_supply_price + $overseas_shipping_supply_price + $keep_supply_price + $subsidiary_supply_price;
        $total_supply_vat = $center_work_vat + $domestic_shipping_vat + $overseas_shipping_vat + $keep_vat + $subsidiary_supply_vat;
        $total_sum = $center_work_sum + $domestic_shipping_sum + $overseas_shipping_sum + $keep_sum + $subsidiary_supply_sum;

        DB::beginTransaction();
        $co_no = Auth::user()->co_no;
        DB::commit();
        $user = Auth::user();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        $sheet->setTitle('종합');

        $sheet->setCellValue('A1', '항목');
        $sheet->mergeCells('A1:B1');
        $sheet->setCellValue('C1', '공급가');
        $sheet->setCellValue('D1', '부가세');
        $sheet->setCellValue('E1', '합계');
        $sheet->setCellValue('F1', '비고');
        $sheet->mergeCells('F1:G1');

        $sheet->setCellValue('A2', '센터작업료');
        $sheet->mergeCells('A2:B2');
        $sheet->setCellValue('C2', $center_work_supply_price);
        $sheet->setCellValue('D2', $center_work_vat);
        $sheet->setCellValue('E2', $center_work_sum);
        $sheet->setCellValue('F2', '');
        $sheet->mergeCells('F2:G2');

        $sheet->setCellValue('A3', '국내 운송료');
        $sheet->mergeCells('A3:B3');
        $sheet->setCellValue('C3', $domestic_shipping_supply_price);
        $sheet->setCellValue('D3', $domestic_shipping_vat);
        $sheet->setCellValue('E3', $domestic_shipping_sum);
        $sheet->setCellValue('F3', '');
        $sheet->mergeCells('F3:G3');

        $sheet->setCellValue('A4', '해외 운송료');
        $sheet->mergeCells('A4:B4');
        $sheet->setCellValue('C4', $overseas_shipping_supply_price);
        $sheet->setCellValue('D4', $overseas_shipping_vat);
        $sheet->setCellValue('E4', $overseas_shipping_sum);
        $sheet->setCellValue('F4', '');
        $sheet->mergeCells('F4:G4');

        $sheet->setCellValue('A5', '보관');
        $sheet->mergeCells('A5:B5');
        $sheet->setCellValue('C5', $keep_supply_price);
        $sheet->setCellValue('D5', $keep_vat);
        $sheet->setCellValue('E5', $keep_sum);
        $sheet->setCellValue('F5', '');
        $sheet->mergeCells('F5:G5');

        $sheet->setCellValue('A6', '부자재');
        $sheet->mergeCells('A6:B6');
        $sheet->setCellValue('C6', $subsidiary_supply_price);
        $sheet->setCellValue('D6', $subsidiary_supply_vat);
        $sheet->setCellValue('E6', $subsidiary_supply_sum);
        $sheet->setCellValue('F6', '');
        $sheet->mergeCells('F6:G6');

        $sheet->setCellValue('A7', '합계');
        $sheet->mergeCells('A7:B7');
        $sheet->setCellValue('C7', $total_supply_price);
        $sheet->setCellValue('D7', $total_supply_vat);
        $sheet->setCellValue('E7', $total_sum);
        $sheet->setCellValue('F7', '');
        $sheet->mergeCells('F7:G7');

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);

        $sheet2->setTitle('센터 작업료');

        $sheet2->setCellValue('A1', '항목');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', '건수');
        $sheet2->setCellValue('F1', '공급가');
        $sheet2->setCellValue('G1', '부가세');
        $sheet2->setCellValue('H1', '급액');
        $sheet2->setCellValue('I1', '비고');
        $sheet2->mergeCells('I1:J1');

        if (!empty($data_fullfill1)) {
            $sheet_row = 2;
            foreach ($data_fullfill1 as $data) {
                $sheet2->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet2->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet2->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet2->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet2->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet2->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet2->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet2->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet2->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet2->setCellValue('A' . $sheet_row, '합계');
            $sheet2->setCellValue('B' . $sheet_row, '');
            $sheet2->setCellValue('C' . $sheet_row, '');
            $sheet2->setCellValue('D' . $sheet_row, '');
            $sheet2->setCellValue('E' . $sheet_row, '');
            $sheet2->setCellValue('F' . $sheet_row, $center_work_supply_price);
            $sheet2->setCellValue('G' . $sheet_row, $center_work_vat);
            $sheet2->setCellValue('H' . $sheet_row, $center_work_sum);
            $sheet2->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);

        $sheet3->setTitle('국내운송료');

        $sheet3->setCellValue('A1', '항목');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', '건수');
        $sheet3->setCellValue('F1', '공급가');
        $sheet3->setCellValue('G1', '부가세');
        $sheet3->setCellValue('H1', '급액');
        $sheet3->setCellValue('I1', '비고');
        $sheet3->mergeCells('I1:J1');

        if (!empty($data_fullfill2)) {
            $sheet_row = 2;
            foreach ($data_fullfill2 as $data) {
                $sheet3->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet3->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet3->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet3->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet3->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet3->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet3->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet3->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet3->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet3->setCellValue('A' . $sheet_row, '합계');
            $sheet3->setCellValue('B' . $sheet_row, '');
            $sheet3->setCellValue('C' . $sheet_row, '');
            $sheet3->setCellValue('D' . $sheet_row, '');
            $sheet3->setCellValue('E' . $sheet_row, '');
            $sheet3->setCellValue('F' . $sheet_row, $domestic_shipping_supply_price);
            $sheet3->setCellValue('G' . $sheet_row, $domestic_shipping_vat);
            $sheet3->setCellValue('H' . $sheet_row, $domestic_shipping_sum);
            $sheet3->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet4 = $spreadsheet->getSheet(3);

        $sheet4->setTitle('해외운송료');

        $sheet4->setCellValue('A1', '항목');
        $sheet4->mergeCells('A1:B1');
        $sheet4->setCellValue('C1', '단위');
        $sheet4->setCellValue('D1', '단가');
        $sheet4->setCellValue('E1', '건수');
        $sheet4->setCellValue('F1', '공급가');
        $sheet4->setCellValue('G1', '부가세');
        $sheet4->setCellValue('H1', '급액');
        $sheet4->setCellValue('I1', '비고');
        $sheet4->mergeCells('I1:J1');

        if (!empty($data_fullfill3)) {
            $sheet_row = 2;
            foreach ($data_fullfill3 as $data) {
                $sheet4->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet4->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet4->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet4->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet4->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet4->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet4->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet4->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet4->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet4->setCellValue('A' . $sheet_row, '합계');
            $sheet4->setCellValue('B' . $sheet_row, '');
            $sheet4->setCellValue('C' . $sheet_row, '');
            $sheet4->setCellValue('D' . $sheet_row, '');
            $sheet4->setCellValue('E' . $sheet_row, '');
            $sheet4->setCellValue('F' . $sheet_row, $overseas_shipping_supply_price);
            $sheet4->setCellValue('G' . $sheet_row, $overseas_shipping_vat);
            $sheet4->setCellValue('H' . $sheet_row, $overseas_shipping_sum);
            $sheet4->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet5 = $spreadsheet->getSheet(4);

        $sheet5->setTitle('보관');

        $sheet5->setCellValue('A1', '항목');
        $sheet5->mergeCells('A1:B1');
        $sheet5->setCellValue('C1', '단위');
        $sheet5->setCellValue('D1', '단가');
        $sheet5->setCellValue('E1', '건수');
        $sheet5->setCellValue('F1', '공급가');
        $sheet5->setCellValue('G1', '부가세');
        $sheet5->setCellValue('H1', '급액');
        $sheet5->setCellValue('I1', '비고');
        $sheet5->mergeCells('I1:J1');

        if (!empty($data_fullfill4)) {
            $sheet_row = 2;
            foreach ($data_fullfill4 as $data) {
                $sheet5->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet5->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet5->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet5->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet5->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet5->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet5->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet5->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet5->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet5->setCellValue('A' . $sheet_row, '합계');
            $sheet5->setCellValue('B' . $sheet_row, '');
            $sheet5->setCellValue('C' . $sheet_row, '');
            $sheet5->setCellValue('D' . $sheet_row, '');
            $sheet5->setCellValue('E' . $sheet_row, '');
            $sheet5->setCellValue('F' . $sheet_row, $keep_supply_price);
            $sheet5->setCellValue('G' . $sheet_row, $keep_vat);
            $sheet5->setCellValue('H' . $sheet_row, $keep_sum);
            $sheet5->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet6 = $spreadsheet->getSheet(5);

        $sheet6->setTitle('부자재');

        $sheet6->setCellValue('A1', '항목');
        $sheet6->mergeCells('A1:B1');
        $sheet6->setCellValue('C1', '단위');
        $sheet6->setCellValue('D1', '단가');
        $sheet6->setCellValue('E1', '건수');
        $sheet6->setCellValue('F1', '공급가');
        $sheet6->setCellValue('G1', '부가세');
        $sheet6->setCellValue('H1', '급액');
        $sheet6->setCellValue('I1', '비고');
        $sheet6->mergeCells('I1:J1');

        if (!empty($data_fullfill5)) {
            $sheet_row = 2;
            foreach ($data_fullfill5 as $data) {
                $sheet6->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet6->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet6->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet6->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet6->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet6->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet6->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet6->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet6->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet6->setCellValue('A' . $sheet_row, '합계');
            $sheet6->setCellValue('B' . $sheet_row, '');
            $sheet6->setCellValue('C' . $sheet_row, '');
            $sheet6->setCellValue('D' . $sheet_row, '');
            $sheet6->setCellValue('E' . $sheet_row, '');
            $sheet6->setCellValue('F' . $sheet_row, $subsidiary_supply_price);
            $sheet6->setCellValue('G' . $sheet_row, $subsidiary_supply_vat);
            $sheet6->setCellValue('H' . $sheet_row, $subsidiary_supply_sum);
            $sheet6->setCellValue('I' . $sheet_row, '');
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Excel-Fulfillment-Final-Monthbill-Edit-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Excel-Fulfillment-Final-Monthbill-Edit-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }

    public function download_fulfillment_additional($rgd_no)
    {

        $data_fullfill1 = $data_fullfill2 = $data_fullfill3 = $data_fullfill4 = $data_fullfill5 = null;

        $rmd_no1 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill1_final', 'fulfill1_additional');
        $rmd_no2 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill2_final', 'fulfill2_additional');
        $rmd_no3 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill3_final', 'fulfill3_additional');
        $rmd_no4 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill4_final', 'fulfill4_additional');
        $rmd_no5 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill5_final', 'fulfill5_additional');

        $data_fullfill1 = !empty($rmd_no1) ? $this->get_set_data_raw($rmd_no1) : array();
        $data_fullfill2 = !empty($rmd_no2) ? $this->get_set_data_raw($rmd_no2) : array();
        $data_fullfill3 = !empty($rmd_no3) ? $this->get_set_data_raw($rmd_no3) : array();
        $data_fullfill4 = !empty($rmd_no4) ? $this->get_set_data_raw($rmd_no4) : array();
        $data_fullfill5 = !empty($rmd_no5) ? $this->get_set_data_raw($rmd_no5) : array();

        $center_work_supply_price = $center_work_vat = $center_work_sum = 0;
        if (!empty($data_fullfill1)) {
            foreach ($data_fullfill1 as $dt1) {
                $center_work_supply_price += !empty($dt1->rd_data5) ? $dt1->rd_data5 : 0;
                $center_work_vat += !empty($dt1->rd_data6) ? $dt1->rd_data6 : 0;
                $center_work_sum += !empty($dt1->rd_data7) ? $dt1->rd_data7 : 0;
            }
        }

        $domestic_shipping_supply_price = $domestic_shipping_vat = $domestic_shipping_sum = 0;

        if (!empty($data_fullfill2)) {
            foreach ($data_fullfill2 as $dt2) {
                $domestic_shipping_supply_price += !empty($dt2->rd_data5) ? $dt2->rd_data5 : 0;
                $domestic_shipping_vat += !empty($dt2->rd_data6) ? $dt2->rd_data6 : 0;
                $domestic_shipping_sum += !empty($dt2->rd_data7) ? $dt2->rd_data7 : 0;
            }
        }

        $overseas_shipping_supply_price = $overseas_shipping_vat = $overseas_shipping_sum = 0;

        if (!empty($data_fullfill3)) {
            foreach ($data_fullfill3 as $dt3) {
                $overseas_shipping_supply_price += !empty($dt3->rd_data5) ? $dt3->rd_data5 : 0;
                $overseas_shipping_vat += !empty($dt3->rd_data6) ? $dt3->rd_data6 : 0;
                $overseas_shipping_sum += !empty($dt3->rd_data7) ? $dt3->rd_data7 : 0;
            }
        }

        $keep_supply_price = $keep_vat = $keep_sum = 0;

        if (!empty($data_fullfill4)) {
            foreach ($data_fullfill4 as $dt4) {
                $keep_supply_price += !empty($dt4->rd_data5) ? $dt4->rd_data5 : 0;
                $keep_vat += !empty($dt4->rd_data6) ? $dt4->rd_data6 : 0;
                $keep_sum += !empty($dt4->rd_data7) ? $dt4->rd_data7 : 0;
            }
        }

        $subsidiary_supply_price = $subsidiary_supply_vat = $subsidiary_supply_sum = 0;

        if (!empty($data_fullfill5)) {
            foreach ($data_fullfill5 as $dt5) {
                $subsidiary_supply_price += !empty($dt5->rd_data5) ? $dt5->rd_data5 : 0;
                $subsidiary_supply_vat += !empty($dt5->rd_data6) ? $dt5->rd_data6 : 0;
                $subsidiary_supply_sum += !empty($dt5->rd_data7) ? $dt5->rd_data7 : 0;
            }
        }

        $total_supply_price = $center_work_supply_price + $domestic_shipping_supply_price + $overseas_shipping_supply_price + $keep_supply_price + $subsidiary_supply_price;
        $total_supply_vat = $center_work_vat + $domestic_shipping_vat + $overseas_shipping_vat + $keep_vat + $subsidiary_supply_vat;
        $total_sum = $center_work_sum + $domestic_shipping_sum + $overseas_shipping_sum + $keep_sum + $subsidiary_supply_sum;

        DB::beginTransaction();
        $co_no = Auth::user()->co_no;
        DB::commit();
        $user = Auth::user();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        $sheet->setTitle('종합');

        $sheet->setCellValue('A1', '항목');
        $sheet->mergeCells('A1:B1');
        $sheet->setCellValue('C1', '공급가');
        $sheet->setCellValue('D1', '부가세');
        $sheet->setCellValue('E1', '합계');
        $sheet->setCellValue('F1', '비고');
        $sheet->mergeCells('F1:G1');

        $sheet->setCellValue('A2', '센터작업료');
        $sheet->mergeCells('A2:B2');
        $sheet->setCellValue('C2', $center_work_supply_price);
        $sheet->setCellValue('D2', $center_work_vat);
        $sheet->setCellValue('E2', $center_work_sum);
        $sheet->setCellValue('F2', '');
        $sheet->mergeCells('F2:G2');

        $sheet->setCellValue('A3', '국내 운송료');
        $sheet->mergeCells('A3:B3');
        $sheet->setCellValue('C3', $domestic_shipping_supply_price);
        $sheet->setCellValue('D3', $domestic_shipping_vat);
        $sheet->setCellValue('E3', $domestic_shipping_sum);
        $sheet->setCellValue('F3', '');
        $sheet->mergeCells('F3:G3');

        $sheet->setCellValue('A4', '해외 운송료');
        $sheet->mergeCells('A4:B4');
        $sheet->setCellValue('C4', $overseas_shipping_supply_price);
        $sheet->setCellValue('D4', $overseas_shipping_vat);
        $sheet->setCellValue('E4', $overseas_shipping_sum);
        $sheet->setCellValue('F4', '');
        $sheet->mergeCells('F4:G4');

        $sheet->setCellValue('A5', '보관');
        $sheet->mergeCells('A5:B5');
        $sheet->setCellValue('C5', $keep_supply_price);
        $sheet->setCellValue('D5', $keep_vat);
        $sheet->setCellValue('E5', $keep_sum);
        $sheet->setCellValue('F5', '');
        $sheet->mergeCells('F5:G5');

        $sheet->setCellValue('A6', '부자재');
        $sheet->mergeCells('A6:B6');
        $sheet->setCellValue('C6', $subsidiary_supply_price);
        $sheet->setCellValue('D6', $subsidiary_supply_vat);
        $sheet->setCellValue('E6', $subsidiary_supply_sum);
        $sheet->setCellValue('F6', '');
        $sheet->mergeCells('F6:G6');

        $sheet->setCellValue('A7', '합계');
        $sheet->mergeCells('A7:B7');
        $sheet->setCellValue('C7', $total_supply_price);
        $sheet->setCellValue('D7', $total_supply_vat);
        $sheet->setCellValue('E7', $total_sum);
        $sheet->setCellValue('F7', '');
        $sheet->mergeCells('F7:G7');

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);

        $sheet2->setTitle('센터 작업료');

        $sheet2->setCellValue('A1', '항목');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', '건수');
        $sheet2->setCellValue('F1', '공급가');
        $sheet2->setCellValue('G1', '부가세');
        $sheet2->setCellValue('H1', '급액');
        $sheet2->setCellValue('I1', '비고');
        $sheet2->mergeCells('I1:J1');

        if (!empty($data_fullfill1)) {
            $sheet_row = 2;
            foreach ($data_fullfill1 as $data) {
                $sheet2->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet2->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet2->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet2->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet2->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet2->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet2->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet2->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet2->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet2->setCellValue('A' . $sheet_row, '합계');
            $sheet2->setCellValue('B' . $sheet_row, '');
            $sheet2->setCellValue('C' . $sheet_row, '');
            $sheet2->setCellValue('D' . $sheet_row, '');
            $sheet2->setCellValue('E' . $sheet_row, '');
            $sheet2->setCellValue('F' . $sheet_row, $center_work_supply_price);
            $sheet2->setCellValue('G' . $sheet_row, $center_work_vat);
            $sheet2->setCellValue('H' . $sheet_row, $center_work_sum);
            $sheet2->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);

        $sheet3->setTitle('센터 작업료');

        $sheet3->setCellValue('A1', '항목');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', '건수');
        $sheet3->setCellValue('F1', '공급가');
        $sheet3->setCellValue('G1', '부가세');
        $sheet3->setCellValue('H1', '급액');
        $sheet3->setCellValue('I1', '비고');
        $sheet3->mergeCells('I1:J1');

        if (!empty($data_fullfill2)) {
            $sheet_row = 2;
            foreach ($data_fullfill2 as $data) {
                $sheet3->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet3->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet3->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet3->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet3->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet3->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet3->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet3->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet3->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet3->setCellValue('A' . $sheet_row, '합계');
            $sheet3->setCellValue('B' . $sheet_row, '');
            $sheet3->setCellValue('C' . $sheet_row, '');
            $sheet3->setCellValue('D' . $sheet_row, '');
            $sheet3->setCellValue('E' . $sheet_row, '');
            $sheet3->setCellValue('F' . $sheet_row, $domestic_shipping_supply_price);
            $sheet3->setCellValue('G' . $sheet_row, $domestic_shipping_vat);
            $sheet3->setCellValue('H' . $sheet_row, $domestic_shipping_sum);
            $sheet3->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet4 = $spreadsheet->getSheet(3);

        $sheet4->setTitle('센터 작업료');

        $sheet4->setCellValue('A1', '항목');
        $sheet4->mergeCells('A1:B1');
        $sheet4->setCellValue('C1', '단위');
        $sheet4->setCellValue('D1', '단가');
        $sheet4->setCellValue('E1', '건수');
        $sheet4->setCellValue('F1', '공급가');
        $sheet4->setCellValue('G1', '부가세');
        $sheet4->setCellValue('H1', '급액');
        $sheet4->setCellValue('I1', '비고');
        $sheet4->mergeCells('I1:J1');

        if (!empty($data_fullfill3)) {
            $sheet_row = 2;
            foreach ($data_fullfill3 as $data) {
                $sheet4->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet4->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet4->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet4->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet4->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet4->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet4->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet4->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet4->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet4->setCellValue('A' . $sheet_row, '합계');
            $sheet4->setCellValue('B' . $sheet_row, '');
            $sheet4->setCellValue('C' . $sheet_row, '');
            $sheet4->setCellValue('D' . $sheet_row, '');
            $sheet4->setCellValue('E' . $sheet_row, '');
            $sheet4->setCellValue('F' . $sheet_row, $overseas_shipping_supply_price);
            $sheet4->setCellValue('G' . $sheet_row, $overseas_shipping_vat);
            $sheet4->setCellValue('H' . $sheet_row, $overseas_shipping_sum);
            $sheet4->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet5 = $spreadsheet->getSheet(4);

        $sheet5->setTitle('센터 작업료');

        $sheet5->setCellValue('A1', '항목');
        $sheet5->mergeCells('A1:B1');
        $sheet5->setCellValue('C1', '단위');
        $sheet5->setCellValue('D1', '단가');
        $sheet5->setCellValue('E1', '건수');
        $sheet5->setCellValue('F1', '공급가');
        $sheet5->setCellValue('G1', '부가세');
        $sheet5->setCellValue('H1', '급액');
        $sheet5->setCellValue('I1', '비고');
        $sheet5->mergeCells('I1:J1');

        if (!empty($data_fullfill4)) {
            $sheet_row = 2;
            foreach ($data_fullfill4 as $data) {
                $sheet5->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet5->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet5->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet5->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet5->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet5->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet5->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet5->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet5->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet5->setCellValue('A' . $sheet_row, '합계');
            $sheet5->setCellValue('B' . $sheet_row, '');
            $sheet5->setCellValue('C' . $sheet_row, '');
            $sheet5->setCellValue('D' . $sheet_row, '');
            $sheet5->setCellValue('E' . $sheet_row, '');
            $sheet5->setCellValue('F' . $sheet_row, $keep_supply_price);
            $sheet5->setCellValue('G' . $sheet_row, $keep_vat);
            $sheet5->setCellValue('H' . $sheet_row, $keep_sum);
            $sheet5->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet6 = $spreadsheet->getSheet(5);

        $sheet6->setTitle('센터 작업료');

        $sheet6->setCellValue('A1', '항목');
        $sheet6->mergeCells('A1:B1');
        $sheet6->setCellValue('C1', '단위');
        $sheet6->setCellValue('D1', '단가');
        $sheet6->setCellValue('E1', '건수');
        $sheet6->setCellValue('F1', '공급가');
        $sheet6->setCellValue('G1', '부가세');
        $sheet6->setCellValue('H1', '급액');
        $sheet6->setCellValue('I1', '비고');
        $sheet6->mergeCells('I1:J1');

        if (!empty($data_fullfill5)) {
            $sheet_row = 2;
            foreach ($data_fullfill5 as $data) {
                $sheet6->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet6->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet6->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet6->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet6->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet6->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet6->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet6->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet6->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet6->setCellValue('A' . $sheet_row, '합계');
            $sheet6->setCellValue('B' . $sheet_row, '');
            $sheet6->setCellValue('C' . $sheet_row, '');
            $sheet6->setCellValue('D' . $sheet_row, '');
            $sheet6->setCellValue('E' . $sheet_row, '');
            $sheet6->setCellValue('F' . $sheet_row, $subsidiary_supply_price);
            $sheet6->setCellValue('G' . $sheet_row, $subsidiary_supply_vat);
            $sheet6->setCellValue('H' . $sheet_row, $subsidiary_supply_sum);
            $sheet6->setCellValue('I' . $sheet_row, '');
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Excel-FulfillMent-Month-Bill-Issue-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Excel-FulfillMent-Month-Bill-Issue-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }
    public function download_full_fillment_final($rgd_no)
    {

        $data_fullfill1 = $data_fullfill2 = $data_fullfill3 = $data_fullfill4 = $data_fullfill5 = null;

        $rmd_no1 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill1_final', 'fulfill1');
        $rmd_no2 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill2_final', 'fulfill2');
        $rmd_no3 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill3_final', 'fulfill3');
        $rmd_no4 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill4_final', 'fulfill4');
        $rmd_no5 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill5_final', 'fulfill5');

        $data_fullfill1 = !empty($rmd_no1) ? $this->get_set_data_raw($rmd_no1) : array();
        $data_fullfill2 = !empty($rmd_no2) ? $this->get_set_data_raw($rmd_no2) : array();
        $data_fullfill3 = !empty($rmd_no3) ? $this->get_set_data_raw($rmd_no3) : array();
        $data_fullfill4 = !empty($rmd_no4) ? $this->get_set_data_raw($rmd_no4) : array();
        $data_fullfill5 = !empty($rmd_no5) ? $this->get_set_data_raw($rmd_no5) : array();

        $center_work_supply_price = $center_work_vat = $center_work_sum = 0;
        if (!empty($data_fullfill1)) {
            foreach ($data_fullfill1 as $dt1) {
                $center_work_supply_price += !empty($dt1->rd_data5) ? $dt1->rd_data5 : 0;
                $center_work_vat += !empty($dt1->rd_data6) ? $dt1->rd_data6 : 0;
                $center_work_sum += !empty($dt1->rd_data7) ? $dt1->rd_data7 : 0;
            }
        }

        $domestic_shipping_supply_price = $domestic_shipping_vat = $domestic_shipping_sum = 0;

        if (!empty($data_fullfill2)) {
            foreach ($data_fullfill2 as $dt2) {
                $domestic_shipping_supply_price += !empty($dt2->rd_data5) ? $dt2->rd_data5 : 0;
                $domestic_shipping_vat += !empty($dt2->rd_data6) ? $dt2->rd_data6 : 0;
                $domestic_shipping_sum += !empty($dt2->rd_data7) ? $dt2->rd_data7 : 0;
            }
        }

        $overseas_shipping_supply_price = $overseas_shipping_vat = $overseas_shipping_sum = 0;

        if (!empty($data_fullfill3)) {
            foreach ($data_fullfill3 as $dt3) {
                $overseas_shipping_supply_price += !empty($dt3->rd_data5) ? $dt3->rd_data5 : 0;
                $overseas_shipping_vat += !empty($dt3->rd_data6) ? $dt3->rd_data6 : 0;
                $overseas_shipping_sum += !empty($dt3->rd_data7) ? $dt3->rd_data7 : 0;
            }
        }

        $keep_supply_price = $keep_vat = $keep_sum = 0;

        if (!empty($data_fullfill4)) {
            foreach ($data_fullfill4 as $dt4) {
                $keep_supply_price += !empty($dt4->rd_data5) ? $dt4->rd_data5 : 0;
                $keep_vat += !empty($dt4->rd_data6) ? $dt4->rd_data6 : 0;
                $keep_sum += !empty($dt4->rd_data7) ? $dt4->rd_data7 : 0;
            }
        }

        $subsidiary_supply_price = $subsidiary_supply_vat = $subsidiary_supply_sum = 0;

        if (!empty($data_fullfill5)) {
            foreach ($data_fullfill5 as $dt5) {
                $subsidiary_supply_price += !empty($dt5->rd_data5) ? $dt5->rd_data5 : 0;
                $subsidiary_supply_vat += !empty($dt5->rd_data6) ? $dt5->rd_data6 : 0;
                $subsidiary_supply_sum += !empty($dt5->rd_data7) ? $dt5->rd_data7 : 0;
            }
        }

        $total_supply_price = $center_work_supply_price + $domestic_shipping_supply_price + $overseas_shipping_supply_price + $keep_supply_price + $subsidiary_supply_price;
        $total_supply_vat = $center_work_vat + $domestic_shipping_vat + $overseas_shipping_vat + $keep_vat + $subsidiary_supply_vat;
        $total_sum = $center_work_sum + $domestic_shipping_sum + $overseas_shipping_sum + $keep_sum + $subsidiary_supply_sum;

        DB::beginTransaction();
        $co_no = Auth::user()->co_no;
        DB::commit();
        $user = Auth::user();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        $sheet->setTitle('종합');

        $sheet->setCellValue('A1', '항목');
        $sheet->mergeCells('A1:B1');
        $sheet->setCellValue('C1', '공급가');
        $sheet->setCellValue('D1', '부가세');
        $sheet->setCellValue('E1', '합계');
        $sheet->setCellValue('F1', '비고');
        $sheet->mergeCells('F1:G1');

        $sheet->setCellValue('A2', '센터작업료');
        $sheet->mergeCells('A2:B2');
        $sheet->setCellValue('C2', $center_work_supply_price);
        $sheet->setCellValue('D2', $center_work_vat);
        $sheet->setCellValue('E2', $center_work_sum);
        $sheet->setCellValue('F2', '');
        $sheet->mergeCells('F2:G2');

        $sheet->setCellValue('A3', '국내 운송료');
        $sheet->mergeCells('A3:B3');
        $sheet->setCellValue('C3', $domestic_shipping_supply_price);
        $sheet->setCellValue('D3', $domestic_shipping_vat);
        $sheet->setCellValue('E3', $domestic_shipping_sum);
        $sheet->setCellValue('F3', '');
        $sheet->mergeCells('F3:G3');

        $sheet->setCellValue('A4', '해외 운송료');
        $sheet->mergeCells('A4:B4');
        $sheet->setCellValue('C4', $overseas_shipping_supply_price);
        $sheet->setCellValue('D4', $overseas_shipping_vat);
        $sheet->setCellValue('E4', $overseas_shipping_sum);
        $sheet->setCellValue('F4', '');
        $sheet->mergeCells('F4:G4');

        $sheet->setCellValue('A5', '보관');
        $sheet->mergeCells('A5:B5');
        $sheet->setCellValue('C5', $keep_supply_price);
        $sheet->setCellValue('D5', $keep_vat);
        $sheet->setCellValue('E5', $keep_sum);
        $sheet->setCellValue('F5', '');
        $sheet->mergeCells('F5:G5');

        $sheet->setCellValue('A6', '부자재');
        $sheet->mergeCells('A6:B6');
        $sheet->setCellValue('C6', $subsidiary_supply_price);
        $sheet->setCellValue('D6', $subsidiary_supply_vat);
        $sheet->setCellValue('E6', $subsidiary_supply_sum);
        $sheet->setCellValue('F6', '');
        $sheet->mergeCells('F6:G6');

        $sheet->setCellValue('A7', '합계');
        $sheet->mergeCells('A7:B7');
        $sheet->setCellValue('C7', $total_supply_price);
        $sheet->setCellValue('D7', $total_supply_vat);
        $sheet->setCellValue('E7', $total_sum);
        $sheet->setCellValue('F7', '');
        $sheet->mergeCells('F7:G7');

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);

        $sheet2->setTitle('센터 작업료');

        $sheet2->setCellValue('A1', '항목');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', '건수');
        $sheet2->setCellValue('F1', '공급가');
        $sheet2->setCellValue('G1', '부가세');
        $sheet2->setCellValue('H1', '급액');
        $sheet2->setCellValue('I1', '비고');
        $sheet2->mergeCells('I1:J1');

        if (!empty($data_fullfill1)) {
            $sheet_row = 2;
            foreach ($data_fullfill1 as $data) {
                $sheet2->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet2->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet2->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet2->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet2->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet2->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet2->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet2->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet2->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet2->setCellValue('A' . $sheet_row, '합계');
            $sheet2->setCellValue('B' . $sheet_row, '');
            $sheet2->setCellValue('C' . $sheet_row, '');
            $sheet2->setCellValue('D' . $sheet_row, '');
            $sheet2->setCellValue('E' . $sheet_row, '');
            $sheet2->setCellValue('F' . $sheet_row, $center_work_supply_price);
            $sheet2->setCellValue('G' . $sheet_row, $center_work_vat);
            $sheet2->setCellValue('H' . $sheet_row, $center_work_sum);
            $sheet2->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);

        $sheet3->setTitle('센터 작업료');

        $sheet3->setCellValue('A1', '항목');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', '건수');
        $sheet3->setCellValue('F1', '공급가');
        $sheet3->setCellValue('G1', '부가세');
        $sheet3->setCellValue('H1', '급액');
        $sheet3->setCellValue('I1', '비고');
        $sheet3->mergeCells('I1:J1');

        if (!empty($data_fullfill2)) {
            $sheet_row = 2;
            foreach ($data_fullfill2 as $data) {
                $sheet3->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet3->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet3->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet3->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet3->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet3->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet3->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet3->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet3->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet3->setCellValue('A' . $sheet_row, '합계');
            $sheet3->setCellValue('B' . $sheet_row, '');
            $sheet3->setCellValue('C' . $sheet_row, '');
            $sheet3->setCellValue('D' . $sheet_row, '');
            $sheet3->setCellValue('E' . $sheet_row, '');
            $sheet3->setCellValue('F' . $sheet_row, $domestic_shipping_supply_price);
            $sheet3->setCellValue('G' . $sheet_row, $domestic_shipping_vat);
            $sheet3->setCellValue('H' . $sheet_row, $domestic_shipping_sum);
            $sheet3->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet4 = $spreadsheet->getSheet(3);

        $sheet4->setTitle('센터 작업료');

        $sheet4->setCellValue('A1', '항목');
        $sheet4->mergeCells('A1:B1');
        $sheet4->setCellValue('C1', '단위');
        $sheet4->setCellValue('D1', '단가');
        $sheet4->setCellValue('E1', '건수');
        $sheet4->setCellValue('F1', '공급가');
        $sheet4->setCellValue('G1', '부가세');
        $sheet4->setCellValue('H1', '급액');
        $sheet4->setCellValue('I1', '비고');
        $sheet4->mergeCells('I1:J1');

        if (!empty($data_fullfill3)) {
            $sheet_row = 2;
            foreach ($data_fullfill3 as $data) {
                $sheet4->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet4->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet4->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet4->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet4->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet4->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet4->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet4->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet4->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet4->setCellValue('A' . $sheet_row, '합계');
            $sheet4->setCellValue('B' . $sheet_row, '');
            $sheet4->setCellValue('C' . $sheet_row, '');
            $sheet4->setCellValue('D' . $sheet_row, '');
            $sheet4->setCellValue('E' . $sheet_row, '');
            $sheet4->setCellValue('F' . $sheet_row, $overseas_shipping_supply_price);
            $sheet4->setCellValue('G' . $sheet_row, $overseas_shipping_vat);
            $sheet4->setCellValue('H' . $sheet_row, $overseas_shipping_sum);
            $sheet4->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet5 = $spreadsheet->getSheet(4);

        $sheet5->setTitle('센터 작업료');

        $sheet5->setCellValue('A1', '항목');
        $sheet5->mergeCells('A1:B1');
        $sheet5->setCellValue('C1', '단위');
        $sheet5->setCellValue('D1', '단가');
        $sheet5->setCellValue('E1', '건수');
        $sheet5->setCellValue('F1', '공급가');
        $sheet5->setCellValue('G1', '부가세');
        $sheet5->setCellValue('H1', '급액');
        $sheet5->setCellValue('I1', '비고');
        $sheet5->mergeCells('I1:J1');

        if (!empty($data_fullfill4)) {
            $sheet_row = 2;
            foreach ($data_fullfill4 as $data) {
                $sheet5->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet5->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet5->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet5->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet5->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet5->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet5->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet5->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet5->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet5->setCellValue('A' . $sheet_row, '합계');
            $sheet5->setCellValue('B' . $sheet_row, '');
            $sheet5->setCellValue('C' . $sheet_row, '');
            $sheet5->setCellValue('D' . $sheet_row, '');
            $sheet5->setCellValue('E' . $sheet_row, '');
            $sheet5->setCellValue('F' . $sheet_row, $keep_supply_price);
            $sheet5->setCellValue('G' . $sheet_row, $keep_vat);
            $sheet5->setCellValue('H' . $sheet_row, $keep_sum);
            $sheet5->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet6 = $spreadsheet->getSheet(5);

        $sheet6->setTitle('센터 작업료');

        $sheet6->setCellValue('A1', '항목');
        $sheet6->mergeCells('A1:B1');
        $sheet6->setCellValue('C1', '단위');
        $sheet6->setCellValue('D1', '단가');
        $sheet6->setCellValue('E1', '건수');
        $sheet6->setCellValue('F1', '공급가');
        $sheet6->setCellValue('G1', '부가세');
        $sheet6->setCellValue('H1', '급액');
        $sheet6->setCellValue('I1', '비고');
        $sheet6->mergeCells('I1:J1');

        if (!empty($data_fullfill5)) {
            $sheet_row = 2;
            foreach ($data_fullfill5 as $data) {
                $sheet6->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet6->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet6->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet6->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet6->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet6->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet6->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet6->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet6->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet6->setCellValue('A' . $sheet_row, '합계');
            $sheet6->setCellValue('B' . $sheet_row, '');
            $sheet6->setCellValue('C' . $sheet_row, '');
            $sheet6->setCellValue('D' . $sheet_row, '');
            $sheet6->setCellValue('E' . $sheet_row, '');
            $sheet6->setCellValue('F' . $sheet_row, $subsidiary_supply_price);
            $sheet6->setCellValue('G' . $sheet_row, $subsidiary_supply_vat);
            $sheet6->setCellValue('H' . $sheet_row, $subsidiary_supply_sum);
            $sheet6->setCellValue('I' . $sheet_row, '');
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Excel-Fulfillment-Final-Monthbill-Check-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Excel-Fulfillment-Final-Monthbill-Check-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }
    public function fulfillment_add_monthbill_check($rgd_no)
    {

        $data_fullfill1 = $data_fullfill2 = $data_fullfill3 = $data_fullfill4 = $data_fullfill5 = null;

        $rmd_no1 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill1_additional', 'fulfill1_final');
        $rmd_no2 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill2_additional', 'fulfill2_final');
        $rmd_no3 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill3_additional', 'fulfill3_final');
        $rmd_no4 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill4_additional', 'fulfill4_final');
        $rmd_no5 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill5_additional', 'fulfill5_final');

        $data_fullfill1 = !empty($rmd_no1) ? $this->get_set_data_raw($rmd_no1) : array();
        $data_fullfill2 = !empty($rmd_no2) ? $this->get_set_data_raw($rmd_no2) : array();
        $data_fullfill3 = !empty($rmd_no3) ? $this->get_set_data_raw($rmd_no3) : array();
        $data_fullfill4 = !empty($rmd_no4) ? $this->get_set_data_raw($rmd_no4) : array();
        $data_fullfill5 = !empty($rmd_no5) ? $this->get_set_data_raw($rmd_no5) : array();

        $center_work_supply_price = $center_work_vat = $center_work_sum = 0;
        if (!empty($data_fullfill1)) {
            foreach ($data_fullfill1 as $dt1) {
                $center_work_supply_price += !empty($dt1->rd_data5) ? $dt1->rd_data5 : 0;
                $center_work_vat += !empty($dt1->rd_data6) ? $dt1->rd_data6 : 0;
                $center_work_sum += !empty($dt1->rd_data7) ? $dt1->rd_data7 : 0;
            }
        }

        $domestic_shipping_supply_price = $domestic_shipping_vat = $domestic_shipping_sum = 0;

        if (!empty($data_fullfill2)) {
            foreach ($data_fullfill2 as $dt2) {
                $domestic_shipping_supply_price += !empty($dt2->rd_data5) ? $dt2->rd_data5 : 0;
                $domestic_shipping_vat += !empty($dt2->rd_data6) ? $dt2->rd_data6 : 0;
                $domestic_shipping_sum += !empty($dt2->rd_data7) ? $dt2->rd_data7 : 0;
            }
        }

        $overseas_shipping_supply_price = $overseas_shipping_vat = $overseas_shipping_sum = 0;

        if (!empty($data_fullfill3)) {
            foreach ($data_fullfill3 as $dt3) {
                $overseas_shipping_supply_price += !empty($dt3->rd_data5) ? $dt3->rd_data5 : 0;
                $overseas_shipping_vat += !empty($dt3->rd_data6) ? $dt3->rd_data6 : 0;
                $overseas_shipping_sum += !empty($dt3->rd_data7) ? $dt3->rd_data7 : 0;
            }
        }

        $keep_supply_price = $keep_vat = $keep_sum = 0;

        if (!empty($data_fullfill4)) {
            foreach ($data_fullfill4 as $dt4) {
                $keep_supply_price += !empty($dt4->rd_data5) ? $dt4->rd_data5 : 0;
                $keep_vat += !empty($dt4->rd_data6) ? $dt4->rd_data6 : 0;
                $keep_sum += !empty($dt4->rd_data7) ? $dt4->rd_data7 : 0;
            }
        }

        $subsidiary_supply_price = $subsidiary_supply_vat = $subsidiary_supply_sum = 0;

        if (!empty($data_fullfill5)) {
            foreach ($data_fullfill5 as $dt5) {
                $subsidiary_supply_price += !empty($dt5->rd_data5) ? $dt5->rd_data5 : 0;
                $subsidiary_supply_vat += !empty($dt5->rd_data6) ? $dt5->rd_data6 : 0;
                $subsidiary_supply_sum += !empty($dt5->rd_data7) ? $dt5->rd_data7 : 0;
            }
        }

        $total_supply_price = $center_work_supply_price + $domestic_shipping_supply_price + $overseas_shipping_supply_price + $keep_supply_price + $subsidiary_supply_price;
        $total_supply_vat = $center_work_vat + $domestic_shipping_vat + $overseas_shipping_vat + $keep_vat + $subsidiary_supply_vat;
        $total_sum = $center_work_sum + $domestic_shipping_sum + $overseas_shipping_sum + $keep_sum + $subsidiary_supply_sum;

        DB::beginTransaction();
        $co_no = Auth::user()->co_no;
        DB::commit();
        $user = Auth::user();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        $sheet->setTitle('종합');

        $sheet->setCellValue('A1', '항목');
        $sheet->mergeCells('A1:B1');
        $sheet->setCellValue('C1', '공급가');
        $sheet->setCellValue('D1', '부가세');
        $sheet->setCellValue('E1', '합계');
        $sheet->setCellValue('F1', '비고');
        $sheet->mergeCells('F1:G1');

        $sheet->setCellValue('A2', '센터작업료');
        $sheet->mergeCells('A2:B2');
        $sheet->setCellValue('C2', $center_work_supply_price);
        $sheet->setCellValue('D2', $center_work_vat);
        $sheet->setCellValue('E2', $center_work_sum);
        $sheet->setCellValue('F2', '');
        $sheet->mergeCells('F2:G2');

        $sheet->setCellValue('A3', '국내 운송료');
        $sheet->mergeCells('A3:B3');
        $sheet->setCellValue('C3', $domestic_shipping_supply_price);
        $sheet->setCellValue('D3', $domestic_shipping_vat);
        $sheet->setCellValue('E3', $domestic_shipping_sum);
        $sheet->setCellValue('F3', '');
        $sheet->mergeCells('F3:G3');

        $sheet->setCellValue('A4', '해외 운송료');
        $sheet->mergeCells('A4:B4');
        $sheet->setCellValue('C4', $overseas_shipping_supply_price);
        $sheet->setCellValue('D4', $overseas_shipping_vat);
        $sheet->setCellValue('E4', $overseas_shipping_sum);
        $sheet->setCellValue('F4', '');
        $sheet->mergeCells('F4:G4');

        $sheet->setCellValue('A5', '보관');
        $sheet->mergeCells('A5:B5');
        $sheet->setCellValue('C5', $keep_supply_price);
        $sheet->setCellValue('D5', $keep_vat);
        $sheet->setCellValue('E5', $keep_sum);
        $sheet->setCellValue('F5', '');
        $sheet->mergeCells('F5:G5');

        $sheet->setCellValue('A6', '부자재');
        $sheet->mergeCells('A6:B6');
        $sheet->setCellValue('C6', $subsidiary_supply_price);
        $sheet->setCellValue('D6', $subsidiary_supply_vat);
        $sheet->setCellValue('E6', $subsidiary_supply_sum);
        $sheet->setCellValue('F6', '');
        $sheet->mergeCells('F6:G6');

        $sheet->setCellValue('A7', '합계');
        $sheet->mergeCells('A7:B7');
        $sheet->setCellValue('C7', $total_supply_price);
        $sheet->setCellValue('D7', $total_supply_vat);
        $sheet->setCellValue('E7', $total_sum);
        $sheet->setCellValue('F7', '');
        $sheet->mergeCells('F7:G7');

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);

        $sheet2->setTitle('센터 작업료');

        $sheet2->setCellValue('A1', '항목');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', '건수');
        $sheet2->setCellValue('F1', '공급가');
        $sheet2->setCellValue('G1', '부가세');
        $sheet2->setCellValue('H1', '급액');
        $sheet2->setCellValue('I1', '비고');
        $sheet2->mergeCells('I1:J1');

        if (!empty($data_fullfill1)) {
            $sheet_row = 2;
            foreach ($data_fullfill1 as $data) {
                $sheet2->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet2->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet2->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet2->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet2->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet2->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet2->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet2->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet2->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet2->setCellValue('A' . $sheet_row, '합계');
            $sheet2->setCellValue('B' . $sheet_row, '');
            $sheet2->setCellValue('C' . $sheet_row, '');
            $sheet2->setCellValue('D' . $sheet_row, '');
            $sheet2->setCellValue('E' . $sheet_row, '');
            $sheet2->setCellValue('F' . $sheet_row, $center_work_supply_price);
            $sheet2->setCellValue('G' . $sheet_row, $center_work_vat);
            $sheet2->setCellValue('H' . $sheet_row, $center_work_sum);
            $sheet2->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);

        $sheet3->setTitle('센터 작업료');

        $sheet3->setCellValue('A1', '항목');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', '건수');
        $sheet3->setCellValue('F1', '공급가');
        $sheet3->setCellValue('G1', '부가세');
        $sheet3->setCellValue('H1', '급액');
        $sheet3->setCellValue('I1', '비고');
        $sheet3->mergeCells('I1:J1');

        if (!empty($data_fullfill2)) {
            $sheet_row = 2;
            foreach ($data_fullfill2 as $data) {
                $sheet3->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet3->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet3->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet3->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet3->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet3->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet3->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet3->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet3->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet3->setCellValue('A' . $sheet_row, '합계');
            $sheet3->setCellValue('B' . $sheet_row, '');
            $sheet3->setCellValue('C' . $sheet_row, '');
            $sheet3->setCellValue('D' . $sheet_row, '');
            $sheet3->setCellValue('E' . $sheet_row, '');
            $sheet3->setCellValue('F' . $sheet_row, $domestic_shipping_supply_price);
            $sheet3->setCellValue('G' . $sheet_row, $domestic_shipping_vat);
            $sheet3->setCellValue('H' . $sheet_row, $domestic_shipping_sum);
            $sheet3->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet4 = $spreadsheet->getSheet(3);

        $sheet4->setTitle('센터 작업료');

        $sheet4->setCellValue('A1', '항목');
        $sheet4->mergeCells('A1:B1');
        $sheet4->setCellValue('C1', '단위');
        $sheet4->setCellValue('D1', '단가');
        $sheet4->setCellValue('E1', '건수');
        $sheet4->setCellValue('F1', '공급가');
        $sheet4->setCellValue('G1', '부가세');
        $sheet4->setCellValue('H1', '급액');
        $sheet4->setCellValue('I1', '비고');
        $sheet4->mergeCells('I1:J1');

        if (!empty($data_fullfill3)) {
            $sheet_row = 2;
            foreach ($data_fullfill3 as $data) {
                $sheet4->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet4->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet4->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet4->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet4->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet4->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet4->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet4->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet4->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet4->setCellValue('A' . $sheet_row, '합계');
            $sheet4->setCellValue('B' . $sheet_row, '');
            $sheet4->setCellValue('C' . $sheet_row, '');
            $sheet4->setCellValue('D' . $sheet_row, '');
            $sheet4->setCellValue('E' . $sheet_row, '');
            $sheet4->setCellValue('F' . $sheet_row, $overseas_shipping_supply_price);
            $sheet4->setCellValue('G' . $sheet_row, $overseas_shipping_vat);
            $sheet4->setCellValue('H' . $sheet_row, $overseas_shipping_sum);
            $sheet4->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet5 = $spreadsheet->getSheet(4);

        $sheet5->setTitle('센터 작업료');

        $sheet5->setCellValue('A1', '항목');
        $sheet5->mergeCells('A1:B1');
        $sheet5->setCellValue('C1', '단위');
        $sheet5->setCellValue('D1', '단가');
        $sheet5->setCellValue('E1', '건수');
        $sheet5->setCellValue('F1', '공급가');
        $sheet5->setCellValue('G1', '부가세');
        $sheet5->setCellValue('H1', '급액');
        $sheet5->setCellValue('I1', '비고');
        $sheet5->mergeCells('I1:J1');

        if (!empty($data_fullfill4)) {
            $sheet_row = 2;
            foreach ($data_fullfill4 as $data) {
                $sheet5->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet5->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet5->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet5->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet5->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet5->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet5->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet5->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet5->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet5->setCellValue('A' . $sheet_row, '합계');
            $sheet5->setCellValue('B' . $sheet_row, '');
            $sheet5->setCellValue('C' . $sheet_row, '');
            $sheet5->setCellValue('D' . $sheet_row, '');
            $sheet5->setCellValue('E' . $sheet_row, '');
            $sheet5->setCellValue('F' . $sheet_row, $keep_supply_price);
            $sheet5->setCellValue('G' . $sheet_row, $keep_vat);
            $sheet5->setCellValue('H' . $sheet_row, $keep_sum);
            $sheet5->setCellValue('I' . $sheet_row, '');
        }

        $spreadsheet->createSheet();
        $sheet6 = $spreadsheet->getSheet(5);

        $sheet6->setTitle('센터 작업료');

        $sheet6->setCellValue('A1', '항목');
        $sheet6->mergeCells('A1:B1');
        $sheet6->setCellValue('C1', '단위');
        $sheet6->setCellValue('D1', '단가');
        $sheet6->setCellValue('E1', '건수');
        $sheet6->setCellValue('F1', '공급가');
        $sheet6->setCellValue('G1', '부가세');
        $sheet6->setCellValue('H1', '급액');
        $sheet6->setCellValue('I1', '비고');
        $sheet6->mergeCells('I1:J1');

        if (!empty($data_fullfill5)) {
            $sheet_row = 2;
            foreach ($data_fullfill5 as $data) {
                $sheet6->setCellValue('A' . $sheet_row, $data->rd_cate1);
                $sheet6->setCellValue('B' . $sheet_row, $data->rd_cate2);
                $sheet6->setCellValue('C' . $sheet_row, !empty($data->rd_data1) ? $data->rd_data1 : '');
                $sheet6->setCellValue('D' . $sheet_row, !empty($data->rd_data2) ? $data->rd_data2 : '');
                $sheet6->setCellValue('E' . $sheet_row, !empty($data->rd_data4) ? $data->rd_data4 : '');
                $sheet6->setCellValue('F' . $sheet_row, !empty($data->rd_data5) ? $data->rd_data5 : '');
                $sheet6->setCellValue('G' . $sheet_row, !empty($data->rd_data6) ? $data->rd_data6 : '');
                $sheet6->setCellValue('H' . $sheet_row, !empty($data->rd_data7) ? $data->rd_data7 : '');
                $sheet6->setCellValue('I' . $sheet_row, !empty($data->rd_data8) ? $data->rd_data8 : '');
                $sheet_row++;
            }
            $sheet6->setCellValue('A' . $sheet_row, '합계');
            $sheet6->setCellValue('B' . $sheet_row, '');
            $sheet6->setCellValue('C' . $sheet_row, '');
            $sheet6->setCellValue('D' . $sheet_row, '');
            $sheet6->setCellValue('E' . $sheet_row, '');
            $sheet6->setCellValue('F' . $sheet_row, $subsidiary_supply_price);
            $sheet6->setCellValue('G' . $sheet_row, $subsidiary_supply_vat);
            $sheet6->setCellValue('H' . $sheet_row, $subsidiary_supply_sum);
            $sheet6->setCellValue('I' . $sheet_row, '');
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = '../storage/download/' . $user->mb_no . '/';
        } else {
            $path = '../storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Excel-Fulfillment_Add_Monthbill_Check-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Excel-Fulfillment_Add_Monthbill_Check-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
        ], 500);
        ob_end_clean();
    }
    public function deleteRateData($rm_no)
    {
        try {
            $rmd_no = RateMetaData::where('rm_no', $rm_no)->first()->rmd_no;
            $delete_rate_data = RateData::where('rmd_no', $rmd_no)->delete();
            $delete_rate_meta = RateMeta::where('rm_no', $rm_no)->delete();
            $delete_rate_meta_data = RateMetaData::where('rm_no', $rm_no)->delete();

            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function cancel_bill(Request $request)
    {
        try {
            DB::beginTransaction();
            // if ($request->bill_type == 'case') {
            //     $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
            // } else if ($request->bill_type == 'monthly') {
            //     foreach ($request->rgds as $rgd) {
            //         ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->delete();
            //     }
            // }
            $user = Auth::user();
            //month_bill_edit && case_bill_edit && case_bill_final
            if ($request->bill_type == 'case_bill_final' || $request->bill_type == 'case_bill_edit' || $request->bill_type == 'month_bill_edit') {
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                    'rgd_status5' => 'cancel',
                    'rgd_canceled_date' => Carbon::now()->toDateTimeString(),
                ]);
                $insert_cancel_bill = CancelBillHistory::insertGetId([
                    'mb_no' => Auth::user()->mb_no,
                    'rgd_no' => $request->rgd_no,
                    'cbh_status_after' => 'cancel',
                    'cbh_type' => 'cancel',
                ]);
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
                $rgd_parent = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();

                if ($rgd_parent->rgd_status5 == 'issued') {
                    ReceivingGoodsDelivery::where('rgd_no', $rgd_parent->rgd_no)->update([
                        'rgd_status5' => ($rgd_parent->rgd_status4 == '확정청구서' ? 'confirmed' : null),
                        'rgd_issued_date' => NULL,
                    ]);
                }

            } else if ($request->bill_type == 'monthly') { //final bill

                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();

                $settlement_number = $rgd->rgd_settlement_number;

                $rgds = ReceivingGoodsDelivery::where('rgd_settlement_number', $settlement_number)->get();
                foreach ($rgds as $rgd) {
                    $rgd_update = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                        'rgd_status5' => 'cancel',
                        'rgd_canceled_date' => Carbon::now()->toDateTimeString(),
                    ]);
                    CancelBillHistory::insertGetId([
                        'mb_no' => Auth::user()->mb_no,
                        'rgd_no' => $rgd['rgd_no'],
                        'cbh_status_after' => 'cancel',
                        'cbh_type' => 'cancel',
                    ]);
                    $rgd_parent = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_parent_no'])->first();

                    ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_parent_no'])->update([
                        'rgd_status5' => ($rgd_parent['rgd_status4'] == '확정청구서' ? 'confirmed' : null),
                        'rgd_issued_date' => NULL,
                    ]);
                    CancelBillHistory::insertGetId([
                        'mb_no' => Auth::user()->mb_no,
                        'rgd_no' => $rgd['rgd_parent_no'],
                        'cbh_status_after' => 'revert',
                        'cbh_type' => 'revert',
                    ]);
                }
            } else if ($request->bill_type == 'monthly_service2') { //page 253 service 2
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();

                $settlement_number = $rgd->rgd_settlement_number;

                $rgds = ReceivingGoodsDelivery::where('rgd_settlement_number', $settlement_number)->get();
                foreach ($rgds as $rgd) {
                    $rgd_update = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                        'rgd_status5' => 'cancel',
                        'rgd_canceled_date' => Carbon::now()->toDateTimeString(),
                    ]);
                    $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->first();
                    $rgd_parent_no = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->update(
                        $user->mb_type == 'spasys' ? [
                            'rgd_status5' => NULL,
                            'rgd_issued_date' => NULL,
                        ] : [
                            'rgd_status4' => NULL,
                            'rgd_issued_date' => NULL,
                        ]
                    );
                    RateMetaData::where('rgd_no', $rgd->rgd_parent_no)->update([
                        'rgd_no' => $rgd['rgd_no'],
                    ]);

                    CancelBillHistory::insertGetId([
                        'mb_no' => Auth::user()->mb_no,
                        'rgd_no' => $rgd['rgd_no'],
                        'cbh_status_after' => 'cancel',
                        'cbh_type' => 'cancel',
                    ]);
                }
            } else if ($request->bill_type == 'case_bill_final_issue') {
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                    'rgd_status5' => NULL,
                    'rgd_confirmed_date' => NULL
                ]);
                $insert_cancel_bill = CancelBillHistory::insertGetId([
                    'mb_no' => Auth::user()->mb_no,
                    'rgd_no' => $request->rgd_no,
                    'cbh_status_before' => 'issued',
                    'cbh_status_after' =>  NULL,
                    'cbh_type' => 'revert',
                ]);

            } else if ($request->bill_type == 'month_bill_final_issue') { //page 264
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();

                $settlement_number = $rgd->rgd_settlement_number;

                $rgds = ReceivingGoodsDelivery::where('rgd_settlement_number', $settlement_number)->get();
                foreach ($rgds as $rgd) {
                    $rgd_update = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                        'rgd_status5' => null,
                    ]);
                    CancelBillHistory::insertGetId([
                        'mb_no' => Auth::user()->mb_no,
                        'rgd_no' => $rgd['rgd_no'],
                        'cbh_status_before' => 'confirmed',
                        'cbh_status_after' =>  null,
                        'cbh_type' => 'revert',
                    ]);
                    $rgd_parent_no = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();

                    $rgd_update_parent = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_parent_no'])->update([
                        'rgd_status5' => 'issued',
                    ]);
                    CancelBillHistory::insertGetId([
                        'mb_no' => Auth::user()->mb_no,
                        'rgd_no' => $rgd['rgd_parent_no'],
                        'cbh_status_before' => 'confirmed',
                        'cbh_status_after' => null,
                        'cbh_type' => 'revert',
                    ]);
                }
            } else { //case_bill,monthly_bill
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                    'rgd_status5' => 'cancel',
                    'rgd_canceled_date' => Carbon::now()->toDateTimeString(),
                ]);
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
                $rgd_parent_no = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->update(
                    $user->mb_type == 'spasys' ? [
                        'rgd_status5' => NULL,
                        'rgd_issued_date' => NULL,
                    ] : [
                        'rgd_status4' => NULL,
                        'rgd_issued_date' => NULL,
                    ]
                );
                RateMetaData::where('rgd_no', $rgd->rgd_parent_no)->update([
                    'rgd_no' => $rgd['rgd_no'],
                ]);

                $insert_cancel_bill = CancelBillHistory::insertGetId([
                    'mb_no' => Auth::user()->mb_no,
                    'rgd_no' => $request->rgd_no,
                    'cbh_status_after' => 'cancel',
                    'cbh_type' => 'cancel',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Success',
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function get_list_cancel_bill(CancelBillRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $list = CancelBillHistory::where('rgd_no', '=', $request->rgd_no)->where('cbh_type', '=', 'cancel')->paginate($per_page, ['*'], 'page', $page);

            return response()->json($list);
        } catch (\Exception $e) {
            return $e;
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function get_list_payment_history(CancelBillRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $list = CancelBillHistory::with('member')->where('rgd_no', '=', $request->rgd_no)->where('cbh_type', '=', 'payment')->whereIn('cbh_status_after', ['payment_bill', 'cancel_payment_bill'])->orderBy('cbh_no', 'DESC')->paginate($per_page, ['*'], 'page', $page);

            return response()->json($list);
        } catch (\Exception $e) {
            return $e;
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function tax_invoice_issue(request $request)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            foreach ($request->rgds as $rgd) {
                $tax_number = CommonFunc::generate_tax_number($request->rgds[0]['rgd_no']);
                ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                    'rgd_status7' => 'taxed',
                    'rgd_tax_invoice_number' => $tax_number ? $tax_number : null,
                    'rgd_tax_invoice_date' => Carbon::now()->toDateTimeString(),
                ]);

                $id = TaxInvoiceDivide::insertGetId([
                    'tid_supply_price' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : $rgd['rate_data_general']['rdg_supply_price4'],
                    'tid_vat' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : $rgd['rate_data_general']['rdg_supply_price4'],
                    'tid_sum' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : $rgd['rate_data_general']['rdg_supply_price4'],
                    'rgd_no' => $rgd['rgd_no'],
                    'mb_no' => $user->mb_no,
                ]);
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

    public function get_tax_invoice_by_rgd_no(request $request)
    {
        try {
            DB::beginTransaction();

            $rgd = ReceivingGoodsDelivery::with('w_no')->where('rgd_no', $request['rgd_no'])->first();

            $rgds = ReceivingGoodsDelivery::with('w_no')->where('rgd_tax_invoice_number', $rgd['rgd_tax_invoice_number'])->get();

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rgds' => $rgds,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function get_rate_data_info_by_rgd($rgd_no)
    {
        try {
            DB::beginTransaction();
            $rate_data_general = RateDataGeneral::where('rgd_no', $rgd_no)->first();


            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rate_data_general' => $rate_data_general,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

}

<?php

namespace App\Http\Controllers\RateData;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBill\CancelBillRequest;
use App\Http\Requests\RateData\RateDataRequest;
use App\Http\Requests\RateData\RateDataSendMailRequest;
use App\Models\AdjustmentGroup;
use App\Models\CancelBillHistory;
use App\Models\Company;
use App\Models\CompanyPayment;
use App\Models\Payment;
use App\Models\Member;
use App\Models\Contract;
use App\Models\Export;
use App\Models\Import;
use App\Models\RateData;
use App\Models\RateDataGeneral;
use App\Models\RateMeta;
use App\Models\RateMetaData;
use App\Models\ReceivingGoodsDelivery;
use App\Models\Warehousing;
use App\Models\AlarmData;
use App\Models\Alarm;
use App\Utils\CommonFunc;
use App\Utils\Messages;
use App\Models\TaxInvoiceDivide;
use App\Models\ImportExpected;
use Carbon\Carbon;
use App\Models\File as FileTable;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

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

            if (isset($validated['create_new'])) {
                $create_new = $validated['create_new'];
            } else {
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
                $rmd_no_new = $rmd_no;
            } else if (isset($validated['rmd_no']) && isset($validated['rm_no'])) {
                $rmd = RateMetaData::where('rmd_no', $validated['rmd_no'])->first();
                $index = RateMetaData::where('rm_no', $validated['rm_no'])->get()->count() + 1;
                $rmd_no = RateMetaData::insertGetId(
                    [
                        'mb_no' => Auth::user()->mb_no,
                        'rm_no' => $validated['rm_no'],
                        'rmd_number' => CommonFunc::generate_rmd_number($validated['rm_no'], $index),
                        'rmd_parent_no' => $rmd->rmd_parent_no ? $rmd->rmd_parent_no : $validated['rmd_no'],
                        'rmd_mail_detail1a' => isset($validated['rmd_mail_detail1a']) ? $validated['rmd_mail_detail1a'] : '',
                        'rmd_mail_detail1b' => isset($validated['rmd_mail_detail1b']) ? $validated['rmd_mail_detail1b'] : '',
                        'rmd_mail_detail1c' => isset($validated['rmd_mail_detail1c']) ? $validated['rmd_mail_detail1c'] : '',
                        'rmd_mail_detail2' => isset($validated['rmd_mail_detail2']) ? $validated['rmd_mail_detail2'] : '',
                        'rmd_mail_detail3' => isset($validated['rmd_mail_detail3']) ? $validated['rmd_mail_detail3'] : '',
                    ]
                );

                $rmd_no_new = $rmd_no;
                $rmd_arr = RateMetaData::where('rmd_number', $rmd->rmd_number)->orderBy('rmd_no', 'DESC')->get();
            } else if (isset($validated['rmd_no']) && isset($validated['co_no']) && $create_new == true) {
                $rmd = RateMetaData::where('rmd_no', $validated['rmd_no'])->first();
                $index = RateMetaData::where('co_no', $validated['co_no'])->get()->count() + 1;
                $rmd_no = RateMetaData::insertGetId(
                    [
                        'mb_no' => Auth::user()->mb_no,
                        'co_no' => $validated['co_no'],
                        'rmd_number' => CommonFunc::generate_rmd_number($validated['co_no'], $index),
                        'rmd_parent_no' => $rmd->rmd_parent_no ? $rmd->rmd_parent_no : $validated['rmd_no'],
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
            if ($create_new == true)
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
            // if (isset($validated['type'])) {
            //     if (
            //         $validated['type'] == 'domestic_additional_edit' ||
            //         $validated['type'] == 'work_additional_edit' ||
            //         $validated['type'] == 'storage_additional_edit' ||
            //         $validated['type'] == 'work_monthly_additional_edit' ||
            //         $validated['type'] == 'storage_monthly_additional_edit' ||
            //         $validated['type'] == 'domestic_monthly_additional_edit' ||
            //         $validated['type'] == 'edit' ||
            //         $validated['set_type'] == 'work_final_edit' ||
            //         $validated['set_type'] == 'storage_final_edit'
            //     ) {
            //         $validated['rgd_no'] = $rgd->rgd_parent_no;
            //     }
            // }
            $rmd_file = RateMetaData::with('files')->where('rmd_no', $request->rmd_no_file)->first();

            if (isset($w_no)) {
                $is_new = RateMetaData::where([
                    'rgd_no' => $validated['rgd_no'],
                    'set_type' => $validated['set_type']
                ])->first();

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
            $check_exist_rmd = RateMetaData::where('rmd_no', $rmd->rmd_no)->first();
            if ($check_exist_rmd->rmd_no != $request->rmd_no_file) {
                if (isset($rmd_file)) {
                    $files = [];
                    foreach ($rmd_file->files as $key => $file) {
                        $files[] = [
                            'file_table' => 'rate_data',
                            'file_table_key' => $rmd->rmd_no,
                            'file_name_old' => $file->file_name_old,
                            'file_name' => $file->file_name,
                            'file_size' => $file->file_size,
                            'file_extension' => $file->file_extension,
                            'file_position' => $file->file_position,
                            'file_url' => $file->file_url
                        ];
                    }
                    FileTable::insert($files);
                }
            }

            $check_duplicate_cate = 0;
            $check_duplicate_total = 0;

            foreach ($validated['rate_data'] as $index => $val) {
                Log::error($val);
                if (!isset($validated['rate_data'][$index]['rd_cate1'])) {
                    $validated['rate_data'][$index]['rd_cate1'] = isset($validated['rate_data'][$index]['rd_cate2']) ?  $validated['rate_data'][$index]['rd_cate2'] : '';
                    $val['rd_cate1'] = isset($validated['rate_data'][$index]['rd_cate2']) ?  $validated['rate_data'][$index]['rd_cate2'] : '';
                } else if ($validated['rate_data'][$index]['rd_cate1'] == '') {
                    $validated['rate_data'][$index]['rd_cate1'] = isset($validated['rate_data'][$index]['rd_cate2']) ?  $validated['rate_data'][$index]['rd_cate2'] : '';
                    $val['rd_cate1'] = isset($validated['rate_data'][$index]['rd_cate2']) ?  $validated['rate_data'][$index]['rd_cate2'] : '';
                }
                if (!isset($validated['rate_data'][$index]['rd_cate2'])) {
                    $validated['rate_data'][$index]['rd_cate2'] = isset($validated['rate_data'][$index]['rd_cate1']) ?  $validated['rate_data'][$index]['rd_cate1'] : '';
                    $val['rd_cate2'] = isset($validated['rate_data'][$index]['rd_cate1']) ?  $validated['rate_data'][$index]['rd_cate1'] : '';
                } else if ($validated['rate_data'][$index]['rd_cate2'] == '') {
                    $validated['rate_data'][$index]['rd_cate2'] = isset($validated['rate_data'][$index]['rd_cate1']) ?  $validated['rate_data'][$index]['rd_cate1'] : '';
                    $val['rd_cate2'] = isset($validated['rate_data'][$index]['rd_cate1']) ?  $validated['rate_data'][$index]['rd_cate1'] : '';
                }

                if ($index != 0) {
                    if ($val['rd_cate1'] != $validated['rate_data'][$index - 1]['rd_cate1']) {
                        $check_duplicate_cate = 0;
                        $check_duplicate_total = 0;
                    }
                }

                if ($val['rd_cate2'] == 'bonded345' || $val['rd_cate2'] == 'bonded1' || $val['rd_cate2'] == 'bonded2') {
                    $check_duplicate_cate += 1;
                }
                if ($val['rd_cate2'] == '소계') {
                    $check_duplicate_total += 1;
                }

                if (($val['rd_cate2'] == 'bonded345' || $val['rd_cate2'] == 'bonded1' || $val['rd_cate2'] == 'bonded2') && $check_duplicate_cate <= 1) {
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
                            'rd_cate1' => isset($val['rd_cate1']) ? $val['rd_cate1'] : (isset($val['rd_cate2']) ? $val['rd_cate2'] : ''),
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
                } else if ($val['rd_cate2'] == '소계' && $check_duplicate_total <= 1) {
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
                            'rd_cate1' => isset($val['rd_cate1']) ? $val['rd_cate1'] : (isset($val['rd_cate2']) ? $val['rd_cate2'] : ''),
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
                } else if ($val['rd_cate2'] != '소계' && $val['rd_cate2'] != 'bonded345' && $val['rd_cate2'] != 'bonded1' && $val['rd_cate2'] != 'bonded2') {
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
                            'rd_cate1' => isset($val['rd_cate1']) ? $val['rd_cate1'] : (isset($val['rd_cate2']) ? $val['rd_cate2'] : ''),
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
                $index = RateMetaData::where('co_no', $request['co_no'])->where(function ($q) {
                    $q->where('set_type', '=', 'estimated_costs')
                        ->orWhere('set_type', 'precalculate');
                })->get()->count() + 1;
                $rmd_no = RateMetaData::insertGetId(
                    [
                        'co_no' => $request['co_no'],
                        'set_type' => $request['set_type'],
                        'rmd_number' => CommonFunc::generate_rmd_number($request['co_no'], $index),
                        'mb_no' => Auth::user()->mb_no,
                    ]
                );
            }

            $check_duplicate_cate = 0;
            $check_duplicate_total = 0;

            foreach ($request['rate_data'] as $index => $val) {
                Log::error($val);
                if ($index != 0) {
                    if ($val['rd_cate1'] != $request['rate_data'][$index - 1]['rd_cate1']) {
                        $check_duplicate_cate = 0;
                        $check_duplicate_total = 0;
                    }
                }


                if ($val['rd_cate2'] == 'bonded345' || $val['rd_cate2'] == 'bonded1' || $val['rd_cate2'] == 'bonded2') {
                    $check_duplicate_cate += 1;
                }
                if ($val['rd_cate2'] == '소계') {
                    $check_duplicate_total += 1;
                }

                if (($val['rd_cate2'] == 'bonded345' || $val['rd_cate2'] == 'bonded1' || $val['rd_cate2'] == 'bonded2') && $check_duplicate_cate <= 1) {
                    $rd_no = RateData::updateOrCreate(
                        [
                            'rd_no' => isset($val['rd_no']) ? $val['rd_no'] : null,
                            'rmd_no' => isset($rmd_no) ? $rmd_no : ($request->rmd_no ? $request->rmd_no : null),
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
                } else if ($val['rd_cate2'] == '소계' && $check_duplicate_total <= 1) {
                    $rd_no = RateData::updateOrCreate(
                        [
                            'rd_no' => isset($val['rd_no']) ? $val['rd_no'] : null,
                            'rmd_no' => isset($rmd_no) ? $rmd_no : ($request->rmd_no ? $request->rmd_no : null),
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
                } else if ($val['rd_cate2'] != '소계' && $val['rd_cate2'] != 'bonded345' && $val['rd_cate2'] != 'bonded1' && $val['rd_cate2'] != 'bonded2') {
                    $rd_no = RateData::updateOrCreate(
                        [
                            'rd_no' => isset($val['rd_no']) ? $val['rd_no'] : null,
                            'rmd_no' => isset($rmd_no) ? $rmd_no : ($request->rmd_no ? $request->rmd_no : null),
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
                }
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
        $previous_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->where(function ($q) {
            $q->where('rgd_status5', '!=', 'cancel')->orWhereNull('rgd_status5');
        })->first();

        $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();

        $rmd = RateMetaData::where(
            [
                'rgd_no' => $rgd_no,
                'set_type' => str_replace('_check', '', $set_type),
            ]
        )->first();
        if (!isset($rmd->rmd_no)) {
            $rmd = RateMetaData::where(
                [
                    'rgd_no' => $rgd->rgd_parent_no,
                    'set_type' => $set_type,
                ]
            )->first();
        }

        if (!isset($rmd->rmd_no) && $set_type == 'work_final') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
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
                        'rgd_no' => $rgd->rgd_no,
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
                        'rgd_no' => $rgd->rgd_no,
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
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded1_final',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded1_shop',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded1_spasys',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded1_shop',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded1_spasys',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded2_final') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded2_final',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
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
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded2_shop',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded2_spasys',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded3_final') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded3_final',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
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
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded3_shop',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded3_spasys',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded4_final') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded4_final',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
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
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded4_shop',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded4_spasys',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded5_final') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded5_final',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
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
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded5_shop',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded5_spasys',
                    ]
                )->first();
            }
        } else if (!isset($rmd->rmd_no) && $set_type == 'bonded6_final') {
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
                        'set_type' => 'bonded6_final',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_no,
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
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded6_shop',
                    ]
                )->first();
            }
            if (empty($rmd)) {
                $rmd = RateMetaData::where(
                    [
                        'rgd_no' => $rgd->rgd_parent_no,
                        'set_type' => 'bonded6_spasys',
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
            $files = RateMetaData::with('files')->where('rmd_no', '=', $rmd_no)->first();
            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data, 'warehousing' => $warehousing, 'files' => $files], 200);
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

    public function update_total_precalculate(Request $request)
    {
        try {
            $rate_data_general = RateDataGeneral::where('rmd_no', $request->rmd_no)->update(
                [
                    'rdg_precalculate_total' => $request->total
                ]
            );

            return response()->json(['message' => Messages::MSG_0007], 200);
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
            $index = RateMetaData::where('co_no', $request['co_no'])->where(function ($q) {
                $q->where('set_type', 'estimated_code')
                    ->orWhere('set_type', 'precalculate');
            })->get()->count() + 1;

            $rmd = RateMetaData::updateOrCreate(
                [
                    'rmd_no' => isset($request->rmd_no) ? $request->rmd_no : null,
                    'set_type' => 'precalculate',
                    'co_no' => isset($request->co_no) ? $request->co_no : null,
                ],
                $request->rmd_no ? [
                    'mb_no' => $user->mb_no,
                    'rmd_service' => isset($request->activeTab2) ? $request->activeTab2 : null,
                    'rmd_tab_child' => isset($request->rmd_tab_child) ? $request->rmd_tab_child : null,
                    'rmd_device' => $request->device == 'web' ? 1 : 0,
                ] : [
                    'mb_no' => $user->mb_no,
                    'rmd_number' => CommonFunc::generate_rmd_number($request['co_no'], $index),
                    'rmd_service' => isset($request->activeTab2) ? $request->activeTab2 : null,
                    'rmd_tab_child' => isset($request->rmd_tab_child) ? $request->rmd_tab_child : null,
                    'rmd_device' => $request->device == 'web' ? 1 : 0,
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

                    // 'rdg_precalculate_total' => $request->total,

                    'mb_no' => $user->mb_no,
                ]
            );

            return response()->json(['message' => Messages::MSG_0007, 'rmd_no' => $rmd->rmd_no, 'rate_data_general' => $rate_data_general, '$request->activeTab2' => $request->activeTab2], 200);
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
        $rmd = "";
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
                } else {
                    $co_rate_data1 = [];
                    $co_rate_data2 = [];
                    $co_rate_data3 = [];
                }
            }

            if ($rmd_no) {
                $rmd = RateMetaData::where('rmd_no', $rmd_no)->first();
                if (isset($rmd->rmd_parent_no)) {
                    $rmd_arr = RateMetaData::where('rmd_no', $rmd->rmd_parent_no)->orWhere('rmd_parent_no', $rmd->rmd_parent_no)->orderBy('rmd_no', 'DESC')->get();
                } else {
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
                'rmd_arr' => isset($rmd_arr) ? $rmd_arr : null,
                'rmd' => $rmd
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
                } else {
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
                } else {
                    $co_rate_data1 = [];
                    $co_rate_data2 = [];
                    $co_rate_data3 = [];
                }
            }

            $rate_meta_data = RateMetaData::where('rmd_no', $rmd_no)->first();

            if ($rmd_no) {
                $rmd = RateMetaData::where('rmd_no', $rmd_no)->first();
                if (isset($rmd->rmd_parent_no)) {
                    $rmd_arr = RateMetaData::where('rmd_no', $rmd->rmd_parent_no)->orWhere('rmd_parent_no', $rmd->rmd_parent_no)->orderBy('rmd_no', 'DESC')->get();
                } else {
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

            if (isset($validated['rmd_mail_detail1a']) || isset($validated['rmd_mail_detail1b']) || isset($validated['rmd_mail_detail1c']) || isset($validated['rmd_mail_detail2']) || isset($validated['rmd_mail_detail3'])) {
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
                        'rdg_precalculate_total' =>  isset($request->total['totalall5']) ? $request->total['totalall5'] : '',
                    ]
                );
            } else {
                if (isset($request->co_no)) {
                    $index = RateMetaData::where('co_no', $request['co_no'])->where(function ($q) {
                        $q->where('set_type', 'estimated_code')
                            ->orWhere('set_type', 'precalculate');
                    })->get()->count() + 1;
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
                            'rd_cate_meta1' => isset($val['rd_cate_meta1']) ? $val['rd_cate_meta1'] : '',
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
                        'rdg_precalculate_total' =>  isset($request->total['totalall5']) ? $request->total['totalall5'] : '',

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
            if ($request->type_page == "te_carry_out_number") {
                $export = Export::with(['import', 'import_expected'])->where('te_carry_out_number', $request->is_no)->first();
                $company = Company::with(['co_parent'])->where('co_license', $export->import_expected->tie_co_license)->first();
            } else if ($request->type_page == "ti_carry_in_number") {
                $import = Import::with(['import_expect', 'export_confirm'])->where('ti_carry_in_number', $request->is_no)->first();
                $company = Company::with(['co_parent'])->where('co_license', $import->import_expect->tie_co_license)->first();
            } else {
                $import_expected = ImportExpected::where('tie_logistic_manage_number', $request->is_no)->first();
                $company = Company::with(['co_parent'])->where('co_license', $import_expected->tie_co_license)->first();
            }

            //$company = Company::with(['co_parent'])->where('co_license', $export->import_expected->tie_co_license)->first();
            $rate_data = RateData::where('rd_cate_meta1', '보세화물');

            $rmd = RateMetaData::where('co_no', $user->mb_type == 'spasys' ? $company->co_parent_no : $company->co_no)->whereNull('set_type')->orderBy('rmd_no', 'DESC')->first();
            $rate_data = $rate_data->where('rd_co_no', $user->mb_type == 'spasys' ? $company->co_parent_no : $company->co_no);
            if (isset($rmd->rmd_no)) {
                $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
            } else {
                $rate_data = [];
            }


            // if ($user->mb_type == 'spasys') {
            //     $co_no = $company->co_no;

            //     $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
            //     $rate_data = $rate_data->where('rd_co_no', $company->co_parent->co_no);
            //     if (isset($rmd->rmd_no)) {
            //         $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
            //     }else {
            //         $rate_data = [];
            //     }
            // } else if ($user->mb_type == 'shop') {
            //     $co_no = $company->co_no;
            //     $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
            //     $rate_data = $rate_data->where('rd_co_no', $co_no);
            //     if (isset($rmd->rmd_no)) {
            //         $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
            //     }else {
            //         $rate_data = [];
            //     }
            // } else {
            //     $co_no = $company->co_no;
            //     $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
            //     $rate_data = $rate_data->where('rd_co_no', $co_no);
            //     if (isset($rmd->rmd_no)) {
            //         $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
            //     }else {
            //         $rate_data = [];
            //     }
            // }

            $adjustment_group = AdjustmentGroup::where('co_no', $user->mb_type == 'spasys' ? $company->co_parent_no : $company->co_no)->first();

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

            $rgd = ReceivingGoodsDelivery::with(['warehousing', 't_import', 't_export', 't_import_expected', 'rate_data_general', 'rgd_child'])->where('rgd_no', $is_no)->first();



            $import = Import::with(['export_confirm', 'export', 'import_expect'])->where('ti_carry_in_number', isset($rgd->rgd_ti_carry_in_number) ? $rgd->rgd_ti_carry_in_number : '')->first();


            $company = Company::where('co_no', $user->mb_type == 'spasys' ? $rgd->warehousing->company->co_parent->co_no : $rgd->warehousing->co_no)->first();

            $company_shipper = $company;



            $rate_data = RateData::where('rd_cate_meta1', '보세화물');

            $rmd = RateMetaData::where('co_no', $company->co_no)->whereNull('set_type')->orderBy('rmd_no', 'DESC')->first();
            $rate_data = $rate_data->where('rd_co_no', $company->co_no);
            if (isset($rmd->rmd_no)) {
                $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
            }

            $rate_data = $rate_data->get();

            $adjustment_group = AdjustmentGroup::where('co_no', '=', $company->co_no)->first();
            $adjustment_group_all = AdjustmentGroup::where('co_no', '=', $company->co_no)->get();

            $export = Export::with(['import', 'import_expected', 't_export_confirm'])->where('te_carry_out_number', $rgd->rgd_te_carry_out_number)->first();

            if (empty($export)) {
                $export = [
                    'import' => $import,
                    'import_expected' => $import->import_expect,

                ];
            }
            return response()->json([
                'message' => Messages::MSG_0007,
                'company' => $company,
                'adjustment_group_all' => $adjustment_group_all,
                'rate_data' => $rate_data,
                'rgd' => $rgd,
                'export' => $export,
                //CLIENT CHANGE NOT ONLY SHOW SHIPPER, SO WE USER RECERVER COMPANY
                'company_shipper' => $company,
                'adjustment_group' => $adjustment_group,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getspasys1fromlogisticnumbercheck($is_no)
    {

        try {
            $user = Auth::user();

            $rgd = ReceivingGoodsDelivery::with(['warehousing', 't_import', 't_export', 't_import_expected', 'rate_data_general', 'rgd_parent_payment'])->where('rgd_no', $is_no)->first();



            $import = Import::with(['export_confirm', 'export', 'import_expect'])->where('ti_carry_in_number', $rgd->rgd_ti_carry_in_number)->first();


            $company = Company::where('co_no', $user->mb_type == 'shop' ? $rgd->warehousing->company->co_parent->co_no :  $rgd->warehousing->co_no)->first();

            $company_shipper = $company;



            $rate_data = RateData::where('rd_cate_meta1', '보세화물');

            $rmd = RateMetaData::where('co_no', $company->co_no)->whereNull('set_type')->orderBy('rmd_no', 'DESC')->first();
            $rate_data = $rate_data->where('rd_co_no', $company->co_no);
            if (isset($rmd->rmd_no)) {
                $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
            }

            $rate_data = $rate_data->get();

            $adjustment_group = AdjustmentGroup::where('co_no', '=', $company->co_no)->first();
            $adjustment_group_all = AdjustmentGroup::where('co_no', '=', $company->co_no)->get();

            $export = Export::with(['import', 'import_expected', 't_export_confirm'])->where('te_carry_out_number', $rgd->rgd_te_carry_out_number)->first();

            if (empty($export)) {
                $export = [
                    'import' => $import,
                    'import_expected' => $import->import_expect,

                ];
            }
            return response()->json([
                'message' => Messages::MSG_0007,
                'company' => $company,
                'adjustment_group_all' => $adjustment_group_all,
                'rate_data' => $rate_data,
                'rgd' => $rgd,
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
                } else {
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

    public function getRateDataByRgd($rgd_no, $service, Request $request)
    {
        $user = Auth::user();
        $pathname = $request->header('pathname');
        $is_check_page = str_contains($pathname, '_check');


        $rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $rgd_no)->first();

        $service_korean_name = $service == 'distribution' ? '유통가공' : ($service == 'fulfillment' ? '수입풀필먼트' : '보세화물');

        try {
            $rate_data = RateData::where('rd_cate_meta1', $service_korean_name);

            if ($user->mb_type == 'spasys') {
                $co_no = ($service == 'distribution' || $service == 'bonded') ? $rgd->warehousing->company->co_parent->co_no : $rgd->warehousing->co_no;
            } else if ($user->mb_type == 'shop' && !$is_check_page) {
                $co_no = $rgd->warehousing->co_no;
            } else if ($user->mb_type == 'shop' && $is_check_page) {
                if ($service_korean_name == '수입풀필먼트') {
                    $co_no = $rgd->warehousing->co_no;
                } else {
                    $co_no = $rgd->warehousing->company->co_parent->co_no;
                }
            } else if ($user->mb_type == 'shipper' && $is_check_page) {
                $co_no = $rgd->warehousing->co_no;
            }

            $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
            $rate_data = $rate_data->where('rd_co_no', $co_no);
            if (isset($rmd->rmd_no)) {
                $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
            } else {
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
            if ($service == 'bonded') {
                $service = '보세화물';
            } else if ($service == 'fulfillment') {
                $service = '수입풀필먼트';
            } else if ($service == 'distribution') {
                $service = '유통가공';
            }
            $rate_data = RateData::where('rd_cate_meta1', $service);


            $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->orderBy('rmd_no', 'DESC')->first();
            $rate_data = $rate_data->where('rd_co_no', $co_no);
            if (isset($rmd->rmd_no)) {
                $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
            } else {
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

            $rate_data_general = RateDataGeneral::where('rmd_no', $request->rmd_no)->where('rdg_set_type', 'estimated_costs')->first();

            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data, 'rate_data_general' => $rate_data_general], 200);
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

    // public function registe_rate_data_general(Request $request)
    // {
    //     try {
    //         DB::beginTransaction();
    //         $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
    //         $ag = AdjustmentGroup::where('ag_no', $request->rdg_set_type)->first();

    //         $rdg = RateDataGeneral::updateOrCreate(
    //             [
    //                 'rdg_no' => $request->rdg_no,
    //                 'rdg_bill_type' => $request->bill_type,
    //             ],
    //             [
    //                 'w_no' => $rgd->w_no,
    //                 'rgd_no' => isset($rgd->rgd_no) ? $rgd->rgd_no : null,
    //                 'rdg_bill_type' => $request->bill_type,
    //                 'mb_no' => Auth::user()->mb_no,
    //                 'rdg_set_type' => isset($ag->ag_name) ? $ag->ag_name : null,
    //                 'ag_no' => isset($ag->ag_no) ? $ag->ag_no : null,
    //                 'rdg_supply_price1' => $request->storageData['supply_price'],
    //                 'rdg_supply_price2' => $request->workData['supply_price'],
    //                 'rdg_supply_price3' => $request->domesticData['supply_price'],
    //                 'rdg_supply_price4' => $request->total['supply_price'],
    //                 'rdg_vat1' => $request->storageData['taxes'],
    //                 'rdg_vat2' => $request->workData['taxes'],
    //                 'rdg_vat3' => $request->domesticData['taxes'],
    //                 'rdg_vat4' => $request->total['taxes'],
    //                 'rdg_sum1' => $request->storageData['sum'],
    //                 'rdg_sum2' => $request->workData['sum'],
    //                 'rdg_sum3' => $request->domesticData['sum'],
    //                 'rdg_sum4' => $request->total['sum'],
    //                 'rdg_etc1' => $request->storageData['etc'],
    //                 'rdg_etc2' => $request->workData['etc'],
    //                 'rdg_etc3' => $request->domesticData['etc'],
    //                 'rdg_etc4' => $request->total['etc'],
    //             ]
    //         );

    //         ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
    //             'rgd_status4' => '예상경비청구서',
    //             'rgd_issue_date' => Carbon::now()->toDateTimeString(),
    //             'rgd_bill_type' => $request->bill_type,
    //         ]);

    //         DB::commit();
    //         return response()->json([
    //             'message' => Messages::MSG_0007,
    //             'rdg' => $rdg,
    //         ], 201);
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         Log::error($e);

    //         return response()->json(['message' => Messages::MSG_0020], 500);
    //     }
    // }

    public function get_rate_data_general($rgd_no, $bill_type)
    {
        try {
            DB::beginTransaction();
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();

            $is_check_page = str_contains($bill_type, 'check');
            $bill_type = str_replace('_check', '', $bill_type);

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

            $co_no = Company::with(['co_parent'])->where('co_no', $warehousing->co_no)->first();

            if ($user->mb_type == 'spasys') {
                if ($is_check_page) {
                    $co_no = $warehousing->co_no;
                } else {
                    $co_no = $co_no->co_parent->co_no;
                }
            } else if ($user->mb_type == 'shop') {
                if ($is_check_page) {
                    $co_no = $co_no->co_parent->co_no;
                } else {
                    $co_no = $warehousing->co_no;
                }
            } else {

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

    public function get_rate_data_fulfillment_final($rgd_no)
    {
        try {
            DB::beginTransaction();
            $rgd = ReceivingGoodsDelivery::with(['warehousing', 'rate_data_general', 'payment'])->where('rgd_no', $rgd_no)->first();

            $rdg = RateDataGeneral::with(['warehousing'])->where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final')->first();
            if (empty($rdg)) {
                $rdg = RateDataGeneral::with(['warehousing'])->where('rgd_no', $rgd_no)->where(function ($q) {
                    $q->where('rdg_bill_type', 'final_spasys')
                        ->orWhere('rdg_bill_type', 'final_shop');
                })->first();
            }
            if (empty($rdg)) {
                $rdg = RateDataGeneral::with(['warehousing'])->where('rgd_no_expectation', $rgd->rgd_parent_no)->where(function ($q) {
                    $q->where('rdg_bill_type', 'final_spasys')
                        ->orWhere('rdg_bill_type', 'final_shop');
                })->first();
            }
            if (empty($rdg)) {
                $rdg = RateDataGeneral::with(['warehousing'])->where('rgd_no', $rgd_no)->where('rdg_bill_type', 'additional')->first();
            }


            $user = Auth::user();

            $contract = Contract::where('co_no',  $user->co_no)->first();
            if (isset($contract->c_calculate_deadline_yn)) {
                $rgd['c_calculate_deadline_yn'] = $contract->c_calculate_deadline_yn;
            } else {
                $rgd['c_calculate_deadline_yn'] = 'n';
            }

            $ag_name = AdjustmentGroup::where('co_no', $rgd->warehousing->co_no)->get();

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'ag_name' => $ag_name,
                'rdg' => $rdg,
                'rgd' => $rgd,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function registe_rate_data_general(Request $request)
    {
        try {
            DB::beginTransaction();

            //CHECK EXIST IN DOUBLE CLICK CASE
            $check_settlement_number = ReceivingGoodsDelivery::where('rgd_settlement_number', $request->settlement_number)->first();
            if (isset($check_settlement_number->rgd_no) && str_contains($request->type, 'create')) {
                return;
            }

            $user = Auth::user();

            //Check is there already RateDataGeneral with rdg_no and bill_type from request yet
            $is_exist = RateDataGeneral::where('rgd_no', $request->rgd_no)->where('rdg_bill_type', $request->bill_type)->first();

            //Get RecevingGoodsDelivery base on rgd_no
            $rgd = ReceivingGoodsDelivery::with('rate_data_general')->where('rgd_no', $request->rgd_no)->first();

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
                    'rdg_set_type' => isset($ag->ag_name) ? $ag->ag_name : (isset($rgd->rate_data_general) ? $rgd->rate_data_general->rdg_set_type : null),
                    'ag_no' => isset($ag->ag_no) ? $ag->ag_no : (isset($rgd->rate_data_general) ? $rgd->rate_data_general->ag_no : null),
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
                    'rdg_etc1' => isset($request->storageData['etc']) ? $request->storageData['etc'] : '',
                    'rdg_etc2' => isset($request->workData['etc']) ? $request->workData['etc'] : '',
                    'rdg_etc3' => isset($request->domesticData['etc']) ? $request->domesticData['etc'] : '',
                    'rdg_etc4' => isset($request->total['etc']) ? $request->total['etc'] : '',
                    'rdg_count_work' => isset($request->workData['count_work']) ? $request->workData['count_work'] : 0,
                ]
            );

            if (!isset($is_exist->rdg_no) && isset($request->previous_bill_type)) {
                if ($rgd->rgd_bill_type == null) {
                    $rgd->rgd_status4 = $user->mb_type == 'shop' ? 'issued' : $rgd->rgd_status4;
                    $rgd->rgd_status5 = $user->mb_type == 'spasys' ? 'issued' : $rgd->rgd_status5;
                    $rgd->save();
                } else if ($rgd->rgd_bill_type != 'expectation_monthly') {
                    $rgd->rgd_status5 = 'issued';
                    $rgd->save();
                }


                $word_type = '';
                if ($rgd->service_korean_name == '수입풀필먼트') {
                    $word_type = 'MF';
                } else if (str_contains($request->bill_type, 'final') && str_contains($request->bill_type, 'month')) {
                    $word_type = 'MF';
                } else if (str_contains($request->bill_type, 'expectation') && str_contains($request->bill_type, 'month')) {
                    $word_type = 'M';
                } else if (str_contains($request->bill_type, 'expectation') && !str_contains($request->bill_type, 'month')) {
                    $word_type = 'C';
                } else if (str_contains($request->bill_type, 'final') && !str_contains($request->bill_type, 'month')) {
                    $word_type = 'CF';
                }


                if ($word_type == 'C' || $word_type == 'M') {
                    $count = ReceivingGoodsDelivery::select(DB::raw('receiving_goods_delivery.*'))
                        ->whereNotNull('rgd_settlement_number')
                        ->whereMonth('created_at', Carbon::today()->month)
                        ->whereYear('created_at', Carbon::today()->year)
                        ->where(\DB::raw('substr(rgd_settlement_number, -1)'), '=', $word_type)
                        ->orderBy('rgd_no', 'DESC')
                        ->first();
                    if (isset($count->rgd_no)) {
                        $count = substr($count->rgd_settlement_number, 7, 5) + 1;
                    } else {
                        $count = 1;
                    }
                } else if ($word_type == 'CF' || $word_type == 'MF') {
                    $count = ReceivingGoodsDelivery::select(DB::raw('receiving_goods_delivery.*'))
                        ->whereNotNull('rgd_settlement_number')
                        ->whereMonth('created_at', Carbon::today()->month)
                        ->whereYear('created_at', Carbon::today()->year)
                        ->where(\DB::raw('substr(rgd_settlement_number, -2)'), '=', $word_type)
                        ->orderBy('rgd_no', 'DESC')
                        ->first();
                    if (isset($count->rgd_no)) {
                        $count = substr($count->rgd_settlement_number, 7, 5) + 1;
                    } else {
                        $count = 1;
                    }
                }

                if ($count >= 0  && $count < 10) {
                    $count = "0000" . $count;
                } else if ($count >= 10 && $count < 100) {
                    $count = "000" . $count;
                } else if ($count >= 100 && $count < 1000) {
                    $count = "00" . $count;
                } else if ($count >= 1000 && $count < 10000) {
                    $count = "0" . $count;
                }

                $rgd_settlement_number = Carbon::now()->format('Ym') . '_' . $count . '_' . $word_type;


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
                $final_rgd->rgd_calculate_deadline_yn = $user->mb_type == 'spasys' ? 'y' : ($request->rgd_calculate_deadline_yn ? $request->rgd_calculate_deadline_yn : $rgd->rgd_calculate_deadline_yn);
                $final_rgd->rgd_settlement_number = $rgd_settlement_number ? $rgd_settlement_number : null;
                $final_rgd->mb_no = Auth::user()->mb_no;
                $final_rgd->save();

                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $final_rgd->rgd_no,
                ]);

                if ($request->type != 'create_final') {
                    //Update rgd_no for rateMetaData
                    RateMetaData::where('rgd_no', $request->rgd_no)
                        ->where('set_type', 'LIKE', '%' . ($user->mb_type == 'spasys' ? '_spasys' : '_shop') . '%')
                        ->update([
                            'rgd_no' => $final_rgd->rgd_no,
                        ]);
                }


                // if ($request->bill_type == 'final') {
                //     $settlement_number = explode('_', $final_rgd->rgd_settlement_number);
                //     $settlement_number[2] = str_replace("C", "CF", $settlement_number[2]);
                //     $final_rgd->rgd_settlement_number = implode("_", $settlement_number);
                //     $final_rgd->save();
                // } else if ($request->bill_type == 'final_monthly') {
                //     $settlement_number = explode('_', $final_rgd->rgd_settlement_number);
                //     $settlement_number[2] = str_replace("M", "MF", $settlement_number[2]);
                //     $final_rgd->rgd_settlement_number = implode("_", $settlement_number);
                //     $final_rgd->save();
                // } else if ($request->bill_type == 'additional_monthly') {
                //     $settlement_number = explode('_', $final_rgd->rgd_settlement_number);
                //     $settlement_number[2] = str_replace("MF", "MA", $settlement_number[2]);
                //     $final_rgd->rgd_settlement_number = implode("_", $settlement_number);
                //     $final_rgd->save();
                // }

            } else {
                $final_rgd = $is_exist;
                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $is_exist ? $is_exist->rgd_no : $rgd->rgd_no,
                ]);
            }

            //Rate Data
            foreach (['work', 'storage', 'domestic'] as $index) {
                if ($request->type == 'create_final') {
                    $set_type = $index . '_final';

                    $rmd = RateMetaData::where('rgd_no', $final_rgd->rgd_no)->where('set_type', $set_type)->first();

                    if (!isset($rmd->rmd_no)) {
                        $set_type = $user->mb_type == 'spasys' ? ($index . '_spasys') : ($index . '_shop');

                        $rmd = RateMetaData::where('rgd_no', $rgd->rgd_no)->where('set_type', $set_type)->first();
                        if (isset($rmd->rmd_no)) {
                            $rmd_expectation = $rmd->replicate();
                            $rmd_expectation->rgd_no = $final_rgd->rgd_no;
                            $rmd_expectation->set_type = $index . '_final';
                            $rmd_expectation->save();

                            $rds = RateData::where('rmd_no', $rmd->rmd_no)->get();
                            foreach ($rds as $index => $rd) {
                                $rd_expectation = $rd->replicate();
                                $rd_expectation->rmd_no = $rmd_expectation->rmd_no;
                                $rd_expectation->save();
                            }
                        }
                    }
                }
            }

            //UPDATE EST BILL WHEN ISSUE FINAL BILL
            if ($request->type == 'create_final') {
                RateMetaData::where('rgd_no', $request->rgd_no)->where('set_type', 'like', '%final%')->update([
                    'rgd_no' => $final_rgd->rgd_no,
                ]);

                $est_rgd =  ReceivingGoodsDelivery::where('rgd_no', $final_rgd->rgd_parent_no)->first();

                if ($est_rgd->rgd_status6 != 'paid' && $est_rgd->service_korean_name == '유통가공' && $est_rgd->rgd_calculate_deadline_yn == 'y' && !str_contains($est_rgd->rgd_bill_type, 'month')) {
                    ReceivingGoodsDelivery::where('rgd_settlement_number', $est_rgd->rgd_settlement_number)->update([
                        'rgd_status6' => 'paid',
                        'is_expect_payment' => 'y', //NOT REAL PAID
                        'rgd_paid_date' => Carbon::now()->toDateTimeString()
                    ]);

                    Payment::updateOrCreate(
                        [
                            'rgd_no' => $est_rgd['rgd_no'],
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
                        'rgd_no' => $est_rgd->rgd_no,
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'payment',
                        'cbh_status_before' => $est_rgd->rgd_status6,
                        'cbh_status_after' => 'payment_bill'
                    ]);
                }
            }

            //INSERT ALARM DATA TABLE

            if (isset($final_rgd) && !str_contains($request->type, 'edit')) {
                $final_rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $final_rgd->rgd_no)->first();

                CommonFunc::insert_alarm($request->type == 'create_final' ? '[유통가공] 확정청구서 발송' : '[유통가공] 예상경비청구서 발송', $final_rgd, $user, null, 'settle_payment', null);
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

    public function registe_rate_data_general_additional(Request $request)
    {
        try {
            DB::beginTransaction();
            $is_new = RateDataGeneral::where('rdg_no', $request->rdg_no)->where('rdg_bill_type', 'additional')->first();

            $rgd = ReceivingGoodsDelivery::with('rate_data_general')->where('rgd_no', $request->rgd_no)->first();
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
                    'rdg_set_type' => isset($ag->ag_name) ? $ag->ag_name : (isset($rgd->rate_data_general) ? $rgd->rate_data_general->rdg_set_type : null),
                    'ag_no' => isset($ag->ag_no) ? $ag->ag_no : (isset($rgd->rate_data_general) ? $rgd->rate_data_general->ag_no : null),
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

            if ($user->mb_type == 'spasys') {
                $co_no = $rgd->warehousing->company->co_parent->co_no;
            } else {
                $co_no = $rgd->warehousing->co_no;
            }

            $adjustmentgroupall = AdjustmentGroup::where('co_no', $co_no)->get();
            $created_at = Carbon::createFromFormat('Y.m.d H:i:s', $rgd->created_at->format('Y.m.d H:i:s'));

            $start_date = $created_at->startOfMonth()->toDateString();
            $end_date = $created_at->endOfMonth()->toDateString();

            $rgds = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general', 'rgd_child', 'rate_meta_data', 'rate_meta_data_parent'])
                ->whereHas('w_no', function ($q) use ($rgd) {
                    $q->where('w_category_name', '유통가공');
                })->whereHas('mb_no', function ($q) {
                    if (Auth::user()->mb_type == 'spasys') {
                        $q->where('mb_type', 'spasys');
                    } else if (Auth::user()->mb_type == 'shop') {
                        $q->where('mb_type', 'shop');
                    }
                })
                // ->doesntHave('rgd_child')
                ->whereHas('rate_data_general', function ($q) use ($rgd) {
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
                ->orderBy('created_at')
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
            if ($user->mb_type == 'spasys') {
                if ($bill_type == 'check') {
                    $co_no = $rgd->warehousing->co_no;
                } else {
                    $co_no = $rgd->warehousing->company->co_parent->co_no;
                }
            } else if ($user->mb_type == 'shop') {
                if ($bill_type == 'check') {
                    $co_no = $rgd->warehousing->company->co_parent->co_no;
                } else {
                    $co_no = $rgd->warehousing->co_no;
                }
            } else if ($user->mb_type == 'shipper') {
                if ($bill_type == 'check') {
                    $co_no = $rgd->warehousing->co_no;
                } else {
                    $co_no = $rgd->warehousing->co_no;
                }
            }
            $adjustmentgroupall = AdjustmentGroup::where('co_no', $co_no)->get();
            $created_at = Carbon::createFromFormat('Y.m.d H:i:s', $rgd->created_at->format('Y.m.d H:i:s'));

            $start_date = $created_at->startOfMonth()->toDateString();
            $end_date = $created_at->endOfMonth()->toDateString();

            $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general', 'rgd_child', 'rate_meta_data', 'rate_meta_data_parent'])
                ->whereHas('w_no', function ($q) use ($rgd) {
                    $q->where('w_category_name', '유통가공');
                })
                // ->doesntHave('rgd_child')
                ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($start_date)))
                ->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($end_date)))
                ->where('rgd_status1', '=', '입고')
                ->where('rgd_settlement_number', $rgd->rgd_settlement_number)
                ->orderBy('rgd_no')
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
                if (isset($rmd->rmd_no)) {
                    $work_sum = RateData::where('rmd_no', $rmd->rmd_no)->sum('rd_data4');
                    $rgd2['work_sum'] = $work_sum;
                } else {
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

            $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general', 't_export', 'rgd_child', 'rate_meta_data', 'rate_meta_data_parent', 't_import', 't_import_expected'])
                ->whereHas('w_no', function ($q) use ($co_no) {
                    $q->where('w_category_name', '보세화물');
                })
                ->whereHas('rate_data_general', function ($q) use ($rgd) {
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
                ->orderBy('created_at')
                ->get();

            $rdgs = [];
            foreach ($rgds as $rgd) {
                $rdg = RateDataGeneral::where('rgd_no', $rgd->rgd_no)
                    ->where('rdg_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();
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
            $user = Auth::user();
            $rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $rgd_no)->first();
            $co_no = $rgd->warehousing->co_no;
            $adjustmentgroupall = AdjustmentGroup::where('co_no', $co_no)->get();
            $created_at = Carbon::createFromFormat('Y.m.d H:i:s', $rgd->created_at->format('Y.m.d H:i:s'));

            $start_date = $created_at->startOfMonth()->toDateString();
            $end_date = $created_at->endOfMonth()->toDateString();

            $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general', 'rgd_child', 'rate_meta_data', 'rate_meta_data_parent', 't_export', 't_import', 't_import_expected'])
                ->whereHas('w_no', function ($q) use ($co_no) {
                    $q->where('w_category_name', '보세화물');
                })
                // ->doesntHave('rgd_child')
                ->where('rgd_settlement_number', $rgd->rgd_settlement_number)
                // ->whereDoesntHave('rgd_child')
                ->orderBy('rgd_no')
                ->get();

            $rdgs = [];
            foreach ($rgds as $index => $rgd) {
                $child_rgd = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general', 'rgd_child', 'rate_meta_data', 'rate_meta_data_parent', 't_export', 't_import', 't_import_expected'])->where('rgd_no', $rgd['rgd_parent_no'])->first();
                $rgds[$index] = $child_rgd;

                $rdg = RateDataGeneral::where('rgd_no', $rgd->rgd_no)
                    ->where('rdg_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();
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

    public function registe_rate_data_general_monthly_final(Request $request)
    {
        try {
            DB::beginTransaction();

            //CHECK EXIST IN DOUBLE CLICK CASE
            $check_settlement_number = ReceivingGoodsDelivery::where('rgd_settlement_number', $request->settlement_number)->first();
            if (isset($check_settlement_number->rgd_no) && !str_contains($request->is_edit, 'edit')) {
                return;
            }

            $user = Auth::user();

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


            if ($request->is_edit == 'edit') {
                RateDataGeneral::where('rgd_no', $request->rgd_no)->where('rdg_bill_type', 'final_monthly')->update([
                    'rdg_supply_price1' =>  $rdg_supply_price1,
                    'rdg_supply_price2' =>  $rdg_supply_price2,
                    'rdg_supply_price3' =>  $rdg_supply_price3,
                    'rdg_supply_price4' =>  $rdg_supply_price4,
                    'rdg_supply_price5' =>  $rdg_supply_price5,
                    'rdg_supply_price6' =>  $rdg_supply_price6,
                    'rdg_supply_price7' =>  $rdg_supply_price7,

                    'rdg_vat1' =>  $rdg_vat1,
                    'rdg_vat2' =>  $rdg_vat2,
                    'rdg_vat3' =>  $rdg_vat3,
                    'rdg_vat4' =>  $rdg_vat4,
                    'rdg_vat5' =>  $rdg_vat5,
                    'rdg_vat6' =>  $rdg_vat6,
                    'rdg_vat7' =>  $rdg_vat7,

                    'rdg_sum1' =>  $rdg_sum1,
                    'rdg_sum2' =>  $rdg_sum2,
                    'rdg_sum3' =>  $rdg_sum3,
                    'rdg_sum4' =>  $rdg_sum4,
                    'rdg_sum5' =>  $rdg_sum5,
                    'rdg_sum6' =>  $rdg_sum6,
                    'rdg_sum7' =>  $rdg_sum7,
                ]);
            } else {

                $word_type = '';
                if ($request->rgds[0]['service_korean_name'] == '수입풀필먼트') {
                    $word_type = 'MF';
                } else if (str_contains($request->bill_type, 'final') && str_contains($request->bill_type, 'month')) {
                    $word_type = 'MF';
                } else if (str_contains($request->bill_type, 'expectation') && str_contains($request->bill_type, 'month')) {
                    $word_type = 'M';
                } else if (str_contains($request->bill_type, 'expectation') && !str_contains($request->bill_type, 'month')) {
                    $word_type = 'C';
                } else if (str_contains($request->bill_type, 'final') && !str_contains($request->bill_type, 'month')) {
                    $word_type = 'CF';
                }


                if ($word_type == 'C' || $word_type == 'M') {
                    $count = ReceivingGoodsDelivery::select(DB::raw('receiving_goods_delivery.*'))
                        ->whereNotNull('rgd_settlement_number')
                        ->whereMonth('created_at', Carbon::today()->month)
                        ->whereYear('created_at', Carbon::today()->year)
                        ->where(\DB::raw('substr(rgd_settlement_number, -1)'), '=', $word_type)
                        ->orderBy('rgd_no', 'DESC')
                        ->first();
                    if (isset($count->rgd_no)) {
                        $count = substr($count->rgd_settlement_number, 7, 5) + 1;
                    } else {
                        $count = 1;
                    }
                } else if ($word_type == 'CF' || $word_type == 'MF') {
                    $count = ReceivingGoodsDelivery::select(DB::raw('receiving_goods_delivery.*'))
                        ->whereNotNull('rgd_settlement_number')
                        ->whereMonth('created_at', Carbon::today()->month)
                        ->whereYear('created_at', Carbon::today()->year)
                        ->where(\DB::raw('substr(rgd_settlement_number, -2)'), '=', $word_type)
                        ->orderBy('rgd_no', 'DESC')
                        ->first();
                    if (isset($count->rgd_no)) {
                        $count = substr($count->rgd_settlement_number, 7, 5) + 1;
                    } else {
                        $count = 1;
                    }
                }

                if ($count >= 0  && $count < 10) {
                    $count = "0000" . $count;
                } else if ($count >= 10 && $count < 100) {
                    $count = "000" . $count;
                } else if ($count >= 100 && $count < 1000) {
                    $count = "00" . $count;
                } else if ($count >= 1000 && $count < 10000) {
                    $count = "0" . $count;
                }

                $rgd_settlement_number = Carbon::now()->format('Ym') . '_' . $count . '_' . $word_type;

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
                        if ($final_rgd->rgd_status5 == 'cancel') {
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
                        } else
                            $final_rdg = $is_exist;
                    }
                    $main_rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
                    $expectation_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->where('rgd_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();
                    $final_rgd = ReceivingGoodsDelivery::where('rgd_parent_no', $rgd['rgd_no'])->where('rgd_bill_type', 'final_monthly')->where('rgd_status5', '!=', 'cancel')->first();

                    if (!$final_rgd) {
                        $expectation_rgd->rgd_status5 = 'issued';
                        $expectation_rgd->save();

                        $final_rgd = $expectation_rgd->replicate();
                        $final_rgd->rgd_bill_type = $request->bill_type; // the new project_id
                        $final_rgd->rgd_status4 = '확정청구서';
                        $final_rgd->rgd_issue_date = Carbon::now()->toDateTimeString();
                        $final_rgd->rgd_memo_settle = $main_rgd->rgd_memo_settle;
                        $final_rgd->rgd_status5 = null;
                        $final_rgd->rgd_status6 = null;
                        $final_rgd->rgd_status7 = null;
                        $final_rgd->rgd_is_show = ($i == 0 ? 'y' : 'n');
                        $final_rgd->rgd_parent_no = $expectation_rgd->rgd_no;
                        $final_rgd->rgd_calculate_deadline_yn = $user->mb_type == 'spasys' ? 'y' : $expectation_rgd->rgd_calculate_deadline_yn;
                        $final_rgd->rgd_settlement_number = $rgd_settlement_number ? $rgd_settlement_number : null;
                        $final_rgd->mb_no = $user->mb_no;
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

                        if ($i == 0) {
                            $final_rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $final_rgd->rgd_no)->first();

                            CommonFunc::insert_alarm('[유통가공] 확정청구서 발송', $final_rgd, $user, null, 'settle_payment', null);
                        }

                        //UPDATE EST BILL WHEN ISSUE FINAL BILL
                        $est_rgd =  ReceivingGoodsDelivery::where('rgd_no', $final_rgd->rgd_parent_no)->first();

                        if ($est_rgd->rgd_status6 != 'paid' && $est_rgd->service_korean_name == '유통가공' && $est_rgd->rgd_calculate_deadline_yn == 'y' && !str_contains($est_rgd->rgd_bill_type, 'month')) {

                            ReceivingGoodsDelivery::where('rgd_settlement_number', $est_rgd->rgd_settlement_number)->update([
                                'rgd_status6' => 'paid',
                                'is_expect_payment' => 'y', //NOT REAL PAID
                                'rgd_paid_date' => Carbon::now()->toDateTimeString()
                            ]);

                            Payment::updateOrCreate(
                                [
                                    'rgd_no' => $est_rgd['rgd_no'],
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
                                'rgd_no' => $est_rgd->rgd_no,
                                'mb_no' => $user->mb_no,
                                'cbh_type' => 'payment',
                                'cbh_status_before' => $est_rgd->rgd_status6,
                                'cbh_status_after' => 'payment_bill'
                            ]);
                        }
                        //END UPDATE EST BILL WHEN ISSUE FINAL BILL

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

            //CHECK EXIST IN DOUBLE CLICK CASE
            $check_settlement_number = ReceivingGoodsDelivery::where('rgd_settlement_number', $request->settlement_number)->first();
            if (isset($check_settlement_number->rgd_no) && !str_contains($request->type, 'edit')) {
                return;
            }

            $user = Auth::user();

            $i = 0;
            $final_rgds = [];

            $rdg_supply_price1 = 0;
            $rdg_supply_price2 = 0;
            $rdg_supply_price3 = 0;
            $rdg_supply_price4 = 0;
            $rdg_supply_price5 = 0;
            $rdg_supply_price6 = 0;
            $rdg_supply_price7 = 0;
            $rdg_supply_price8 = 0;
            $rdg_supply_price9 = 0;
            $rdg_supply_price10 = 0;
            $rdg_supply_price11 = 0;
            $rdg_supply_price12 = 0;
            $rdg_supply_price13 = 0;
            $rdg_supply_price14 = 0;

            $rdg_vat1 = 0;
            $rdg_vat2 = 0;
            $rdg_vat3 = 0;
            $rdg_vat4 = 0;
            $rdg_vat5 = 0;
            $rdg_vat6 = 0;
            $rdg_vat7 = 0;
            $rdg_vat8 = 0;
            $rdg_vat9 = 0;
            $rdg_vat10 = 0;
            $rdg_vat11 = 0;
            $rdg_vat12 = 0;
            $rdg_vat13 = 0;
            $rdg_vat14 = 0;

            $rdg_sum1 = 0;
            $rdg_sum2 = 0;
            $rdg_sum3 = 0;
            $rdg_sum4 = 0;
            $rdg_sum5 = 0;
            $rdg_sum6 = 0;
            $rdg_sum7 = 0;
            $rdg_sum8 = 0;
            $rdg_sum9 = 0;
            $rdg_sum10 = 0;
            $rdg_sum11 = 0;
            $rdg_sum12 = 0;
            $rdg_sum13 = 0;
            $rdg_sum14 = 0;

            foreach ($request->rgds as $key => $rgd) {
                $is_exist = RateDataGeneral::where('rgd_no', $rgd['rgd_no'])->where('rdg_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();
                $rdg_supply_price1 = $rdg_supply_price1 + $is_exist['rdg_supply_price1'];
                $rdg_supply_price2 = $rdg_supply_price2 + $is_exist['rdg_supply_price2'];
                $rdg_supply_price3 = $rdg_supply_price3 + $is_exist['rdg_supply_price3'];
                $rdg_supply_price4 = $rdg_supply_price4 + $is_exist['rdg_supply_price4'];
                $rdg_supply_price5 = $rdg_supply_price5 + $is_exist['rdg_supply_price5'];
                $rdg_supply_price6 = $rdg_supply_price6 + $is_exist['rdg_supply_price6'];
                $rdg_supply_price7 = $rdg_supply_price7 + $is_exist['rdg_supply_price7'];
                $rdg_supply_price8 = $rdg_supply_price8 + $is_exist['rdg_supply_price8'];
                $rdg_supply_price9 = $rdg_supply_price9 + $is_exist['rdg_supply_price9'];
                $rdg_supply_price10 = $rdg_supply_price10 + $is_exist['rdg_supply_price10'];
                $rdg_supply_price11 = $rdg_supply_price11 + $is_exist['rdg_supply_price11'];
                $rdg_supply_price12 = $rdg_supply_price12 + $is_exist['rdg_supply_price12'];
                $rdg_supply_price13 = $rdg_supply_price13 + $is_exist['rdg_supply_price13'];
                $rdg_supply_price14 = $rdg_supply_price14 + $is_exist['rdg_supply_price14'];

                $rdg_vat1 = $rdg_vat1 + $is_exist['rdg_vat1'];
                $rdg_vat2 = $rdg_vat2 + $is_exist['rdg_vat2'];
                $rdg_vat3 = $rdg_vat3 + $is_exist['rdg_vat3'];
                $rdg_vat4 = $rdg_vat4 + $is_exist['rdg_vat4'];
                $rdg_vat5 = $rdg_vat5 + $is_exist['rdg_vat5'];
                $rdg_vat6 = $rdg_vat6 + $is_exist['rdg_vat6'];
                $rdg_vat7 = $rdg_vat7 + $is_exist['rdg_vat7'];
                $rdg_vat8 = $rdg_vat8 + $is_exist['rdg_vat8'];
                $rdg_vat9 = $rdg_vat9 + $is_exist['rdg_vat9'];
                $rdg_vat10 = $rdg_vat10 + $is_exist['rdg_vat10'];
                $rdg_vat11 = $rdg_vat11 + $is_exist['rdg_vat11'];
                $rdg_vat12 = $rdg_vat12 + $is_exist['rdg_vat12'];
                $rdg_vat13 = $rdg_vat13 + $is_exist['rdg_vat13'];
                $rdg_vat14 = $rdg_vat14 + $is_exist['rdg_vat14'];

                $rdg_sum1 = $rdg_sum1 + $is_exist['rdg_sum1'];
                $rdg_sum2 = $rdg_sum2 + $is_exist['rdg_sum2'];
                $rdg_sum3 = $rdg_sum3 + $is_exist['rdg_sum3'];
                $rdg_sum4 = $rdg_sum4 + $is_exist['rdg_sum4'];
                $rdg_sum5 = $rdg_sum5 + $is_exist['rdg_sum5'];
                $rdg_sum6 = $rdg_sum6 + $is_exist['rdg_sum6'];
                $rdg_sum7 = $rdg_sum7 + $is_exist['rdg_sum7'];
                $rdg_sum8 = $rdg_sum8 + $is_exist['rdg_sum8'];
                $rdg_sum9 = $rdg_sum9 + $is_exist['rdg_sum9'];
                $rdg_sum10 = $rdg_sum10 + $is_exist['rdg_sum10'];
                $rdg_sum11 = $rdg_sum11 + $is_exist['rdg_sum11'];
                $rdg_sum12 = $rdg_sum12 + $is_exist['rdg_sum12'];
                $rdg_sum13 = $rdg_sum13 + $is_exist['rdg_sum13'];
                $rdg_sum14 = $rdg_sum14 + $is_exist['rdg_sum14'];
            }

            $word_type = '';
            if ($request->rgds[0]['service_korean_name'] == '수입풀필먼트') {
                $word_type = 'MF';
            } else if (str_contains($request->bill_type, 'final') && str_contains($request->bill_type, 'month')) {
                $word_type = 'MF';
            } else if (str_contains($request->bill_type, 'expectation') && str_contains($request->bill_type, 'month')) {
                $word_type = 'M';
            } else if (str_contains($request->bill_type, 'expectation') && !str_contains($request->bill_type, 'month')) {
                $word_type = 'C';
            } else if (str_contains($request->bill_type, 'final') && !str_contains($request->bill_type, 'month')) {
                $word_type = 'CF';
            }


            if ($word_type == 'C' || $word_type == 'M') {
                $count = ReceivingGoodsDelivery::select(DB::raw('receiving_goods_delivery.*'))
                    ->whereNotNull('rgd_settlement_number')
                    ->whereMonth('created_at', Carbon::today()->month)
                    ->whereYear('created_at', Carbon::today()->year)
                    ->where(\DB::raw('substr(rgd_settlement_number, -1)'), '=', $word_type)
                    ->orderBy('rgd_no', 'DESC')
                    ->first();
                if (isset($count->rgd_no)) {
                    $count = substr($count->rgd_settlement_number, 7, 5) + 1;
                } else {
                    $count = 1;
                }
            } else if ($word_type == 'CF' || $word_type == 'MF') {
                $count = ReceivingGoodsDelivery::select(DB::raw('receiving_goods_delivery.*'))
                    ->whereNotNull('rgd_settlement_number')
                    ->whereMonth('created_at', Carbon::today()->month)
                    ->whereYear('created_at', Carbon::today()->year)
                    ->where(\DB::raw('substr(rgd_settlement_number, -2)'), '=', $word_type)
                    ->orderBy('rgd_no', 'DESC')
                    ->first();
                if (isset($count->rgd_no)) {
                    $count = substr($count->rgd_settlement_number, 7, 5) + 1;
                } else {
                    $count = 1;
                }
            }

            if ($count >= 0  && $count < 10) {
                $count = "0000" . $count;
            } else if ($count >= 10 && $count < 100) {
                $count = "000" . $count;
            } else if ($count >= 100 && $count < 1000) {
                $count = "00" . $count;
            } else if ($count >= 1000 && $count < 10000) {
                $count = "0" . $count;
            }

            $rgd_settlement_number = Carbon::now()->format('Ym') . '_' . $count . '_' . $word_type;

            //Loop through  rgds from the request
            foreach ($request->rgds as $key => $rgd) {

                //Get the est bill
                $expectation_rdg = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->where('rgd_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();

                //Get the final bill if any
                $final_rgd = ReceivingGoodsDelivery::where('rgd_parent_no', $rgd['rgd_no'])->where('rgd_bill_type', 'final_monthly')->where(function ($q) {
                    $q->where('rgd_status5', '!=', 'cancel')->orWhereNull('rgd_status5');
                })->first();

                if ($final_rgd) {
                    //Check if there is any RateDataGeneral of est bill
                    $is_exist = RateDataGeneral::where('rgd_no_expectation', $rgd['rgd_no'])->where('rgd_no', $final_rgd->rgd_no)->where('rdg_bill_type', 'final_monthly')->first();
                } else {
                    //Check if there is any RateDataGeneral of est bill
                    $is_exist = RateDataGeneral::where('rgd_no_expectation', $rgd['rgd_no'])->where('rdg_bill_type', 'final_monthly')->first();
                }

                //Creating RateDataGeneral for final bill if not
                if (!$final_rgd) {
                    $expectation_rdg = RateDataGeneral::where('rgd_no', $rgd['rgd_no'])->where('rdg_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();

                    $final_rdg = $expectation_rdg->replicate();
                    $final_rdg->rdg_bill_type = $request->bill_type; // the new project_id

                    $final_rdg->rdg_supply_price1 = $rdg_supply_price1;
                    $final_rdg->rdg_supply_price2 = $rdg_supply_price2;
                    $final_rdg->rdg_supply_price3 = $rdg_supply_price3;
                    $final_rdg->rdg_supply_price4 = $rdg_supply_price4;
                    $final_rdg->rdg_supply_price5 = $rdg_supply_price5;
                    $final_rdg->rdg_supply_price6 = $rdg_supply_price6;
                    $final_rdg->rdg_supply_price7 = $rdg_supply_price7;
                    $final_rdg->rdg_supply_price8 = $rdg_supply_price8;
                    $final_rdg->rdg_supply_price9 = $rdg_supply_price9;
                    $final_rdg->rdg_supply_price10 = $rdg_supply_price10;
                    $final_rdg->rdg_supply_price11 = $rdg_supply_price11;
                    $final_rdg->rdg_supply_price12 = $rdg_supply_price12;
                    $final_rdg->rdg_supply_price13 = $rdg_supply_price13;
                    $final_rdg->rdg_supply_price14 = $rdg_supply_price14;

                    $final_rdg->rdg_vat1 = $rdg_vat1;
                    $final_rdg->rdg_vat2 = $rdg_vat2;
                    $final_rdg->rdg_vat3 = $rdg_vat3;
                    $final_rdg->rdg_vat4 = $rdg_vat4;
                    $final_rdg->rdg_vat5 = $rdg_vat5;
                    $final_rdg->rdg_vat6 = $rdg_vat6;
                    $final_rdg->rdg_vat7 = $rdg_vat7;
                    $final_rdg->rdg_vat8 = $rdg_vat8;
                    $final_rdg->rdg_vat9 = $rdg_vat9;
                    $final_rdg->rdg_vat10 = $rdg_vat10;
                    $final_rdg->rdg_vat11 = $rdg_vat11;
                    $final_rdg->rdg_vat12 = $rdg_vat12;
                    $final_rdg->rdg_vat13 = $rdg_vat13;
                    $final_rdg->rdg_vat14 = $rdg_vat14;

                    $final_rdg->rdg_sum1 = $rdg_sum1;
                    $final_rdg->rdg_sum2 = $rdg_sum2;
                    $final_rdg->rdg_sum3 = $rdg_sum3;
                    $final_rdg->rdg_sum4 = $rdg_sum4;
                    $final_rdg->rdg_sum5 = $rdg_sum5;
                    $final_rdg->rdg_sum6 = $rdg_sum6;
                    $final_rdg->rdg_sum7 = $rdg_sum7;
                    $final_rdg->rdg_sum8 = $rdg_sum8;
                    $final_rdg->rdg_sum9 = $rdg_sum9;
                    $final_rdg->rdg_sum10 = $rdg_sum10;
                    $final_rdg->rdg_sum11 = $rdg_sum11;
                    $final_rdg->rdg_sum12 = $rdg_sum12;
                    $final_rdg->rdg_sum13 = $rdg_sum13;
                    $final_rdg->rdg_sum14 = $rdg_sum14;

                    $final_rdg->save();
                } else {
                    $final_rdg = $is_exist;
                    RateDataGeneral::where('rgd_no_expectation', $rgd['rgd_no'])->where('rdg_bill_type', 'final_monthly')->update([
                        'rdg_supply_price1' => $rdg_supply_price1,
                        'rdg_supply_price2' => $rdg_supply_price2,
                        'rdg_supply_price3' => $rdg_supply_price3,
                        'rdg_supply_price4' => $rdg_supply_price4,
                        'rdg_supply_price5' => $rdg_supply_price5,
                        'rdg_supply_price6' => $rdg_supply_price6,
                        'rdg_supply_price7' => $rdg_supply_price7,
                        'rdg_supply_price8' => $rdg_supply_price8,
                        'rdg_supply_price9' => $rdg_supply_price9,
                        'rdg_supply_price10' => $rdg_supply_price10,
                        'rdg_supply_price11' => $rdg_supply_price11,
                        'rdg_supply_price12' => $rdg_supply_price12,
                        'rdg_supply_price13' => $rdg_supply_price13,
                        'rdg_supply_price14' => $rdg_supply_price14,

                        'rdg_vat1' => $rdg_vat1,
                        'rdg_vat2' => $rdg_vat2,
                        'rdg_vat3' => $rdg_vat3,
                        'rdg_vat4' => $rdg_vat4,
                        'rdg_vat5' => $rdg_vat5,
                        'rdg_vat6' => $rdg_vat6,
                        'rdg_vat7' => $rdg_vat7,
                        'rdg_vat8' => $rdg_vat8,
                        'rdg_vat9' => $rdg_vat9,
                        'rdg_vat10' => $rdg_vat10,
                        'rdg_vat11' => $rdg_vat11,
                        'rdg_vat12' => $rdg_vat12,
                        'rdg_vat13' => $rdg_vat13,
                        'rdg_vat14' => $rdg_vat14,

                        'rdg_sum1' => $rdg_sum1,
                        'rdg_sum2' => $rdg_sum2,
                        'rdg_sum3' => $rdg_sum3,
                        'rdg_sum4' => $rdg_sum4,
                        'rdg_sum5' => $rdg_sum5,
                        'rdg_sum6' => $rdg_sum6,
                        'rdg_sum7' => $rdg_sum7,
                        'rdg_sum8' => $rdg_sum8,
                        'rdg_sum9' => $rdg_sum9,
                        'rdg_sum10' => $rdg_sum10,
                        'rdg_sum11' => $rdg_sum11,
                        'rdg_sum12' => $rdg_sum12,
                        'rdg_sum13' => $rdg_sum13,
                        'rdg_sum14' => $rdg_sum14,
                    ]);
                }
                $expectation_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->where('rgd_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')->first();
                $final_rgds[] = $final_rgd;
                //Creating final bill
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
                    $final_rgd->rgd_settlement_number = $rgd_settlement_number;
                    $final_rgd->rgd_calculate_deadline_yn = $user->mb_type == 'spasys' ? 'y' : $expectation_rgd->rgd_calculate_deadline_yn;
                    $final_rgd->mb_no = $user->mb_no;
                    $final_rgd->save();

                    $ag = AdjustmentGroup::where('ag_no', $request->rdg_set_type)->first();
                    //Update for the created RateDataGeneral
                    RateDataGeneral::where('rdg_no', $final_rdg->rdg_no)->update([
                        'rgd_no' => $final_rgd->rgd_no,
                        'rgd_no_expectation' => $expectation_rgd->rgd_no,
                        'rdg_set_type' => isset($ag->ag_name) ? $ag->ag_name : $is_exist->rdg_set_type,
                        'ag_no' => isset($ag->ag_no) ? $ag->ag_no : $is_exist->ag_no,
                    ]);
                    //Update for the created RateMetaData
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

                    if ($i == 0) {
                        $final_rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $final_rgd->rgd_no)->first();

                        CommonFunc::insert_alarm('[보세화물] 확정청구서 발송', $final_rgd, $user, null, 'settle_payment', null);
                    }

                    //UPDATE EST BILL WHEN ISSUE FINAL BILL
                    $est_rgd =  ReceivingGoodsDelivery::where('rgd_no', $final_rgd->rgd_parent_no)->first();

                    if ($est_rgd->rgd_status6 != 'paid' && $est_rgd->service_korean_name == '보세화물' && $est_rgd->rgd_calculate_deadline_yn == 'y' && !str_contains($est_rgd->rgd_bill_type, 'month')) {

                        ReceivingGoodsDelivery::where('rgd_settlement_number', $est_rgd->rgd_settlement_number)->update([
                            'rgd_status6' => 'paid',
                            'is_expect_payment' => 'y', //NOT REAL PAID
                            'rgd_paid_date' => Carbon::now()->toDateTimeString()
                        ]);

                        Payment::updateOrCreate(
                            [
                                'rgd_no' => $est_rgd['rgd_no'],
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
                            'rgd_no' => $est_rgd->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'payment',
                            'cbh_status_before' => $est_rgd->rgd_status6,
                            'cbh_status_after' => 'payment_bill'
                        ]);
                    }
                    //END UPDATE EST BILL WHEN ISSUE FINAL BILL

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

            //CHECK EXIST IN DOUBLE CLICK CASE
            $check_settlement_number = ReceivingGoodsDelivery::where('rgd_settlement_number', $request->settlement_number)->first();
            if (isset($check_settlement_number->rgd_no) && $request->type != 'edit_final') {
                return;
            }

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

            if (($request->bill_type == 'final_spasys' || $request->bill_type == 'final_shop') && $request->type != 'edit_final') {
                $previous_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_no)->first();

                $previous_rgd->rgd_status4 = $user->mb_type == 'shop' ? 'issued' : $previous_rgd->rgd_status4;
                $previous_rgd->rgd_status5 = $user->mb_type == 'spasys' ? 'issued' : $previous_rgd->rgd_status5;
                $previous_rgd->save();


                $word_type = '';
                if ($previous_rgd->service_korean_name == '수입풀필먼트') {
                    $word_type = 'MF';
                } else if (str_contains($request->bill_type, 'final') && str_contains($request->bill_type, 'month')) {
                    $word_type = 'MF';
                } else if (str_contains($request->bill_type, 'expectation') && str_contains($request->bill_type, 'month')) {
                    $word_type = 'M';
                } else if (str_contains($request->bill_type, 'expectation') && !str_contains($request->bill_type, 'month')) {
                    $word_type = 'C';
                } else if (str_contains($request->bill_type, 'final') && !str_contains($request->bill_type, 'month')) {
                    $word_type = 'CF';
                }


                if ($word_type == 'C' || $word_type == 'M') {
                    $count = ReceivingGoodsDelivery::select(DB::raw('receiving_goods_delivery.*'))
                        ->whereNotNull('rgd_settlement_number')
                        ->whereMonth('created_at', Carbon::today()->month)
                        ->whereYear('created_at', Carbon::today()->year)
                        ->where(\DB::raw('substr(rgd_settlement_number, -1)'), '=', $word_type)
                        ->orderBy('rgd_no', 'DESC')
                        ->first();
                    if (isset($count->rgd_no)) {
                        $count = substr($count->rgd_settlement_number, 7, 5) + 1;
                    } else {
                        $count = 1;
                    }
                } else if ($word_type == 'CF' || $word_type == 'MF') {
                    $count = ReceivingGoodsDelivery::select(DB::raw('receiving_goods_delivery.*'))
                        ->whereNotNull('rgd_settlement_number')
                        ->whereMonth('created_at', Carbon::today()->month)
                        ->whereYear('created_at', Carbon::today()->year)
                        ->where(\DB::raw('substr(rgd_settlement_number, -2)'), '=', $word_type)
                        ->orderBy('rgd_no', 'DESC')
                        ->first();
                    if (isset($count->rgd_no)) {
                        $count = substr($count->rgd_settlement_number, 7, 5) + 1;
                    } else {
                        $count = 1;
                    }
                }

                if ($count >= 0  && $count < 10) {
                    $count = "0000" . $count;
                } else if ($count >= 10 && $count < 100) {
                    $count = "000" . $count;
                } else if ($count >= 100 && $count < 1000) {
                    $count = "00" . $count;
                } else if ($count >= 1000 && $count < 10000) {
                    $count = "0" . $count;
                }

                $rgd_settlement_number = Carbon::now()->format('Ym') . '_' . $count . '_' . $word_type;


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
                $final_rgd->rgd_settlement_number = $rgd_settlement_number;
                $final_rgd->rgd_calculate_deadline_yn = $user->mb_type == 'spasys' ? 'y' : ($request->rgd_calculate_deadline_yn ? $request->rgd_calculate_deadline_yn : $previous_rgd->rgd_calculate_deadline_yn);
                $final_rgd->mb_no = $user->mb_no;
                $final_rgd->save();

                RateDataGeneral::where('rgd_no_expectation', $previous_rgd->rgd_no)->where(function ($q) use ($previous_rgd) {
                    $q->whereNull('rgd_no')->orwhere('rgd_no', $previous_rgd->rgd_no);
                })->update([
                    'rgd_no' => $final_rgd->rgd_no,
                ]);

                RateMetaData::where('rgd_no', $previous_rgd->rgd_no)->update([
                    'rgd_no' => $final_rgd->rgd_no,
                ]);

                $final_rgd = ReceivingGoodsDelivery::with(['warehousing', 'rate_data_general'])->where('rgd_no', $final_rgd->rgd_no)->first();

                CommonFunc::insert_alarm('[수입풀필먼트] 확정청구서 발송', $final_rgd, $user, null, 'settle_payment', null);
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

            //CHECK EXIST IN DOUBLE CLICK CASE
            $check_settlement_number = ReceivingGoodsDelivery::where('rgd_settlement_number', $request->rgd_settlement_number)->first();
            if (isset($check_settlement_number->rgd_no)  && str_contains($request->type, 'create')) {
                return;
            }


            $user = Auth::user();
            //Check is there already RateDataGeneral with rgd_no yet
            $is_exist = RateDataGeneral::where('rgd_no', $request->rgd_no)->where('rdg_bill_type', $request->bill_type)->first();

            //Get RecevingGoodsDelivery base on rgd_no
            $rgd = ReceivingGoodsDelivery::with('rate_data_general')->where('rgd_no', $request->rgd_no)->first();
            //Get settlement group if there is any
            $ag = AdjustmentGroup::where('ag_no', $request->rdg_set_type)->first();

            $bonded1_supply_price = isset($request->bonded1['supply_price']) ? $request->bonded1['supply_price'] : 0;
            $bonded2_supply_price = isset($request->bonded2['supply_price']) ? $request->bonded2['supply_price'] : 0;
            $bonded3_supply_price = isset($request->bonded3['supply_price']) ? $request->bonded3['supply_price'] : 0;
            $bonded4_supply_price = isset($request->bonded4['supply_price']) ? $request->bonded4['supply_price'] : 0;
            $bonded5_supply_price = isset($request->bonded5['supply_price']) ? $request->bonded5['supply_price'] : 0;
            $bonded6_supply_price = isset($request->bonded6['supply_price']) ? $request->bonded6['supply_price'] : 0;
            $bonded7_supply_price = $bonded1_supply_price + $bonded2_supply_price + $bonded3_supply_price + $bonded4_supply_price + $bonded5_supply_price + $bonded6_supply_price;
            $bonded8_supply_price = isset($request->bonded1['supply_price1']) ? $request->bonded1['supply_price1'] : 0;
            $bonded9_supply_price = isset($request->bonded2['supply_price1']) ? $request->bonded2['supply_price1'] : 0;
            $bonded10_supply_price = isset($request->bonded3['supply_price1']) ? $request->bonded3['supply_price1'] : 0;
            $bonded11_supply_price = isset($request->bonded4['supply_price1']) ? $request->bonded4['supply_price1'] : 0;
            $bonded12_supply_price = isset($request->bonded5['supply_price1']) ? $request->bonded5['supply_price1'] : 0;
            $bonded13_supply_price = isset($request->bonded6['supply_price1']) ? $request->bonded6['supply_price1'] : 0;
            $bonded14_supply_price = $bonded8_supply_price + $bonded9_supply_price + $bonded10_supply_price + $bonded11_supply_price + $bonded12_supply_price + $bonded13_supply_price;

            $bonded1_taxes = isset($request->bonded1['taxes']) ? $request->bonded1['taxes'] : 0;
            $bonded2_taxes = isset($request->bonded2['taxes']) ? $request->bonded2['taxes'] : 0;
            $bonded3_taxes = isset($request->bonded3['taxes']) ? $request->bonded3['taxes'] : 0;
            $bonded4_taxes = isset($request->bonded4['taxes']) ? $request->bonded4['taxes'] : 0;
            $bonded5_taxes = isset($request->bonded5['taxes']) ? $request->bonded5['taxes'] : 0;
            $bonded6_taxes = isset($request->bonded6['taxes']) ? $request->bonded6['taxes'] : 0;
            $bonded7_taxes = $bonded1_taxes + $bonded2_taxes + $bonded3_taxes + $bonded4_taxes + $bonded5_taxes + $bonded6_taxes;
            $bonded8_taxes = isset($request->bonded1['taxes1']) ? $request->bonded1['taxes1'] : 0;
            $bonded9_taxes = isset($request->bonded2['taxes1']) ? $request->bonded2['taxes1'] : 0;
            $bonded10_taxes = isset($request->bonded3['taxes1']) ? $request->bonded3['taxes1'] : 0;
            $bonded11_taxes = isset($request->bonded4['taxes1']) ? $request->bonded4['taxes1'] : 0;
            $bonded12_taxes = isset($request->bonded5['taxes1']) ? $request->bonded5['taxes1'] : 0;
            $bonded13_taxes = isset($request->bonded6['taxes1']) ? $request->bonded6['taxes1'] : 0;
            $bonded14_taxes = $bonded8_taxes + $bonded9_taxes + $bonded10_taxes + $bonded11_taxes + $bonded12_taxes + $bonded13_taxes;

            $bonded1_sum = isset($request->bonded1['sum']) ? $request->bonded1['sum'] : 0;
            $bonded2_sum = isset($request->bonded2['sum']) ? $request->bonded2['sum'] : 0;
            $bonded3_sum = isset($request->bonded3['sum']) ? $request->bonded3['sum'] : 0;
            $bonded4_sum = isset($request->bonded4['sum']) ? $request->bonded4['sum'] : 0;
            $bonded5_sum = isset($request->bonded5['sum']) ? $request->bonded5['sum'] : 0;
            $bonded6_sum = isset($request->bonded6['sum']) ? $request->bonded6['sum'] : 0;
            $bonded7_sum = $bonded1_sum + $bonded2_sum + $bonded3_sum + $bonded4_sum + $bonded5_sum + $bonded6_sum;
            $bonded8_sum = isset($request->bonded1['sum1']) ? $request->bonded1['sum1'] : 0;
            $bonded9_sum = isset($request->bonded2['sum1']) ? $request->bonded2['sum1'] : 0;
            $bonded10_sum = isset($request->bonded3['sum1']) ? $request->bonded3['sum1'] : 0;
            $bonded11_sum = isset($request->bonded4['sum1']) ? $request->bonded4['sum1'] : 0;
            $bonded12_sum = isset($request->bonded5['sum1']) ? $request->bonded5['sum1'] : 0;
            $bonded13_sum = isset($request->bonded6['sum1']) ? $request->bonded6['sum1'] : 0;
            $bonded14_sum = $bonded8_sum + $bonded9_sum + $bonded10_sum + $bonded11_sum + $bonded12_sum + $bonded13_sum;

            $rdg = RateDataGeneral::updateOrCreate(
                [
                    'rdg_no' => isset($is_exist->rdg_no) ? $is_exist->rdg_no : null,
                    'rdg_bill_type' => $request->bill_type,
                ],
                [
                    'w_no' => $rgd->w_no,
                    'rdg_bill_type' => $request->bill_type,
                    'rgd_no_expectation' => $request->type == 'edit_final' ? $is_exist->rgd_no_expectation : (str_contains($request->bill_type, 'final') ? $request->rgd_no : null),
                    'rgd_no_final' => $request->type == 'edit_additional' ? $is_exist->rgd_no_final : (str_contains($request->bill_type, 'additional') ? $request->rgd_no : null),
                    'mb_no' => Auth::user()->mb_no,
                    'rdg_set_type' => isset($ag->ag_name) ? $ag->ag_name : (isset($rgd->rate_data_general) ? $rgd->rate_data_general->rdg_set_type : NULL),
                    'ag_no' => isset($ag->ag_no) ? $ag->ag_no : (isset($rgd->rate_data_general) ? $rgd->rate_data_general->ag_no : NULL),
                    'rdg_supply_price1' => $bonded1_supply_price,
                    'rdg_supply_price2' => $bonded2_supply_price,
                    'rdg_supply_price3' => $bonded3_supply_price,
                    'rdg_supply_price4' => $bonded4_supply_price,
                    'rdg_supply_price5' => $bonded5_supply_price,
                    'rdg_supply_price6' => $bonded6_supply_price,
                    'rdg_supply_price7' => $bonded7_supply_price,
                    'rdg_supply_price8' => $bonded8_supply_price,
                    'rdg_supply_price9' => $bonded9_supply_price,
                    'rdg_supply_price10' => $bonded10_supply_price,
                    'rdg_supply_price11' => $bonded11_supply_price,
                    'rdg_supply_price12' => $bonded12_supply_price,
                    'rdg_supply_price13' => $bonded13_supply_price,
                    'rdg_supply_price14' => $bonded14_supply_price,

                    'rdg_vat1' => $bonded1_taxes,
                    'rdg_vat2' => $bonded2_taxes,
                    'rdg_vat3' => $bonded3_taxes,
                    'rdg_vat4' => $bonded4_taxes,
                    'rdg_vat5' => $bonded5_taxes,
                    'rdg_vat6' => $bonded6_taxes,
                    'rdg_vat7' => $bonded7_taxes,
                    'rdg_vat8' => $bonded8_taxes,
                    'rdg_vat9' => $bonded9_taxes,
                    'rdg_vat10' => $bonded10_taxes,
                    'rdg_vat11' => $bonded11_taxes,
                    'rdg_vat12' => $bonded12_taxes,
                    'rdg_vat13' => $bonded13_taxes,
                    'rdg_vat14' => $bonded14_taxes,

                    'rdg_sum1' => $bonded1_sum,
                    'rdg_sum2' => $bonded2_sum,
                    'rdg_sum3' => $bonded3_sum,
                    'rdg_sum4' => $bonded4_sum,
                    'rdg_sum5' => $bonded5_sum,
                    'rdg_sum6' => $bonded6_sum,
                    'rdg_sum7' => $bonded7_sum,
                    'rdg_sum8' => $bonded8_sum,
                    'rdg_sum9' => $bonded9_sum,
                    'rdg_sum10' => $bonded10_sum,
                    'rdg_sum11' => $bonded11_sum,
                    'rdg_sum12' => $bonded12_sum,
                    'rdg_sum13' => $bonded13_sum,
                    'rdg_sum14' => $bonded14_sum,

                    'rdg_etc1' => isset($request->bonded1['etc']) ? $request->bonded1['etc'] : '',
                    'rdg_etc2' => isset($request->bonded2['etc']) ? $request->bonded2['etc'] : '',
                    'rdg_etc3' => isset($request->bonded3['etc']) ? $request->bonded3['etc'] : '',
                    'rdg_etc4' => isset($request->bonded4['etc']) ? $request->bonded4['etc'] : '',
                    'rdg_etc5' => isset($request->bonded5['etc']) ? $request->bonded5['etc'] : '',
                    'rdg_etc6' => isset($request->bonded6['etc']) ? $request->bonded6['etc'] : '',
                    'rdg_etc7' => isset($request->total['etc']) ? $request->total['etc'] : '',
                ]
            );


            $previous_rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->where('rgd_bill_type', '=', $request->previous_bill_type)->first();
            //Case of creating a est bill.
            if ($request->type == 'create_expectation' || $request->type == 'create_expectation_monthly') {
                $previous_rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();
                //The status distinguishes the creator as spasys or shop, only applicable to est bill
                $previous_rgd->rgd_status4 = $user->mb_type == 'shop' ? 'issued' : $previous_rgd->rgd_status4;
                $previous_rgd->rgd_status5 = $user->mb_type == 'spasys' ? 'issued' : $previous_rgd->rgd_status5;
                $previous_rgd->save();



                $word_type = '';
                if ($previous_rgd->service_korean_name == '수입풀필먼트') {
                    $word_type = 'MF';
                } else if (str_contains($request->bill_type, 'final') && str_contains($request->bill_type, 'month')) {
                    $word_type = 'MF';
                } else if (str_contains($request->bill_type, 'expectation') && str_contains($request->bill_type, 'month')) {
                    $word_type = 'M';
                } else if (str_contains($request->bill_type, 'expectation') && !str_contains($request->bill_type, 'month')) {
                    $word_type = 'C';
                } else if (str_contains($request->bill_type, 'final') && !str_contains($request->bill_type, 'month')) {
                    $word_type = 'CF';
                }


                if ($word_type == 'C' || $word_type == 'M') {
                    $count = ReceivingGoodsDelivery::select(DB::raw('receiving_goods_delivery.*'))
                        ->whereNotNull('rgd_settlement_number')
                        ->whereMonth('created_at', Carbon::today()->month)
                        ->whereYear('created_at', Carbon::today()->year)
                        ->where(\DB::raw('substr(rgd_settlement_number, -1)'), '=', $word_type)
                        ->orderBy('rgd_no', 'DESC')
                        ->first();
                    if (isset($count->rgd_no)) {
                        $count = substr($count->rgd_settlement_number, 7, 5) + 1;
                    } else {
                        $count = 1;
                    }
                } else if ($word_type == 'CF' || $word_type == 'MF') {
                    $count = ReceivingGoodsDelivery::select(DB::raw('receiving_goods_delivery.*'))
                        ->whereNotNull('rgd_settlement_number')
                        ->whereMonth('created_at', Carbon::today()->month)
                        ->whereYear('created_at', Carbon::today()->year)
                        ->where(\DB::raw('substr(rgd_settlement_number, -2)'), '=', $word_type)
                        ->orderBy('rgd_no', 'DESC')
                        ->first();
                    if (isset($count->rgd_no)) {
                        $count = substr($count->rgd_settlement_number, 7, 5) + 1;
                    } else {
                        $count = 1;
                    }
                }

                if ($count >= 0  && $count < 10) {
                    $count = "0000" . $count;
                } else if ($count >= 10 && $count < 100) {
                    $count = "000" . $count;
                } else if ($count >= 100 && $count < 1000) {
                    $count = "00" . $count;
                } else if ($count >= 1000 && $count < 10000) {
                    $count = "0" . $count;
                }

                $rgd_settlement_number = Carbon::now()->format('Ym') . '_' . $count . '_' . $word_type;


                //Copy final bill from est bill
                $final_rgd = $previous_rgd->replicate();
                $final_rgd->rgd_bill_type = $request->bill_type; // the new project_id
                $final_rgd->rgd_status3 = null;
                $final_rgd->rgd_status4 = $request->status;
                $final_rgd->rgd_issue_date = Carbon::now()->toDateTimeString();
                $final_rgd->rgd_settlement_number = $rgd_settlement_number;
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
                $final_rgd->rgd_calculate_deadline_yn = $user->mb_type == 'spasys' ? 'y' : ($request->rgd_calculate_deadline_yn ? $request->rgd_calculate_deadline_yn : $previous_rgd->rgd_calculate_deadline_yn);
                $final_rgd->rgd_discount_rate = $request->rgd_discount_rate;
                $final_rgd->save();

                //Update rgd_no for the previously created rdg_no
                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $final_rgd->rgd_no,
                ]);

                //Update rgd_no for rateMetaData
                RateMetaData::where('rgd_no', $request->rgd_no)
                    ->where('set_type', 'LIKE', '%' . ($user->mb_type == 'spasys' ? '_spasys' : '_shop') . '%')
                    ->update([
                        'rgd_no' => $final_rgd->rgd_no,
                    ]);

                //INSERT ALARM DATA TABLE

                $final_rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $final_rgd->rgd_no)->first();

                CommonFunc::insert_alarm('[보세화물] 예상경비청구서 발송', $final_rgd, $user, null, 'settle_payment', null);


                //Case of creating a final bill.
            } else if (!isset($is_exist->rdg_no) && isset($request->previous_bill_type) && !empty($previous_rgd)) {

                //Update the status of the est bill to 'issued', which means a final bill  has been created from this est bill.
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
                $final_rgd->rgd_calculate_deadline_yn = $user->mb_type == 'spasys' ? 'y' : $previous_rgd->rgd_calculate_deadline_yn;
                $final_rgd->mb_no = Auth::user()->mb_no;
                $final_rgd->rgd_parent_no = $previous_rgd->rgd_no;
                $final_rgd->save();

                //Update rgd_no for the previously created rdg_no
                RateDataGeneral::where('rdg_no', $rdg->rdg_no)->update([
                    'rgd_no' => $final_rgd->rgd_no,
                ]);
                //Update rgd_no for rateMetaData
                RateMetaData::where('rgd_no', $request->rgd_no)
                    ->where('set_type', 'LIKE', '%_final%')
                    ->update([
                        'rgd_no' => $final_rgd->rgd_no,
                    ]);

                //INSERT ALARM DATA TABLE

                $final_rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $final_rgd->rgd_no)->first();

                CommonFunc::insert_alarm('[보세화물] 확정청구서 발송', $final_rgd, $user, null, 'settle_payment', null);

                //UPDATE EST BILL WHEN ISSUE FINAL BILL
                $est_rgd =  ReceivingGoodsDelivery::where('rgd_no', $final_rgd->rgd_parent_no)->first();

                if ($est_rgd->rgd_status6 != 'paid' && $est_rgd->service_korean_name == '보세화물' && $est_rgd->rgd_calculate_deadline_yn == 'y' && !str_contains($est_rgd->rgd_bill_type, 'month')) {
                    ReceivingGoodsDelivery::where('rgd_settlement_number', $est_rgd->rgd_settlement_number)->update([
                        'rgd_status6' => 'paid',
                        'is_expect_payment' => 'y', //NOT REAL PAID
                        'rgd_paid_date' => Carbon::now()->toDateTimeString()
                    ]);

                    Payment::updateOrCreate(
                        [
                            'rgd_no' => $est_rgd['rgd_no'],
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
                        'rgd_no' => $est_rgd->rgd_no,
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'payment',
                        'cbh_status_before' => $est_rgd->rgd_status6,
                        'cbh_status_after' => 'payment_bill'
                    ]);
                }
                //END UPDATE EST BILL WHEN ISSUE FINAL BILL

            }

            //Rate Data
            foreach ([1, 2, 3, 4, 5] as $index) {
                if ($request->type == 'create_final') {
                    $set_type = 'bonded' . $index . '_final';

                    $rmd = RateMetaData::where('rgd_no', $final_rgd->rgd_no)->where('set_type', $set_type)->first();

                    if (!isset($rmd->rmd_no)) {
                        $set_type = $user->mb_type == 'spasys' ? ('bonded' . $index . '_spasys') : ('bonded' . $index . '_shop');

                        $rmd = RateMetaData::where('rgd_no', $rgd->rgd_no)->where('set_type', $set_type)->first();
                        if (isset($rmd->rmd_no)) {
                            $rmd_expectation = $rmd->replicate();
                            $rmd_expectation->rgd_no = $final_rgd->rgd_no;
                            $rmd_expectation->set_type = 'bonded' . $index . '_final';
                            $rmd_expectation->save();

                            $rds = RateData::where('rmd_no', $rmd->rmd_no)->get();
                            foreach ($rds as $index => $rd) {
                                $rd_expectation = $rd->replicate();
                                $rd_expectation->rmd_no = $rmd_expectation->rmd_no;
                                $rd_expectation->save();
                            }

                            $rmd_file = RateMetaData::with('files')->where('rmd_no', $rmd->rmd_no)->first();


                            if (isset($rmd_file)) {
                                $files = [];
                                foreach ($rmd_file->files as $key => $file) {
                                    $files[] = [
                                        'file_table' => 'rate_data',
                                        'file_table_key' => $rmd_expectation->rmd_no,
                                        'file_name_old' => $file->file_name_old,
                                        'file_name' => $file->file_name,
                                        'file_size' => $file->file_size,
                                        'file_extension' => $file->file_extension,
                                        'file_position' => $file->file_position,
                                        'file_url' => $file->file_url
                                    ];
                                }
                                FileTable::insert($files);
                            }
                        }
                    }
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

            ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $request->rgd_no)->update([
                'rgd_storage_days' => $request->storage_days,
                'rgd_e_price' => $request->te_e_price ? $request->te_e_price : null,
            ]);

            $rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $request->rgd_no)->first();

            $company = Company::where('co_no', $rgd->warehousing->co_no)->first();

            Import::where('ti_logistic_manage_number', $request->ti_logistic_manage_number)->update([
                'ti_co_license' => isset($company->co_license) ? $company->co_license : null,
                'ti_logistic_type' => isset($request->ti_logistic_type) ? $request->ti_logistic_type : null,
                // 'ti_i_storeday' => $request->storage_days || $request->storage_days == '0' ? $request->storage_days : $request->storagedays,
            ]);

            ImportExpected::where('tie_logistic_manage_number', $request->ti_logistic_manage_number)->update([
                'tie_co_license' => isset($company->co_license) ? $company->co_license : null,
            ]);

            Export::where('te_logistic_manage_number', $request->ti_logistic_manage_number)->update([
                'te_e_price' => isset($request->te_e_price) ? $request->te_e_price : null,
            ]);

            // Warehousing::where('logistic_manage_number', $request->ti_logistic_manage_number)->update([
            //     'co_no' => isset($request->co_no) ? $request->co_no : null,
            // ]);

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
            return $e;
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

    public function download_distribution_monthbill_excel($rgd_no, Request $request)
    {
        Log::error($rgd_no);
        DB::beginTransaction();
        $user = Auth::user();
        $pathname = $request->header('pathname');
        $is_check_page = str_contains($pathname, 'check');
        $rgd = ReceivingGoodsDelivery::with(['rate_data_general', 'warehousing'])->where('rgd_no', $rgd_no)->first();

        $rgds = ReceivingGoodsDelivery::with(['rate_data_general', 'warehousing', 't_import_expected'])->whereHas('rgd_child', function ($q) use ($rgd) {
            $q->where('rgd_settlement_number',  $rgd->rgd_settlement_number);
        })->get();

        $rgds[] = $rgd;

        $is_month_bill = str_contains($rgd->rgd_bill_type, 'month') ? '_monthly' : '';
        $is_final_bill = str_contains($rgd->rgd_bill_type, 'final');

        if ($user->mb_type == 'shop') {
            $company = $is_check_page ? $rgd->warehousing->company->co_parent : $rgd->warehousing->company;
        } else if ($user->mb_type == 'spasys') {
            $company = $rgd->warehousing->company->co_parent;
        } else if ($user->mb_type == 'shipper') {
            $company = $rgd->warehousing->company;
        }

        $company->company_payment = CompanyPayment::where('co_no', $company->co_no)->first();

        // return response()->json([
        //     'rgd' => $rgd->rate_data_general,
        //     '1' => $rate_data_work,
        //     '2' => $rate_data_storage,
        //     '3' => $rate_data_domestic,
        // ], 200);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $sheet = $spreadsheet->getActiveSheet(0);

        // $sheet->getProtection()->setSheet(true);
        $sheet->getDefaultColumnDimension()->setWidth(4.5);
        // $sheet->getDefaultRowDimension()->setRowHeight(24);
        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(24);
        $sheet->getStyle('A1:Z200')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:CT200')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->setTitle('유통가공 확정청구(월별)');


        $sheet->mergeCells('B2:Z6');
        $sheet->getStyle('B2:Z6')->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->setCellValue('B2', $company->co_name);
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2')->getFont()->setSize(22)->setBold(true);

        $sheet->getStyle('Z8')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Z8', '사업자번호 : ' . $company->co_license);
        $sheet->getStyle('Z9')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Z9', '사업장 주소 : ' . $company->co_address . ' ' . $company->co_address_detail);
        $sheet->getStyle('Z10')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Z10', '수신자명 : ' . $company->co_owner . ' (' . $company->co_email . ')');

        $sheet->getStyle('B12:B17')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
        $sheet->getStyle('B12:B17')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->getStyle('B12:B17')->getFont()->setBold(true);
        $sheet->mergeCells('B12:Z12');
        $sheet->setCellValue('B12', ' ∙ 서   비  스 : 유통가공');
        $sheet->mergeCells('B13:Z13');
        $sheet->setCellValue('B13', ' ∙ 입고화물번호 건수 : ' . (count($rgds) - 1) . '건');
        $sheet->mergeCells('B14:Z14');
        $sheet->setCellValue('B14', ' ∙ 청구서 No : ' . $rgd->rgd_status4 . ' ' . $rgd->rgd_settlement_number);
        $sheet->mergeCells('B15:Z15');
        $sheet->setCellValue('B15', ' ∙ 청구서 발행일 : ' . Carbon::createFromFormat('Y-m-d H:i:s', $rgd->created_at)->format('Y.m.d'));
        $sheet->mergeCells('B16:Z16');
        $sheet->setCellValue('B16', ' ∙ 예상 청구금액 : ' . number_format($rgd->rate_data_general->rdg_sum4) . '원');
        $sheet->mergeCells('B17:Z17');
        $sheet->setCellValue('B17', isset($company->company_payment) ? (' ∙ 계좌  정보 : ㈜' . $company->company_payment->cp_bank_name . ' ' . $company->company_payment->cp_bank_number . ' (' . $company->company_payment->cp_card_name . ')') : ' ∙ 계좌  정보 : ㈜');

        //GENERAL TABLE
        $sheet->getStyle('B19')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->getStyle('B19')->getFont()->setBold(true);
        $sheet->mergeCells('B19:Z19');
        $sheet->setCellValue('B19', ' ∙ 화물별 청구 금액');

        $headers = ['작업료', '보관료', '국내운송료', '공급가', '부가세', '급액', '비고'];
        $col_start = ['F', 'I', 'L', 'O', 'R', 'U', 'X'];
        $col_end = ['H', 'K', 'N', 'Q', 'T', 'W', 'Z'];

        $categories = ['작업료', '보관료', '국내운송료', '합계'];

        $current_row = 20;
        $count_row = 0;

        $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
        $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getFont()->setBold(true);

        $sheet->mergeCells('B' . ($current_row));
        $sheet->getStyle('B' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('B' . ($current_row), 'NO');

        $sheet->mergeCells('C' . ($current_row));
        $sheet->getStyle('C' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('C' . ($current_row), '예상경비청구서 No.');

        $sheet->mergeCells('D' . ($current_row));
        $sheet->getStyle('D' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('D' . ($current_row), '입고 화물번호');

        $sheet->mergeCells('E' . ($current_row));
        $sheet->getStyle('E' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('E' . ($current_row), '출고일자');


        foreach ($headers as $key => $header) {
            $sheet->mergeCells($col_start[$key] . ($current_row) . ':' . $col_end[$key] . ($current_row));
            $sheet->getStyle($col_start[$key] . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue($col_start[$key] . ($current_row), $header);
        }

        $current_row += 1;

        foreach ($rgds as $key_rgd => $rgd) {

            $child_length = count($rgd['warehousing']['warehousing_child']);

            $sheet->mergeCells('B' . ($current_row));
            $sheet->getStyle('B' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B' . ($current_row), $key_rgd == (count($rgds) - 1) ? '합계' : $key_rgd + 1);

            $sheet->mergeCells('C' . ($current_row));
            $sheet->getStyle('C' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('C' . ($current_row), $key_rgd == (count($rgds) - 1) ? '' : $rgd['rgd_settlement_number']);

            $sheet->mergeCells('D' . ($current_row));
            $sheet->getStyle('D' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('D' . ($current_row), $key_rgd == (count($rgds) - 1) ? '' : $rgd['warehousing']['w_schedule_number2']);

            $sheet->mergeCells('E' . ($current_row));
            $sheet->getStyle('E' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('E' . ($current_row), $key_rgd == (count($rgds) - 1) ? '' : str_replace(' 00:00:00', '', isset($rgd['warehousing']['warehousing_child'][$child_length - 1]['w_completed_day'])  ? Carbon::createFromFormat('Y-m-d H:i:s', $rgd['warehousing']['warehousing_child'][$child_length - 1]['w_completed_day'])->format('Y.m.d') : ''));


            foreach ($headers as $key => $header) {

                $sheet->mergeCells($col_start[$key] . ($current_row) . ':' . $col_end[$key] . ($current_row));
                $sheet->getStyle($col_start[$key] . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col_start[$key] . ($current_row))->getNumberFormat()->setFormatCode('#,##0_-""');
                if ($key == 0) {

                    $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_supply_price2']);
                }

                if ($key == 1) {
                    $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_supply_price1']);
                }

                if ($key == 2) {
                    $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_supply_price3']);
                }

                if ($key == 3) {
                    $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_supply_price4']);
                }

                if ($key == 4) {
                    $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_vat4']);
                }

                if ($key == 5) {
                    $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_sum4']);
                }

                if ($key == 6) {
                    $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_etc3']);
                }
            }

            $current_row += 1;
            $count_row += 1;
        }


        $sheet->getStyle('B' . ($current_row - $count_row) . ':Z' . ($current_row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));

        $current_row += 1;

        $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row + 3))->getBorders()->getOutLine()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->getStyle('B' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)->setWrapText(true);
        $sheet->mergeCells('B' . ($current_row) . ':Z' . ($current_row + 3));
        $sheet->setCellValue('B' . ($current_row), $rgd['rgd_memo_settle']);

        $current_row += 4;

        $sheet->setCellValue('B' . ($current_row), '');
        // $sheet->setCellValue('B'. ($current_row + 1), '1. 보세화물 서비스의 예상경비 청구서는 BL번호 단위로 발송됩니다.(단 분할인 경우 반출단위)');
        // $sheet->setCellValue('B'. ($current_row + 2), '2. 세금계산서 발행은 확정청구서와 함께 처리 됩니다.');
        $sheet->setCellValue('B' . ($current_row + 1), '1. 결제는 PC/Mobile에 접속하여서 결제하시면 되며, 월별 청구인 경우 매달 24일까지 결제가 되지 않으면 25일 등록 된 카드로 자동결제 됩니다.');
        $sheet->setCellValue('B' . ($current_row + 2), '2. 결제수단에 따라 수수료가 추가 청구 됩니다.(카드/카카오페이 2.9%, 실시간계좌이체 1.8% 등)');

        $issuer = Member::where('mb_no', $rgd->mb_no)->first();
        $company = Company::where('co_no', $issuer->co_no)->first();

        $sheet->getStyle('B' . ($current_row + 6) . ':Z' . ($current_row + 9))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
        $sheet->getStyle('B' . ($current_row + 6) . ':Z' . ($current_row + 9))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B' . ($current_row + 6) . ':Z' . ($current_row + 9))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->mergeCells('B' . ($current_row + 6) . ':Z' . ($current_row + 6));
        $sheet->setCellValue('B' . ($current_row + 6), $company->co_name);
        $sheet->mergeCells('B' . ($current_row + 7) . ':Z' . ($current_row + 7));
        $sheet->setCellValue('B' . ($current_row + 7), $company->co_address . ' ' . $company->co_address_detail);
        // $sheet->mergeCells('B'. ($current_row + 8). ':Z'. ($current_row + 8));
        // $sheet->setCellValue('B'. ($current_row + 8), $company->co_owner);
        $sheet->mergeCells('B' . ($current_row + 8) . ':Z' . ($current_row + 8));
        $sheet->setCellValue('B' . ($current_row + 8), $company->co_tel);
        $sheet->mergeCells('B' . ($current_row + 9) . ':Z' . ($current_row + 9));
        $sheet->setCellValue('B' . ($current_row + 9), $company->co_email);

        // $sheet->getDefaultRowDimension()->setRowHeight(24);
        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = 'storage/download/' . $user->mb_no . '/';
        } else {
            $path = 'storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }

        if ($rgd->service_korean_name == '유통가공' && !str_contains($rgd->rgd_bill_type, 'month') && $rgd->rgd_status4 == '예상경비청구서') {
            $name = 'distribution_est_casebill_';
        } else if ($rgd->service_korean_name == '유통가공' && str_contains($rgd->rgd_bill_type, 'month') && $rgd->rgd_status4 == '예상경비청구서') {
            $name = 'distribution_est_monthbill_';
        } else {
            $name = 'distribution_final_monthbill_';
        }

        $mask = $path . $name . '*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . $name . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => '../' . $file_name_download,
            'message' => 'Download File',
        ], 200);
        ob_end_clean();
    }

    public function download_distribution_casebill_excel($rgd_no, Request $request)
    {
        Log::error($rgd_no);
        DB::beginTransaction();
        $user = Auth::user();
        $pathname = $request->header('pathname');
        $is_check_page = str_contains($pathname, 'check');

        $rgd = ReceivingGoodsDelivery::with(['rate_data_general', 'warehousing'])->where('rgd_no', $rgd_no)->first();
        $is_month_bill = str_contains($rgd->rgd_bill_type, 'month') ? '_monthly' : '';
        $is_final_bill = str_contains($rgd->rgd_bill_type, 'final');

        if ($user->mb_type == 'shop') {
            $company = $is_check_page ? $rgd->warehousing->company->co_parent : $rgd->warehousing->company;

            $rmd_no_work = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'work' . $is_month_bill . ($is_final_bill ? '_final' : ($is_check_page ? '_spasys' : '_shop')))->first();
            $rmd_no_storage = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'storage' . $is_month_bill . ($is_final_bill ? '_final' : ($is_check_page ? '_spasys' : '_shop')))->first();
            $rmd_no_domestic = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'domestic' . $is_month_bill . ($is_final_bill ? '_final' : ($is_check_page ? '_spasys' : '_shop')))->first();
        } else if ($user->mb_type == 'spasys') {
            $company = $rgd->warehousing->company->co_parent;
            $rmd_no_work = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'work' . $is_month_bill . ($is_final_bill ? '_final' : '_spasys'))->first();
            $rmd_no_storage = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'storage' . $is_month_bill . ($is_final_bill ? '_final' : '_spasys'))->first();
            $rmd_no_domestic = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'domestic' . $is_month_bill . ($is_final_bill ? '_final' : '_spasys'))->first();
        } else if ($user->mb_type == 'shipper') {
            $company = $rgd->warehousing->company;
            $rmd_no_work = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'work' . $is_month_bill . ($is_final_bill ? '_final' : '_shop'))->first();
            $rmd_no_storage = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'storage' . $is_month_bill . ($is_final_bill ? '_final' : '_shop'))->first();
            $rmd_no_domestic = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'domestic' . $is_month_bill . ($is_final_bill ? '_final' : '_shop'))->first();
        }

        $company->company_payment = CompanyPayment::where('co_no', $company->co_no)->first();

        $rate_data_work = $rate_data = RateData::where('rmd_no', isset($rmd_no_work) ? $rmd_no_work->rmd_no : 0)->where(function ($q) {
        })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

        $rate_data_storage = $rate_data = RateData::where('rmd_no', isset($rmd_no_storage) ? $rmd_no_storage->rmd_no : 0)->where(function ($q) {
        })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

        $rate_data_domestic = $rate_data = RateData::where('rmd_no', isset($rmd_no_domestic) ? $rmd_no_domestic->rmd_no : 0)->where(function ($q) {
        })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

        // return response()->json([
        //     'rgd' => $rgd->rate_data_general,
        //     '1' => $rate_data_work,
        //     '2' => $rate_data_storage,
        //     '3' => $rate_data_domestic,
        // ], 200);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $sheet = $spreadsheet->getActiveSheet(0);

        // $sheet->getProtection()->setSheet(true);
        $sheet->getDefaultColumnDimension()->setWidth(4.5);
        // $sheet->getDefaultRowDimension()->setRowHeight(24);
        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getStyle('A1:Z200')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:CT200')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->setTitle($is_final_bill ? '유통가공 확정청구(건별)' : '유통가공 예상경비(건별.월별)');


        $sheet->mergeCells('B2:Z6');
        $sheet->getStyle('B2:Z6')->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->setCellValue('B2', $company->co_name);
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2')->getFont()->setSize(22)->setBold(true);

        $sheet->getStyle('Z8')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Z8', '사업자번호 : ' . $company->co_license);
        $sheet->getStyle('Z9')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Z9', '사업장 주소 : ' . $company->co_address . ' ' . $company->co_address_detail);
        $sheet->getStyle('Z10')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Z10', '수신자명 : ' . $company->co_owner . ' (' . $company->co_email . ')');

        $sheet->getRowDimension('11')->setVisible(false);
        $sheet->getStyle('B13:B17')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
        $sheet->getStyle('B13:B17')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->getStyle('B13:B17')->getFont()->setBold(true);
        $sheet->mergeCells('B13:Z13');
        $sheet->setCellValue('B13', ' ∙ 서   비  스 : 유통가공');
        $sheet->mergeCells('B14:Z14');
        $sheet->setCellValue('B14', ' ∙ 청구서 No : ' . $rgd->rgd_status4 . ' ' . $rgd->rgd_settlement_number);
        $sheet->mergeCells('B15:Z15');
        $sheet->setCellValue('B15', ' ∙ 청구서 발행일 : ' . Carbon::createFromFormat('Y-m-d H:i:s', $rgd->created_at)->format('Y.m.d'));
        $sheet->mergeCells('B16:Z16');
        $sheet->setCellValue('B16', ' ∙ 예상 청구금액 : ' . number_format($rgd->rate_data_general->rdg_sum4) . '원');
        $sheet->mergeCells('B17:Z17');
        $sheet->setCellValue('B17', isset($company->company_payment) ? (' ∙ 계좌  정보 : ㈜' . $company->company_payment->cp_bank_name . ' ' . $company->company_payment->cp_bank_number . ' (' . $company->company_payment->cp_card_name . ')') : ' ∙ 계좌  정보 : ㈜');

        //GENERAL TABLE
        $sheet->getStyle('B19')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->getStyle('B19')->getFont()->setBold(true);
        $sheet->mergeCells('B19:Z19');
        $sheet->setCellValue('B19', '  ∙ 항목별 청구 금액');

        $headers = ['단위', '단가', '건수', '공급가', '부가세', '급액', '비고'];
        $col_start = ['F', 'I', 'L', 'O', 'R', 'U', 'X'];
        $col_end = ['H', 'K', 'N', 'Q', 'T', 'W', 'Z'];

        $categories = ['작업료', '보관료', '국내운송료', '합계'];

        $current_row = 22;
        $count_row = 0;

        foreach ($categories as $key => $category) {

            if ($key == 0) $index = 2;
            else if ($key == 1) $index = 1;
            else $index = $key + 1;

            if ($rgd->rate_data_general['rdg_sum' . ($index)] != 0) {
                $sheet->mergeCells('B' . ($current_row + $count_row) . ':E' . ($current_row + $count_row));
                $sheet->setCellValue('B' . ($current_row + $count_row), $category);
                $sheet->mergeCells('F' . ($current_row + $count_row) . ':I' . ($current_row + $count_row));
                $sheet->getStyle('F' . ($current_row + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');
                $sheet->setCellValue('F' . ($current_row + $count_row), $rgd->rate_data_general['rdg_supply_price' . ($index)]);
                $sheet->mergeCells('J' . ($current_row + $count_row) . ':M' . ($current_row + $count_row));
                $sheet->getStyle('J' . ($current_row + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');
                $sheet->setCellValue('J' . ($current_row + $count_row), $rgd->rate_data_general['rdg_vat' . ($index)]);
                $sheet->mergeCells('N' . ($current_row + $count_row) . ':Q' . ($current_row + $count_row));
                $sheet->getStyle('N' . ($current_row + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');
                $sheet->setCellValue('N' . ($current_row + $count_row), $rgd->rate_data_general['rdg_sum' . ($index)]);
                $sheet->mergeCells('R' . ($current_row + $count_row) . ':Z' . ($current_row + $count_row));
                $sheet->setCellValue('R' . ($current_row + $count_row), $rgd->rate_data_general['rdg_etc' . ($index)]);

                $count_row += 1;
            }
        }
        //FORMAT NUMBER
        $sheet->getStyle('F' . ($current_row) . ':Q' . ($current_row - 1 + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');

        $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row - 1 + $count_row))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
        $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row - 1 + $count_row))->getFont()->setBold(true);
        $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row - 1 + $count_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 1 + $count_row))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2 + 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
        $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2 + 1))->getFont()->setBold(true);

        $sheet->mergeCells('B' . ($current_row - 2) . ':E' . ($current_row - 2 + 1));
        $sheet->getStyle('B' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('B' . ($current_row - 2), '항목');

        $sheet->mergeCells('F' . ($current_row - 2) . ':I' . ($current_row - 2 + 1));
        $sheet->getStyle('F' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('F' . ($current_row - 2), '공급가');

        $sheet->mergeCells('J' . ($current_row - 2) . ':M' . ($current_row - 2 + 1));
        $sheet->getStyle('J' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('J' . ($current_row - 2), '부가세');

        $sheet->mergeCells('N' . ($current_row - 2) . ':Q' . ($current_row - 2 + 1));
        $sheet->getStyle('N' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('N' . ($current_row - 2), '비고');

        $sheet->mergeCells('R' . ($current_row - 2) . ':Z' . ($current_row - 2 + 1));
        $sheet->getStyle('R' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('R' . ($current_row - 2), '비고');

        $current_row += $count_row;

        $rdg_sum = ['rdg_sum2', 'rdg_sum1', 'rdg_sum3'];
        $titles = [' ∙ 작업료 상세', ' ∙ 보관료 상세', ' ∙ 국내운송료 상세'];
        $rate_data_arr = [$rate_data_work, $rate_data_storage, $rate_data_domestic];

        foreach ($rate_data_arr as $key_rate => $rate_data_cate) {
            if ($rgd->rate_data_general[$rdg_sum[$key_rate]] > 0) {
                $sheet->getStyle('B' . $current_row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
                $sheet->getStyle('B' . $current_row)->getFont()->setBold(true);
                $sheet->mergeCells('B' . $current_row . ':Z' . $current_row);
                $sheet->setCellValue('B' . $current_row, $titles[$key_rate]);

                $current_row += 3;



                $count_row = 0;

                $count_row_fulfill1 = 0;
                $current_row_fulfill1 = $current_row - 1;

                $rd_cate1 = [];
                $rd_data4_sum = 0;
                $rd_data5_sum = 0;
                $rd_data6_sum = 0;
                $rd_data7_sum = 0;
                $rd_data4_total = 0;


                $rate_data_ = [];

                foreach ($rate_data_cate as $key => $rate_data) {
                    if ($rate_data['rd_data7'] > 0) {
                        array_push($rate_data_, $rate_data);
                        $rate_data['rd_data4'] = $rate_data['rd_data4'] == '' ? 0 : $rate_data['rd_data4'];
                        $rate_data['rd_data5'] = $rate_data['rd_data5'] == '' ? 0 : $rate_data['rd_data5'];
                        $rate_data['rd_data6'] = $rate_data['rd_data6'] == '' ? 0 : $rate_data['rd_data6'];
                        $rate_data['rd_data7'] = $rate_data['rd_data7'] == '' ? 0 : $rate_data['rd_data7'];
                        $rd_data4_total += $rate_data['rd_data4'];

                        if (!in_array($rate_data['rd_cate1'], $rd_cate1)) {
                            $rd_data4_sum = 0;
                            $rd_data5_sum = 0;
                            $rd_data6_sum = 0;
                            $rd_data7_sum = 0;
                            $rd_cate1[] = $rate_data['rd_cate1'];
                        }

                        $rd_data4_sum += $rate_data['rd_data4'];
                        $rd_data5_sum += $rate_data['rd_data5'];
                        $rd_data6_sum += $rate_data['rd_data6'];
                        $rd_data7_sum += $rate_data['rd_data7'];
                    }
                }


                foreach ($rate_data_ as $key => $rate_data) {



                    if ($rate_data['rd_cate1'] == $rd_cate1[0]) {
                        if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_[$key - 1]['rd_cate1'])) {
                            $sheet->setCellValue('B' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate1']);
                            $count_row_fulfill1 = 0;
                            if ($rate_data['rd_data7'] > 0) {

                                if ($rate_data['rd_cate1'] == '원산지 표시' || $rate_data['rd_cate1'] == 'TAG' || $rate_data['rd_cate1'] == '라벨' || $rate_data['rd_cate1'] == '보관' || $rate_data['rd_cate1'] == '운송') {
                                    $start_c = 'C';
                                } else {
                                    $start_c = 'B';
                                }

                                $sheet->mergeCells($start_c . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue($start_c . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2'] != '' ? $rate_data['rd_cate2'] : $rate_data['rd_cate1']);
                                $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                                $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                                $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                                $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                                $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                                $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                                $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                                $count_row_fulfill1 += 1;
                                $count_row += 1;
                            }
                        } else if ($rate_data['rd_data7'] > 0) {

                            if ($rate_data['rd_cate1'] == '원산지 표시' || $rate_data['rd_cate1'] == 'TAG' || $rate_data['rd_cate1'] == '라벨' || $rate_data['rd_cate1'] == '보관' || $rate_data['rd_cate1'] == '운송') {
                                $start_c = 'C';
                            } else {
                                $start_c = 'B';
                            }

                            $sheet->mergeCells($start_c . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue($start_c . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2'] != '' ? $rate_data['rd_cate2'] : $rate_data['rd_cate1']);
                            $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                            $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                            $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                            $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                            $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                            $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                            $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                            $count_row_fulfill1 += 1;
                            $count_row += 1;
                        }
                    } else {
                        if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_[$key - 1]['rd_cate1'])) {
                            $sheet->mergeCells('B' . ($current_row_fulfill1) . ':B' . ($current_row_fulfill1 + $count_row_fulfill1 - 1));
                            $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                            $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFont()->setBold(true);
                            $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                            $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('B' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate1']);
                            $current_row_fulfill1 = $current_row_fulfill1 + $count_row_fulfill1;
                            $count_row_fulfill1 = 0;

                            if ($rate_data['rd_data7'] > 0) {

                                if ($rate_data['rd_cate1'] != '원산지 표시' && $rate_data['rd_cate1'] != 'TAG' && $rate_data['rd_cate1'] != '라벨') {
                                    $sheet->mergeCells('B' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                                    $sheet->setCellValue('B' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2'] != '' ? $rate_data['rd_cate2'] : $rate_data['rd_cate1']);
                                } else {
                                    $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                                    $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2'] != '' ? $rate_data['rd_cate2'] : $rate_data['rd_cate1']);
                                }

                                $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                                $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                                $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                                $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                                $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                                $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                                $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                                $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                                $count_row_fulfill1 += 1;
                                $count_row += 1;
                            }
                        } else if ($rate_data['rd_data7'] > 0) {


                            $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                            $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                            $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                            $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                            $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                            $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                            $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                            $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                            $count_row_fulfill1 += 1;
                            $count_row += 1;
                        }
                    }

                    if (count($rate_data_) == $key + 1) {
                        $sheet->mergeCells('B' . ($current_row_fulfill1) . ':B' . ($current_row_fulfill1 + $count_row_fulfill1 - 1));
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFont()->setBold(true);
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $current_row_fulfill1 = $current_row_fulfill1 + $count_row_fulfill1;
                        $count_row_fulfill1 = 0;
                    }
                }

                //FORMAT NUMBER
                $sheet->getStyle('F' . ($current_row - 2) . ':W' . ($current_row - 1 + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');

                $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getFont()->setBold(true);

                $sheet->mergeCells('B' . ($current_row - 2) . ':E' . ($current_row - 2));
                $sheet->getStyle('B' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('B' . ($current_row - 2), '항목');


                foreach ($headers as $key => $header) {
                    $sheet->mergeCells($col_start[$key] . ($current_row - 2) . ':' . $col_end[$key] . ($current_row - 2));
                    $sheet->getStyle($col_start[$key] . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->setCellValue($col_start[$key] . ($current_row - 2), $header);
                }

                $current_row += $count_row;

                $sheet->getStyle('B' . ($current_row - 1) . ':Z' . ($current_row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                $sheet->getStyle('B' . ($current_row - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                $sheet->getStyle('B' . ($current_row - 1) . ':Z' . ($current_row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B' . ($current_row - 1))->getFont()->setBold(true);
                $sheet->setCellValue('B' . ($current_row - 1), '합계');
                $sheet->setCellValue('B' . ($current_row - 1), '합계');

                $sheet->mergeCells('B' . ($current_row - 1) . ':E' . ($current_row - 1));
                $sheet->mergeCells('F' . ($current_row - 1) . ':H' . ($current_row - 1));
                $sheet->mergeCells('I' . ($current_row - 1) . ':K' . ($current_row - 1));
                $sheet->mergeCells('L' . ($current_row - 1) . ':N' . ($current_row - 1));
                $sheet->setCellValue('L' . ($current_row - 1), $rd_data4_total);
                $sheet->mergeCells('O' . ($current_row - 1) . ':Q' . ($current_row - 1));
                $sheet->setCellValue('O' . ($current_row - 1), $rgd->rate_data_general['rdg_supply_price' . ($key_rate == 0 ? '2' : ($key_rate == 1 ? '1' : ($key_rate + 1)))]);
                $sheet->mergeCells('R' . ($current_row - 1) . ':T' . ($current_row - 1));
                $sheet->setCellValue('R' . ($current_row - 1), $rgd->rate_data_general['rdg_vat' . ($key_rate == 0 ? '2' : ($key_rate == 1 ? '1' : ($key_rate + 1)))]);
                $sheet->mergeCells('U' . ($current_row - 1) . ':W' . ($current_row - 1));
                $sheet->setCellValue('U' . ($current_row - 1), $rgd->rate_data_general['rdg_sum' . ($key_rate == 0 ? '2' : ($key_rate == 1 ? '1' : ($key_rate + 1)))]);
                $sheet->mergeCells('X' . ($current_row - 1) . ':Z' . ($current_row - 1));
            }
        }

        $sheet->setCellValue('B' . ($current_row), '');
        // $sheet->setCellValue('B'. ($current_row + 1), '1. 보세화물 서비스의 예상경비 청구서는 BL번호 단위로 발송됩니다.(단 분할인 경우 반출단위)');
        // $sheet->setCellValue('B'. ($current_row + 2), '2. 세금계산서 발행은 확정청구서와 함께 처리 됩니다.');
        $sheet->setCellValue('B' . ($current_row + 1), '1. 결제는 PC/Mobile에 접속하여서 결제하시면 되며, 월별 청구인 경우 매달 24일까지 결제가 되지 않으면 25일 등록 된 카드로 자동결제 됩니다.');
        $sheet->setCellValue('B' . ($current_row + 2), '2. 결제수단에 따라 수수료가 추가 청구 됩니다.(카드/카카오페이 2.9%, 실시간계좌이체 1.8% 등)');

        $issuer = Member::where('mb_no', $rgd->mb_no)->first();
        $company = Company::where('co_no', $issuer->co_no)->first();

        $sheet->getStyle('B' . ($current_row + 6) . ':Z' . ($current_row + 9))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
        $sheet->getStyle('B' . ($current_row + 6) . ':Z' . ($current_row + 9))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B' . ($current_row + 6) . ':Z' . ($current_row + 9))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->mergeCells('B' . ($current_row + 6) . ':Z' . ($current_row + 6));
        $sheet->setCellValue('B' . ($current_row + 6), $company->co_name);
        $sheet->mergeCells('B' . ($current_row + 7) . ':Z' . ($current_row + 7));
        $sheet->setCellValue('B' . ($current_row + 7), $company->co_address . ' ' . $company->co_address_detail);
        // $sheet->mergeCells('B'. ($current_row + 8). ':Z'. ($current_row + 8));
        // $sheet->setCellValue('B'. ($current_row + 8), $company->co_owner);
        $sheet->mergeCells('B' . ($current_row + 8) . ':Z' . ($current_row + 8));
        $sheet->setCellValue('B' . ($current_row + 8), $company->co_tel);
        $sheet->mergeCells('B' . ($current_row + 9) . ':Z' . ($current_row + 9));
        $sheet->setCellValue('B' . ($current_row + 9), $company->co_email);

        // $sheet->getDefaultRowDimension()->setRowHeight(24);

        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = 'storage/download/' . $user->mb_no . '/';
        } else {
            $path = 'storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }

        if ($rgd->service_korean_name == '유통가공' && !str_contains($rgd->rgd_bill_type, 'month') && $rgd->rgd_status4 == '예상경비청구서') {
            $name = 'distribution_est_casebill_';
        } else if ($rgd->service_korean_name == '유통가공' && str_contains($rgd->rgd_bill_type, 'month') && $rgd->rgd_status4 == '예상경비청구서') {
            $name = 'distribution_est_monthbill_';
        } else {
            $name = 'distribution_final_casebill_';
        }

        $mask = $path . $name . '*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . $name . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => '../' . $file_name_download,
            'message' => 'Download File',
        ], 200);
        ob_end_clean();
    }

    public function download_bonded_casebill_excel($rgd_no, Request $request)
    {
        Log::error($rgd_no);
        DB::beginTransaction();
        $user = Auth::user();
        $pathname = $request->header('pathname');
        $is_check_page = str_contains($pathname, 'check');

        $rgd = ReceivingGoodsDelivery::with(['rate_data_general', 'warehousing'])->where('rgd_no', $rgd_no)->first();
        $is_month_bill = str_contains($rgd->rgd_bill_type, 'month') ? '_monthly' : '';
        $is_final_bill = str_contains($rgd->rgd_bill_type, 'final');

        if ($user->mb_type == 'shop') {
            $company = $is_check_page ? $rgd->warehousing->company->co_parent : $rgd->warehousing->company;

            $rmd_no_bonded1 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded1' . $is_month_bill . ($is_final_bill ? '_final' : ($is_check_page ? '_spasys' : '_shop')))->first();
            $rmd_no_bonded2 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded2' . $is_month_bill . ($is_final_bill ? '_final' : ($is_check_page ? '_spasys' : '_shop')))->first();
            $rmd_no_bonded3 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded3' . $is_month_bill . ($is_final_bill ? '_final' : ($is_check_page ? '_spasys' : '_shop')))->first();
            $rmd_no_bonded4 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded4' . $is_month_bill . ($is_final_bill ? '_final' : ($is_check_page ? '_spasys' : '_shop')))->first();
            $rmd_no_bonded5 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded5' . $is_month_bill . ($is_final_bill ? '_final' : ($is_check_page ? '_spasys' : '_shop')))->first();
        } else if ($user->mb_type == 'spasys') {
            $company = $rgd->warehousing->company->co_parent;
            $rmd_no_bonded1 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded1' . $is_month_bill . ($is_final_bill ? '_final' : '_spasys'))->first();
            $rmd_no_bonded2 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded2' . $is_month_bill . ($is_final_bill ? '_final' : '_spasys'))->first();
            $rmd_no_bonded3 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded3' . $is_month_bill . ($is_final_bill ? '_final' : '_spasys'))->first();
            $rmd_no_bonded4 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded4' . $is_month_bill . ($is_final_bill ? '_final' : '_spasys'))->first();
            $rmd_no_bonded5 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded5' . $is_month_bill . ($is_final_bill ? '_final' : '_spasys'))->first();
        } else if ($user->mb_type == 'shipper') {
            $company = $rgd->warehousing->company;
            $rmd_no_bonded1 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded1' . $is_month_bill . ($is_final_bill ? '_final' : '_shop'))->first();
            $rmd_no_bonded2 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded2' . $is_month_bill . ($is_final_bill ? '_final' : '_shop'))->first();
            $rmd_no_bonded3 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded3' . $is_month_bill . ($is_final_bill ? '_final' : '_shop'))->first();
            $rmd_no_bonded4 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded4' . $is_month_bill . ($is_final_bill ? '_final' : '_shop'))->first();
            $rmd_no_bonded5 = RateMetaData::where('rgd_no', $rgd_no)->where('set_type', 'bonded5' . $is_month_bill . ($is_final_bill ? '_final' : '_shop'))->first();
        }

        $company->company_payment = CompanyPayment::where('co_no', $company->co_no)->first();

        $rate_data_bonded1 = $rate_data = RateData::where('rmd_no', isset($rmd_no_bonded1->rmd_no) ? $rmd_no_bonded1->rmd_no : 0)->where(function ($q) {
            $q->where('rd_cate_meta1', '유통가공')
                ->orWhere('rd_cate_meta1', '수입풀필먼트')
                ->orWhere('rd_cate_meta1', '보세화물');
        })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

        $rate_data_bonded2 = $rate_data = RateData::where('rmd_no', isset($rmd_no_bonded2->rmd_no) ? $rmd_no_bonded2->rmd_no : 0)->where(function ($q) {
            $q->where('rd_cate_meta1', '유통가공')
                ->orWhere('rd_cate_meta1', '수입풀필먼트')
                ->orWhere('rd_cate_meta1', '보세화물');
        })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

        $rate_data_bonded3 = $rate_data = RateData::where('rmd_no', isset($rmd_no_bonded3->rmd_no) ? $rmd_no_bonded3->rmd_no : 0)->where(function ($q) {
            $q->where('rd_cate_meta1', '유통가공')
                ->orWhere('rd_cate_meta1', '수입풀필먼트')
                ->orWhere('rd_cate_meta1', '보세화물');
        })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

        $rate_data_bonded4 = $rate_data = RateData::where('rmd_no', isset($rmd_no_bonded4->rmd_no) ? $rmd_no_bonded4->rmd_no : 0)->where(function ($q) {
            $q->where('rd_cate_meta1', '유통가공')
                ->orWhere('rd_cate_meta1', '수입풀필먼트')
                ->orWhere('rd_cate_meta1', '보세화물');
        })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

        $rate_data_bonded5 = $rate_data = RateData::where('rmd_no', isset($rmd_no_bonded5->rmd_no) ? $rmd_no_bonded5->rmd_no : 0)->where(function ($q) {
            $q->where('rd_cate_meta1', '유통가공')
                ->orWhere('rd_cate_meta1', '수입풀필먼트')
                ->orWhere('rd_cate_meta1', '보세화물');
        })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

        // return response()->json([
        //     'status' => $rate_data_bonded4,
        // ], 200);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $sheet = $spreadsheet->getActiveSheet(0);

        // $sheet->getProtection()->setSheet(true);
        $sheet->getDefaultColumnDimension()->setWidth(4.5);
        // $sheet->getDefaultRowDimension()->setRowHeight(24);
        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getStyle('A1:Z200')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:CT200')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->setTitle('보세화물 예상경비(건별,월별)');


        $sheet->mergeCells('B2:Z6');
        $sheet->getStyle('B2:Z6')->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->setCellValue('B2', $company->co_name);
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2')->getFont()->setSize(22)->setBold(true);

        $sheet->getStyle('Z8')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Z8', '사업자번호 : ' . $company->co_license);
        $sheet->getStyle('Z9')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Z9', '사업장 주소 : ' . $company->co_address . ' ' . $company->co_address_detail);
        $sheet->getStyle('Z10')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Z10', '수신자명 : ' . $company->co_owner . ' (' . $company->co_email . ')');

        $sheet->getStyle('B12:B17')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
        $sheet->getStyle('B12:B17')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->getStyle('B12:B17')->getFont()->setBold(true);
        $sheet->mergeCells('B12:Z12');
        $sheet->setCellValue('B12', ' ∙ 서   비  스 : 보세화물');
        $sheet->mergeCells('B13:Z13');
        $sheet->setCellValue('B13', ' ∙ H-BL  No : ' . $rgd->warehousing->import_expect->tie_h_bl);
        $sheet->mergeCells('B14:Z14');
        $sheet->setCellValue('B14', ' ∙ 청구서 No : ' . $rgd->rgd_status4 . ' ' . $rgd->rgd_settlement_number);
        $sheet->mergeCells('B15:Z15');
        $sheet->setCellValue('B15', ' ∙ 청구서 발행일 : ' . Carbon::createFromFormat('Y-m-d H:i:s', $rgd->created_at)->format('Y.m.d'));
        $sheet->mergeCells('B16:Z16');
        $sheet->setCellValue('B16', ' ∙ 예상 청구금액 : ' . (number_format($rgd->rate_data_general->rdg_sum7 + $rgd->rate_data_general->rdg_sum14)) . '원');
        $sheet->mergeCells('B17:Z17');
        $sheet->setCellValue('B17', ' ∙ 계좌  정보 : ㈜' . $company->company_payment->cp_bank_name . ' ' . $company->company_payment->cp_bank_number . ' (' . $company->company_payment->cp_card_name . ')');

        //GENERAL TABLE
        $sheet->getStyle('B19:Z19')->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
        $sheet->getStyle('B19')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->getStyle('B19')->getFont()->setBold(true);
        $sheet->mergeCells('B19:Z19');
        $sheet->setCellValue('B19', '  ∙ 항목별 청구 금액');

        $headers = ['공급가', '부가세', '합계', '공급가', '부가세', '합계'];
        $col_start = ['F', 'I', 'L', 'O', 'R', 'U'];
        $col_end = ['H', 'K', 'N', 'Q', 'T', 'W'];

        foreach ($headers as $key => $header) {
            $sheet->mergeCells($col_start[$key] . '21' . ':' . $col_end[$key] . '21');
            $sheet->getStyle($col_start[$key] . '21')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue($col_start[$key] . '21', $header);
        }

        $categories = ['BLP센터비용', '관세사비용', '포워더비용', '국내운송비', '요건비용', '합계'];

        $current_row = 22;
        $count_row = 0;

        foreach ($categories as $key => $category) {
            if ($rgd->rate_data_general['rdg_sum' . ($key  == 5 ? ($key + 2) : ($key + 1))] != 0 || $rgd->rate_data_general['rdg_sum' . ($key  == 5 ? ($key + 9) : ($key + 8))] != 0) {
                $sheet->mergeCells('B' . ($current_row + $count_row) . ':E' . ($current_row + $count_row));
                $sheet->setCellValue('B' . ($current_row + $count_row), $category);
                $sheet->mergeCells('F' . ($current_row + $count_row) . ':H' . ($current_row + $count_row));
                $sheet->setCellValue('F' . ($current_row + $count_row), $rgd->rate_data_general['rdg_supply_price' . ($key  == 5 ? ($key + 2) : ($key + 1))]);
                $sheet->mergeCells('I' . ($current_row + $count_row) . ':K' . ($current_row + $count_row));
                $sheet->setCellValue('I' . ($current_row + $count_row), $rgd->rate_data_general['rdg_vat' . ($key  == 5 ? ($key + 2) : ($key + 1))]);
                $sheet->mergeCells('L' . ($current_row + $count_row) . ':N' . ($current_row + $count_row));
                $sheet->setCellValue('L' . ($current_row + $count_row), $rgd->rate_data_general['rdg_sum' . ($key  == 5 ? ($key + 2) : ($key + 1))]);
                $sheet->mergeCells('O' . ($current_row + $count_row) . ':Q' . ($current_row + $count_row));
                $sheet->setCellValue('O' . ($current_row + $count_row), $rgd->rate_data_general['rdg_supply_price' . ($key  == 5 ? ($key + 9) : ($key + 8))]);
                $sheet->mergeCells('R' . ($current_row + $count_row) . ':T' . ($current_row + $count_row));
                $sheet->setCellValue('R' . ($current_row + $count_row), $rgd->rate_data_general['rdg_vat' . ($key  == 5 ? ($key + 9) : ($key + 8))]);
                $sheet->mergeCells('U' . ($current_row + $count_row) . ':W' . ($current_row + $count_row));
                $sheet->setCellValue('U' . ($current_row + $count_row), $rgd->rate_data_general['rdg_sum' . ($key  == 5 ? ($key + 9) : ($key + 8))]);
                $sheet->mergeCells('X' . ($current_row + $count_row) . ':Z' . ($current_row + $count_row));
                $sheet->setCellValue('X' . ($current_row + $count_row), $rgd->rate_data_general['rdg_etc' . ($key  == 5 ? ($key + 9) : ($key + 8))]);

                $count_row += 1;
            }
        }


        $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row - 1 + $count_row))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
        $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row - 1 + $count_row))->getFont()->setBold(true);
        $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row - 1 + $count_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        //FORMAT NUMBER
        $sheet->getStyle('F' . ($current_row - 2) . ':W' . ($current_row - 1 + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');

        $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 1 + $count_row))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2 + 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
        $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2 + 1))->getFont()->setBold(true);
        $sheet->mergeCells('B' . ($current_row - 2) . ':E' . ($current_row - 2 + 1));
        $sheet->getStyle('B' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('B' . ($current_row - 2), '항목');

        $sheet->mergeCells('F' . ($current_row - 2) . ':N' . ($current_row - 2));
        $sheet->getStyle('F' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('F' . ($current_row - 2), '세금계산서 발행');

        $sheet->mergeCells('O' . ($current_row - 2) . ':W' . ($current_row - 2));
        $sheet->getStyle('O' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('O' . ($current_row - 2), '세금계산서 미발행');

        $sheet->mergeCells('X' . ($current_row - 2) . ':Z' . ($current_row - 1));
        $sheet->getStyle('X' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('X' . ($current_row - 2), '비고');

        $current_row += $count_row;

        //BONDED1
        if ($rgd->rate_data_general['rdg_sum1'] > 0) {
            $sheet->getStyle('B' . $current_row . ':Z' . $current_row)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
            $sheet->getStyle('B' . $current_row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
            $sheet->getStyle('B' . $current_row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $current_row . ':Z' . $current_row);
            $sheet->setCellValue('B' . $current_row, ' ∙ BLP센터비용');

            $current_row += 3;

            $sheet->mergeCells('B' . ($current_row) . ':E' . ($current_row));

            $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row))->getFont()->setBold(true);
            $sheet->setCellValue('B' . ($current_row), 'BLP센터비용');

            $sheet->mergeCells('F' . ($current_row) . ':H' . ($current_row));
            $sheet->setCellValue('F' . ($current_row), $rgd->rate_data_general['rdg_supply_price1']);
            $sheet->mergeCells('I' . ($current_row) . ':K' . ($current_row));
            $sheet->setCellValue('I' . ($current_row), $rgd->rate_data_general['rdg_vat1']);
            $sheet->mergeCells('L' . ($current_row) . ':N' . ($current_row));
            $sheet->setCellValue('L' . ($current_row), $rgd->rate_data_general['rdg_sum1']);
            $sheet->mergeCells('O' . ($current_row) . ':Q' . ($current_row));
            $sheet->setCellValue('O' . ($current_row), '');
            $sheet->mergeCells('R' . ($current_row) . ':T' . ($current_row));
            $sheet->setCellValue('R' . ($current_row), '');
            $sheet->mergeCells('U' . ($current_row) . ':W' . ($current_row));
            $sheet->setCellValue('U' . ($current_row), '');
            $sheet->mergeCells('X' . ($current_row) . ':Z' . ($current_row));
            $sheet->setCellValue('X' . ($current_row), '');


            $count_row = 0;
            $current_row += 1;

            $count_row_bonded1 = 0;
            $current_row_bonded1 = $current_row;

            $rd_cate1 = [];
            $rd_sum = [];
            foreach ($rate_data_bonded1 as $key => $rate_data) {
                if (!in_array($rate_data['rd_cate1'], $rd_cate1)) {
                    $rd_cate1[] = $rate_data['rd_cate1'];
                    $rd_sum[] = $rate_data_bonded1[$key + 1]['rd_data4'];
                }
            }

            foreach ($rate_data_bonded1 as $key => $rate_data) {

                if ($rate_data['rd_cate1'] == '하역비용') {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_bonded1[$key - 1]['rd_cate1'])) {
                        $sheet->setCellValue('B' . ($current_row_bonded1 + $count_row_bonded1), '하역비용');
                        $sheet->getStyle('F' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $count_row_bonded1 = 0;
                    } else if ($rate_data['rd_data4'] > 0) {

                        $sheet->mergeCells('C' . ($current_row_bonded1 + $count_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('C' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_bonded1 + $count_row_bonded1) . ':H' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('F' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_bonded1 + $count_row_bonded1) . ':K' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('I' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_bonded1 + $count_row_bonded1) . ':N' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('L' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_bonded1 + $count_row_bonded1) . ':Q' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('O' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_bonded1 + $count_row_bonded1) . ':T' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('R' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_bonded1 + $count_row_bonded1) . ':W' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('U' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('X' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data8']);

                        $count_row_bonded1 += 1;
                        $count_row += 1;
                    }
                } else if ($rate_data['rd_cate1'] == '센터 작업료') {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_bonded1[$key - 1]['rd_cate1'])) {
                        $sheet->getStyle('F' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->mergeCells('B' . ($current_row_bonded1) . ':B' . ($current_row_bonded1 + $count_row_bonded1 - 1));
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFont()->setBold(true);
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($current_row_bonded1 + $count_row_bonded1), '센터 작업료');
                        $current_row_bonded1 = $current_row_bonded1 + $count_row_bonded1;
                        $count_row_bonded1 = 0;
                        $sheet->getStyle('F' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    } else if ($rate_data['rd_data4'] > 0 || (($rate_data['rd_cate2'] == '할인율') && $rd_sum[1] > 0)) {


                        $sheet->mergeCells('C' . ($current_row_bonded1 + $count_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('C' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_bonded1 + $count_row_bonded1) . ':H' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('F' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_bonded1 + $count_row_bonded1) . ':K' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('I' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_bonded1 + $count_row_bonded1) . ':N' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('L' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_bonded1 + $count_row_bonded1) . ':Q' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('O' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_bonded1 + $count_row_bonded1) . ':T' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('R' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_bonded1 + $count_row_bonded1) . ':W' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('U' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('X' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data8']);

                        $count_row_bonded1 += 1;
                        $count_row += 1;
                    }
                } else if ($rate_data['rd_cate1'] == '기타 비용') {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_bonded1[$key - 1]['rd_cate1'])) {
                        $sheet->getStyle('F' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->mergeCells('B' . ($current_row_bonded1) . ':B' . ($current_row_bonded1 + $count_row_bonded1 - 1));
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFont()->setBold(true);
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($current_row_bonded1 + $count_row_bonded1), '기타 비용');
                        $current_row_bonded1 = $current_row_bonded1 + $count_row_bonded1;
                        $count_row_bonded1 = 0;
                    } else if ($rate_data['rd_data4'] > 0) {

                        $sheet->mergeCells('C' . ($current_row_bonded1 + $count_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('C' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_bonded1 + $count_row_bonded1) . ':H' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('F' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_bonded1 + $count_row_bonded1) . ':K' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('I' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_bonded1 + $count_row_bonded1) . ':N' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('L' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_bonded1 + $count_row_bonded1) . ':Q' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('O' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_bonded1 + $count_row_bonded1) . ':T' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('R' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_bonded1 + $count_row_bonded1) . ':W' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('U' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('X' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data8']);

                        $count_row_bonded1 += 1;
                        $count_row += 1;
                    }
                }

                if (count($rate_data_bonded1) == $key + 1) {
                    $sheet->mergeCells('B' . ($current_row_bonded1) . ':B' . ($current_row_bonded1 + $count_row_bonded1 - 1));
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $current_row_bonded1 = $current_row_bonded1 + $count_row_bonded1;
                    $count_row_bonded1 = 0;
                }
            }
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 2 + 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 3 + 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 3 + 1))->getFont()->setBold(true);
            $sheet->mergeCells('B' . ($current_row - 3) . ':E' . ($current_row - 3 + 1));
            $sheet->getStyle('B' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B' . ($current_row - 3), '항목');

            $sheet->mergeCells('F' . ($current_row - 3) . ':N' . ($current_row - 3));
            $sheet->getStyle('F' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('F' . ($current_row - 3), '세금계산서 발행');

            $sheet->mergeCells('O' . ($current_row - 3) . ':W' . ($current_row - 3));
            $sheet->getStyle('O' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('O' . ($current_row - 3), '세금계산서 미발행');

            $sheet->mergeCells('X' . ($current_row - 3) . ':Z' . ($current_row - 2));
            $sheet->getStyle('X' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('X' . ($current_row - 3), '비고');

            foreach ($headers as $key => $header) {
                $sheet->mergeCells($col_start[$key] . ($current_row - 2) . ':' . $col_end[$key] . ($current_row - 2));
                $sheet->getStyle($col_start[$key] . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . ($current_row - 2), $header);
            }

            //FORMAT NUMBER
            $sheet->getStyle('F' . ($current_row - 1) . ':W' . ($current_row - 1 + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');

            $current_row += $count_row;
        }
        //END BONDED1

        //BONDED2
        if ($rgd->rate_data_general['rdg_sum2'] > 0 || $rgd->rate_data_general['rdg_sum9'] > 0) {
            $sheet->getStyle('B' . $current_row . ':Z' . $current_row)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
            $sheet->getStyle('B' . $current_row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
            $sheet->getStyle('B' . $current_row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $current_row . ':Z' . $current_row);
            $sheet->setCellValue('B' . $current_row, ' ∙ 관세사비용');

            $current_row += 3;

            $sheet->mergeCells('B' . ($current_row) . ':E' . ($current_row));

            $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row))->getFont()->setBold(true);
            $sheet->setCellValue('B' . ($current_row), '관세사비용');

            $sheet->mergeCells('F' . ($current_row) . ':H' . ($current_row));
            $sheet->setCellValue('F' . ($current_row), $rgd->rate_data_general['rdg_supply_price2']);
            $sheet->mergeCells('I' . ($current_row) . ':K' . ($current_row));
            $sheet->setCellValue('I' . ($current_row), $rgd->rate_data_general['rdg_vat2']);
            $sheet->mergeCells('L' . ($current_row) . ':N' . ($current_row));
            $sheet->setCellValue('L' . ($current_row), $rgd->rate_data_general['rdg_sum2']);
            $sheet->mergeCells('O' . ($current_row) . ':Q' . ($current_row));
            $sheet->setCellValue('O' . ($current_row), $rgd->rate_data_general['rdg_supply_price9']);
            $sheet->mergeCells('R' . ($current_row) . ':T' . ($current_row));
            $sheet->setCellValue('R' . ($current_row), $rgd->rate_data_general['rdg_vat9']);
            $sheet->mergeCells('U' . ($current_row) . ':W' . ($current_row));
            $sheet->setCellValue('U' . ($current_row), $rgd->rate_data_general['rdg_sum9']);
            $sheet->mergeCells('X' . ($current_row) . ':Z' . ($current_row));
            $sheet->setCellValue('X' . ($current_row), $rgd->rate_data_general['rdg_etc9']);


            $count_row = 0;
            $current_row += 1;

            $count_row_bonded1 = 0;
            $current_row_bonded1 = $current_row;

            $rd_cate1 = [];
            $rd_sum = [];
            foreach ($rate_data_bonded2 as $key => $rate_data) {
                if (!in_array($rate_data['rd_cate1'], $rd_cate1)) {
                    $rd_cate1[] = $rate_data['rd_cate1'];
                    $rd_sum[] = $rate_data_bonded2[$key + 1]['rd_data4'];
                }
            }

            foreach ($rate_data_bonded2 as $key => $rate_data) {



                if ($rate_data == $rd_cate1[0]) {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_bonded2[$key - 1]['rd_cate1'])) {
                        $sheet->getStyle('F' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->setCellValue('B' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate1']);
                        $count_row_bonded1 = 0;
                    } else if ($rate_data['rd_data4'] > 0 || $rate_data['rd_data7'] > 0) {

                        $sheet->mergeCells('C' . ($current_row_bonded1 + $count_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('C' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_bonded1 + $count_row_bonded1) . ':H' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('F' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_bonded1 + $count_row_bonded1) . ':K' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('I' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_bonded1 + $count_row_bonded1) . ':N' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('L' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_bonded1 + $count_row_bonded1) . ':Q' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('O' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_bonded1 + $count_row_bonded1) . ':T' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('R' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_bonded1 + $count_row_bonded1) . ':W' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('U' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('X' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data8']);

                        $count_row_bonded1 += 1;
                        $count_row += 1;
                    }
                } else {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_bonded2[$key - 1]['rd_cate1'])) {
                        $sheet->getStyle('F' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->mergeCells('B' . ($current_row_bonded1) . ':B' . ($current_row_bonded1 + $count_row_bonded1 - 1));
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFont()->setBold(true);
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate1']);
                        $current_row_bonded1 = $current_row_bonded1 + $count_row_bonded1;
                        $count_row_bonded1 = 0;
                    } else if ($rate_data['rd_data4'] > 0 || $rate_data['rd_data7'] > 0) {


                        $sheet->mergeCells('C' . ($current_row_bonded1 + $count_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('C' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_bonded1 + $count_row_bonded1) . ':H' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('F' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_bonded1 + $count_row_bonded1) . ':K' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('I' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_bonded1 + $count_row_bonded1) . ':N' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('L' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_bonded1 + $count_row_bonded1) . ':Q' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('O' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_bonded1 + $count_row_bonded1) . ':T' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('R' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_bonded1 + $count_row_bonded1) . ':W' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('U' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('X' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data8']);

                        $count_row_bonded1 += 1;
                        $count_row += 1;
                    }
                }

                if (count($rate_data_bonded2) == $key + 1) {
                    $sheet->mergeCells('B' . ($current_row_bonded1) . ':B' . ($current_row_bonded1 + $count_row_bonded1 - 1));
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $current_row_bonded1 = $current_row_bonded1 + $count_row_bonded1;
                    $count_row_bonded1 = 0;
                }
            }
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 2 + 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 3 + 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 3 + 1))->getFont()->setBold(true);
            $sheet->mergeCells('B' . ($current_row - 3) . ':E' . ($current_row - 3 + 1));
            $sheet->getStyle('B' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B' . ($current_row - 3), '항목');

            $sheet->mergeCells('F' . ($current_row - 3) . ':N' . ($current_row - 3));
            $sheet->getStyle('F' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('F' . ($current_row - 3), '세금계산서 발행');

            $sheet->mergeCells('O' . ($current_row - 3) . ':W' . ($current_row - 3));
            $sheet->getStyle('O' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('O' . ($current_row - 3), '세금계산서 미발행');

            $sheet->mergeCells('X' . ($current_row - 3) . ':Z' . ($current_row - 2));
            $sheet->getStyle('X' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('X' . ($current_row - 3), '비고');

            foreach ($headers as $key => $header) {
                $sheet->mergeCells($col_start[$key] . ($current_row - 2) . ':' . $col_end[$key] . ($current_row - 2));
                $sheet->getStyle($col_start[$key] . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . ($current_row - 2), $header);
            }

            //FORMAT NUMBER
            $sheet->getStyle('F' . ($current_row - 1) . ':W' . ($current_row - 1 + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');
            $current_row += $count_row;
        }
        //END BONDED2

        //BONDED3
        if ($rgd->rate_data_general['rdg_sum3'] > 0 || $rgd->rate_data_general['rdg_sum10'] > 0) {
            $sheet->getStyle('B' . $current_row . ':Z' . $current_row)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
            $sheet->getStyle('B' . $current_row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
            $sheet->getStyle('B' . $current_row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $current_row . ':Z' . $current_row);
            $sheet->setCellValue('B' . $current_row, ' ∙ 포워더비용');

            $current_row += 3;

            $sheet->mergeCells('B' . ($current_row) . ':E' . ($current_row));

            $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row))->getFont()->setBold(true);
            $sheet->setCellValue('B' . ($current_row), '포워더비용');

            $sheet->mergeCells('F' . ($current_row) . ':H' . ($current_row));
            $sheet->setCellValue('F' . ($current_row), $rgd->rate_data_general['rdg_supply_price3']);
            $sheet->mergeCells('I' . ($current_row) . ':K' . ($current_row));
            $sheet->setCellValue('I' . ($current_row), $rgd->rate_data_general['rdg_vat3']);
            $sheet->mergeCells('L' . ($current_row) . ':N' . ($current_row));
            $sheet->setCellValue('L' . ($current_row), $rgd->rate_data_general['rdg_sum3']);
            $sheet->mergeCells('O' . ($current_row) . ':Q' . ($current_row));
            $sheet->setCellValue('O' . ($current_row), $rgd->rate_data_general['rdg_supply_price10']);
            $sheet->mergeCells('R' . ($current_row) . ':T' . ($current_row));
            $sheet->setCellValue('R' . ($current_row), $rgd->rate_data_general['rdg_vat10']);
            $sheet->mergeCells('U' . ($current_row) . ':W' . ($current_row));
            $sheet->setCellValue('U' . ($current_row), $rgd->rate_data_general['rdg_sum10']);
            $sheet->mergeCells('X' . ($current_row) . ':Z' . ($current_row));
            $sheet->setCellValue('X' . ($current_row), $rgd->rate_data_general['rdg_etc10']);


            $count_row = 0;
            $current_row += 1;

            $count_row_bonded1 = 0;
            $current_row_bonded1 = $current_row;

            $rd_cate1 = [];
            $rd_sum = [];
            foreach ($rate_data_bonded3 as $key => $rate_data) {
                if (!in_array($rate_data['rd_cate1'], $rd_cate1)) {
                    $rd_cate1[] = $rate_data['rd_cate1'];
                    $rd_sum[] = $rate_data_bonded3[$key + 1]['rd_data4'];
                }
            }

            foreach ($rate_data_bonded3 as $key => $rate_data) {



                if ($rate_data == $rd_cate1[0]) {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_bonded3[$key - 1]['rd_cate1'])) {
                        $sheet->getStyle('F' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->setCellValue('B' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate1']);
                        $count_row_bonded1 = 0;
                    } else if ($rate_data['rd_data4'] > 0 || $rate_data['rd_data7'] > 0) {

                        $sheet->mergeCells('C' . ($current_row_bonded1 + $count_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('C' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_bonded1 + $count_row_bonded1) . ':H' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('F' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_bonded1 + $count_row_bonded1) . ':K' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('I' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_bonded1 + $count_row_bonded1) . ':N' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('L' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_bonded1 + $count_row_bonded1) . ':Q' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('O' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_bonded1 + $count_row_bonded1) . ':T' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('R' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_bonded1 + $count_row_bonded1) . ':W' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('U' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('X' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data8']);

                        $count_row_bonded1 += 1;
                        $count_row += 1;
                    }
                } else {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_bonded3[$key - 1]['rd_cate1'])) {
                        $sheet->getStyle('F' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->mergeCells('B' . ($current_row_bonded1) . ':B' . ($current_row_bonded1 + $count_row_bonded1 - 1));
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFont()->setBold(true);
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate1']);
                        $current_row_bonded1 = $current_row_bonded1 + $count_row_bonded1;
                        $count_row_bonded1 = 0;
                    } else if ($rate_data['rd_data4'] > 0 || $rate_data['rd_data7'] > 0) {


                        $sheet->mergeCells('C' . ($current_row_bonded1 + $count_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('C' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_bonded1 + $count_row_bonded1) . ':H' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('F' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_bonded1 + $count_row_bonded1) . ':K' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('I' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_bonded1 + $count_row_bonded1) . ':N' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('L' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_bonded1 + $count_row_bonded1) . ':Q' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('O' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_bonded1 + $count_row_bonded1) . ':T' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('R' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_bonded1 + $count_row_bonded1) . ':W' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('U' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('X' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data8']);

                        $count_row_bonded1 += 1;
                        $count_row += 1;
                    }
                }

                if (count($rate_data_bonded3) == $key + 1) {
                    $sheet->mergeCells('B' . ($current_row_bonded1) . ':B' . ($current_row_bonded1 + $count_row_bonded1 - 1));
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $current_row_bonded1 = $current_row_bonded1 + $count_row_bonded1;
                    $count_row_bonded1 = 0;
                }
            }
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 2 + 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 3 + 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 3 + 1))->getFont()->setBold(true);
            $sheet->mergeCells('B' . ($current_row - 3) . ':E' . ($current_row - 3 + 1));
            $sheet->getStyle('B' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B' . ($current_row - 3), '항목');

            $sheet->mergeCells('F' . ($current_row - 3) . ':N' . ($current_row - 3));
            $sheet->getStyle('F' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('F' . ($current_row - 3), '세금계산서 발행');

            $sheet->mergeCells('O' . ($current_row - 3) . ':W' . ($current_row - 3));
            $sheet->getStyle('O' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('O' . ($current_row - 3), '세금계산서 미발행');

            $sheet->mergeCells('X' . ($current_row - 3) . ':Z' . ($current_row - 2));
            $sheet->getStyle('X' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('X' . ($current_row - 3), '비고');

            foreach ($headers as $key => $header) {
                $sheet->mergeCells($col_start[$key] . ($current_row - 2) . ':' . $col_end[$key] . ($current_row - 2));
                $sheet->getStyle($col_start[$key] . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . ($current_row - 2), $header);
            }
            //FORMAT NUMBER
            $sheet->getStyle('F' . ($current_row - 1) . ':W' . ($current_row - 1 + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');
            $current_row += $count_row;
        }
        //END BONDED3

        //BONDED4
        if ($rgd->rate_data_general['rdg_sum4'] > 0 || $rgd->rate_data_general['rdg_sum11'] > 0) {
            $sheet->getStyle('B' . $current_row . ':Z' . $current_row)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
            $sheet->getStyle('B' . $current_row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
            $sheet->getStyle('B' . $current_row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $current_row . ':Z' . $current_row);
            $sheet->setCellValue('B' . $current_row, ' ∙ 국내운송비');

            $current_row += 3;

            $sheet->mergeCells('B' . ($current_row) . ':E' . ($current_row));

            $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row))->getFont()->setBold(true);
            $sheet->setCellValue('B' . ($current_row), '국내운송비');

            $sheet->mergeCells('F' . ($current_row) . ':H' . ($current_row));
            $sheet->setCellValue('F' . ($current_row), $rgd->rate_data_general['rdg_supply_price4']);
            $sheet->mergeCells('I' . ($current_row) . ':K' . ($current_row));
            $sheet->setCellValue('I' . ($current_row), $rgd->rate_data_general['rdg_vat4']);
            $sheet->mergeCells('L' . ($current_row) . ':N' . ($current_row));
            $sheet->setCellValue('L' . ($current_row), $rgd->rate_data_general['rdg_sum4']);
            $sheet->mergeCells('O' . ($current_row) . ':Q' . ($current_row));
            $sheet->setCellValue('O' . ($current_row), $rgd->rate_data_general['rdg_supply_price11']);
            $sheet->mergeCells('R' . ($current_row) . ':T' . ($current_row));
            $sheet->setCellValue('R' . ($current_row), $rgd->rate_data_general['rdg_vat11']);
            $sheet->mergeCells('U' . ($current_row) . ':W' . ($current_row));
            $sheet->setCellValue('U' . ($current_row), $rgd->rate_data_general['rdg_sum11']);
            $sheet->mergeCells('X' . ($current_row) . ':Z' . ($current_row));
            $sheet->setCellValue('X' . ($current_row), $rgd->rate_data_general['rdg_etc11']);


            $count_row = 0;
            $current_row += 1;

            $count_row_bonded1 = 0;
            $current_row_bonded1 = $current_row;

            $rd_cate1 = [];
            $rd_sum = [];
            foreach ($rate_data_bonded4 as $key => $rate_data) {
                if (!in_array($rate_data['rd_cate1'], $rd_cate1)) {
                    $rd_cate1[] = $rate_data['rd_cate1'];
                    $rd_sum[] = $rate_data_bonded4[$key + 1]['rd_data4'];
                }
            }

            foreach ($rate_data_bonded4 as $key => $rate_data) {



                if ($rate_data == $rd_cate1[0]) {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_bonded4[$key - 1]['rd_cate1'])) {
                        $sheet->getStyle('F' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->setCellValue('B' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate1']);
                        $count_row_bonded1 = 0;
                    } else if ($rate_data['rd_data4'] > 0 || $rate_data['rd_data7'] > 0) {

                        $sheet->mergeCells('C' . ($current_row_bonded1 + $count_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('C' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_bonded1 + $count_row_bonded1) . ':H' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('F' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_bonded1 + $count_row_bonded1) . ':K' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('I' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_bonded1 + $count_row_bonded1) . ':N' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('L' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_bonded1 + $count_row_bonded1) . ':Q' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('O' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_bonded1 + $count_row_bonded1) . ':T' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('R' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_bonded1 + $count_row_bonded1) . ':W' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('U' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('X' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data8']);

                        $count_row_bonded1 += 1;
                        $count_row += 1;
                    }
                } else {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_bonded4[$key - 1]['rd_cate1'])) {
                        $sheet->getStyle('F' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->mergeCells('B' . ($current_row_bonded1) . ':B' . ($current_row_bonded1 + $count_row_bonded1 - 1));
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFont()->setBold(true);
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate1']);
                        $current_row_bonded1 = $current_row_bonded1 + $count_row_bonded1;
                        $count_row_bonded1 = 0;
                    } else if ($rate_data['rd_data4'] > 0 || $rate_data['rd_data7'] > 0) {


                        $sheet->mergeCells('C' . ($current_row_bonded1 + $count_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('C' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_bonded1 + $count_row_bonded1) . ':H' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('F' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_bonded1 + $count_row_bonded1) . ':K' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('I' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_bonded1 + $count_row_bonded1) . ':N' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('L' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_bonded1 + $count_row_bonded1) . ':Q' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('O' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_bonded1 + $count_row_bonded1) . ':T' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('R' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_bonded1 + $count_row_bonded1) . ':W' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('U' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('X' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data8']);

                        $count_row_bonded1 += 1;
                        $count_row += 1;
                    }
                }

                if (count($rate_data_bonded4) == $key + 1) {
                    $sheet->mergeCells('B' . ($current_row_bonded1) . ':B' . ($current_row_bonded1 + $count_row_bonded1 - 1));
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $current_row_bonded1 = $current_row_bonded1 + $count_row_bonded1;
                    $count_row_bonded1 = 0;
                }
            }
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 2 + 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 3 + 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 3 + 1))->getFont()->setBold(true);
            $sheet->mergeCells('B' . ($current_row - 3) . ':E' . ($current_row - 3 + 1));
            $sheet->getStyle('B' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B' . ($current_row - 3), '항목');

            $sheet->mergeCells('F' . ($current_row - 3) . ':N' . ($current_row - 3));
            $sheet->getStyle('F' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('F' . ($current_row - 3), '세금계산서 발행');

            $sheet->mergeCells('O' . ($current_row - 3) . ':W' . ($current_row - 3));
            $sheet->getStyle('O' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('O' . ($current_row - 3), '세금계산서 미발행');

            $sheet->mergeCells('X' . ($current_row - 3) . ':Z' . ($current_row - 2));
            $sheet->getStyle('X' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('X' . ($current_row - 3), '비고');

            foreach ($headers as $key => $header) {
                $sheet->mergeCells($col_start[$key] . ($current_row - 2) . ':' . $col_end[$key] . ($current_row - 2));
                $sheet->getStyle($col_start[$key] . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . ($current_row - 2), $header);
            }
            //FORMAT NUMBER
            $sheet->getStyle('F' . ($current_row - 1) . ':W' . ($current_row - 1 + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');
            $current_row += $count_row;
        }
        //END BONDED4

        //BONDED5
        if ($rgd->rate_data_general['rdg_sum5'] > 0 || $rgd->rate_data_general['rdg_sum12'] > 0) {
            $sheet->getStyle('B' . $current_row . ':Z' . $current_row)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
            $sheet->getStyle('B' . $current_row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
            $sheet->getStyle('B' . $current_row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $current_row . ':Z' . $current_row);
            $sheet->setCellValue('B' . $current_row, '  ∙ 요건비용');

            $current_row += 3;

            $sheet->mergeCells('B' . ($current_row) . ':E' . ($current_row));

            $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row))->getFont()->setBold(true);
            $sheet->setCellValue('B' . ($current_row), '요건비용');

            $sheet->mergeCells('F' . ($current_row) . ':H' . ($current_row));
            $sheet->setCellValue('F' . ($current_row), $rgd->rate_data_general['rdg_supply_price5']);
            $sheet->mergeCells('I' . ($current_row) . ':K' . ($current_row));
            $sheet->setCellValue('I' . ($current_row), $rgd->rate_data_general['rdg_vat5']);
            $sheet->mergeCells('L' . ($current_row) . ':N' . ($current_row));
            $sheet->setCellValue('L' . ($current_row), $rgd->rate_data_general['rdg_sum5']);
            $sheet->mergeCells('O' . ($current_row) . ':Q' . ($current_row));
            $sheet->setCellValue('O' . ($current_row), $rgd->rate_data_general['rdg_supply_price12']);
            $sheet->mergeCells('R' . ($current_row) . ':T' . ($current_row));
            $sheet->setCellValue('R' . ($current_row), $rgd->rate_data_general['rdg_vat12']);
            $sheet->mergeCells('U' . ($current_row) . ':W' . ($current_row));
            $sheet->setCellValue('U' . ($current_row), $rgd->rate_data_general['rdg_sum12']);
            $sheet->mergeCells('X' . ($current_row) . ':Z' . ($current_row));
            $sheet->setCellValue('X' . ($current_row), $rgd->rate_data_general['rdg_etc12']);


            $count_row = 0;
            $current_row += 1;

            $count_row_bonded1 = 0;
            $current_row_bonded1 = $current_row;

            $rd_cate1 = [];
            $rd_sum = [];
            foreach ($rate_data_bonded5 as $key => $rate_data) {
                if (!in_array($rate_data['rd_cate1'], $rd_cate1)) {
                    $rd_cate1[] = $rate_data['rd_cate1'];
                    $rd_sum[] = $rate_data_bonded5[$key + 1]['rd_data4'];
                }
            }

            foreach ($rate_data_bonded5 as $key => $rate_data) {



                if ($rate_data == $rd_cate1[0]) {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_bonded5[$key - 1]['rd_cate1'])) {
                        $sheet->getStyle('F' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->setCellValue('B' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate1']);
                        $count_row_bonded1 = 0;
                    } else if ($rate_data['rd_data4'] > 0 || $rate_data['rd_data7'] > 0) {

                        $sheet->mergeCells('C' . ($current_row_bonded1 + $count_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('C' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_bonded1 + $count_row_bonded1) . ':H' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('F' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_bonded1 + $count_row_bonded1) . ':K' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('I' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_bonded1 + $count_row_bonded1) . ':N' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('L' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_bonded1 + $count_row_bonded1) . ':Q' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('O' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_bonded1 + $count_row_bonded1) . ':T' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('R' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_bonded1 + $count_row_bonded1) . ':W' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('U' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('X' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data8']);

                        $count_row_bonded1 += 1;
                        $count_row += 1;
                    }
                } else {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_bonded5[$key - 1]['rd_cate1'])) {
                        $sheet->getStyle('F' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->mergeCells('B' . ($current_row_bonded1) . ':B' . ($current_row_bonded1 + $count_row_bonded1 - 1));
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFont()->setBold(true);
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate1']);
                        $current_row_bonded1 = $current_row_bonded1 + $count_row_bonded1;
                        $count_row_bonded1 = 0;
                    } else if ($rate_data['rd_data4'] > 0 || $rate_data['rd_data7'] > 0) {


                        $sheet->mergeCells('C' . ($current_row_bonded1 + $count_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('C' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_bonded1 + $count_row_bonded1) . ':H' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('F' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_bonded1 + $count_row_bonded1) . ':K' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('I' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_bonded1 + $count_row_bonded1) . ':N' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('L' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_bonded1 + $count_row_bonded1) . ':Q' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('O' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_bonded1 + $count_row_bonded1) . ':T' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('R' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_bonded1 + $count_row_bonded1) . ':W' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('U' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_bonded1 + $count_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1));
                        $sheet->setCellValue('X' . ($current_row_bonded1 + $count_row_bonded1), $rate_data['rd_data8']);

                        $count_row_bonded1 += 1;
                        $count_row += 1;
                    }
                }

                if (count($rate_data_bonded5) == $key + 1) {
                    $sheet->mergeCells('B' . ($current_row_bonded1) . ':B' . ($current_row_bonded1 + $count_row_bonded1 - 1));
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':E' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row_bonded1) . ':Z' . ($current_row_bonded1 + $count_row_bonded1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $current_row_bonded1 = $current_row_bonded1 + $count_row_bonded1;
                    $count_row_bonded1 = 0;
                }
            }
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 2 + 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 3 + 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 3) . ':Z' . ($current_row - 3 + 1))->getFont()->setBold(true);
            $sheet->mergeCells('B' . ($current_row - 3) . ':E' . ($current_row - 3 + 1));
            $sheet->getStyle('B' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B' . ($current_row - 3), '항목');

            $sheet->mergeCells('F' . ($current_row - 3) . ':N' . ($current_row - 3));
            $sheet->getStyle('F' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('F' . ($current_row - 3), '세금계산서 발행');

            $sheet->mergeCells('O' . ($current_row - 3) . ':W' . ($current_row - 3));
            $sheet->getStyle('O' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('O' . ($current_row - 3), '세금계산서 미발행');

            $sheet->mergeCells('X' . ($current_row - 3) . ':Z' . ($current_row - 2));
            $sheet->getStyle('X' . ($current_row - 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('X' . ($current_row - 3), '비고');

            foreach ($headers as $key => $header) {
                $sheet->mergeCells($col_start[$key] . ($current_row - 2) . ':' . $col_end[$key] . ($current_row - 2));
                $sheet->getStyle($col_start[$key] . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . ($current_row - 2), $header);
            }
            //FORMAT NUMBER
            $sheet->getStyle('F' . ($current_row - 1) . ':W' . ($current_row - 1 + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');
            $current_row += $count_row;
        }
        //END BONDED5

        $sheet->setCellValue('B' . ($current_row), '');
        $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF');
        $sheet->setCellValue('B' . ($current_row + 1), '1. 보세화물 서비스의 예상경비 청구서는 BL번호 단위로 발송됩니다.(단 분할인 경우 반출단위)');
        $sheet->setCellValue('B' . ($current_row + 2), '2. 세금계산서 발행은 확정청구서와 함께 처리 됩니다.');
        $sheet->setCellValue('B' . ($current_row + 3), '3. 결제는 PC/Mobile에 접속하여서 결제하시면 되며, 월별 청구인 경우 매달 24일까지 결제가 되지 않으면 25일 등록 된 카드로 자동결제 됩니다.');
        $sheet->setCellValue('B' . ($current_row + 4), '4. 결제수단에 따라 수수료가 추가 청구 됩니다.(카드/카카오페이 2.9%, 실시간계좌이체 1.8% 등)');

        $issuer = Member::where('mb_no', $rgd->mb_no)->first();
        $company = Company::where('co_no', $issuer->co_no)->first();

        $sheet->getStyle('B' . ($current_row + 6) . ':Z' . ($current_row + 9))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
        $sheet->getStyle('B' . ($current_row + 6) . ':Z' . ($current_row + 9))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B' . ($current_row + 6) . ':Z' . ($current_row + 9))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->mergeCells('B' . ($current_row + 6) . ':Z' . ($current_row + 6));
        $sheet->setCellValue('B' . ($current_row + 6), $company->co_name);
        $sheet->mergeCells('B' . ($current_row + 7) . ':Z' . ($current_row + 7));
        $sheet->setCellValue('B' . ($current_row + 7), $company->co_address . ' ' . $company->co_address_detail);
        // $sheet->mergeCells('B'. ($current_row + 8). ':Z'. ($current_row + 8));
        // $sheet->setCellValue('B'. ($current_row + 8), $company->co_owner);
        $sheet->mergeCells('B' . ($current_row + 8) . ':Z' . ($current_row + 8));
        $sheet->setCellValue('B' . ($current_row + 8), $company->co_tel);
        $sheet->mergeCells('B' . ($current_row + 9) . ':Z' . ($current_row + 9));
        $sheet->setCellValue('B' . ($current_row + 9), $company->co_email);

        // $sheet->getDefaultRowDimension()->setRowHeight(24);

        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }
        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = 'storage/download/' . $user->mb_no . '/';
        } else {
            $path = 'storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }

        if ($rgd->service_korean_name == '보세화물' && !str_contains($rgd->rgd_bill_type, 'month') && $rgd->rgd_status4 == '예상경비청구서') {
            $name = 'bonded_est_casebill_';
        } else if ($rgd->service_korean_name == '보세화물' && str_contains($rgd->rgd_bill_type, 'month') && $rgd->rgd_status4 == '예상경비청구서') {
            $name = 'bonded_est_monthbill_';
        } else {
            $name = 'bonded_final_casebill_';
        }

        $mask = $path . $name . '*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . $name . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => '../' . $file_name_download,
            'message' => 'Download File',
        ], 200);
        ob_end_clean();
    }

    public function download_bonded_monthbill_excel($rgd_no, Request $request)
    {
        Log::error($rgd_no);
        DB::beginTransaction();
        $user = Auth::user();
        $pathname = $request->header('pathname');
        $is_check_page = str_contains($pathname, 'check');

        $rgd = ReceivingGoodsDelivery::with(['rate_data_general', 'warehousing'])->where('rgd_no', $rgd_no)->first();

        $rgds = ReceivingGoodsDelivery::with(['rate_data_general', 'warehousing', 't_import_expected'])->whereHas('rgd_child', function ($q) use ($rgd) {
            $q->where('rgd_settlement_number',  $rgd->rgd_settlement_number);
        })->get();

        $rgds[] = $rgd;

        $is_month_bill = str_contains($rgd->rgd_bill_type, 'month') ? '_monthly' : '';
        $is_final_bill = str_contains($rgd->rgd_bill_type, 'final');

        if ($user->mb_type == 'shop') {
            $company = $is_check_page ? $rgd->warehousing->company->co_parent : $rgd->warehousing->company;
        } else if ($user->mb_type == 'spasys') {
            $company = $rgd->warehousing->company->co_parent;
        } else if ($user->mb_type == 'shipper') {
            $company = $rgd->warehousing->company;
        }

        $company->company_payment = CompanyPayment::where('co_no', $company->co_no)->first();

        // return response()->json([
        //     'status' => $rate_data_bonded4,
        // ], 200);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $sheet = $spreadsheet->getActiveSheet(0);

        // $sheet->getProtection()->setSheet(true);
        $sheet->getDefaultColumnDimension()->setWidth(4.5);
        // $sheet->getDefaultRowDimension()->setRowHeight(24);
        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }
        $sheet->getStyle('A1:S200')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:CT200')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->setTitle('보세화물 예상경비(건별,월별)');


        $sheet->mergeCells('B2:R6');
        $sheet->getStyle('B2:R6')->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->setCellValue('B2', $company->co_name);
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2')->getFont()->setSize(22)->setBold(true);

        $sheet->getStyle('R8')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('R8', '사업자번호 : ' . $company->co_license);
        $sheet->getStyle('R9')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('R9', '사업장 주소 : ' . $company->co_address . ' ' . $company->co_address_detail);
        $sheet->getStyle('R10')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('R10', '수신자명 : ' . $company->co_owner . ' (' . $company->co_email . ')');

        $sheet->getStyle('B12:B17')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
        $sheet->getStyle('B12:B17')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->getStyle('B12:B17')->getFont()->setBold(true);
        $sheet->mergeCells('B12:R12');
        $sheet->setCellValue('B12', ' ∙ 서   비  스 : 보세화물');
        $sheet->mergeCells('B13:R13');
        $sheet->setCellValue('B13', ' ∙ H-BL  건수 : ' . (count($rgds) - 1) . '건');
        $sheet->mergeCells('B14:R14');
        $sheet->setCellValue('B14', ' ∙ 청구서 No : ' . $rgd->rgd_status4 . ' ' . $rgd->rgd_settlement_number);
        $sheet->mergeCells('B15:R15');
        $sheet->setCellValue('B15', ' ∙ 청구서 발행일 : ' . Carbon::createFromFormat('Y-m-d H:i:s', $rgd->created_at)->format('Y.m.d'));
        $sheet->mergeCells('B16:R16');
        $sheet->setCellValue('B16', ' ∙ 예상 청구금액 : ' . (number_format($rgd->rate_data_general->rdg_sum7 + $rgd->rate_data_general->rdg_sum14)) . '원');
        $sheet->mergeCells('B17:R17');
        $sheet->setCellValue('B17', ' ∙ 계좌  정보 : ㈜' . $company->company_payment->cp_bank_name . ' ' . $company->company_payment->cp_bank_number . ' (' . $company->company_payment->cp_card_name . ')');

        //GENERAL TABLE
        $sheet->getStyle('B19')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->getStyle('B19')->getFont()->setBold(true);
        $sheet->mergeCells('B19:R19');
        $sheet->setCellValue('B19', '∙ 화물별 청구 금액');


        $sheet->getStyle('B20:R21')->getFont()->setBold(true);
        $sheet->getStyle('B20:R21')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');

        $sheet->mergeCells('B20:E20');
        $sheet->setCellValue('B20', '구분');

        $sheet->mergeCells('F20:H20');
        $sheet->setCellValue('F20', '세금계산서 발행');

        $sheet->mergeCells('I20:K20');
        $sheet->setCellValue('I20', '세금계산서 미발행');

        $headers = ['NO', '정산번호', '화물관리번호', 'H-BL', '공급가', '부가세', '합계', '공급가', '부가세', '합계', '합계 (VAT포함)', 'BLP 센터비용', '관세사 비용', '포워더 비용', '국내운송료', '요건 비용', '기타'];
        $col_start = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R'];


        foreach ($headers as $key => $header) {
            if ($key == 0) {
                $sheet->getColumnDimension($col_start[$key])->setWidth(8);
                $sheet->getStyle($col_start[$key] . '21')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . '21', $header);
            } else if ($key == 1) {
                $sheet->getColumnDimension($col_start[$key])->setWidth(20);
                $sheet->getStyle($col_start[$key] . '21')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . '21', $header);
            } else if ($key == 2) {
                $sheet->getColumnDimension($col_start[$key])->setWidth(22);
                $sheet->getStyle($col_start[$key] . '21')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . '21', $header);
            } else if ($key == 3) {
                $sheet->getColumnDimension($col_start[$key])->setWidth(20);
                $sheet->getStyle($col_start[$key] . '21')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . '21', $header);
            } else if ($key < 10) {
                $sheet->getColumnDimension($col_start[$key])->setWidth(14);
                $sheet->getStyle($col_start[$key] . '21')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . '21', $header);
            } else if ($key >= 10) {
                $sheet->getColumnDimension($col_start[$key])->setWidth(14);
                $sheet->mergeCells($col_start[$key] . '20' . ':' . $col_start[$key] . '21');
                $sheet->getStyle($col_start[$key] . '20')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . '20', $header);
            }
        }

        $current_row = 22;
        $count_row = 0;

        // return $rgds;

        foreach ($rgds as $key => $rgd) {
            foreach ($headers as $key_col => $header) {

                $sheet->getStyle($col_start[$key_col] . ($current_row + $key))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                if ($key_col == 0 && ($key != count($rgds) - 1)) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $key + 1);
                    $sheet->getStyle($col_start[$key_col] . ($current_row + $key))->getFont()->setBold(true);
                    $sheet->getStyle($col_start[$key_col] . ($current_row + $key))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                } else if ($key_col == 0 && ($key == count($rgds) - 1)) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), '합계');
                    $sheet->getStyle($col_start[$key_col] . ($current_row + $key))->getFont()->setBold(true);
                    $sheet->getStyle($col_start[$key_col] . ($current_row + $key))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                } else if ($key_col == 1 && ($key != count($rgds) - 1)) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rgd_settlement_number']);
                } else if ($key_col == 2 && ($key != count($rgds) - 1)) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['warehousing']['logistic_manage_number']);
                } else if ($key_col == 3 && ($key != count($rgds) - 1)) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['t_import_expected']['tie_h_bl']);
                } else if ($key_col == 4) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_supply_price7'] ? $rgd['rate_data_general']['rdg_supply_price7'] : 0);
                } else if ($key_col == 5) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_vat7'] ? $rgd['rate_data_general']['rdg_vat7'] : 0);
                } else if ($key_col == 6) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_sum7'] ? $rgd['rate_data_general']['rdg_sum7'] : 0);
                } else if ($key_col == 7) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_supply_price14'] ? $rgd['rate_data_general']['rdg_supply_price14'] : 0);
                } else if ($key_col == 8) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_vat14'] ? $rgd['rate_data_general']['rdg_vat14'] : 0);
                } else if ($key_col == 9) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_sum14'] ? $rgd['rate_data_general']['rdg_sum14'] : 0);
                } else if ($key_col == 10) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_sum7'] ? $rgd['rate_data_general']['rdg_sum7'] : 0);
                } else if ($key_col == 11) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_supply_price1'] ? $rgd['rate_data_general']['rdg_supply_price1'] : 0);
                } else if ($key_col == 12) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_supply_price2'] ? $rgd['rate_data_general']['rdg_supply_price2'] : 0);
                } else if ($key_col == 13) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_supply_price3'] ? $rgd['rate_data_general']['rdg_supply_price3'] : 0);
                } else if ($key_col == 14) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_supply_price4'] ? $rgd['rate_data_general']['rdg_supply_price4'] : 0);
                } else if ($key_col == 15) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_supply_price5'] ? $rgd['rate_data_general']['rdg_supply_price5'] : 0);
                } else if ($key_col == 16) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), '');
                }
            }
            $count_row = $key;
        }
        //FORMAT NUMBER
        $sheet->getStyle('C' . ($current_row) . ':R' . ($current_row + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');

        $current_row += $count_row;
        $sheet->getStyle('B20:R' . $current_row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->getStyle('B20:R' . $current_row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $current_row += 2;

        $sheet->getStyle('B' . ($current_row) . ':R' . ($current_row + 3))->getBorders()->getOutLine()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->getStyle('B' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)->setWrapText(true);
        $sheet->mergeCells('B' . ($current_row) . ':R' . ($current_row + 3));
        $sheet->setCellValue('B' . ($current_row), $rgd['rgd_memo_settle']);
        $current_row += 4;

        //END PART
        $sheet->setCellValue('B' . ($current_row), '');
        $sheet->setCellValue('B' . ($current_row + 1), '1. 보세화물 서비스의 예상경비 청구서는 BL번호 단위로 발송됩니다.(단 분할인 경우 반출단위)');
        $sheet->setCellValue('B' . ($current_row + 2), '2. 세금계산서 발행은 확정청구서와 함께 처리 됩니다.');
        $sheet->setCellValue('B' . ($current_row + 3), '3. 결제는 PC/Mobile에 접속하여서 결제하시면 되며, 월별 청구인 경우 매달 24일까지 결제가 되지 않으면 25일 등록 된 카드로 자동결제 됩니다.');
        $sheet->setCellValue('B' . ($current_row + 4), '4. 결제수단에 따라 수수료가 추가 청구 됩니다.(카드/카카오페이 2.9%, 실시간계좌이체 1.8% 등)');

        $issuer = Member::where('mb_no', $rgd->mb_no)->first();
        $company = Company::where('co_no', $issuer->co_no)->first();

        $sheet->getStyle('B' . ($current_row + 6) . ':R' . ($current_row + 9))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
        $sheet->getStyle('B' . ($current_row + 6) . ':R' . ($current_row + 9))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B' . ($current_row + 6) . ':R' . ($current_row + 9))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->mergeCells('B' . ($current_row + 6) . ':R' . ($current_row + 6));
        $sheet->setCellValue('B' . ($current_row + 6), $company->co_name);
        $sheet->mergeCells('B' . ($current_row + 7) . ':R' . ($current_row + 7));
        $sheet->setCellValue('B' . ($current_row + 7), $company->co_address . ' ' . $company->co_address_detail);
        // $sheet->mergeCells('B'. ($current_row + 8). ':R'. ($current_row + 8));
        // $sheet->setCellValue('B'. ($current_row + 8), $company->co_owner);
        $sheet->mergeCells('B' . ($current_row + 8) . ':R' . ($current_row + 8));
        $sheet->setCellValue('B' . ($current_row + 8), $company->co_tel);
        $sheet->mergeCells('B' . ($current_row + 9) . ':R' . ($current_row + 9));
        $sheet->setCellValue('B' . ($current_row + 9), $company->co_email);

        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = 'storage/download/' . $user->mb_no . '/';
        } else {
            $path = 'storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }

        $name = 'bonded_final_monthbill_';


        $mask = $path . $name . '*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . $name . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => '../' . $file_name_download,
            'message' => 'Download File',
            'rgds' => $rgds
        ], 200);
        ob_end_clean();
    }

    public function download_fulfill_excel($rgd_no, Request $request)
    {
        Log::error($rgd_no);
        DB::beginTransaction();
        $user = Auth::user();
        $pathname = $request->header('pathname');
        $is_check_page = str_contains($pathname, 'check');

        $rgd = ReceivingGoodsDelivery::with(['rate_data_general', 'warehousing'])->where('rgd_no', $rgd_no)->first();
        $is_month_bill = str_contains($rgd->rgd_bill_type, 'month') ? '_monthly' : '';
        $is_final_bill = str_contains($rgd->rgd_bill_type, 'final');

        if ($user->mb_type == 'shop') {
            $company = $rgd->warehousing->company;

            $rmd_no_fulfill1 = $this->get_rmd_no_fulfill_raw($rgd_no, $is_check_page ? 'fulfill1_final_spasys' : 'fulfill1_final_shop', 'fulfill1');
            $rmd_no_fulfill2 = $this->get_rmd_no_fulfill_raw($rgd_no, $is_check_page ? 'fulfill2_final_spasys' : 'fulfill2_final_shop', 'fulfill2');
            $rmd_no_fulfill3 = $this->get_rmd_no_fulfill_raw($rgd_no, $is_check_page ? 'fulfill3_final_spasys' : 'fulfill3_final_shop', 'fulfill3');
            $rmd_no_fulfill4 = $this->get_rmd_no_fulfill_raw($rgd_no, $is_check_page ? 'fulfill4_final_spasys' : 'fulfill4_final_shop', 'fulfill4');
            $rmd_no_fulfill5 = $this->get_rmd_no_fulfill_raw($rgd_no, $is_check_page ? 'fulfill5_final_spasys' : 'fulfill5_final_shop', 'fulfill5');
        } else if ($user->mb_type == 'spasys') {
            $company = $rgd->warehousing->company->co_parent;
            $rmd_no_fulfill1 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill1_final_spasys', 'fulfill1');
            $rmd_no_fulfill2 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill2_final_spasys', 'fulfill2');
            $rmd_no_fulfill3 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill3_final_spasys', 'fulfill3');
            $rmd_no_fulfill4 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill4_final_spasys', 'fulfill4');
            $rmd_no_fulfill5 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill5_final_spasys', 'fulfill5');
        } else if ($user->mb_type == 'shipper') {
            $company = $rgd->warehousing->company;
            $rmd_no_fulfill1 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill1_final_shop', 'fulfill1');
            $rmd_no_fulfill2 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill2_final_shop', 'fulfill2');
            $rmd_no_fulfill3 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill3_final_shop', 'fulfill3');
            $rmd_no_fulfill4 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill4_final_shop', 'fulfill4');
            $rmd_no_fulfill5 = $this->get_rmd_no_fulfill_raw($rgd_no, 'fulfill5_final_shop', 'fulfill5');
        }

        if ($rgd->service_korean_name == '수입풀필먼트') {
            $company = $rgd->warehousing->company;
        }

        $company->company_payment = CompanyPayment::where('co_no', $company->co_no)->first();

        $rate_data_fulfill1 = $rate_data = RateData::where('rmd_no', isset($rmd_no_fulfill1) ? $rmd_no_fulfill1 : 0)->where(function ($q) {
        })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

        $rate_data_fulfill2 = $rate_data = RateData::where('rmd_no', isset($rmd_no_fulfill2) ? $rmd_no_fulfill2 : 0)->where(function ($q) {
        })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

        $rate_data_fulfill3 = $rate_data = RateData::where('rmd_no', isset($rmd_no_fulfill3) ? $rmd_no_fulfill3 : 0)->where(function ($q) {
        })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

        $rate_data_fulfill4 = $rate_data = RateData::where('rmd_no', isset($rmd_no_fulfill4) ? $rmd_no_fulfill4 : 0)->where(function ($q) {
        })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

        $rate_data_fulfill5 = $rate_data = RateData::where('rmd_no', isset($rmd_no_fulfill5) ? $rmd_no_fulfill5 : 0)->where(function ($q) {
        })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

        // return response()->json([
        //     'rgd' => $rgd,
        //     '1' => $rate_data_fulfill1,
        //     '2' => $rate_data_fulfill2,
        //     '3' => $rate_data_fulfill3,
        //     '4' => $rate_data_fulfill4,
        //     '5' => $rate_data_fulfill5,
        // ], 200);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $sheet = $spreadsheet->getActiveSheet(0);

        // $sheet->getProtection()->setSheet(true);
        $sheet->getDefaultColumnDimension()->setWidth(4.5);
        // $sheet->getDefaultRowDimension()->setRowHeight(24);
        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getStyle('A1:Z200')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:CT200')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->setTitle('수입풀필먼트 확정(월별)');


        $sheet->mergeCells('B2:Z6');
        $sheet->getStyle('B2:Z6')->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->setCellValue('B2', $company->co_name);
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2')->getFont()->setSize(22)->setBold(true);

        $sheet->getStyle('Z8')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Z8', '사업자번호 : ' . $company->co_license);
        $sheet->getStyle('Z9')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Z9', '사업장 주소 : ' . $company->co_address . ' ' . $company->co_address_detail);
        $sheet->getStyle('Z10')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Z10', '수신자명 : ' . $company->co_owner . ' (' . $company->co_email . ')');

        $sheet->getStyle('B13:B17')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
        $sheet->getStyle('B13:B17')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->getStyle('B13:B17')->getFont()->setBold(true);
        $sheet->mergeCells('B13:Z13');
        $sheet->setCellValue('B13', ' ∙ 서   비  스 : 수입풀필먼트');
        $sheet->mergeCells('B14:Z14');
        $sheet->setCellValue('B14', ' ∙ 청구서 No : ' . $rgd->rgd_status4 . ' ' . $rgd->rgd_settlement_number);
        $sheet->mergeCells('B15:Z15');
        $sheet->setCellValue('B15', ' ∙ 청구서 발행일 : ' . Carbon::createFromFormat('Y-m-d H:i:s', $rgd->created_at)->format('Y.m.d'));
        $sheet->mergeCells('B16:Z16');
        $sheet->setCellValue('B16', ' ∙ 예상 청구금액 : ' . number_format($rgd->rate_data_general->rdg_sum6) . '원');
        $sheet->mergeCells('B17:Z17');
        $sheet->setCellValue('B17', isset($company->company_payment) ? (' ∙ 계좌  정보 : ㈜' . $company->company_payment->cp_bank_name . ' ' . $company->company_payment->cp_bank_number . ' (' . $company->company_payment->cp_card_name . ')') : ' ∙ 계좌  정보 : ㈜');

        //GENERAL TABLE
        $sheet->getStyle('B19')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->getStyle('B19')->getFont()->setBold(true);
        $sheet->mergeCells('B19:Z19');
        $sheet->setCellValue('B19', '  ∙ 항목별 청구 금액');

        $headers = ['단위', '단가', '건수', '공급가', '부가세', '급액', '비고'];
        $col_start = ['F', 'I', 'L', 'O', 'R', 'U', 'X'];
        $col_end = ['H', 'K', 'N', 'Q', 'T', 'W', 'Z'];

        $categories = ['센터작업료', '국내 운송료', '해외 운송료', '보관', '부자재', '합계'];

        $current_row = 22;
        $count_row = 0;

        foreach ($categories as $key => $category) {
            if ($rgd->rate_data_general['rdg_sum' . ($key + 1)] != 0) {
                $sheet->mergeCells('B' . ($current_row + $count_row) . ':E' . ($current_row + $count_row));
                $sheet->setCellValue('B' . ($current_row + $count_row), $category);
                $sheet->mergeCells('F' . ($current_row + $count_row) . ':I' . ($current_row + $count_row));
                $sheet->setCellValue('F' . ($current_row + $count_row), $rgd->rate_data_general['rdg_supply_price' . ($key + 1)]);
                $sheet->mergeCells('J' . ($current_row + $count_row) . ':M' . ($current_row + $count_row));
                $sheet->setCellValue('J' . ($current_row + $count_row), $rgd->rate_data_general['rdg_vat' . ($key + 1)]);
                $sheet->mergeCells('N' . ($current_row + $count_row) . ':Q' . ($current_row + $count_row));
                $sheet->setCellValue('N' . ($current_row + $count_row), $rgd->rate_data_general['rdg_sum' . ($key + 1)]);
                $sheet->mergeCells('R' . ($current_row + $count_row) . ':Z' . ($current_row + $count_row));
                $sheet->setCellValue('R' . ($current_row + $count_row), $rgd->rate_data_general['rdg_etc' . ($key + 1)]);

                $count_row += 1;
            }
        }

        //FORMAT NUMBER
        $sheet->getStyle('F' . ($current_row) . ':W' . ($current_row - 1 + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');

        $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row - 1 + $count_row))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
        $sheet->getStyle('B' . ($current_row) . ':E' . ($current_row - 1 + $count_row))->getFont()->setBold(true);
        $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row - 1 + $count_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 1 + $count_row))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2 + 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
        $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2 + 1))->getFont()->setBold(true);

        $sheet->mergeCells('B' . ($current_row - 2) . ':E' . ($current_row - 2 + 1));
        $sheet->getStyle('B' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('B' . ($current_row - 2), '항목');

        $sheet->mergeCells('F' . ($current_row - 2) . ':I' . ($current_row - 2 + 1));
        $sheet->getStyle('F' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('F' . ($current_row - 2), '공급가');

        $sheet->mergeCells('J' . ($current_row - 2) . ':M' . ($current_row - 2 + 1));
        $sheet->getStyle('J' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('J' . ($current_row - 2), '부가세');

        $sheet->mergeCells('N' . ($current_row - 2) . ':Q' . ($current_row - 2 + 1));
        $sheet->getStyle('N' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('N' . ($current_row - 2), '비고');

        $sheet->mergeCells('R' . ($current_row - 2) . ':Z' . ($current_row - 2 + 1));
        $sheet->getStyle('R' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('R' . ($current_row - 2), '비고');

        $current_row += $count_row;

        //FULFILL1
        if ($rgd->rate_data_general['rdg_sum1'] > 0) {
            $sheet->getStyle('B' . $current_row . ':Z' . $current_row)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
            $sheet->getStyle('B' . $current_row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
            $sheet->getStyle('B' . $current_row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $current_row . ':Z' . $current_row);
            $sheet->setCellValue('B' . $current_row, ' ∙ 센터 작업료 상세');

            $current_row += 3;



            $count_row = 0;

            $count_row_fulfill1 = 0;
            $current_row_fulfill1 = $current_row - 1;

            $rd_cate1 = [];
            $rd_data4_sum = 0;
            $rd_data5_sum = 0;
            $rd_data6_sum = 0;
            $rd_data7_sum = 0;
            $rd_data4_total = 0;


            $rate_data_fulfill1_ = [];

            foreach ($rate_data_fulfill1 as $key => $rate_data) {
                array_push($rate_data_fulfill1_, $rate_data);
                $rate_data['rd_data4'] = $rate_data['rd_data4'] == '' ? 0 : $rate_data['rd_data4'];
                $rate_data['rd_data5'] = $rate_data['rd_data5'] == '' ? 0 : $rate_data['rd_data5'];
                $rate_data['rd_data6'] = $rate_data['rd_data6'] == '' ? 0 : $rate_data['rd_data6'];
                $rate_data['rd_data7'] = $rate_data['rd_data7'] == '' ? 0 : $rate_data['rd_data7'];
                if ($rate_data['rd_data7'] > 0) $rd_data4_total += $rate_data['rd_data4'];

                if (!in_array($rate_data['rd_cate1'], $rd_cate1)) {
                    $rd_data4_sum = 0;
                    $rd_data5_sum = 0;
                    $rd_data6_sum = 0;
                    $rd_data7_sum = 0;
                    $rd_cate1[] = $rate_data['rd_cate1'];
                }

                $rd_data4_sum += $rate_data['rd_data4'];
                $rd_data5_sum += $rate_data['rd_data5'];
                $rd_data6_sum += $rate_data['rd_data6'];
                $rd_data7_sum += $rate_data['rd_data7'];


                if (isset($rate_data_fulfill1[$key + 1]) && $rate_data['rd_cate1'] != $rate_data_fulfill1[$key + 1]['rd_cate1']) {
                    $data = clone $rate_data;

                    $data['rd_cate2'] = '소계';
                    $data['rd_data1'] = '';
                    $data['rd_data2'] = '';
                    $data['rd_data4'] = $rd_data4_sum;
                    $data['rd_data5'] = $rd_data5_sum;
                    $data['rd_data6'] = $rd_data6_sum;
                    $data['rd_data7'] = $rd_data7_sum;

                    array_push($rate_data_fulfill1_, $data);
                }
                if ($key == count($rate_data_fulfill1) - 1) {

                    $data = clone $rate_data;

                    $data['rd_cate2'] = '소계';
                    $data['rd_data1'] = '';
                    $data['rd_data2'] = '';
                    $data['rd_data4'] = $rd_data4_sum;
                    $data['rd_data5'] = $rd_data5_sum;
                    $data['rd_data6'] = $rd_data6_sum;
                    $data['rd_data7'] = $rd_data7_sum;

                    array_push($rate_data_fulfill1_, $data);
                }
            }

            foreach ($rate_data_fulfill1_ as $key => $rate_data) {



                if ($rate_data['rd_cate1'] == $rd_cate1[0]) {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_fulfill1_[$key - 1]['rd_cate1'])) {
                        $sheet->setCellValue('B' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate1']);
                        $count_row_fulfill1 = 0;
                        if ($rate_data['rd_data7'] > 0) {

                            $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                            $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                            $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                            $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                            $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                            $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                            $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                            $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                            $count_row_fulfill1 += 1;
                            $count_row += 1;
                        }
                    } else if ($rate_data['rd_data7'] > 0) {

                        $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                        $count_row_fulfill1 += 1;
                        $count_row += 1;
                    }
                } else {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_fulfill1_[$key - 1]['rd_cate1'])) {
                        $sheet->mergeCells('B' . ($current_row_fulfill1) . ':B' . ($current_row_fulfill1 + $count_row_fulfill1 - 1));
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFont()->setBold(true);
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate1']);
                        $current_row_fulfill1 = $current_row_fulfill1 + $count_row_fulfill1;
                        $count_row_fulfill1 = 0;

                        if ($rate_data['rd_data7'] > 0) {


                            $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                            $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                            $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                            $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                            $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                            $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                            $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                            $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                            $count_row_fulfill1 += 1;
                            $count_row += 1;
                        }
                    } else if ($rate_data['rd_data7'] > 0) {


                        $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                        $count_row_fulfill1 += 1;
                        $count_row += 1;
                    }
                }

                if (count($rate_data_fulfill1_) == $key + 1) {
                    $sheet->mergeCells('B' . ($current_row_fulfill1) . ':B' . ($current_row_fulfill1 + $count_row_fulfill1 - 1));
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $current_row_fulfill1 = $current_row_fulfill1 + $count_row_fulfill1;
                    $count_row_fulfill1 = 0;
                }
            }
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getFont()->setBold(true);

            $sheet->mergeCells('B' . ($current_row - 2) . ':E' . ($current_row - 2));
            $sheet->getStyle('B' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B' . ($current_row - 2), '항목');


            foreach ($headers as $key => $header) {
                $sheet->mergeCells($col_start[$key] . ($current_row - 2) . ':' . $col_end[$key] . ($current_row - 2));
                $sheet->getStyle($col_start[$key] . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . ($current_row - 2), $header);
            }

            //FORMAT NUMBER
            $sheet->getStyle('F' . ($current_row - 1) . ':W' . ($current_row - 1 + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');
            $current_row += $count_row;

            $sheet->getStyle('B' . ($current_row - 1) . ':Z' . ($current_row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 1) . ':Z' . ($current_row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . ($current_row - 1))->getFont()->setBold(true);
            $sheet->setCellValue('B' . ($current_row - 1), '합계');

            $sheet->mergeCells('B' . ($current_row - 1) . ':E' . ($current_row - 1));
            $sheet->mergeCells('F' . ($current_row - 1) . ':H' . ($current_row - 1));
            $sheet->mergeCells('I' . ($current_row - 1) . ':K' . ($current_row - 1));
            $sheet->mergeCells('L' . ($current_row - 1) . ':N' . ($current_row - 1));
            $sheet->setCellValue('L' . ($current_row - 1), $rd_data4_total);
            $sheet->mergeCells('O' . ($current_row - 1) . ':Q' . ($current_row - 1));
            $sheet->setCellValue('O' . ($current_row - 1), $rgd->rate_data_general['rdg_supply_price1']);
            $sheet->mergeCells('R' . ($current_row - 1) . ':T' . ($current_row - 1));
            $sheet->setCellValue('R' . ($current_row - 1), $rgd->rate_data_general['rdg_vat1']);
            $sheet->mergeCells('U' . ($current_row - 1) . ':W' . ($current_row - 1));
            $sheet->setCellValue('U' . ($current_row - 1), $rgd->rate_data_general['rdg_sum1']);
            $sheet->mergeCells('X' . ($current_row - 1) . ':Z' . ($current_row - 1));
        }
        //END FULFILL1

        //FULFILL2
        if ($rgd->rate_data_general['rdg_sum2'] > 0) {
            $sheet->getStyle('B' . $current_row . ':Z' . $current_row)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
            $sheet->getStyle('B' . $current_row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
            $sheet->getStyle('B' . $current_row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $current_row . ':Z' . $current_row);
            $sheet->setCellValue('B' . $current_row, ' ∙ 국내운송료 상세');

            $current_row += 3;



            $count_row = 0;

            $count_row_fulfill1 = 0;
            $current_row_fulfill1 = $current_row - 1;

            $rd_cate1 = [];
            $rd_data4_sum = 0;
            $rd_data5_sum = 0;
            $rd_data6_sum = 0;
            $rd_data7_sum = 0;
            $rd_data4_total = 0;

            $rate_data_fulfill2_ = [];

            foreach ($rate_data_fulfill2 as $key => $rate_data) {
                array_push($rate_data_fulfill2_, $rate_data);
                $rate_data['rd_data4'] = $rate_data['rd_data4'] == '' ? 0 : $rate_data['rd_data4'];
                $rate_data['rd_data5'] = $rate_data['rd_data5'] == '' ? 0 : $rate_data['rd_data5'];
                $rate_data['rd_data6'] = $rate_data['rd_data6'] == '' ? 0 : $rate_data['rd_data6'];
                $rate_data['rd_data7'] = $rate_data['rd_data7'] == '' ? 0 : $rate_data['rd_data7'];
                if ($rate_data['rd_data7'] > 0) $rd_data4_total += $rate_data['rd_data4'];
                if (!in_array($rate_data['rd_cate1'], $rd_cate1)) {
                    $rd_data4_sum = 0;
                    $rd_data5_sum = 0;
                    $rd_data6_sum = 0;
                    $rd_data7_sum = 0;
                    $rd_cate1[] = $rate_data['rd_cate1'];
                }

                $rd_data4_sum += $rate_data['rd_data4'];
                $rd_data5_sum += $rate_data['rd_data5'];
                $rd_data6_sum += $rate_data['rd_data6'];
                $rd_data7_sum += $rate_data['rd_data7'];


                if (isset($rate_data_fulfill2[$key + 1]) && $rate_data['rd_cate1'] != $rate_data_fulfill2[$key + 1]['rd_cate1']) {
                    $data = clone $rate_data;

                    $data['rd_cate2'] = '합계';
                    $data['rd_data1'] = '';
                    $data['rd_data2'] = '';
                    $data['rd_data4'] = $rd_data4_sum;
                    $data['rd_data5'] = $rd_data5_sum;
                    $data['rd_data6'] = $rd_data6_sum;
                    $data['rd_data7'] = $rd_data7_sum;

                    array_push($rate_data_fulfill2_, $data);
                }
                // if($key == count($rate_data_fulfill2) - 1) {

                //     $data = clone $rate_data;

                //     $data['rd_cate2'] = '합계';
                //     $data['rd_data1'] = '';
                //     $data['rd_data2'] = '';
                //     $data['rd_data4'] = $rd_data4_sum;
                //     $data['rd_data5'] = $rd_data5_sum;
                //     $data['rd_data6'] = $rd_data6_sum;
                //     $data['rd_data7'] = $rd_data7_sum;

                //     array_push($rate_data_fulfill2_, $data);
                // }
            }

            foreach ($rate_data_fulfill2_ as $key => $rate_data) {



                if ($rate_data['rd_cate1'] == $rd_cate1[0]) {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_fulfill2_[$key - 1]['rd_cate1'])) {
                        $sheet->setCellValue('B' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate1']);
                        $count_row_fulfill1 = 0;
                        if ($rate_data['rd_data7'] > 0) {

                            $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                            $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                            $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                            $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                            $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                            $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                            $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                            $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                            $count_row_fulfill1 += 1;
                            $count_row += 1;
                        }
                    } else if ($rate_data['rd_data7'] > 0) {

                        $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                        $count_row_fulfill1 += 1;
                        $count_row += 1;
                    }
                } else {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_fulfill2_[$key - 1]['rd_cate1'])) {
                        $sheet->mergeCells('B' . ($current_row_fulfill1) . ':B' . ($current_row_fulfill1 + $count_row_fulfill1 - 1));
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFont()->setBold(true);
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate1']);
                        $current_row_fulfill1 = $current_row_fulfill1 + $count_row_fulfill1;
                        $count_row_fulfill1 = 0;
                    } else if ($rate_data['rd_data7'] > 0) {


                        $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                        $count_row_fulfill1 += 1;
                        $count_row += 1;
                    }
                }

                if (count($rate_data_fulfill2_) == $key + 1) {
                    $sheet->mergeCells('B' . ($current_row_fulfill1) . ':B' . ($current_row_fulfill1 + $count_row_fulfill1 - 1));
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $current_row_fulfill1 = $current_row_fulfill1 + $count_row_fulfill1;
                    $count_row_fulfill1 = 0;
                }
            }
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getFont()->setBold(true);

            $sheet->mergeCells('B' . ($current_row - 2) . ':E' . ($current_row - 2));
            $sheet->getStyle('B' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B' . ($current_row - 2), '항목');


            foreach ($headers as $key => $header) {
                $sheet->mergeCells($col_start[$key] . ($current_row - 2) . ':' . $col_end[$key] . ($current_row - 2));
                $sheet->getStyle($col_start[$key] . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . ($current_row - 2), $header);
            }
            //FORMAT NUMBER
            $sheet->getStyle('F' . ($current_row - 1) . ':W' . ($current_row - 1 + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');
            $current_row += $count_row;

            $sheet->getStyle('B' . ($current_row - 1) . ':Z' . ($current_row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 1) . ':Z' . ($current_row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . ($current_row - 1))->getFont()->setBold(true);
            $sheet->setCellValue('B' . ($current_row - 1), '합계');

            $sheet->mergeCells('B' . ($current_row - 1) . ':E' . ($current_row - 1));
            $sheet->mergeCells('F' . ($current_row - 1) . ':H' . ($current_row - 1));
            $sheet->mergeCells('I' . ($current_row - 1) . ':K' . ($current_row - 1));
            $sheet->mergeCells('L' . ($current_row - 1) . ':N' . ($current_row - 1));
            $sheet->setCellValue('L' . ($current_row - 1), $rd_data4_total);
            $sheet->mergeCells('O' . ($current_row - 1) . ':Q' . ($current_row - 1));
            $sheet->setCellValue('O' . ($current_row - 1), $rgd->rate_data_general['rdg_supply_price2']);
            $sheet->mergeCells('R' . ($current_row - 1) . ':T' . ($current_row - 1));
            $sheet->setCellValue('R' . ($current_row - 1), $rgd->rate_data_general['rdg_vat2']);
            $sheet->mergeCells('U' . ($current_row - 1) . ':W' . ($current_row - 1));
            $sheet->setCellValue('U' . ($current_row - 1), $rgd->rate_data_general['rdg_sum2']);
            $sheet->mergeCells('X' . ($current_row - 1) . ':Z' . ($current_row - 1));
        }
        //END FULFILL2

        //FULFILL3
        if ($rgd->rate_data_general['rdg_sum3'] > 0) {
            $sheet->getStyle('B' . $current_row . ':Z' . $current_row)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
            $sheet->getStyle('B' . $current_row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
            $sheet->getStyle('B' . $current_row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $current_row . ':Z' . $current_row);
            $sheet->setCellValue('B' . $current_row, ' ∙ 해외운송료 상세');

            $current_row += 3;



            $count_row = 0;

            $count_row_fulfill1 = 0;
            $current_row_fulfill1 = $current_row - 1;

            $rd_cate1 = [];
            $rd_data4_sum = 0;
            $rd_data5_sum = 0;
            $rd_data6_sum = 0;
            $rd_data7_sum = 0;
            $rd_data4_total = 0;

            $rate_data_fulfill3_ = [];

            foreach ($rate_data_fulfill3 as $key => $rate_data) {
                array_push($rate_data_fulfill3_, $rate_data);
                $rate_data['rd_data4'] = $rate_data['rd_data4'] == '' ? 0 : $rate_data['rd_data4'];
                $rate_data['rd_data5'] = $rate_data['rd_data5'] == '' ? 0 : $rate_data['rd_data5'];
                $rate_data['rd_data6'] = $rate_data['rd_data6'] == '' ? 0 : $rate_data['rd_data6'];
                $rate_data['rd_data7'] = $rate_data['rd_data7'] == '' ? 0 : $rate_data['rd_data7'];
                if ($rate_data['rd_data7'] > 0) $rd_data4_total += $rate_data['rd_data4'];
                if (!in_array($rate_data['rd_cate1'], $rd_cate1)) {
                    $rd_data4_sum = 0;
                    $rd_data5_sum = 0;
                    $rd_data6_sum = 0;
                    $rd_data7_sum = 0;
                    $rd_cate1[] = $rate_data['rd_cate1'];
                }

                $rd_data4_sum += $rate_data['rd_data4'];
                $rd_data5_sum += $rate_data['rd_data5'];
                $rd_data6_sum += $rate_data['rd_data6'];
                $rd_data7_sum += $rate_data['rd_data7'];


                if (isset($rate_data_fulfill3[$key + 1]) && $rate_data['rd_cate1'] != $rate_data_fulfill3[$key + 1]['rd_cate1']) {
                    $data = clone $rate_data;

                    $data['rd_cate2'] = '합계';
                    $data['rd_data1'] = '';
                    $data['rd_data2'] = '';
                    $data['rd_data4'] = $rd_data4_sum;
                    $data['rd_data5'] = $rd_data5_sum;
                    $data['rd_data6'] = $rd_data6_sum;
                    $data['rd_data7'] = $rd_data7_sum;

                    array_push($rate_data_fulfill3_, $data);
                }
                // if($key == count($rate_data_fulfill3) - 1) {

                //     $data = clone $rate_data;

                //     $data['rd_cate2'] = '합계';
                //     $data['rd_data1'] = '';
                //     $data['rd_data2'] = '';
                //     $data['rd_data4'] = $rd_data4_sum;
                //     $data['rd_data5'] = $rd_data5_sum;
                //     $data['rd_data6'] = $rd_data6_sum;
                //     $data['rd_data7'] = $rd_data7_sum;

                //     array_push($rate_data_fulfill3_, $data);
                // }
            }

            foreach ($rate_data_fulfill3_ as $key => $rate_data) {



                if ($rate_data['rd_cate1'] == $rd_cate1[0]) {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_fulfill3_[$key - 1]['rd_cate1'])) {
                        $sheet->setCellValue('B' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate1']);
                        $count_row_fulfill1 = 0;
                        if ($rate_data['rd_data7'] > 0) {

                            $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                            $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                            $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                            $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                            $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                            $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                            $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                            $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                            $count_row_fulfill1 += 1;
                            $count_row += 1;
                        }
                    } else if ($rate_data['rd_data7'] > 0) {

                        $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                        $count_row_fulfill1 += 1;
                        $count_row += 1;
                    }
                } else {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_fulfill3_[$key - 1]['rd_cate1'])) {
                        $sheet->mergeCells('B' . ($current_row_fulfill1) . ':B' . ($current_row_fulfill1 + $count_row_fulfill1 - 1));
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFont()->setBold(true);
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate1']);
                        $current_row_fulfill1 = $current_row_fulfill1 + $count_row_fulfill1;
                        $count_row_fulfill1 = 0;
                    } else if ($rate_data['rd_data7'] > 0) {


                        $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                        $count_row_fulfill1 += 1;
                        $count_row += 1;
                    }
                }

                if (count($rate_data_fulfill3_) == $key + 1) {
                    $sheet->mergeCells('B' . ($current_row_fulfill1) . ':B' . ($current_row_fulfill1 + $count_row_fulfill1 - 1));
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $current_row_fulfill1 = $current_row_fulfill1 + $count_row_fulfill1;
                    $count_row_fulfill1 = 0;
                }
            }
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getFont()->setBold(true);

            $sheet->mergeCells('B' . ($current_row - 2) . ':E' . ($current_row - 2));
            $sheet->getStyle('B' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B' . ($current_row - 2), '항목');


            foreach ($headers as $key => $header) {
                $sheet->mergeCells($col_start[$key] . ($current_row - 2) . ':' . $col_end[$key] . ($current_row - 2));
                $sheet->getStyle($col_start[$key] . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . ($current_row - 2), $header);
            }
            //FORMAT NUMBER
            $sheet->getStyle('F' . ($current_row - 1) . ':W' . ($current_row - 1 + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');
            $current_row += $count_row;

            $sheet->getStyle('B' . ($current_row - 1) . ':Z' . ($current_row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 1) . ':Z' . ($current_row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . ($current_row - 1))->getFont()->setBold(true);
            $sheet->setCellValue('B' . ($current_row - 1), '합계');

            $sheet->mergeCells('B' . ($current_row - 1) . ':E' . ($current_row - 1));
            $sheet->mergeCells('F' . ($current_row - 1) . ':H' . ($current_row - 1));
            $sheet->mergeCells('I' . ($current_row - 1) . ':K' . ($current_row - 1));
            $sheet->mergeCells('L' . ($current_row - 1) . ':N' . ($current_row - 1));
            $sheet->setCellValue('L' . ($current_row - 1), $rd_data4_total);
            $sheet->mergeCells('O' . ($current_row - 1) . ':Q' . ($current_row - 1));
            $sheet->setCellValue('O' . ($current_row - 1), $rgd->rate_data_general['rdg_supply_price3']);
            $sheet->mergeCells('R' . ($current_row - 1) . ':T' . ($current_row - 1));
            $sheet->setCellValue('R' . ($current_row - 1), $rgd->rate_data_general['rdg_vat3']);
            $sheet->mergeCells('U' . ($current_row - 1) . ':W' . ($current_row - 1));
            $sheet->setCellValue('U' . ($current_row - 1), $rgd->rate_data_general['rdg_sum3']);
            $sheet->mergeCells('X' . ($current_row - 1) . ':Z' . ($current_row - 1));
        }
        //END FULFILL3

        //FULFILL4
        if ($rgd->rate_data_general['rdg_sum4'] > 0) {
            $sheet->getStyle('B' . $current_row . ':Z' . $current_row)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
            $sheet->getStyle('B' . $current_row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
            $sheet->getStyle('B' . $current_row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $current_row . ':Z' . $current_row);
            $sheet->setCellValue('B' . $current_row, ' ∙ 보관료 상세');

            $current_row += 3;



            $count_row = 0;

            $count_row_fulfill1 = 0;
            $current_row_fulfill1 = $current_row - 1;

            $rd_cate1 = [];
            $rd_data4_sum = 0;
            $rd_data5_sum = 0;
            $rd_data6_sum = 0;
            $rd_data7_sum = 0;
            $rd_data4_total = 0;
            $rate_data_fulfill4_ = [];

            foreach ($rate_data_fulfill4 as $key => $rate_data) {
                array_push($rate_data_fulfill4_, $rate_data);
                $rate_data['rd_data4'] = $rate_data['rd_data4'] == '' ? 0 : $rate_data['rd_data4'];
                $rate_data['rd_data5'] = $rate_data['rd_data5'] == '' ? 0 : $rate_data['rd_data5'];
                $rate_data['rd_data6'] = $rate_data['rd_data6'] == '' ? 0 : $rate_data['rd_data6'];
                $rate_data['rd_data7'] = $rate_data['rd_data7'] == '' ? 0 : $rate_data['rd_data7'];
                if ($rate_data['rd_data7'] > 0) $rd_data4_total += $rate_data['rd_data4'];
                if (!in_array($rate_data['rd_cate1'], $rd_cate1)) {
                    $rd_data4_sum = 0;
                    $rd_data5_sum = 0;
                    $rd_data6_sum = 0;
                    $rd_data7_sum = 0;
                    $rd_cate1[] = $rate_data['rd_cate1'];
                }

                $rd_data4_sum += $rate_data['rd_data4'];
                $rd_data5_sum += $rate_data['rd_data5'];
                $rd_data6_sum += $rate_data['rd_data6'];
                $rd_data7_sum += $rate_data['rd_data7'];


                if (isset($rate_data_fulfill4[$key + 1]) && $rate_data['rd_cate1'] != $rate_data_fulfill4[$key + 1]['rd_cate1']) {
                    $data = clone $rate_data;

                    $data['rd_cate2'] = '합계';
                    $data['rd_data1'] = '';
                    $data['rd_data2'] = '';
                    $data['rd_data4'] = $rd_data4_sum;
                    $data['rd_data5'] = $rd_data5_sum;
                    $data['rd_data6'] = $rd_data6_sum;
                    $data['rd_data7'] = $rd_data7_sum;

                    array_push($rate_data_fulfill4_, $data);
                }
                // if($key == count($rate_data_fulfill4) - 1) {

                //     $data = clone $rate_data;

                //     $data['rd_cate2'] = '합계';
                //     $data['rd_data1'] = '';
                //     $data['rd_data2'] = '';
                //     $data['rd_data4'] = $rd_data4_sum;
                //     $data['rd_data5'] = $rd_data5_sum;
                //     $data['rd_data6'] = $rd_data6_sum;
                //     $data['rd_data7'] = $rd_data7_sum;

                //     array_push($rate_data_fulfill4_, $data);
                // }
            }

            foreach ($rate_data_fulfill4_ as $key => $rate_data) {



                if ($rate_data['rd_cate1'] == $rd_cate1[0]) {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_fulfill4_[$key - 1]['rd_cate1'])) {
                        $sheet->setCellValue('B' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate1']);
                        $count_row_fulfill1 = 0;
                        if ($rate_data['rd_data7'] > 0) {

                            $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                            $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                            $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                            $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                            $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                            $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                            $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                            $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                            $count_row_fulfill1 += 1;
                            $count_row += 1;
                        }
                    } else if ($rate_data['rd_data7'] > 0) {

                        $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                        $count_row_fulfill1 += 1;
                        $count_row += 1;
                    }
                } else {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_fulfill4_[$key - 1]['rd_cate1'])) {
                        $sheet->mergeCells('B' . ($current_row_fulfill1) . ':B' . ($current_row_fulfill1 + $count_row_fulfill1 - 1));
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFont()->setBold(true);
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate1']);
                        $current_row_fulfill1 = $current_row_fulfill1 + $count_row_fulfill1;
                        $count_row_fulfill1 = 0;
                    } else if ($rate_data['rd_data7'] > 0) {


                        $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                        $count_row_fulfill1 += 1;
                        $count_row += 1;
                    }
                }

                if (count($rate_data_fulfill4_) == $key + 1) {
                    $sheet->mergeCells('B' . ($current_row_fulfill1) . ':B' . ($current_row_fulfill1 + $count_row_fulfill1 - 1));
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $current_row_fulfill1 = $current_row_fulfill1 + $count_row_fulfill1;
                    $count_row_fulfill1 = 0;
                }
            }
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getFont()->setBold(true);

            $sheet->mergeCells('B' . ($current_row - 2) . ':E' . ($current_row - 2));
            $sheet->getStyle('B' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B' . ($current_row - 2), '항목');


            foreach ($headers as $key => $header) {
                $sheet->mergeCells($col_start[$key] . ($current_row - 2) . ':' . $col_end[$key] . ($current_row - 2));
                $sheet->getStyle($col_start[$key] . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . ($current_row - 2), $header);
            }
            //FORMAT NUMBER
            $sheet->getStyle('F' . ($current_row - 1) . ':W' . ($current_row - 1 + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');
            $current_row += $count_row;

            $sheet->getStyle('B' . ($current_row - 1) . ':Z' . ($current_row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 1) . ':Z' . ($current_row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . ($current_row - 1))->getFont()->setBold(true);
            $sheet->setCellValue('B' . ($current_row - 1), '합계');

            $sheet->mergeCells('B' . ($current_row - 1) . ':E' . ($current_row - 1));
            $sheet->mergeCells('F' . ($current_row - 1) . ':H' . ($current_row - 1));
            $sheet->mergeCells('I' . ($current_row - 1) . ':K' . ($current_row - 1));
            $sheet->mergeCells('L' . ($current_row - 1) . ':N' . ($current_row - 1));
            $sheet->setCellValue('L' . ($current_row - 1), $rd_data4_total);
            $sheet->mergeCells('O' . ($current_row - 1) . ':Q' . ($current_row - 1));
            $sheet->setCellValue('O' . ($current_row - 1), $rgd->rate_data_general['rdg_supply_price4']);
            $sheet->mergeCells('R' . ($current_row - 1) . ':T' . ($current_row - 1));
            $sheet->setCellValue('R' . ($current_row - 1), $rgd->rate_data_general['rdg_vat4']);
            $sheet->mergeCells('U' . ($current_row - 1) . ':W' . ($current_row - 1));
            $sheet->setCellValue('U' . ($current_row - 1), $rgd->rate_data_general['rdg_sum4']);
            $sheet->mergeCells('X' . ($current_row - 1) . ':Z' . ($current_row - 1));
        }
        //END FULFILL4

        //FULFILL5
        if ($rgd->rate_data_general['rdg_sum5'] > 0) {
            $sheet->getStyle('B' . $current_row . ':Z' . $current_row)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
            $sheet->getStyle('B' . $current_row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
            $sheet->getStyle('B' . $current_row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $current_row . ':Z' . $current_row);
            $sheet->setCellValue('B' . $current_row, ' ∙ 부자재 상세');

            $current_row += 3;



            $count_row = 0;

            $count_row_fulfill1 = 0;
            $current_row_fulfill1 = $current_row - 1;

            $rd_cate1 = [];
            $rd_data4_sum = 0;
            $rd_data5_sum = 0;
            $rd_data6_sum = 0;
            $rd_data7_sum = 0;
            $rd_data4_total = 0;

            $rate_data_fulfill5_ = [];


            foreach ($rate_data_fulfill5 as $key => $rate_data) {
                array_push($rate_data_fulfill5_, $rate_data);
                $rate_data['rd_data4'] = $rate_data['rd_data4'] == '' ? 0 : $rate_data['rd_data4'];
                $rate_data['rd_data5'] = $rate_data['rd_data5'] == '' ? 0 : $rate_data['rd_data5'];
                $rate_data['rd_data6'] = $rate_data['rd_data6'] == '' ? 0 : $rate_data['rd_data6'];
                $rate_data['rd_data7'] = $rate_data['rd_data7'] == '' ? 0 : $rate_data['rd_data7'];
                if ($rate_data['rd_data7'] > 0) $rd_data4_total += $rate_data['rd_data4'];
                if (!in_array($rate_data['rd_cate1'], $rd_cate1)) {
                    $rd_data4_sum = 0;
                    $rd_data5_sum = 0;
                    $rd_data6_sum = 0;
                    $rd_data7_sum = 0;
                    $rd_cate1[] = $rate_data['rd_cate1'];
                }

                $rd_data4_sum += $rate_data['rd_data4'];
                $rd_data5_sum += $rate_data['rd_data5'];
                $rd_data6_sum += $rate_data['rd_data6'];
                $rd_data7_sum += $rate_data['rd_data7'];


                if (isset($rate_data_fulfill5[$key + 1]) && $rate_data['rd_cate1'] != $rate_data_fulfill5[$key + 1]['rd_cate1']) {
                    $data = clone $rate_data;

                    $data['rd_cate2'] = '합계';
                    $data['rd_data1'] = '';
                    $data['rd_data2'] = '';
                    $data['rd_data4'] = $rd_data4_sum;
                    $data['rd_data5'] = $rd_data5_sum;
                    $data['rd_data6'] = $rd_data6_sum;
                    $data['rd_data7'] = $rd_data7_sum;

                    array_push($rate_data_fulfill5_, $data);
                }
                // if($key == count($rate_data_fulfill5) - 1) {

                //     $data = clone $rate_data;

                //     $data['rd_cate2'] = '합계';
                //     $data['rd_data1'] = '';
                //     $data['rd_data2'] = '';
                //     $data['rd_data4'] = $rd_data4_sum;
                //     $data['rd_data5'] = $rd_data5_sum;
                //     $data['rd_data6'] = $rd_data6_sum;
                //     $data['rd_data7'] = $rd_data7_sum;

                //     array_push($rate_data_fulfill5_, $data);
                // }
            }

            foreach ($rate_data_fulfill5_ as $key => $rate_data) {



                if ($rate_data['rd_cate1'] == $rd_cate1[0]) {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_fulfill5_[$key - 1]['rd_cate1'])) {
                        $sheet->setCellValue('B' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate1']);
                        $count_row_fulfill1 = 0;
                        if ($rate_data['rd_data7'] > 0) {

                            $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                            $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                            $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                            $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                            $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                            $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                            $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                            $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                            $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                            $count_row_fulfill1 += 1;
                            $count_row += 1;
                        }
                    } else if ($rate_data['rd_data7'] > 0) {

                        $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                        $count_row_fulfill1 += 1;
                        $count_row += 1;
                    }
                } else {
                    if ($key == 0 || ($rate_data['rd_cate1'] != $rate_data_fulfill5_[$key - 1]['rd_cate1'])) {
                        $sheet->mergeCells('B' . ($current_row_fulfill1) . ':B' . ($current_row_fulfill1 + $count_row_fulfill1 - 1));
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFont()->setBold(true);
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate1']);
                        $current_row_fulfill1 = $current_row_fulfill1 + $count_row_fulfill1;
                        $count_row_fulfill1 = 0;
                    } else if ($rate_data['rd_data7'] > 0) {


                        $sheet->mergeCells('C' . ($current_row_fulfill1 + $count_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('C' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_cate2']);
                        $sheet->mergeCells('F' . ($current_row_fulfill1 + $count_row_fulfill1) . ':H' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('F' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data1']);
                        $sheet->mergeCells('I' . ($current_row_fulfill1 + $count_row_fulfill1) . ':K' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('I' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data2']);
                        $sheet->mergeCells('L' . ($current_row_fulfill1 + $count_row_fulfill1) . ':N' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('L' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data4']);
                        $sheet->mergeCells('O' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Q' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('O' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data5']);
                        $sheet->mergeCells('R' . ($current_row_fulfill1 + $count_row_fulfill1) . ':T' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('R' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data6']);
                        $sheet->mergeCells('U' . ($current_row_fulfill1 + $count_row_fulfill1) . ':W' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('U' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data7']);
                        $sheet->mergeCells('X' . ($current_row_fulfill1 + $count_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1));
                        $sheet->setCellValue('X' . ($current_row_fulfill1 + $count_row_fulfill1), $rate_data['rd_data8']);

                        $count_row_fulfill1 += 1;
                        $count_row += 1;
                    }
                }

                if (count($rate_data_fulfill5_) == $key + 1) {
                    $sheet->mergeCells('B' . ($current_row_fulfill1) . ':B' . ($current_row_fulfill1 + $count_row_fulfill1 - 1));
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':E' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row_fulfill1) . ':Z' . ($current_row_fulfill1 + $count_row_fulfill1 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $current_row_fulfill1 = $current_row_fulfill1 + $count_row_fulfill1;
                    $count_row_fulfill1 = 0;
                }
            }
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 2) . ':Z' . ($current_row - 2))->getFont()->setBold(true);

            $sheet->mergeCells('B' . ($current_row - 2) . ':E' . ($current_row - 2));
            $sheet->getStyle('B' . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B' . ($current_row - 2), '항목');


            foreach ($headers as $key => $header) {
                $sheet->mergeCells($col_start[$key] . ($current_row - 2) . ':' . $col_end[$key] . ($current_row - 2));
                $sheet->getStyle($col_start[$key] . ($current_row - 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . ($current_row - 2), $header);
            }
            //FORMAT NUMBER
            $sheet->getStyle('F' . ($current_row - 1) . ':W' . ($current_row - 1 + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');
            $current_row += $count_row;

            $sheet->getStyle('B' . ($current_row - 1) . ':Z' . ($current_row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . ($current_row - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . ($current_row - 1) . ':Z' . ($current_row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . ($current_row - 1))->getFont()->setBold(true);
            $sheet->setCellValue('B' . ($current_row - 1), '합계');

            $sheet->mergeCells('B' . ($current_row - 1) . ':E' . ($current_row - 1));
            $sheet->mergeCells('F' . ($current_row - 1) . ':H' . ($current_row - 1));
            $sheet->mergeCells('I' . ($current_row - 1) . ':K' . ($current_row - 1));
            $sheet->mergeCells('L' . ($current_row - 1) . ':N' . ($current_row - 1));
            $sheet->setCellValue('L' . ($current_row - 1), $rd_data4_total);
            $sheet->mergeCells('O' . ($current_row - 1) . ':Q' . ($current_row - 1));
            $sheet->setCellValue('O' . ($current_row - 1), $rgd->rate_data_general['rdg_supply_price5']);
            $sheet->mergeCells('R' . ($current_row - 1) . ':T' . ($current_row - 1));
            $sheet->setCellValue('R' . ($current_row - 1), $rgd->rate_data_general['rdg_vat5']);
            $sheet->mergeCells('U' . ($current_row - 1) . ':W' . ($current_row - 1));
            $sheet->setCellValue('U' . ($current_row - 1), $rgd->rate_data_general['rdg_sum5']);
            $sheet->mergeCells('X' . ($current_row - 1) . ':Z' . ($current_row - 1));
        }
        //END FULFILL5
        $sheet->setCellValue('B' . ($current_row), '');
        // $sheet->setCellValue('B'. ($current_row + 1), '1. 보세화물 서비스의 예상경비 청구서는 BL번호 단위로 발송됩니다.(단 분할인 경우 반출단위)');
        // $sheet->setCellValue('B'. ($current_row + 2), '2. 세금계산서 발행은 확정청구서와 함께 처리 됩니다.');
        $sheet->setCellValue('B' . ($current_row + 1), '1. 결제는 PC/Mobile에 접속하여서 결제하시면 되며, 월별 청구인 경우 매달 24일까지 결제가 되지 않으면 25일 등록 된 카드로 자동결제 됩니다.');
        $sheet->setCellValue('B' . ($current_row + 2), '2. 결제수단에 따라 수수료가 추가 청구 됩니다.(카드/카카오페이 2.9%, 실시간계좌이체 1.8% 등)');

        $issuer = Member::where('mb_no', $rgd->mb_no)->first();
        $company = Company::where('co_no', $issuer->co_no)->first();

        $sheet->getStyle('B' . ($current_row + 6) . ':Z' . ($current_row + 9))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
        $sheet->getStyle('B' . ($current_row + 6) . ':Z' . ($current_row + 9))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B' . ($current_row + 6) . ':Z' . ($current_row + 9))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->mergeCells('B' . ($current_row + 6) . ':Z' . ($current_row + 6));
        $sheet->setCellValue('B' . ($current_row + 6), $company->co_name);
        $sheet->mergeCells('B' . ($current_row + 7) . ':Z' . ($current_row + 7));
        $sheet->setCellValue('B' . ($current_row + 7), $company->co_address . ' ' . $company->co_address_detail);
        // $sheet->mergeCells('B'. ($current_row + 8). ':Z'. ($current_row + 8));
        // $sheet->setCellValue('B'. ($current_row + 8), $company->co_owner);
        $sheet->mergeCells('B' . ($current_row + 8) . ':Z' . ($current_row + 8));
        $sheet->setCellValue('B' . ($current_row + 8), $company->co_tel);
        $sheet->mergeCells('B' . ($current_row + 9) . ':Z' . ($current_row + 9));
        $sheet->setCellValue('B' . ($current_row + 9), $company->co_email);

        // $sheet->getDefaultRowDimension()->setRowHeight(24);
        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = 'storage/download/' . $user->mb_no . '/';
        } else {
            $path = 'storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }


        $name = 'fulfillment_final_monthbill_';


        $mask = $path . $name . '*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . $name . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => '../' . $file_name_download,
            'message' => 'Download File',
        ], 200);
        ob_end_clean();
    }

    public function download_settlement_list_excel(Request $request)
    {

        DB::beginTransaction();
        $user = Auth::user();
        $pathname = $request->header('pathname');
        $is_check_page = str_contains($pathname, 'check');

        $bonded_rgds = [];
        $distribution_rgds = [];
        $fulfillment_rgds = [];

        foreach ($request->rgds as $rgd) {
            if ($rgd['service_korean_name'] == '보세화물') {

                $rgd_ = ReceivingGoodsDelivery::with(['rate_data_general', 'warehousing', 't_import_expected'])->where('rgd_no', $rgd['rgd_no'])->first();
                $bonded_rgds[] = $rgd_;
            } else if ($rgd['service_korean_name'] == '유통가공') {

                $rgd_ = ReceivingGoodsDelivery::with(['rate_data_general', 'warehousing', 't_import_expected'])->where('rgd_no', $rgd['rgd_no'])->first();
                $distribution_rgds[] = $rgd_;
            } else if ($rgd['service_korean_name'] == '수입풀필먼트') {

                $rgd_ = ReceivingGoodsDelivery::with(['rate_data_general', 'warehousing', 't_import_expected'])->where('rgd_no', $rgd['rgd_no'])->first();
                $fulfillment_rgds[] = $rgd_;
            }
        }

        $bonded_rgds[] = $request->rgds[0];
        $distribution_rgds[] = $request->rgds[0];
        $fulfillment_rgds[] = $request->rgds[0];

        //BONDED LIST

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $sheet = $spreadsheet->getActiveSheet(0);

        // $sheet->getProtection()->setSheet(true);
        $sheet->getDefaultColumnDimension()->setWidth(4.5);
        // $sheet->getDefaultRowDimension()->setRowHeight(24);
        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }
        $sheet->getStyle('A1:S200')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:CT200')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->setTitle('보세화물');


        $sheet->mergeCells('B2:R6');
        $sheet->getStyle('B2:R6')->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->setCellValue('B2', '청구서 상세');
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2')->getFont()->setSize(22)->setBold(true);

        // $sheet->getStyle('R8')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        // $sheet->setCellValue('R8', '사업자번호 : ' );
        // $sheet->getStyle('R9')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        // $sheet->setCellValue('R9', '사업장 주소 : ');
        // $sheet->getStyle('R10')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        // $sheet->setCellValue('R10', '수신자명 : ');

        // $sheet->getStyle('B12:B17')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
        // $sheet->getStyle('B12:B17')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        // $sheet->getStyle('B12:B17')->getFont()->setBold(true);
        // $sheet->mergeCells('B12:R12');
        // $sheet->setCellValue('B12', ' ∙ 서   비  스 : 보세화물');
        // $sheet->mergeCells('B13:R13');
        // $sheet->setCellValue('B13', ' ∙ H-BL  건수 : ' . (count($bonded_rgds) - 1) . '건');
        // $sheet->mergeCells('B14:R14');
        // $sheet->setCellValue('B14', ' ∙ 청구서 No : ');
        // $sheet->mergeCells('B15:R15');
        // $sheet->setCellValue('B15', ' ∙ 청구서 발행일 : ');
        // $sheet->mergeCells('B16:R16');
        // $sheet->setCellValue('B16', ' ∙ 예상 청구금액 : ');
        // $sheet->mergeCells('B17:R17');
        // $sheet->setCellValue('B17', ' ∙ 계좌  정보 : ㈜');

        //GENERAL TABLE
        $sheet->getStyle('B8')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->getStyle('B8')->getFont()->setBold(true);
        $sheet->mergeCells('B8:R8');
        $sheet->setCellValue('B8', '∙ 보세화물');


        $sheet->getStyle('B9:R10')->getFont()->setBold(true);
        $sheet->getStyle('B9:R10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');

        $sheet->mergeCells('B9:E9');
        $sheet->setCellValue('B9', '구분');

        $sheet->mergeCells('F9:H9');
        $sheet->setCellValue('F9', '세금계산서 발행');

        $sheet->mergeCells('I9:K9');
        $sheet->setCellValue('I9', '세금계산서 미발행');

        $headers = ['NO', '정산번호', '화물관리번호', 'H-BL', '공급가', '부가세', '합계', '공급가', '부가세', '합계', '합계 (VAT포함)', 'BLP 센터비용', '관세사 비용', '포워더 비용', '국내운송료', '요건 비용', '기타'];
        $col_start = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R'];

        foreach ($headers as $key => $header) {

            if ($key == 0) {
                $sheet->getColumnDimension($col_start[$key])->setWidth(8);
                $sheet->getStyle($col_start[$key] . '10')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . '10', $header);
            } else if ($key == 1) {
                $sheet->getColumnDimension($col_start[$key])->setWidth(20);
                $sheet->getStyle($col_start[$key] . '10')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . '10', $header);
            } else if ($key == 2) {
                $sheet->getColumnDimension($col_start[$key])->setWidth(22);
                $sheet->getStyle($col_start[$key] . '10')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . '10', $header);
            } else if ($key == 3) {
                $sheet->getColumnDimension($col_start[$key])->setWidth(20);
                $sheet->getStyle($col_start[$key] . '10')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . '10', $header);
            } else if ($key < 10) {
                $sheet->getColumnDimension($col_start[$key])->setWidth(14);
                $sheet->getStyle($col_start[$key] . '10')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . '10', $header);
            } else if ($key >= 10) {
                $sheet->getColumnDimension($col_start[$key])->setWidth(14);
                $sheet->mergeCells($col_start[$key] . '9' . ':' . $col_start[$key] . '10');
                $sheet->getStyle($col_start[$key] . '9')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue($col_start[$key] . '9', $header);
            }
        }

        $current_row = 10;
        $count_row = 0;

        // return $bonded_rgds;

        $current_row += 1;
        $rdg_sum =  [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $rdg_supply_price =  [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $rdg_vat =  [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

        foreach ($bonded_rgds as $key => $rgd) {

            if ($key != (count($bonded_rgds) - 1)) {
                for ($counter = 0; $counter <= 13; $counter++) {
                    $rdg_sum[$counter] +=  $rgd['rate_data_general']['rdg_sum' . (1 + $counter)];
                    $rdg_supply_price[$counter] +=  $rgd['rate_data_general']['rdg_supply_price' . (1 + $counter)];
                    $rdg_vat[$counter] +=  $rgd['rate_data_general']['rdg_vat' . (1 + $counter)];
                }
            }

            foreach ($headers as $key_col => $header) {

                $sheet->getStyle($col_start[$key_col] . ($current_row + $key))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                if ($key_col == 0 && ($key != count($bonded_rgds) - 1)) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $key + 1);
                    $sheet->getStyle($col_start[$key_col] . ($current_row + $key))->getFont()->setBold(true);
                    $sheet->getStyle($col_start[$key_col] . ($current_row + $key))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                } else if ($key_col == 0 && ($key == count($bonded_rgds) - 1)) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), '합계');
                    $sheet->getStyle($col_start[$key_col] . ($current_row + $key))->getFont()->setBold(true);
                    $sheet->getStyle($col_start[$key_col] . ($current_row + $key))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                } else if ($key_col == 1 && ($key != count($bonded_rgds) - 1)) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rgd_settlement_number']);
                } else if ($key_col == 2 && ($key != count($bonded_rgds) - 1)) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['warehousing']['logistic_manage_number']);
                } else if ($key_col == 3 && ($key != count($bonded_rgds) - 1)) {
                    $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['t_import_expected']['tie_h_bl']);
                }
                if ($key == (count($bonded_rgds) - 1)) {
                    if ($key_col == 4) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rdg_supply_price[6]);
                    } else if ($key_col == 5) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rdg_vat[6]);
                    } else if ($key_col == 6) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rdg_sum[6]);
                    } else if ($key_col == 7) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rdg_supply_price[13]);
                    } else if ($key_col == 8) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rdg_vat[13]);
                    } else if ($key_col == 9) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rdg_sum[13]);
                    } else if ($key_col == 10) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rdg_sum[6]);
                    } else if ($key_col == 11) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rdg_supply_price[0]);
                    } else if ($key_col == 12) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rdg_supply_price[1]);
                    } else if ($key_col == 13) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rdg_supply_price[2]);
                    } else if ($key_col == 14) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rdg_supply_price[3]);
                    } else if ($key_col == 15) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rdg_supply_price[4]);
                    } else if ($key_col == 16) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), '');
                    }
                } else {
                    if ($key_col == 4) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_supply_price7'] ? $rgd['rate_data_general']['rdg_supply_price7'] : 0);
                    } else if ($key_col == 5) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_vat7'] ? $rgd['rate_data_general']['rdg_vat7'] : 0);
                    } else if ($key_col == 6) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_sum7'] ? $rgd['rate_data_general']['rdg_sum7'] : 0);
                    } else if ($key_col == 7) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_supply_price14'] ? $rgd['rate_data_general']['rdg_supply_price14'] : 0);
                    } else if ($key_col == 8) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_vat14'] ? $rgd['rate_data_general']['rdg_vat14'] : 0);
                    } else if ($key_col == 9) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_sum14'] ? $rgd['rate_data_general']['rdg_sum14'] : 0);
                    } else if ($key_col == 10) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_sum7'] ? $rgd['rate_data_general']['rdg_sum7'] : 0);
                    } else if ($key_col == 11) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_supply_price1'] ? $rgd['rate_data_general']['rdg_supply_price1'] : 0);
                    } else if ($key_col == 12) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_supply_price2'] ? $rgd['rate_data_general']['rdg_supply_price2'] : 0);
                    } else if ($key_col == 13) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_supply_price3'] ? $rgd['rate_data_general']['rdg_supply_price3'] : 0);
                    } else if ($key_col == 14) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_supply_price4'] ? $rgd['rate_data_general']['rdg_supply_price4'] : 0);
                    } else if ($key_col == 15) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), $rgd['rate_data_general']['rdg_supply_price5'] ? $rgd['rate_data_general']['rdg_supply_price5'] : 0);
                    } else if ($key_col == 16) {
                        $sheet->setCellValue($col_start[$key_col] . ($current_row + $key), '');
                    }
                }
            }
            $count_row = $key;
        }
        //FORMAT NUMBER
        $sheet->getStyle('C' . ($current_row) . ':R' . ($current_row + $count_row))->getNumberFormat()->setFormatCode('#,##0_-""');

        $current_row += $count_row;
        $sheet->getStyle('B9:R' . $current_row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->getStyle('B9:R' . $current_row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }

        //END BONDED LIST

        //FULFILLMENT LIST

        $this->fulfillment_settlement_list_excel($spreadsheet, $spreadsheet->createSheet(), $fulfillment_rgds);

        //END FULFILLMENT LIST

        //DISTRIBUTION LIST

        $this->distribution_settlement_list_excel($spreadsheet, $spreadsheet->createSheet(), $distribution_rgds);

        //END DISTRIBUTION LIST




        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = 'storage/download/' . $user->mb_no . '/';
        } else {
            $path = 'storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }

        $name = 'bonded_final_monthbill_';


        $mask = $path . $name . '*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . $name . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => '../' . $file_name_download,
            'message' => 'Download File',
            'rgds' => $bonded_rgds
        ], 200);
        ob_end_clean();
    }

    public function distribution_settlement_list_excel($spreadsheet, $sheet, $rgds)
    {

        // $sheet->getProtection()->setSheet(true);
        $sheet->getDefaultColumnDimension()->setWidth(4.5);
        // $sheet->getDefaultRowDimension()->setRowHeight(24);
        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(24);
        $sheet->getStyle('A1:Z200')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:CT200')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->setTitle('유통가공');


        $sheet->mergeCells('B2:Z6');
        $sheet->getStyle('B2:Z6')->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->setCellValue('B2', '청구서 상세');
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2')->getFont()->setSize(22)->setBold(true);

        //GENERAL TABLE
        $sheet->getStyle('B8')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->getStyle('B8')->getFont()->setBold(true);
        $sheet->mergeCells('B8:Z8');
        $sheet->setCellValue('B8', ' ∙ 유통가공');

        $headers = ['작업료', '보관료', '국내운송료', '공급가', '부가세', '급액', '비고'];
        $col_start = ['F', 'I', 'L', 'O', 'R', 'U', 'X'];
        $col_end = ['H', 'K', 'N', 'Q', 'T', 'W', 'Z'];

        $categories = ['작업료', '보관료', '국내운송료', '합계'];

        $current_row = 9;
        $count_row = 0;

        $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
        $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getFont()->setBold(true);

        $sheet->mergeCells('B' . ($current_row));
        $sheet->getStyle('B' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('B' . ($current_row), 'NO');

        $sheet->mergeCells('C' . ($current_row));
        $sheet->getStyle('C' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('C' . ($current_row), '예상경비청구서 No.');

        $sheet->mergeCells('D' . ($current_row));
        $sheet->getStyle('D' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('D' . ($current_row), '입고 화물번호');

        $sheet->mergeCells('E' . ($current_row));
        $sheet->getStyle('E' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('E' . ($current_row), '출고일자');


        foreach ($headers as $key => $header) {
            $sheet->mergeCells($col_start[$key] . ($current_row) . ':' . $col_end[$key] . ($current_row));
            $sheet->getStyle($col_start[$key] . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue($col_start[$key] . ($current_row), $header);
        }

        $current_row += 1;

        $rdg_sum =  [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $rdg_supply_price =  [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $rdg_vat =  [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

        foreach ($rgds as $key_rgd => $rgd) {

            $child_length = count($rgd['warehousing']['warehousing_child']);

            $sheet->mergeCells('B' . ($current_row));
            $sheet->getStyle('B' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . ($current_row))->getFont()->setBold(true);
            $sheet->getStyle('B' . ($current_row))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->setCellValue('B' . ($current_row), $key_rgd == (count($rgds) - 1) ? '합계' : $key_rgd + 1);

            $sheet->mergeCells('C' . ($current_row));
            $sheet->getStyle('C' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('C' . ($current_row), $key_rgd == (count($rgds) - 1) ? '' : $rgd['rgd_settlement_number']);

            $sheet->mergeCells('D' . ($current_row));
            $sheet->getStyle('D' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('D' . ($current_row), $key_rgd == (count($rgds) - 1) ? '' : $rgd['warehousing']['w_schedule_number2']);

            $sheet->mergeCells('E' . ($current_row));
            $sheet->getStyle('E' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('E' . ($current_row), $key_rgd == (count($rgds) - 1) ? '' : str_replace(' 00:00:00', '', isset($rgd['warehousing']['warehousing_child'][$child_length - 1]['w_completed_day'])  ? Carbon::createFromFormat('Y-m-d H:i:s', $rgd['warehousing']['warehousing_child'][$child_length - 1]['w_completed_day'])->format('Y.m.d') : ''));

            if ($key_rgd != (count($rgds) - 1)) {
                for ($counter = 0; $counter <= 13; $counter++) {
                    $rdg_sum[$counter] +=  $rgd['rate_data_general']['rdg_sum' . (1 + $counter)];
                    $rdg_supply_price[$counter] +=  $rgd['rate_data_general']['rdg_supply_price' . (1 + $counter)];
                    $rdg_vat[$counter] +=  $rgd['rate_data_general']['rdg_vat' . (1 + $counter)];
                }
            }

            foreach ($headers as $key => $header) {

                $sheet->mergeCells($col_start[$key] . ($current_row) . ':' . $col_end[$key] . ($current_row));
                $sheet->getStyle($col_start[$key] . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col_start[$key] . ($current_row))->getNumberFormat()->setFormatCode('#,##0_-""');

                if ($key_rgd == (count($rgds) - 1)) {
                    if ($key == 6) {
                        $sheet->setCellValue($col_start[$key] . ($current_row), '');
                    } else {
                        if ($key == 0) {

                            $sheet->setCellValue($col_start[$key] . ($current_row), $rdg_supply_price[1]);
                        }

                        if ($key == 1) {
                            $sheet->setCellValue($col_start[$key] . ($current_row), $rdg_supply_price[0]);
                        }

                        if ($key == 2) {
                            $sheet->setCellValue($col_start[$key] . ($current_row), $rdg_supply_price[2]);
                        }

                        if ($key == 3) {
                            $sheet->setCellValue($col_start[$key] . ($current_row), $rdg_supply_price[3]);
                        }

                        if ($key == 4) {
                            $sheet->setCellValue($col_start[$key] . ($current_row), $rdg_vat[3]);
                        }

                        if ($key == 5) {
                            $sheet->setCellValue($col_start[$key] . ($current_row), $rdg_sum[3]);
                        }
                    }
                } else {

                    if ($key == 0) {

                        $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_supply_price2']);
                    }

                    if ($key == 1) {
                        $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_supply_price1']);
                    }

                    if ($key == 2) {
                        $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_supply_price3']);
                    }

                    if ($key == 3) {
                        $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_supply_price4']);
                    }

                    if ($key == 4) {
                        $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_vat4']);
                    }

                    if ($key == 5) {
                        $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_sum4']);
                    }

                    if ($key == 6) {
                        $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_etc3']);
                    }
                }
            }

            $current_row += 1;
            $count_row += 1;
        }


        $sheet->getStyle('B' . ($current_row - $count_row) . ':Z' . ($current_row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));

        // $sheet->getDefaultRowDimension()->setRowHeight(24);
        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }

        return $spreadsheet;
    }

    public function fulfillment_settlement_list_excel($spreadsheet, $sheet, $rgds)
    {

        // $sheet->getProtection()->setSheet(true);
        $sheet->getDefaultColumnDimension()->setWidth(4.5);
        // $sheet->getDefaultRowDimension()->setRowHeight(24);
        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(24);
        $sheet->getStyle('A1:Z200')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:CT200')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->setTitle('수입풀필먼트');


        $sheet->mergeCells('B2:Z6');
        $sheet->getStyle('B2:Z6')->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->setCellValue('B2', '청구서 상세');
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2')->getFont()->setSize(22)->setBold(true);


        //GENERAL TABLE
        $sheet->getStyle('B8')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
        $sheet->getStyle('B8')->getFont()->setBold(true);
        $sheet->mergeCells('B8:Z8');
        $sheet->setCellValue('B8', ' ∙ 수입풀필먼트');

        $headers = ['센터작업료', '국내 운송료', '해외 운송료', '보관', '부자재', '합계', '비고'];
        $col_start = ['F', 'I', 'L', 'O', 'R', 'U', 'X'];
        $col_end = ['H', 'K', 'N', 'Q', 'T', 'W', 'Z'];

        $categories = ['작업료', '보관료', '국내운송료', '합계'];

        $current_row = 9;
        $count_row = 0;

        $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
        $sheet->getStyle('B' . ($current_row) . ':Z' . ($current_row))->getFont()->setBold(true);

        $sheet->mergeCells('B' . ($current_row));
        $sheet->getStyle('B' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('B' . ($current_row), 'NO');

        $sheet->mergeCells('C' . ($current_row));
        $sheet->getStyle('C' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('C' . ($current_row), '청구서 NO.');

        $sheet->mergeCells('D' . ($current_row));
        $sheet->getStyle('D' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('D' . ($current_row), '입고 화물번호');

        $sheet->mergeCells('E' . ($current_row));
        $sheet->getStyle('E' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('E' . ($current_row), '등록일');


        foreach ($headers as $key => $header) {
            $sheet->mergeCells($col_start[$key] . ($current_row) . ':' . $col_end[$key] . ($current_row));
            $sheet->getStyle($col_start[$key] . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue($col_start[$key] . ($current_row), $header);
        }

        $current_row += 1;
        $rdg_sum =  [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $rdg_supply_price =  [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $rdg_vat =  [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];


        foreach ($rgds as $key_rgd => $rgd) {

            if ($key_rgd != (count($rgds) - 1)) {
                for ($counter = 0; $counter <= 13; $counter++) {
                    $rdg_sum[$counter] +=  $rgd['rate_data_general']['rdg_sum' . (1 + $counter)];
                    $rdg_supply_price[$counter] +=  $rgd['rate_data_general']['rdg_supply_price' . (1 + $counter)];
                    $rdg_vat[$counter] +=  $rgd['rate_data_general']['rdg_vat' . (1 + $counter)];
                }
            }

            $child_length = count($rgd['warehousing']['warehousing_child']);

            $sheet->mergeCells('B' . ($current_row));
            $sheet->getStyle('B' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . ($current_row))->getFont()->setBold(true);
            $sheet->getStyle('B' . ($current_row))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->setCellValue('B' . ($current_row), $key_rgd == (count($rgds) - 1) ? '합계' : $key_rgd + 1);

            $sheet->mergeCells('C' . ($current_row));
            $sheet->getStyle('C' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('C' . ($current_row), $key_rgd == (count($rgds) - 1) ? '' : $rgd['rgd_settlement_number']);

            $sheet->mergeCells('D' . ($current_row));
            $sheet->getStyle('D' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('D' . ($current_row), $key_rgd == (count($rgds) - 1) ? '' : (isset($rgd['warehousing']['settlement_cargo']) ? $rgd['warehousing']['settlement_cargo']['w_schedule_number2'] : ''));

            $sheet->mergeCells('E' . ($current_row));
            $sheet->getStyle('E' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('E' . ($current_row), $key_rgd == (count($rgds) - 1) ? '' : str_replace(' 00:00:00', '', isset($rgd['created_at'])  ? Carbon::createFromFormat('Y-m-d H:i:s', $rgd['created_at'])->format('Y.m.d') : ''));


            foreach ($headers as $key => $header) {

                $sheet->mergeCells($col_start[$key] . ($current_row) . ':' . $col_end[$key] . ($current_row));
                $sheet->getStyle($col_start[$key] . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col_start[$key] . ($current_row))->getNumberFormat()->setFormatCode('#,##0_-""');
                if ($key_rgd == (count($rgds) - 1)) {
                    if ($key == 6) {
                        $sheet->setCellValue($col_start[$key] . ($current_row), '');
                    } else {
                        $sheet->setCellValue($col_start[$key] . ($current_row), $rdg_sum[$key]);
                    }
                } else {
                    if ($key == 0) {

                        $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_sum1']);
                    }

                    if ($key == 1) {
                        $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_sum2']);
                    }

                    if ($key == 2) {
                        $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_sum3']);
                    }

                    if ($key == 3) {
                        $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_sum4']);
                    }

                    if ($key == 4) {
                        $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_sum5']);
                    }

                    if ($key == 5) {
                        $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_sum6']);
                    }

                    if ($key == 6) {
                        $sheet->setCellValue($col_start[$key] . ($current_row), $rgd['rate_data_general']['rdg_etc3']);
                    }
                }
            }

            $current_row += 1;
            $count_row += 1;
        }


        $sheet->getStyle('B' . ($current_row - $count_row) . ':Z' . ($current_row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));

        // $sheet->getDefaultRowDimension()->setRowHeight(24);
        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
        }

        return $spreadsheet;
    }

    public function download_data_casebill_edit_backup($rgd_no)
    {
        Log::error($rgd_no);
        DB::beginTransaction();


        $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'additional')->first();

        Log::error($rgd_no);
        Log::error($rdg);
        if (!isset($rdg->rdg_no)) {
            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'final')->first();
        }

        if (!isset($rdg->rdg_no)) {
            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->where('rdg_bill_type', 'expectation_spasys')->first();
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
    public function download_pdf_send_meta($rm_no, $rmd_no)
    {
        $user = Auth::user();
        $rmd_last = RateMetaData::where('rm_no', $rm_no)->orderBy('rmd_no', 'desc')->first();
        $rate_data_send_meta = $this->getRateDataRaw($rm_no, $rmd_last['rmd_no']);
        $rmd = RateMetaData::where('rm_no', $rm_no)->first();
        $rate_meta = RateMeta::where('rm_no', $rm_no)->first();
        $member = Member::where('mb_no', $rmd_last->mb_no)->first();
        $co_info = Company::where('co_no', $member->co_no)->first();

        // $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = 'storage/download/' . $user->mb_no . '/';
        } else {
            $path = 'storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . '요율발송_*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . '요율발송_' . $rmd['rmd_number'] . '.pdf';
        // $check_status = $Excel_writer->save($file_name_download);
        $count1 = 0;
        $count2 = 0;
        $count3 = 1;
        $array1 = [];

        $count1_2 = 0;
        $count2_2 = 0;
        $count3_2 = 1;
        $array2 = [];

        $count1_3 = 0;
        $count2_3 = 0;
        $count3_3 = 1;
        $array3 = [];
        if (count($rate_data_send_meta['rate_data1']) > 0) {
            foreach ($rate_data_send_meta['rate_data1'] as $key => $row) {
                if ($key <= 9 && $key != 2 && $key != 4 && $key != 6 && $key != 8 && ($row['rd_data2'] || $row['rd_data1'])) {
                    $array1[] = $key;
                    $count1 += 1;
                }
                if ($key == 11 && ($row['rd_data2'] || $row['rd_data1'])) {
                    $count2 = 2;
                    $array1[] = $key;
                }
                if (($key == 14 && ($row['rd_data1'])) ||  ($key == 13 || $key == 12) && ($row['rd_data2'] || $row['rd_data1'])) {
                    $count3 += 1;
                    $array1[] = $key;
                }
                if ($key >= 15 && $key <= 24 && $key != 17 && $key != 19 && $key != 21 && $key != 23 && ($row['rd_data2'] || $row['rd_data1'])) {
                    $array2[] = $key;
                    $count1_2 += 1;
                }
                if ($key >= 15 && $key == 26 && ($row['rd_data2'] || $row['rd_data1'])) {
                    $count2_2 = 2;
                    $array2[] = $key;
                }
                if (($key == 29 && ($row['rd_data1'])) ||  ($key == 27 || $key == 28) && ($row['rd_data2'] || $row['rd_data1'])) {
                    $count3_2 += 1;
                    $array2[] = $key;
                }
                if ($key >= 30 && $key <= 39 && $key != 32 && $key != 34 && $key != 36 && $key != 38 && ($row['rd_data2'] || $row['rd_data1'])) {
                    $array3[] = $key;
                    $count1_3 += 1;
                }
                if ($key >= 30 && $key == 41 && ($row['rd_data2'] || $row['rd_data1'])) {
                    $count2_3 = 2;
                    $array3[] = $key;
                }
                if (($key == 44 && ($row['rd_data1'])) ||  ($key == 42 || $key == 43) && ($row['rd_data2'] || $row['rd_data1'])) {
                    $count3_3 += 1;
                    $array3[] = $key;
                }
            }
        }
        $count_row2 = $count2 + $count3;
        $count_row2_2 = $count2_2 + $count3_2;
        $count_row2_3 = $count2_3 + $count3_3;
        $count_service2_1 = 0;
        $count_service2_2 = 0;
        $count_service2_3 = 0;
        $count_service2_4 = 0;
        $count_service2_5 = 0;
        $count_service2_6 = 0;
        if (count($rate_data_send_meta['rate_data2']) > 0) {
            foreach ($rate_data_send_meta['rate_data2'] as $key => $row) {
                if (($key == 1 || $key == 2 || $key == 3 || $key == 4) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service2_1 += 1;
                }
                if (($key == 5 || $key == 6 || $key == 7 || $key == 8 || $key == 9 || $key == 10) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service2_2 += 1;
                }
                if (($key == 0 || $key == 24 || $key == 25) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service2_3 += 1;
                }
                if (($key == 27 || $key == 26) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service2_4 += 1;
                }
                if (($key == 14 || $key == 15 || $key == 16 || $key == 17 || $key == 18 || $key == 19) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service2_5 += 1;
                }
                if (($key == 22 || $key == 23) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service2_6 += 1;
                }
            }
        }
        $count_service3_1 = 0;
        $count_service3_2 = 0;
        $count_service3_3 = 0;
        if (count($rate_data_send_meta['rate_data3']) > 0) {
            foreach ($rate_data_send_meta['rate_data3'] as $key => $row) {
                if (($key == 0 || $key == 1 || $key == 2 || $key == 3) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service3_1 += 1;
                }
                if (($key == 5 || $key == 6 || $key == 4) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service3_2 += 1;
                }
                if (($key == 7 || $key == 8) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service3_3 += 1;
                }
            }
        }

        $pdf = Pdf::loadView('pdf.test', [
            'rate_data_send_meta' => $rate_data_send_meta,
            'count1' => $count1, 'array1' => $array1, 'count2' => $count2, 'count_row2' => $count_row2, 'count3' => $count3,
            'count1_2' => $count1_2, 'array2' => $array2, 'count2_2' => $count2_2, 'count_row2_2' => $count_row2_2, 'count3_2' => $count3_2,
            'count1_3' => $count1_3, 'array3' => $array3, 'count2_3' => $count2_3, 'count_row2_3' => $count_row2_3, 'count3_3' => $count3_3,
            'count_service2_1' => $count_service2_1, 'count_service2_2' => $count_service2_2, 'count_service2_3' => $count_service2_3,
            'count_service2_4' => $count_service2_4, 'count_service2_5' => $count_service2_5, 'count_service2_6' => $count_service2_6,
            'count_service3_1' => $count_service3_1, 'count_service3_2' => $count_service3_2, 'count_service3_3' => $count_service3_3,
            'rm_biz_name' => $rate_meta['rm_biz_name'], 'rm_biz_number' => $rate_meta['rm_biz_number'], 'rm_biz_address' => $rate_meta['rm_biz_address'],
            'rm_owner_name' => $rate_meta['rm_owner_name'], 'rm_biz_email' => $rate_meta['rm_biz_email'], 'rmd_mail_detail1a' => nl2br($rmd_last['rmd_mail_detail1a']),
            'rmd_mail_detail1b' => nl2br($rmd_last['rmd_mail_detail1b']), 'rmd_mail_detail1c' => nl2br($rmd_last['rmd_mail_detail1c']),
            'rm_mail_detail2' => nl2br($rate_meta['rm_mail_detail2']), 'rm_mail_detail3' => nl2br($rate_meta['rm_mail_detail3']),
            'co_name' => $co_info['co_name'], 'co_address' => $co_info['co_address'], 'co_address_detail' => $co_info['co_address_detail'], 'co_tel' => $co_info['co_tel'], 'co_email' => $co_info['co_email'], 'date' => Date('Y-m-d', strtotime($rmd_last['created_at']))
        ]);
        $pdf->save($file_name_download);
        return response()->json([
            'status' => 1,
            'link_download' => $file_name_download,
            'message' => 'Download File',
            'namefile' => '요율발송_' . $rmd['rmd_number'] . '.pdf',
        ], 200);
        ob_end_clean();
    }
    public function download_excel_send_meta($rm_no, $rmd_no)
    {


        DB::beginTransaction();
        $co_no = Auth::user()->co_no;
        $rmd_last = RateMetaData::where('rm_no', $rm_no)->orderBy('rmd_no', 'desc')->first();
        $rate_data_send_meta = $this->getRateDataRaw($rm_no, $rmd_last['rmd_no']);
        $member = Member::where('mb_no', $rmd_last->mb_no)->first();
        $co_info = Company::where('co_no', $member->co_no)->first();
        DB::commit();
        $user = Auth::user();
        $rate_meta = RateMeta::where('rm_no', $rm_no)->first();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $sheet = $spreadsheet->getActiveSheet(0);
        $sheet->getDefaultColumnDimension()->setWidth(4.5);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(false);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getStyle('A1:Z200')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:CT200')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->setTitle('보세화물');

        $sheet->mergeCells('B2:Z6');
        $sheet->getStyle('B2:Z6')->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
        $sheet->setCellValue('B2', $rate_meta->rm_biz_name);
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2')->getFont()->setSize(22)->setBold(true);
        $sheet->getStyle('Z8')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Z8', '사업자번호 : ' . $rate_meta->rm_biz_number);
        $sheet->getStyle('Z9')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Z9', '사업장 주소 : ' . $rate_meta->rm_biz_address);
        if ($rate_meta->rm_owner_name && $rate_meta->rm_biz_email) {
            $sheet->getStyle('Z10')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue('Z10', '대표자명 : ' . $rate_meta->rm_owner_name . ' (' . $rate_meta->rm_biz_email . ')');
        } else if ($rate_meta->rm_owner_name && !$rate_meta->rm_biz_email) {
            $sheet->getStyle('Z10')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue('Z10', '대표자명 : ' . $rate_meta->rm_owner_name);
        }

        if (!empty($rate_data_send_meta['rate_data1']) && count($rate_data_send_meta['rate_data1']) > 0) {
            $table1 = 0;
            $table2 = 0;
            $table3 = 0;
            for ($i = 0; $i < 3; $i++) {
                if ($i == 0) {
                    $line1 = 'B13';
                    $line2 = 'Z13';
                    $title1 = 14;
                    $title2 = 15;
                    $content_line1 = '서비스: 보세화물 (창고화물)';
                    $line3 = [16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27];
                } else if ($i == 1) {
                    if ($table1 > 0) {
                        $line1 = 'B36';
                        $line2 = 'Z36';
                        $title1 = 37;
                        $title2 = 38;
                        $line3 = [39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50];
                    } else {
                        $line1 = 'B13';
                        $line2 = 'Z13';
                        $title1 = 14;
                        $title2 = 15;
                        $content_line1 = '서비스: 보세화물 (온도화물)';
                        $line3 = [16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27];
                    }
                    $content_line1 = '서비스: 보세화물 (온도화물)';
                } else if ($i == 2) {
                    if ($table2 > 0 && $table1 > 0) {
                        $line1 = 'B59';
                        $line2 = 'Z59';
                        $title1 = 60;
                        $title2 = 61;
                        $content_line1 = '서비스: 보세화물 (위험물)';
                        $line3 = [62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73];
                    } else if ($table2 == 0 && $table1 == 0) {
                        $line1 = 'B13';
                        $line2 = 'Z13';
                        $title1 = 14;
                        $title2 = 15;
                        $content_line1 = '서비스: 보세화물 (위험물)';
                        $line3 = [16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27];
                    } else {
                        $line1 = 'B36';
                        $line2 = 'Z36';
                        $title1 = 37;
                        $title2 = 38;
                        $line3 = [39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50];
                        $content_line1 = '서비스: 보세화물 (위험물)';
                    }
                }

                $sheet->getStyle($line1)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
                $sheet->getStyle($line1)->getFont()->setBold(true);
                $sheet->mergeCells($line1 . ':' . $line2);
                $sheet->setCellValue($line1, $content_line1);

                $sheet->getStyle('B' . ($title1) . ':F' . ($title1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                $sheet->getStyle('B' . ($title1) . ':F' . ($title1))->getFont()->setBold(true);
                $sheet->getStyle('B' . ($title1) . ':Z' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('B' . ($title1) . ':Z' . ($title2))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                $sheet->getStyle('B' . ($title1) . ':Z' . ($title2))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                $sheet->getStyle('B' . ($title1) . ':Z' . ($title2))->getFont()->setBold(true);


                $sheet->mergeCells('B' . ($title1) . ':F' . ($title1));
                $sheet->getStyle('B' . ($title1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('B' . ($title1), '구분');



                $sheet->mergeCells('G' . ($title1) . ':Z' . ($title1));
                $sheet->getStyle('G' . ($title1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('G' . ($title1), '내역');




                if ($i == 0) {
                    $count  = 0;
                    $count2  = 0;
                    $count3  = 0;
                    $count4 = 0;
                    $count5 = 0;
                    $count6 = 0;
                    $array_hide = [];
                    $array_hide2 = [];
                    foreach ($rate_data_send_meta['rate_data1'] as $key => $row) {
                        if ($key != 2 && $key <= 9 && $key != 4 && $key != 8 && $key != 6 && ($row['rd_data2'] || $row['rd_data1'])) {
                            $sheet->getStyle('B' . ($line3[0] + $count) . ':P' . ($line3[0] + $count))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                            $sheet->getStyle('B' . ($line3[0] + $count) . ':P' . ($line3[0] + $count))->getFont()->setBold(true);
                            $sheet->getStyle('B' . ($line3[0] + $count) . ':Z' . ($line3[0] + $count))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                            $sheet->getStyle('B' . ($line3[0] + $count) . ':Z' . ($line3[0] + $count))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->mergeCells('L' . ($line3[0] + $count) . ':P' . ($line3[0] + $count));
                            $sheet->setCellValue('L' . ($line3[0] + $count), $row['rd_cate3']);
                            $sheet->getStyle('Q' . ($line3[0] + $count))->getNumberFormat()->setFormatCode('#,##0_-""');
                            $sheet->mergeCells('Q' . ($line3[0] + $count) . ':U' . ($line3[0] + $count));
                            $sheet->setCellValue('Q' . ($line3[0] + $count), $row['rd_data1']);
                            $sheet->getStyle('V' . ($line3[0] + $count))->getNumberFormat()->setFormatCode('#,##0_-""');
                            $sheet->mergeCells('V' . ($line3[0] + $count) . ':Z' . ($line3[0] + $count));
                            $sheet->setCellValue('V' . ($line3[0] + $count), $row['rd_data2']);
                            $count++;
                        } else if ($key != 2 && $key <= 9 && $key != 4 && $key != 8 && $key != 6 && (!$row['rd_data2'] && !$row['rd_data1'])) {
                            $count3++;
                            $array_hide[] = $key;
                        }

                        if ($count == 0) {
                            $data = 1;
                        } else {
                            $data = 0;
                        }


                        if ($key == 11 || $key == 12 || $key == 13) {
                            if ($key == 11 && (!$row['rd_data2'] && !$row['rd_data1'])) {
                                $count4 = 2;
                            }

                            if ($key == 11  && ($row['rd_data2'] || $row['rd_data1'])) {
                                $sheet->getStyle('G' . ($line3[7] - $count3 - $data) . ':P' . ($line3[7] - $count3 - $data))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                                $sheet->getStyle('G' . ($line3[7] - $count3 - $data) . ':P' . ($line3[7] - $count3 - $data))->getFont()->setBold(true);
                                $sheet->getStyle('B' . ($line3[7] - $count3 - $data) . ':Z' . ($line3[7] - $count3 - $data))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                                $sheet->getStyle('B' . ($line3[7] - $count3 - $data) . ':Z' . ($line3[7] - $count3 - $data))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                                $sheet->mergeCells('G' . ($line3[7] - $count3 - $data) . ':P' . ($line3[7] - $count3 - $data));
                                $sheet->setCellValue('G' . ($line3[7] - $count3 - $data), '작업료');
                                $sheet->getStyle('Q' . ($line3[7] - $count3 - $data))->getNumberFormat()->setFormatCode('#,##0_-""');
                                $sheet->mergeCells('Q' . ($line3[7] - $count3 - $data) . ':U' . ($line3[7] - $count3 - $data));
                                $sheet->setCellValue('Q' . ($line3[7] - $count3 - $data), $row['rd_data1']);
                                $sheet->getStyle('V' . ($line3[7] - $count3 - $data))->getNumberFormat()->setFormatCode('#,##0_-""');
                                $sheet->mergeCells('V' . ($line3[7] - $count3 - $data) . ':Z' . ($line3[7] - $count3 - $data));
                                $sheet->setCellValue('V' . ($line3[7] - $count3 - $data), $row['rd_data2']);
                            } else if (($key == 12 || $key == 13)  && ($row['rd_data2'] || $row['rd_data1'])) {
                                $sheet->getStyle('G' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':P' . ($line3[9] + $count2 - $count3 - $data - $count4))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                                $sheet->getStyle('G' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':P' . ($line3[9] + $count2 - $count3 - $data - $count4))->getFont()->setBold(true);
                                $sheet->getStyle('B' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':Z' . ($line3[9] + $count2 - $count3 - $data - $count4))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                                $sheet->getStyle('B' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':Z' . ($line3[9] + $count2 - $count3 - $data - $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                                $sheet->mergeCells('G' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':P' . ($line3[9] + $count2 - $count3 - $data - $count4));
                                $sheet->setCellValue('G' . ($line3[9] + $count2 - $count3 - $data - $count4), $row['rd_cate3']);
                                // $sheet->getStyle('Q'.($line3[9] + $count2))->getNumberFormat()->setFormatCode('#,##0_-""');
                                $sheet->mergeCells('Q' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':U' . ($line3[9] + $count2 - $count3 - $data - $count4));
                                $sheet->setCellValue('Q' . ($line3[9] + $count2 - $count3 - $data - $count4), $row['rd_data1']);
                                // $sheet->getStyle('V'.($line3[9] + $count2))->getNumberFormat()->setFormatCode('#,##0_-""');
                                $sheet->mergeCells('V' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':Z' . ($line3[9] + $count2 - $count3 - $data - $count4));
                                $sheet->setCellValue('V' . ($line3[9] + $count2 - $count3 - $data - $count4), $row['rd_data2']);
                                $count2++;
                            }
                            if (($key == 12 || $key == 13) && (!$row['rd_data2'] && !$row['rd_data1'])) {
                                $count5 += 1;
                                $array_hide2[] = $key;
                            }
                        }
                        if ($key == 14 && $row['rd_data1']) {
                            $sheet->getStyle('G' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':P' . ($line3[11] - $count3 - $data - $count4 - $count5))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                            $sheet->getStyle('G' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':P' . ($line3[11] - $count3 - $data - $count4 - $count5))->getFont()->setBold(true);
                            $sheet->getStyle('B' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                            $sheet->getStyle('B' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                            $sheet->mergeCells('G' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':P' . ($line3[11] - $count3 - $data - $count4 - $count5));
                            $sheet->setCellValue('G' . ($line3[11] - $count3 - $data - $count4 - $count5), '할인율');
                            // $sheet->getStyle('Q'.($line3[11]))->getNumberFormat()->setFormatCode('#,##0_-""');
                            $sheet->mergeCells('Q' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5));
                            $sheet->setCellValue('Q' . ($line3[11] - $count3 - $data - $count4 - $count5), $row['rd_data1']);
                            $count6 = 1;
                        }
                        if ($key == 14 && (!$row['rd_data1'])) {
                            $count5 += 1;
                            $array_hide2[] = $key;
                        }
                    }
                } else if ($i == 1) {
                    $count  = 0;
                    $count2  = 0;
                    $count3  = 0;
                    $count4 = 0;
                    $count5 = 0;
                    $count6 = 0;
                    $array_hide = [];
                    $array_hide2 = [];
                    foreach ($rate_data_send_meta['rate_data1'] as $key => $row) {
                        if ($key >= 15 && $key != 17 && $key <= 24 && $key != 19 && $key != 23 && $key != 21 && ($row['rd_data2'] || $row['rd_data1'])) {
                            $sheet->getStyle('B' . ($line3[0] + $count) . ':P' . ($line3[0] + $count))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                            $sheet->getStyle('B' . ($line3[0] + $count) . ':P' . ($line3[0] + $count))->getFont()->setBold(true);
                            $sheet->getStyle('B' . ($line3[0] + $count) . ':Z' . ($line3[0] + $count))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                            $sheet->getStyle('B' . ($line3[0] + $count) . ':Z' . ($line3[0] + $count))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->mergeCells('L' . ($line3[0] + $count) . ':P' . ($line3[0] + $count));
                            $sheet->setCellValue('L' . ($line3[0] + $count), $row['rd_cate3']);
                            $sheet->getStyle('Q' . ($line3[0] + $count))->getNumberFormat()->setFormatCode('#,##0_-""');
                            $sheet->mergeCells('Q' . ($line3[0] + $count) . ':U' . ($line3[0] + $count));
                            $sheet->setCellValue('Q' . ($line3[0] + $count), $row['rd_data1']);
                            $sheet->getStyle('V' . ($line3[0] + $count))->getNumberFormat()->setFormatCode('#,##0_-""');
                            $sheet->mergeCells('V' . ($line3[0] + $count) . ':Z' . ($line3[0] + $count));
                            $sheet->setCellValue('V' . ($line3[0] + $count), $row['rd_data2']);
                            $count++;
                        } else if ($key >= 15 && $key != 17 && $key <= 24 && $key != 19 && $key != 23 && $key != 21 && (!$row['rd_data2'] && !$row['rd_data1'])) {
                            $count3++;
                            $array_hide[] = $key;
                        }

                        if ($count == 0) {
                            $data = 1;
                        } else {
                            $data = 0;
                        }


                        if ($key >= 15 && ($key == 26 || $key == 27 || $key == 28)) {
                            if ($key == 26 && (!$row['rd_data2'] && !$row['rd_data1'])) {
                                $count4 = 2;
                            }

                            if ($key == 26  && ($row['rd_data2'] || $row['rd_data1'])) {
                                $sheet->getStyle('G' . ($line3[7] - $count3 - $data) . ':P' . ($line3[7] - $count3 - $data))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                                $sheet->getStyle('G' . ($line3[7] - $count3 - $data) . ':P' . ($line3[7] - $count3 - $data))->getFont()->setBold(true);
                                $sheet->getStyle('B' . ($line3[7] - $count3 - $data) . ':Z' . ($line3[7] - $count3 - $data))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                                $sheet->getStyle('B' . ($line3[7] - $count3 - $data) . ':Z' . ($line3[7] - $count3 - $data))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                                $sheet->mergeCells('G' . ($line3[7] - $count3 - $data) . ':P' . ($line3[7] - $count3 - $data));
                                $sheet->setCellValue('G' . ($line3[7] - $count3 - $data), '작업료');
                                $sheet->getStyle('Q' . ($line3[7] - $count3 - $data))->getNumberFormat()->setFormatCode('#,##0_-""');
                                $sheet->mergeCells('Q' . ($line3[7] - $count3 - $data) . ':U' . ($line3[7] - $count3 - $data));
                                $sheet->setCellValue('Q' . ($line3[7] - $count3 - $data), $row['rd_data1']);
                                $sheet->getStyle('V' . ($line3[7] - $count3 - $data))->getNumberFormat()->setFormatCode('#,##0_-""');
                                $sheet->mergeCells('V' . ($line3[7] - $count3 - $data) . ':Z' . ($line3[7] - $count3 - $data));
                                $sheet->setCellValue('V' . ($line3[7] - $count3 - $data), $row['rd_data2']);
                            } else if (($key == 27 || $key == 28)  && ($row['rd_data2'] || $row['rd_data1'])) {
                                $sheet->getStyle('G' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':P' . ($line3[9] + $count2 - $count3 - $data - $count4))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                                $sheet->getStyle('G' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':P' . ($line3[9] + $count2 - $count3 - $data - $count4))->getFont()->setBold(true);
                                $sheet->getStyle('B' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':Z' . ($line3[9] + $count2 - $count3 - $data - $count4))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                                $sheet->getStyle('B' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':Z' . ($line3[9] + $count2 - $count3 - $data - $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                                $sheet->mergeCells('G' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':P' . ($line3[9] + $count2 - $count3 - $data - $count4));
                                $sheet->setCellValue('G' . ($line3[9] + $count2 - $count3 - $data - $count4), $row['rd_cate3']);
                                // $sheet->getStyle('Q'.($line3[9] + $count2))->getNumberFormat()->setFormatCode('#,##0_-""');
                                $sheet->mergeCells('Q' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':U' . ($line3[9] + $count2 - $count3 - $data - $count4));
                                $sheet->setCellValue('Q' . ($line3[9] + $count2 - $count3 - $data - $count4), $row['rd_data1']);
                                // $sheet->getStyle('V'.($line3[9] + $count2))->getNumberFormat()->setFormatCode('#,##0_-""');
                                $sheet->mergeCells('V' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':Z' . ($line3[9] + $count2 - $count3 - $data - $count4));
                                $sheet->setCellValue('V' . ($line3[9] + $count2 - $count3 - $data - $count4), $row['rd_data2']);
                                $count2++;
                            }
                            if (($key == 27 || $key == 28) && (!$row['rd_data2'] && !$row['rd_data1'])) {
                                $count5 += 1;
                                $array_hide2[] = $key;
                            }
                        }
                        if ($key == 29 && $row['rd_data1']) {
                            $sheet->getStyle('G' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':P' . ($line3[11] - $count3 - $data - $count4 - $count5))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                            $sheet->getStyle('G' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':P' . ($line3[11] - $count3 - $data - $count4 - $count5))->getFont()->setBold(true);
                            $sheet->getStyle('B' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                            $sheet->getStyle('B' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                            $sheet->mergeCells('G' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':P' . ($line3[11] - $count3 - $data - $count4 - $count5));
                            $sheet->setCellValue('G' . ($line3[11] - $count3 - $data - $count4 - $count5), '할인율');
                            // $sheet->getStyle('Q'.($line3[11]))->getNumberFormat()->setFormatCode('#,##0_-""');
                            $sheet->mergeCells('Q' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5));
                            $sheet->setCellValue('Q' . ($line3[11] - $count3 - $data - $count4 - $count5), $row['rd_data1']);
                            $count6 = 1;
                        }
                        if ($key == 29 && (!$row['rd_data1'])) {
                            $count5 += 1;
                            $array_hide2[] = $key;
                        }
                    }
                } else if ($i == 2) {
                    $count  = 0;
                    $count2  = 0;
                    $count3  = 0;
                    $count4 = 0;
                    $count5 = 0;
                    $count6 = 0;
                    $array_hide = [];
                    $array_hide2 = [];
                    foreach ($rate_data_send_meta['rate_data1'] as $key => $row) {
                        if ($key >= 30 && $key != 32 && $key <= 39 && $key != 34 && $key != 38 && $key != 36 && ($row['rd_data2'] || $row['rd_data1'])) {
                            $sheet->getStyle('B' . ($line3[0] + $count) . ':P' . ($line3[0] + $count))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                            $sheet->getStyle('B' . ($line3[0] + $count) . ':P' . ($line3[0] + $count))->getFont()->setBold(true);
                            $sheet->getStyle('B' . ($line3[0] + $count) . ':Z' . ($line3[0] + $count))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                            $sheet->getStyle('B' . ($line3[0] + $count) . ':Z' . ($line3[0] + $count))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->mergeCells('L' . ($line3[0] + $count) . ':P' . ($line3[0] + $count));
                            $sheet->setCellValue('L' . ($line3[0] + $count), $row['rd_cate3']);
                            $sheet->getStyle('Q' . ($line3[0] + $count))->getNumberFormat()->setFormatCode('#,##0_-""');
                            $sheet->mergeCells('Q' . ($line3[0] + $count) . ':U' . ($line3[0] + $count));
                            $sheet->setCellValue('Q' . ($line3[0] + $count), $row['rd_data1']);
                            $sheet->getStyle('V' . ($line3[0] + $count))->getNumberFormat()->setFormatCode('#,##0_-""');
                            $sheet->mergeCells('V' . ($line3[0] + $count) . ':Z' . ($line3[0] + $count));
                            $sheet->setCellValue('V' . ($line3[0] + $count), $row['rd_data2']);
                            $count++;
                        } else if ($key >= 30 && $key != 32 && $key <= 39 && $key != 34 && $key != 38 && $key != 36 && (!$row['rd_data2'] && !$row['rd_data1'])) {
                            $count3++;
                            $array_hide[] = $key;
                        }

                        if ($count == 0) {
                            $data = 1;
                        } else {
                            $data = 0;
                        }


                        if ($key >= 30 && ($key == 41 || $key == 42 || $key == 43)) {
                            if ($key == 41 && (!$row['rd_data2'] && !$row['rd_data1'])) {
                                $count4 = 2;
                            }

                            if ($key == 41  && ($row['rd_data2'] || $row['rd_data1'])) {
                                $sheet->getStyle('G' . ($line3[7] - $count3 - $data) . ':P' . ($line3[7] - $count3 - $data))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                                $sheet->getStyle('G' . ($line3[7] - $count3 - $data) . ':P' . ($line3[7] - $count3 - $data))->getFont()->setBold(true);
                                $sheet->getStyle('B' . ($line3[7] - $count3 - $data) . ':Z' . ($line3[7] - $count3 - $data))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                                $sheet->getStyle('B' . ($line3[7] - $count3 - $data) . ':Z' . ($line3[7] - $count3 - $data))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                                $sheet->mergeCells('G' . ($line3[7] - $count3 - $data) . ':P' . ($line3[7] - $count3 - $data));
                                $sheet->setCellValue('G' . ($line3[7] - $count3 - $data), '작업료');
                                $sheet->getStyle('Q' . ($line3[7] - $count3 - $data))->getNumberFormat()->setFormatCode('#,##0_-""');
                                $sheet->mergeCells('Q' . ($line3[7] - $count3 - $data) . ':U' . ($line3[7] - $count3 - $data));
                                $sheet->setCellValue('Q' . ($line3[7] - $count3 - $data), $row['rd_data1']);
                                $sheet->getStyle('V' . ($line3[7] - $count3 - $data))->getNumberFormat()->setFormatCode('#,##0_-""');
                                $sheet->mergeCells('V' . ($line3[7] - $count3 - $data) . ':Z' . ($line3[7] - $count3 - $data));
                                $sheet->setCellValue('V' . ($line3[7] - $count3 - $data), $row['rd_data2']);
                            }
                            if (($key == 42 || $key == 43)  && ($row['rd_data2'] || $row['rd_data1'])) {

                                $sheet->getStyle('G' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':P' . ($line3[9] + $count2 - $count3 - $data - $count4))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                                $sheet->getStyle('G' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':P' . ($line3[9] + $count2 - $count3 - $data - $count4))->getFont()->setBold(true);
                                $sheet->getStyle('B' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':Z' . ($line3[9] + $count2 - $count3 - $data - $count4))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                                $sheet->getStyle('B' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':Z' . ($line3[9] + $count2 - $count3 - $data - $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                                $sheet->mergeCells('G' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':P' . ($line3[9] + $count2 - $count3 - $data - $count4));
                                $sheet->setCellValue('G' . ($line3[9] + $count2 - $count3 - $data - $count4), $row['rd_cate3']);
                                // $sheet->getStyle('Q'.($line3[9] + $count2))->getNumberFormat()->setFormatCode('#,##0_-""');
                                $sheet->mergeCells('Q' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':U' . ($line3[9] + $count2 - $count3 - $data - $count4));
                                $sheet->setCellValue('Q' . ($line3[9] + $count2 - $count3 - $data - $count4), $row['rd_data1']);
                                // $sheet->getStyle('V'.($line3[9] + $count2))->getNumberFormat()->setFormatCode('#,##0_-""');
                                $sheet->mergeCells('V' . ($line3[9] + $count2 - $count3 - $data - $count4) . ':Z' . ($line3[9] + $count2 - $count3 - $data - $count4));
                                $sheet->setCellValue('V' . ($line3[9] + $count2 - $count3 - $data - $count4), $row['rd_data2']);
                                $count2++;
                            }
                            if (($key == 42 || $key == 43) && (!$row['rd_data2'] && !$row['rd_data1'])) {
                                $count5 += 1;
                                $array_hide2[] = $key;
                            }
                        }
                        if ($key == 44 && $row['rd_data1']) {
                            $sheet->getStyle('G' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':P' . ($line3[11] - $count3 - $data - $count4 - $count5))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                            $sheet->getStyle('G' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':P' . ($line3[11] - $count3 - $data - $count4 - $count5))->getFont()->setBold(true);
                            $sheet->getStyle('B' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                            $sheet->getStyle('B' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                            $sheet->mergeCells('G' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':P' . ($line3[11] - $count3 - $data - $count4 - $count5));
                            $sheet->setCellValue('G' . ($line3[11] - $count3 - $data - $count4 - $count5), '할인율');
                            // $sheet->getStyle('Q'.($line3[11]))->getNumberFormat()->setFormatCode('#,##0_-""');
                            $sheet->mergeCells('Q' . ($line3[11] - $count3 - $data - $count4 - $count5) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5));
                            $sheet->setCellValue('Q' . ($line3[11] - $count3 - $data - $count4 - $count5), $row['rd_data1']);
                            $count6 = 1;
                        }
                        if ($key == 44 && (!$row['rd_data1'])) {
                            $count5 += 1;
                            $array_hide2[] = $key;
                        }
                    }
                }


                if ($i == 0) {
                    $data1 = 0;
                    if ($count != 0) {
                        $table1 = 1;
                        $sheet->mergeCells('G' . ($title2) . ':K' . ($title2));
                        $sheet->getStyle('G' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('G' . ($title2), '항목');
                        $sheet->mergeCells('L' . ($title2) . ':P' . ($title2));
                        $sheet->getStyle('L' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('L' . ($title2), '상세');

                        $sheet->mergeCells('Q' . ($title2) . ':U' . ($title2));
                        $sheet->getStyle('Q' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('Q' . ($title2), '기본료');

                        $sheet->mergeCells('V' . ($title2) . ':Z' . ($title2));
                        $sheet->getStyle('V' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('V' . ($title2), '단가/KG');
                        $sheet->getStyle('B' . ($title2) . ':K' . ($line3[5] - $count3))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($title2) . ':K' . ($line3[5] - $count3))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->mergeCells('B' . ($title2) . ':F' . ($line3[5] - $count3));
                        $sheet->getStyle('B' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($title2), '하역비용');
                        if (!in_array(0, $array_hide) && !in_array(1, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[0]) . ':K' . ($line3[1]));
                            $sheet->getStyle('G' . ($line3[0]) . ':K' . ($line3[1]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[0]), 'THC');
                        } else if (in_array(0, $array_hide) || in_array(1, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[0]) . ':K' . ($line3[0]));
                            $sheet->getStyle('G' . ($line3[0]) . ':K' . ($line3[0]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[0]), 'THC');
                        }
                        if (!in_array(3, $array_hide) && (in_array(0, $array_hide) || in_array(1, $array_hide))) {
                            $array1 = [0, 1];
                            $result = array_intersect($array1, $array_hide);
                            $sheet->mergeCells('G' . ($line3[2] - count($result)) . ':K' . ($line3[2] - count($result)));
                            $sheet->getStyle('G' . ($line3[2] - count($result)) . ':K' . ($line3[2] - count($result)))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[2] - count($result)), '하기운송료');
                        } else if (!in_array(3, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[2]) . ':K' . ($line3[2]));
                            $sheet->getStyle('G' . ($line3[2]) . ':K' . ($line3[2]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[2]), '하기운송료');
                        }
                        if (!in_array(5, $array_hide)  && (in_array(3, $array_hide) || in_array(0, $array_hide) || in_array(1, $array_hide))) {
                            $array1 = [0, 1, 3];
                            $result = array_intersect($array1, $array_hide);

                            $sheet->mergeCells('G' . ($line3[3] - count($result)) . ':K' . ($line3[3] - count($result)));
                            $sheet->getStyle('G' . ($line3[3] - count($result)) . ':K' . ($line3[3] - count($result)))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[3] - count($result)), '보세운송료');
                        } else if (!in_array(5, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[3]) . ':K' . ($line3[3]));
                            $sheet->getStyle('G' . ($line3[3]) . ':K' . ($line3[3]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[3]), '보세운송료');
                        }
                        if (!in_array(7, $array_hide) && (in_array(5, $array_hide) || in_array(3, $array_hide) ||
                            in_array(0, $array_hide) || in_array(1, $array_hide))) {
                            $array1 = [0, 1, 3, 5];
                            $result = array_intersect($array1, $array_hide);
                            $sheet->mergeCells('G' . ($line3[4] - count($result)) . ':K' . ($line3[4] - count($result)));
                            $sheet->getStyle('G' . ($line3[4] - count($result)) . ':K' . ($line3[4] - count($result)))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[4] - count($result)), '무진동차량');
                        } else if (!in_array(7, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[4]) . ':K' . ($line3[4]));
                            $sheet->getStyle('G' . ($line3[4]) . ':K' . ($line3[4]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[4]), '무진동차량');
                        }
                        if (!in_array(9, $array_hide) && (in_array(7, $array_hide) || in_array(5, $array_hide) || in_array(3, $array_hide) ||
                            in_array(0, $array_hide) || in_array(1, $array_hide))) {
                            $sheet->mergeCells('G' . ($line3[5] - $count3) . ':K' . ($line3[5] - $count3));
                            $sheet->getStyle('G' . ($line3[5] - $count3) . ':K' . ($line3[5] - $count3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[5] - $count3), '온도차량');
                        } else if (!in_array(9, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[5]) . ':K' . ($line3[5]));
                            $sheet->getStyle('G' . ($line3[5]) . ':K' . ($line3[5]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[5]), '온도차량');
                        }
                    } else if ($count == 0) {
                        $data1 = 1;
                    }
                    if ($count5 < 3 || $count4 != 2) {
                        $table1 = 1;
                        if ($count5 == 3) {
                            $count5 = 4;
                        }
                        $sheet->getStyle('B' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[11] - $count3 - $data1 - $count4 - $count5))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[11] - $count3 - $data1 - $count4 - $count5))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[11] - $count3 - $data1 - $count4 - $count5))->getFont()->setBold(true);
                        $sheet->mergeCells('B' . ($line3[6] - $count3 - $data1) . ':F' . ($line3[11] - $count3 - $data1 - $count4 - $count5));
                        $sheet->getStyle('B' . ($line3[6] - $count3 - $data1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($line3[6] - $count3 - $data1), '센터 작업료');

                        $current_row2 = 25;
                        if ($count4 == 0) {
                            $sheet->getStyle('Q' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                            $sheet->getStyle('Q' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                            $sheet->getStyle('Q' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1))->getFont()->setBold(true);
                            $sheet->mergeCells('G' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[6] - $count3 - $data1));
                            $sheet->getStyle('G' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[6] - $count3 - $data1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[6] - $count3 - $data1), '반출입');
                            $sheet->mergeCells('Q' . ($line3[6] - $count3 - $data1) . ':U' . ($line3[6] - $count3 - $data1));
                            $sheet->getStyle('Q' . ($line3[6] - $count3 - $data1) . ':U' . ($line3[6] - $count3 - $data1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('Q' . ($line3[6] - $count3 - $data1), '기본료');
                            $sheet->mergeCells('V' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1));
                            $sheet->getStyle('V' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('V' . ($line3[6] - $count3 - $data1), '단가/KG');
                        }
                        if (($count5 == 0 || $count2 > 0 || $count6  > 0)) {
                            $sheet->getStyle('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8] - $count3 - $data1 - $count4))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                            $sheet->getStyle('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8] - $count3 - $data1 - $count4))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                            $sheet->getStyle('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8]))->getFont()->setBold(true);
                            $sheet->mergeCells('G' . ($line3[8] - $count3 - $data1 - $count4) . ':P' . ($line3[8] - $count3 - $data1 - $count4));
                            $sheet->getStyle('G' . ($line3[8] - $count3 - $data1 - $count4) . ':P' . ($line3[8] - $count3 - $data1 - $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[8] - $count3 - $data1 - $count4), '보관');
                            $sheet->mergeCells('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':U' . ($line3[8] - $count3 - $data1 - $count4));
                            $sheet->getStyle('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':U' . ($line3[8] - $count3 - $data1 - $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('Q' . ($line3[8] - $count3 - $data1 - $count4), '기본료율');
                            $sheet->mergeCells('V' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8] - $count3 - $data1 - $count4));
                            $sheet->getStyle('V' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8] - $count3 - $data1 - $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('V' . ($line3[8] - $count3 - $data1 - $count4), '할증료율(24시간 경과)');
                        }
                    }
                } else if ($i == 1) {

                    $data1 = 0;
                    if ($count != 0) {
                        $table2 = 1;
                        $sheet->mergeCells('G' . ($title2) . ':K' . ($title2));
                        $sheet->getStyle('G' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('G' . ($title2), '항목');
                        $sheet->mergeCells('L' . ($title2) . ':P' . ($title2));
                        $sheet->getStyle('L' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('L' . ($title2), '상세');

                        $sheet->mergeCells('Q' . ($title2) . ':U' . ($title2));
                        $sheet->getStyle('Q' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('Q' . ($title2), '기본료');

                        $sheet->mergeCells('V' . ($title2) . ':Z' . ($title2));
                        $sheet->getStyle('V' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('V' . ($title2), '단가/KG');
                        $sheet->getStyle('B' . ($title2) . ':K' . ($line3[5] - $count3))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($title2) . ':K' . ($line3[5] - $count3))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->mergeCells('B' . ($title2) . ':F' . ($line3[5] - $count3));
                        $sheet->getStyle('B' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($title2), '하역비용');
                        if (!in_array(15, $array_hide) && !in_array(16, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[0]) . ':K' . ($line3[1]));
                            $sheet->getStyle('G' . ($line3[0]) . ':K' . ($line3[1]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[0]), 'THC');
                        } else if (in_array(15, $array_hide) || in_array(16, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[0]) . ':K' . ($line3[0]));
                            $sheet->getStyle('G' . ($line3[0]) . ':K' . ($line3[0]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[0]), 'THC');
                        }
                        if (!in_array(18, $array_hide) && (in_array(15, $array_hide) || in_array(16, $array_hide))) {
                            $array1 = [15, 16];
                            $result = array_intersect($array1, $array_hide);
                            $sheet->mergeCells('G' . ($line3[2] - count($result)) . ':K' . ($line3[2] - count($result)));
                            $sheet->getStyle('G' . ($line3[2] - count($result)) . ':K' . ($line3[2] - count($result)))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[2] - count($result)), '하기운송료');
                        } else if (!in_array(18, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[2]) . ':K' . ($line3[2]));
                            $sheet->getStyle('G' . ($line3[2]) . ':K' . ($line3[2]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[2]), '하기운송료');
                        }
                        if (!in_array(20, $array_hide)  && (in_array(18, $array_hide) || in_array(15, $array_hide) || in_array(16, $array_hide))) {
                            $array1 = [15, 16, 18];
                            $result = array_intersect($array1, $array_hide);

                            $sheet->mergeCells('G' . ($line3[3] - count($result)) . ':K' . ($line3[3] - count($result)));
                            $sheet->getStyle('G' . ($line3[3] - count($result)) . ':K' . ($line3[3] - count($result)))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[3] - count($result)), '보세운송료');
                        } else if (!in_array(20, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[3]) . ':K' . ($line3[3]));
                            $sheet->getStyle('G' . ($line3[3]) . ':K' . ($line3[3]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[3]), '보세운송료');
                        }
                        if (!in_array(22, $array_hide) && (in_array(20, $array_hide) || in_array(18, $array_hide) ||
                            in_array(15, $array_hide) || in_array(16, $array_hide))) {
                            $array1 = [15, 16, 18, 20];
                            $result = array_intersect($array1, $array_hide);
                            $sheet->mergeCells('G' . ($line3[4] - count($result)) . ':K' . ($line3[4] - count($result)));
                            $sheet->getStyle('G' . ($line3[4] - count($result)) . ':K' . ($line3[4] - count($result)))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[4] - count($result)), '무진동차량');
                        } else if (!in_array(22, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[4]) . ':K' . ($line3[4]));
                            $sheet->getStyle('G' . ($line3[4]) . ':K' . ($line3[4]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[4]), '무진동차량');
                        }
                        if (!in_array(24, $array_hide) && (in_array(22, $array_hide) || in_array(20, $array_hide) || in_array(18, $array_hide) ||
                            in_array(15, $array_hide) || in_array(16, $array_hide))) {
                            $sheet->mergeCells('G' . ($line3[5] - $count3) . ':K' . ($line3[5] - $count3));
                            $sheet->getStyle('G' . ($line3[5] - $count3) . ':K' . ($line3[5] - $count3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[5] - $count3), '온도차량');
                        } else if (!in_array(24, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[5]) . ':K' . ($line3[5]));
                            $sheet->getStyle('G' . ($line3[5]) . ':K' . ($line3[5]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[5]), '온도차량');
                        }
                    } else if ($count == 0) {
                        $data1 = 1;
                    }
                    if ($count5 < 3 || $count4 != 2) {
                        $table2 = 1;
                        if ($count5 == 3) {
                            $count5 = 4;
                        }
                        $sheet->getStyle('B' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[11] - $count3 - $data1 - $count4 - $count5))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[11] - $count3 - $data1 - $count4 - $count5))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[11] - $count3 - $data1 - $count4 - $count5))->getFont()->setBold(true);
                        $sheet->mergeCells('B' . ($line3[6] - $count3 - $data1) . ':F' . ($line3[11] - $count3 - $data1 - $count4 - $count5));
                        $sheet->getStyle('B' . ($line3[6] - $count3 - $data1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($line3[6] - $count3 - $data1), '센터 작업료');

                        $current_row2 = 25;

                        if ($count4 == 0) {

                            $sheet->getStyle('Q' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                            $sheet->getStyle('Q' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                            $sheet->getStyle('Q' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1))->getFont()->setBold(true);
                            $sheet->mergeCells('G' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[6] - $count3 - $data1));
                            $sheet->getStyle('G' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[6] - $count3 - $data1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[6] - $count3 - $data1), '반출입');
                            $sheet->mergeCells('Q' . ($line3[6] - $count3 - $data1) . ':U' . ($line3[6] - $count3 - $data1));
                            $sheet->getStyle('Q' . ($line3[6] - $count3 - $data1) . ':U' . ($line3[6] - $count3 - $data1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('Q' . ($line3[6] - $count3 - $data1), '기본료');
                            $sheet->mergeCells('V' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1));
                            $sheet->getStyle('V' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('V' . ($line3[6] - $count3 - $data1), '단가/KG');
                        }
                        if (($count5 == 0 || $count2 > 0 || $count6  > 0)) {
                            $sheet->getStyle('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8] - $count3 - $data1 - $count4))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                            $sheet->getStyle('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8] - $count3 - $data1 - $count4))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                            $sheet->getStyle('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8]))->getFont()->setBold(true);
                            $sheet->mergeCells('G' . ($line3[8] - $count3 - $data1 - $count4) . ':P' . ($line3[8] - $count3 - $data1 - $count4));
                            $sheet->getStyle('G' . ($line3[8] - $count3 - $data1 - $count4) . ':P' . ($line3[8] - $count3 - $data1 - $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[8] - $count3 - $data1 - $count4), '보관');
                            $sheet->mergeCells('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':U' . ($line3[8] - $count3 - $data1 - $count4));
                            $sheet->getStyle('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':U' . ($line3[8] - $count3 - $data1 - $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('Q' . ($line3[8] - $count3 - $data1 - $count4), '기본료율');
                            $sheet->mergeCells('V' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8] - $count3 - $data1 - $count4));
                            $sheet->getStyle('V' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8] - $count3 - $data1 - $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('V' . ($line3[8] - $count3 - $data1 - $count4), '할증료율(24시간 경과)');
                        }
                    }
                } else if ($i == 2) {
                    $data1 = 0;
                    if ($count != 0) {
                        $table3 = 1;
                        $sheet->mergeCells('G' . ($title2) . ':K' . ($title2));
                        $sheet->getStyle('G' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('G' . ($title2), '항목');
                        $sheet->mergeCells('L' . ($title2) . ':P' . ($title2));
                        $sheet->getStyle('L' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('L' . ($title2), '상세');

                        $sheet->mergeCells('Q' . ($title2) . ':U' . ($title2));
                        $sheet->getStyle('Q' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('Q' . ($title2), '기본료');

                        $sheet->mergeCells('V' . ($title2) . ':Z' . ($title2));
                        $sheet->getStyle('V' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('V' . ($title2), '단가/KG');
                        $sheet->getStyle('B' . ($title2) . ':K' . ($line3[5] - $count3))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($title2) . ':K' . ($line3[5] - $count3))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->mergeCells('B' . ($title2) . ':F' . ($line3[5] - $count3));
                        $sheet->getStyle('B' . ($title2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($title2), '하역비용');
                        if (!in_array(30, $array_hide) && !in_array(31, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[0]) . ':K' . ($line3[1]));
                            $sheet->getStyle('G' . ($line3[0]) . ':K' . ($line3[1]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[0]), 'THC');
                        } else if (in_array(30, $array_hide) || in_array(31, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[0]) . ':K' . ($line3[0]));
                            $sheet->getStyle('G' . ($line3[0]) . ':K' . ($line3[0]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[0]), 'THC');
                        }
                        if (!in_array(33, $array_hide) && (in_array(30, $array_hide) || in_array(31, $array_hide))) {
                            $array1 = [30, 31];
                            $result = array_intersect($array1, $array_hide);
                            $sheet->mergeCells('G' . ($line3[2] - count($result)) . ':K' . ($line3[2] - count($result)));
                            $sheet->getStyle('G' . ($line3[2] - count($result)) . ':K' . ($line3[2] - count($result)))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[2] - count($result)), '하기운송료');
                        } else if (!in_array(33, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[2]) . ':K' . ($line3[2]));
                            $sheet->getStyle('G' . ($line3[2]) . ':K' . ($line3[2]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[2]), '하기운송료');
                        }
                        if (!in_array(35, $array_hide)  && (in_array(33, $array_hide) || in_array(30, $array_hide) || in_array(31, $array_hide))) {
                            $array1 = [30, 31, 33];
                            $result = array_intersect($array1, $array_hide);

                            $sheet->mergeCells('G' . ($line3[3] - count($result)) . ':K' . ($line3[3] - count($result)));
                            $sheet->getStyle('G' . ($line3[3] - count($result)) . ':K' . ($line3[3] - count($result)))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[3] - count($result)), '보세운송료');
                        } else if (!in_array(35, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[3]) . ':K' . ($line3[3]));
                            $sheet->getStyle('G' . ($line3[3]) . ':K' . ($line3[3]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[3]), '보세운송료');
                        }
                        if (!in_array(37, $array_hide) && (in_array(35, $array_hide) || in_array(33, $array_hide) ||
                            in_array(30, $array_hide) || in_array(31, $array_hide))) {
                            $array1 = [30, 31, 33, 35];
                            $result = array_intersect($array1, $array_hide);
                            $sheet->mergeCells('G' . ($line3[4] - count($result)) . ':K' . ($line3[4] - count($result)));
                            $sheet->getStyle('G' . ($line3[4] - count($result)) . ':K' . ($line3[4] - count($result)))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[4] - count($result)), '무진동차량');
                        } else if (!in_array(37, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[4]) . ':K' . ($line3[4]));
                            $sheet->getStyle('G' . ($line3[4]) . ':K' . ($line3[4]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[4]), '무진동차량');
                        }
                        if (!in_array(39, $array_hide) && (in_array(37, $array_hide) || in_array(30, $array_hide) || in_array(33, $array_hide) ||
                            in_array(35, $array_hide) || in_array(31, $array_hide))) {
                            $sheet->mergeCells('G' . ($line3[5] - $count3) . ':K' . ($line3[5] - $count3));
                            $sheet->getStyle('G' . ($line3[5] - $count3) . ':K' . ($line3[5] - $count3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[5] - $count3), '온도차량');
                        } else if (!in_array(39, $array_hide)) {
                            $sheet->mergeCells('G' . ($line3[5]) . ':K' . ($line3[5]));
                            $sheet->getStyle('G' . ($line3[5]) . ':K' . ($line3[5]))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[5]), '온도차량');
                        }
                    } else if ($count == 0) {
                        $data1 = 1;
                    }
                    if ($count5 < 3 || $count4 != 2) {
                        $table3 = 1;
                        if ($count5 == 3) {
                            $count5 = 4;
                        }
                        $sheet->getStyle('B' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[11] - $count3 - $data1 - $count4 - $count5))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                        $sheet->getStyle('B' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[11] - $count3 - $data1 - $count4 - $count5))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                        $sheet->getStyle('B' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[11] - $count3 - $data1 - $count4 - $count5))->getFont()->setBold(true);
                        $sheet->mergeCells('B' . ($line3[6] - $count3 - $data1) . ':F' . ($line3[11] - $count3 - $data1 - $count4 - $count5));
                        $sheet->getStyle('B' . ($line3[6] - $count3 - $data1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->setCellValue('B' . ($line3[6] - $count3 - $data1), '센터 작업료');

                        $current_row2 = 25;
                        if ($count4 == 0) {
                            $sheet->getStyle('Q' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                            $sheet->getStyle('Q' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                            $sheet->getStyle('Q' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1))->getFont()->setBold(true);
                            $sheet->mergeCells('G' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[6] - $count3 - $data1));
                            $sheet->getStyle('G' . ($line3[6] - $count3 - $data1) . ':P' . ($line3[6] - $count3 - $data1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[6] - $count3 - $data1), '반출입');
                            $sheet->mergeCells('Q' . ($line3[6] - $count3 - $data1) . ':U' . ($line3[6] - $count3 - $data1));
                            $sheet->getStyle('Q' . ($line3[6] - $count3 - $data1) . ':U' . ($line3[6] - $count3 - $data1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('Q' . ($line3[6] - $count3 - $data1), '기본료');
                            $sheet->mergeCells('V' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1));
                            $sheet->getStyle('V' . ($line3[6] - $count3 - $data1) . ':Z' . ($line3[6] - $count3 - $data1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('V' . ($line3[6] - $count3 - $data1), '단가/KG');
                        }
                        if (($count5 == 0 || $count2 > 0 || $count6  > 0)) {

                            $sheet->getStyle('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8] - $count3 - $data1 - $count4))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                            $sheet->getStyle('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8] - $count3 - $data1 - $count4))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                            $sheet->getStyle('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8]))->getFont()->setBold(true);
                            $sheet->mergeCells('G' . ($line3[8] - $count3 - $data1 - $count4) . ':P' . ($line3[8] - $count3 - $data1 - $count4));
                            $sheet->getStyle('G' . ($line3[8] - $count3 - $data1 - $count4) . ':P' . ($line3[8] - $count3 - $data1 - $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('G' . ($line3[8] - $count3 - $data1 - $count4), '보관');
                            $sheet->mergeCells('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':U' . ($line3[8] - $count3 - $data1 - $count4));
                            $sheet->getStyle('Q' . ($line3[8] - $count3 - $data1 - $count4) . ':U' . ($line3[8] - $count3 - $data1 - $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('Q' . ($line3[8] - $count3 - $data1 - $count4), '기본료율');
                            $sheet->mergeCells('V' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8] - $count3 - $data1 - $count4));
                            $sheet->getStyle('V' . ($line3[8] - $count3 - $data1 - $count4) . ':Z' . ($line3[8] - $count3 - $data1 - $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->setCellValue('V' . ($line3[8] - $count3 - $data1 - $count4), '할증료율(24시간 경과)');
                        }
                    }
                }
                if (($i == 0 && $table1 == 1) || ($i == 1 && $table2 == 1) || ($i == 2 && $table3 == 1)) {
                    $sheet->getStyle('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)->setWrapText(true);
                    $sheet->getStyle('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 2) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5 + 7))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 2) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5 + 7))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
                    $sheet->mergeCells('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 2) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5 + 7));
                    if ($i == 0) {
                        $sheet->setCellValue('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 2), $rmd_last['rmd_mail_detail1a']);
                    }
                    if ($i == 1) {
                        $sheet->setCellValue('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 2), $rmd_last['rmd_mail_detail1b']);
                    }
                    if ($i == 2) {

                        $sheet->setCellValue('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 2), $rmd_last['rmd_mail_detail1c']);
                    }
                }
                if ($i == 2) {
                    $sheet->setCellValue('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 9), '1. 이 요율표의 유효기간은 제출일자로부터 1개월 입니다.');
                    $sheet->setCellValue('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 10), '2. 이 견적 금액은 부가가치세 별도 금액입니다.');
                    $sheet->setCellValue('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 11), '3. 상세 업무 내역에 따라 제공 요율은 변경될 수 있습니다.');
                    // $sheet->getStyle('B'. ($line3[11]-$count3-$data-$count4-$count5 + 5). ':Z'. ($line3[11]-$count3-$data-$count4-$count5 + 9))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
                    $sheet->getStyle('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 13) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5 + 17))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $sheet->mergeCells('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 13) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5 + 13));
                    $sheet->setCellValue('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 13), $co_info->co_name);
                    $sheet->mergeCells('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 14) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5 + 14));
                    $sheet->setCellValue('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 14), $co_info->co_address . ' ' . $co_info->co_address_detail);
                    $sheet->mergeCells('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 15) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5 + 15));
                    $sheet->setCellValue('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 15), $co_info->co_tel);
                    $sheet->mergeCells('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 16) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5 + 16));
                    $sheet->setCellValue('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 16), $co_info->co_email);
                    $sheet->mergeCells('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 17) . ':Z' . ($line3[11] - $count3 - $data - $count4 - $count5 + 17));
                    $sheet->setCellValue('B' . ($line3[11] - $count3 - $data - $count4 - $count5 + 17), Date('Y-m-d', strtotime($rmd_last->created_at)));
                }
            }


            foreach ($sheet->getRowIterator() as $row) {
                $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
            }
        }
        if (!empty($rate_data_send_meta['rate_data3']) && count($rate_data_send_meta['rate_data3']) > 0) {
            $sheet->getStyle('B13')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
            $sheet->getStyle('B13')->getFont()->setBold(true);
            $sheet->mergeCells('B13:Z13');
            $sheet->setCellValue('B13', '서비스: 유통가공');
            $sheet->getStyle('N' . (14) . ':Q' . (14))->getNumberFormat()->setFormatCode('#,##0_-""');

            $sheet->getStyle('B' . (14) . ':I' . (14))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . (14) . ':I' . (14))->getFont()->setBold(true);
            $sheet->getStyle('B' . (14) . ':Z' . (14))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle('B' . (14) . ':Z' . (14))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . (14) . ':Z' . (14))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . (14) . ':Z' . (14))->getFont()->setBold(true);

            $sheet->mergeCells('B' . (14) . ':I' . (14));
            $sheet->getStyle('B' . (14))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B' . (14), '구분');



            $sheet->mergeCells('J' . (14) . ':M' . (14));
            $sheet->getStyle('J' . (14))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('J' . (14), '단위');

            $sheet->mergeCells('N' . (14) . ':Q' . (14));
            $sheet->getStyle('N' . (14))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('N' . (14), '단가');

            $sheet->mergeCells('R' . (14) . ':Z' . (14));
            $sheet->getStyle('R' . (14))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('R' . (14), 'ON/OFF');
            foreach ($sheet->getRowIterator() as $row) {
                $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
            }
            $current_row = 15;
            $count1 = 0;
            $count2 = 0;
            $count3 = 0;
            $count4 = 0;
            foreach ($rate_data_send_meta['rate_data3'] as $key => $row) {
                if ($key >= 0 && $key <= 3 && $row['rd_data3'] == 'ON') {
                    $sheet->getStyle('B' . ($current_row + $count1) . ':I' . ($current_row + $count1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row + $count1) . ':I' . ($current_row + $count1))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row + $count1) . ':Z' . ($current_row + $count1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row + $count1) . ':Z' . ($current_row + $count1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->mergeCells('C' . ($current_row + $count1) . ':I' . ($current_row + $count1));
                    $sheet->setCellValue('C' . ($current_row + $count1), $row['rd_cate2']);
                    $sheet->mergeCells('J' . ($current_row + $count1) . ':M' . ($current_row + $count1));
                    $sheet->setCellValue('J' . ($current_row + $count1), $row['rd_data1']);
                    $sheet->getStyle('N' . ($current_row + $count1))->getNumberFormat()->setFormatCode('#,##0_-""');
                    $sheet->mergeCells('N' . ($current_row + $count1) . ':Q' . ($current_row + $count1));
                    $sheet->setCellValue('N' . ($current_row + $count1), $row['rd_data2']);
                    $sheet->mergeCells('R' . ($current_row + $count1) . ':Z' . ($current_row + $count1));
                    $sheet->setCellValue('R' . ($current_row + $count1), $row['rd_data3']);
                    $count1 += 1;
                }
                if ($key >= 4 && $key <= 6 && $row['rd_data3'] == 'ON') {
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2) . ':I' . ($current_row + $count1 + $count2))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2) . ':I' . ($current_row + $count1 + $count2))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2) . ':Z' . ($current_row + $count1 + $count2))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2) . ':Z' . ($current_row + $count1 + $count2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->mergeCells('C' . ($current_row + $count1 + $count2) . ':I' . ($current_row + $count1 + $count2));
                    $sheet->setCellValue('C' . ($current_row + $count1 + $count2), $row['rd_cate2']);
                    $sheet->mergeCells('J' . ($current_row + $count1 + $count2) . ':M' . ($current_row + $count1 + $count2));
                    $sheet->setCellValue('J' . ($current_row + $count1 + $count2), $row['rd_data1']);
                    $sheet->getStyle('N' . ($current_row + $count1 + $count2))->getNumberFormat()->setFormatCode('#,##0_-""');
                    $sheet->mergeCells('N' . ($current_row + $count1 + $count2) . ':Q' . ($current_row + $count1 + $count2));
                    $sheet->setCellValue('N' . ($current_row + $count1 + $count2), $row['rd_data2']);
                    $sheet->mergeCells('R' . ($current_row + $count1 + $count2) . ':Z' . ($current_row + $count1 + $count2));
                    $sheet->setCellValue('R' . ($current_row + $count1 + $count2), $row['rd_data3']);
                    $count2 += 1;
                }
                if ($key >= 7 && $key <= 8 && $row['rd_data3'] == 'ON') {
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':I' . ($current_row + $count1 + $count2 + $count3))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':I' . ($current_row + $count1 + $count2 + $count3))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':Z' . ($current_row + $count1 + $count2 + $count3))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':Z' . ($current_row + $count1 + $count2 + $count3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->mergeCells('C' . ($current_row + $count1 + $count2 + $count3) . ':I' . ($current_row + $count1 + $count2 + $count3));
                    $sheet->setCellValue('C' . ($current_row + $count1 + $count2 + $count3), $row['rd_cate2']);
                    $sheet->mergeCells('J' . ($current_row + $count1 + $count2 + $count3) . ':M' . ($current_row + $count1 + $count2 + $count3));
                    $sheet->setCellValue('J' . ($current_row + $count1 + $count2 + $count3), $row['rd_data1']);
                    $sheet->getStyle('N' . ($current_row + $count1 + $count2 + $count3))->getNumberFormat()->setFormatCode('#,##0_-""');
                    $sheet->mergeCells('N' . ($current_row + $count1 + $count2 + $count3) . ':Q' . ($current_row + $count1 + $count2 + $count3));
                    $sheet->setCellValue('N' . ($current_row + $count1 + $count2 + $count3), $row['rd_data2']);
                    $sheet->mergeCells('R' . ($current_row + $count1 + $count2 + $count3) . ':Z' . ($current_row + $count1 + $count2 + $count3));
                    $sheet->setCellValue('R' . ($current_row + $count1 + $count2 + $count3), $row['rd_data3']);
                    $count3 += 1;
                }
                if ($key >= 9  && $row['rd_data3'] == 'ON') {

                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->mergeCells('B' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4));
                    $sheet->setCellValue('B' . ($current_row + $count1 + $count2 + $count3 + $count4), $row['rd_cate2']);
                    $sheet->mergeCells('J' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':M' . ($current_row + $count1 + $count2 + $count3 + $count4));
                    $sheet->setCellValue('J' . ($current_row + $count1 + $count2 + $count3 + $count4), $row['rd_data1']);
                    $sheet->getStyle('N' . ($current_row + $count1 + $count2 + $count3 + $count4))->getNumberFormat()->setFormatCode('#,##0_-""');
                    $sheet->mergeCells('N' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':Q' . ($current_row + $count1 + $count2 + $count3 + $count4));
                    $sheet->setCellValue('N' . ($current_row + $count1 + $count2 + $count3 + $count4), $row['rd_data2']);
                    $sheet->mergeCells('R' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4));
                    $sheet->setCellValue('R' . ($current_row + $count1 + $count2 + $count3 + $count4), $row['rd_data3']);
                    $count4 += 1;
                }
            }

            if ($count1 > 0) {
                $sheet->mergeCells('B' . ($current_row) . ':B' . ($current_row + $count1 - 1));
                $sheet->getStyle('B' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('B' . ($current_row), '원산지 표시');
            }
            if ($count2 > 0) {
                $sheet->mergeCells('B' . ($current_row + $count1) . ':B' . ($current_row + $count1 + $count2 - 1));
                $sheet->getStyle('B' . ($current_row + $count1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('B' . ($current_row + $count1), 'TAG');
            }
            if ($count3 > 0) {
                $sheet->mergeCells('B' . ($current_row + $count1 + $count2) . ':B' . ($current_row + $count1 + $count2 + $count3 - 1));
                $sheet->getStyle('B' . ($current_row + $count1 + $count2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('B' . ($current_row + $count1 + $count2), '라벨');
            }

            $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4 +  1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)->setWrapText(true);
            $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4 +  1) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4 +  7))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4 +  1) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4 +  7))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
            $sheet->mergeCells('B' . ($current_row + $count1 + $count2 + $count3 + $count4 +  1) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4 +  7));

            $sheet->setCellValue('B' . ($current_row + $count1 + $count2 + $count3 + $count4 +  1), $rate_meta['rm_mail_detail3']);




            $sheet->setCellValue('B' . ($current_row + $count1 + $count2 + $count3 + $count4 +  9), '1. 이 요율표의 유효기간은 제출일자로부터 1개월 입니다.');
            $sheet->setCellValue('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + 10), '2. 이 견적 금액은 부가가치세 별도 금액입니다.');
            $sheet->setCellValue('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + 11), '3. 상세 업무 내역에 따라 제공 요율은 변경될 수 있습니다.');
            // $sheet->getStyle('B'. ($current_row+$count1+$count2 + $count3 + $count4 + 5). ':Z'. ($current_row+$count1+$count2 + $count3 + $count4 + 9))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
            $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + 13) . ':R' . ($current_row + $count1 + $count2 + $count3 + $count4 + 17))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->mergeCells('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + 13) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4 + 13));
            $sheet->setCellValue('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + 13), $co_info->co_name);
            $sheet->mergeCells('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + 14) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4 + 14));
            $sheet->setCellValue('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + 14), $co_info->co_address . ' ' . $co_info->co_address_detail);
            $sheet->mergeCells('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + 15) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4 + 15));
            $sheet->setCellValue('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + 15), $co_info->co_tel);
            $sheet->mergeCells('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + 16) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4 + 16));
            $sheet->setCellValue('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + 16), $co_info->co_email);
            $sheet->mergeCells('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + 17) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4 + 17));
            $sheet->setCellValue('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + 17), Date('Y-m-d', strtotime($rmd_last->created_at)));
        }
        if (!empty($rate_data_send_meta['rate_data2']) && count($rate_data_send_meta['rate_data2']) > 0) {
            $sheet->getStyle('B13')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EDEDED');
            $sheet->getStyle('B13')->getFont()->setBold(true);
            $sheet->mergeCells('B13:Z13');
            $sheet->setCellValue('B13', '서비스: 수입풀필먼트');

            foreach ($sheet->getRowIterator() as $row) {
                $sheet->getRowDimension($row->getRowIndex())->setRowHeight(21);
            }


            $sheet->getStyle('N' . (14) . ':Q' . (14))->getNumberFormat()->setFormatCode('#,##0_-""');

            $sheet->getStyle('B' . (14) . ':E' . (14))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . (14) . ':E' . (14))->getFont()->setBold(true);
            $sheet->getStyle('B' . (14) . ':Z' . (14))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle('B' . (14) . ':Z' . (14))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
            $sheet->getStyle('B' . (14) . ':Z' . (14))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
            $sheet->getStyle('B' . (14) . ':Z' . (14))->getFont()->setBold(true);

            $sheet->mergeCells('B' . (14) . ':I' . (14));
            $sheet->getStyle('B' . (14))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B' . (14), '기준');



            $sheet->mergeCells('J' . (14) . ':M' . (14));
            $sheet->getStyle('J' . (14))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('J' . (14), '단위');

            $sheet->mergeCells('N' . (14) . ':Q' . (14));
            $sheet->getStyle('N' . (14))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('N' . (14), '단가');

            $sheet->mergeCells('R' . (14) . ':Z' . (14));
            $sheet->getStyle('R' . (14))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('R' . (14), 'ON/OFF');
            $current_row = 15;
            $count1 = 0;
            $count2 = 0;
            $count3 = 0;
            $count4 = 0;
            $count5 = 0;
            $count6 = 0;

            foreach ($rate_data_send_meta['rate_data2'] as $key => $row) {
                if ($row['rd_cate1'] == '입고' && ($row['rd_cate2'] == '정상입고' || $row['rd_cate2'] == '입고검품' || $row['rd_cate2'] == '반품입고' || $row['rd_cate2'] == '반품양품화') && $row['rd_data3'] == 'ON') {
                    $sheet->getStyle('B' . ($current_row + $count1) . ':I' . ($current_row + $count1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row + $count1) . ':I' . ($current_row + $count1))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row + $count1) . ':Z' . ($current_row + $count1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row + $count1) . ':Z' . ($current_row + $count1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->mergeCells('C' . ($current_row + $count1) . ':I' . ($current_row + $count1));
                    $sheet->setCellValue('C' . ($current_row + $count1), $row['rd_cate2']);
                    $sheet->mergeCells('J' . ($current_row + $count1) . ':M' . ($current_row + $count1));
                    $sheet->setCellValue('J' . ($current_row + $count1), $row['rd_data1']);
                    $sheet->getStyle('N' . ($current_row + $count1))->getNumberFormat()->setFormatCode('#,##0_-""');
                    $sheet->mergeCells('N' . ($current_row + $count1) . ':Q' . ($current_row + $count1));
                    $sheet->setCellValue('N' . ($current_row + $count1), $row['rd_data2']);
                    $sheet->mergeCells('R' . ($current_row + $count1) . ':Z' . ($current_row + $count1));
                    $sheet->setCellValue('R' . ($current_row + $count1), $row['rd_data3']);
                    $count1 += 1;
                }
                if ($row['rd_cate1'] == '출고' && ($row['rd_cate2'] == '정상출고' || $row['rd_cate2'] == '합포장'
                    || $row['rd_cate2'] == '사은품' || $row['rd_cate2'] == '반송출고'
                    || $row['rd_cate2'] == '카튼출고' || $row['rd_cate2'] == 'B2B') && $row['rd_data3'] == 'ON') {
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2) . ':I' . ($current_row + $count1 + $count2))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2) . ':I' . ($current_row + $count1 + $count2))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2) . ':Z' . ($current_row + $count1 + $count2))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2) . ':Z' . ($current_row + $count1 + $count2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->mergeCells('C' . ($current_row + $count1 + $count2) . ':I' . ($current_row + $count1 + $count2));
                    $sheet->setCellValue('C' . ($current_row + $count1 + $count2), $row['rd_cate2']);
                    $sheet->mergeCells('J' . ($current_row + $count1 + $count2) . ':M' . ($current_row + $count1 + $count2));
                    $sheet->setCellValue('J' . ($current_row + $count1 + $count2), $row['rd_data1']);
                    $sheet->getStyle('N' . ($current_row + $count1 + $count2))->getNumberFormat()->setFormatCode('#,##0_-""');
                    $sheet->mergeCells('N' . ($current_row + $count1 + $count2) . ':Q' . ($current_row + $count1 + $count2));
                    $sheet->setCellValue('N' . ($current_row + $count1 + $count2), $row['rd_data2']);
                    $sheet->mergeCells('R' . ($current_row + $count1 + $count2) . ':Z' . ($current_row + $count1 + $count2));
                    $sheet->setCellValue('R' . ($current_row + $count1 + $count2), $row['rd_data3']);
                    $count2 += 1;
                }
            }

            if ($rate_data_send_meta['rate_data2'][0]['rd_data3'] != 'OFF') {
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':I' . ($current_row + $count1 + $count2  + $count3))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':I' . ($current_row + $count1 + $count2 + $count3))->getFont()->setBold(true);
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':Z' . ($current_row + $count1 + $count2 + $count3))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':Z' . ($current_row + $count1 + $count2 + $count3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->mergeCells('C' . ($current_row + $count1 + $count2 + $count3) . ':I' . ($current_row + $count1 + $count2 + $count3));
                $sheet->setCellValue('C' . ($current_row + $count1 + $count2 + $count3), '픽업');
                $sheet->mergeCells('J' . ($current_row + $count1 + $count2 + $count3) . ':M' . ($current_row + $count1 + $count2 + $count3));
                $sheet->setCellValue('J' . ($current_row + $count1 + $count2 + $count3), $rate_data_send_meta['rate_data2'][0]['rd_data1']);
                $sheet->getStyle('N' . ($current_row + $count1 + $count2 + $count3))->getNumberFormat()->setFormatCode('#,##0_-""');
                $sheet->mergeCells('N' . ($current_row + $count1 + $count2 + $count3) . ':Q' . ($current_row + $count1 + $count2 + $count3));
                $sheet->setCellValue('N' . ($current_row + $count1 + $count2 + $count3), $rate_data_send_meta['rate_data2'][0]['rd_data2']);
                $sheet->mergeCells('R' . ($current_row + $count1 + $count2 + $count3) . ':Z' . ($current_row + $count1 + $count2 + $count3));
                $sheet->setCellValue('R' . ($current_row + $count1 + $count2 + $count3), $rate_data_send_meta['rate_data2'][0]['rd_data3']);
                $count3 += 1;
            }
            if ($rate_data_send_meta['rate_data2'][24]['rd_data3'] != 'OFF') {
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':I' . ($current_row + $count1 + $count2  + $count3))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':I' . ($current_row + $count1 + $count2 + $count3))->getFont()->setBold(true);
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':Z' . ($current_row + $count1 + $count2 + $count3))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':Z' . ($current_row + $count1 + $count2 + $count3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->mergeCells('C' . ($current_row + $count1 + $count2 + $count3) . ':I' . ($current_row + $count1 + $count2 + $count3));
                $sheet->setCellValue('C' . ($current_row + $count1 + $count2 + $count3), '배차(내륙운송)');
                $sheet->mergeCells('J' . ($current_row + $count1 + $count2 + $count3) . ':M' . ($current_row + $count1 + $count2 + $count3));
                $sheet->setCellValue('J' . ($current_row + $count1 + $count2 + $count3), $rate_data_send_meta['rate_data2'][24]['rd_data1']);
                $sheet->getStyle('N' . ($current_row + $count1 + $count2 + $count3))->getNumberFormat()->setFormatCode('#,##0_-""');
                $sheet->mergeCells('N' . ($current_row + $count1 + $count2 + $count3) . ':Q' . ($current_row + $count1 + $count2 + $count3));
                $sheet->setCellValue('N' . ($current_row + $count1 + $count2 + $count3), $rate_data_send_meta['rate_data2'][24]['rd_data2']);
                $sheet->mergeCells('R' . ($current_row + $count1 + $count2 + $count3) . ':Z' . ($current_row + $count1 + $count2 + $count3));
                $sheet->setCellValue('R' . ($current_row + $count1 + $count2 + $count3), $rate_data_send_meta['rate_data2'][24]['rd_data3']);
                $count3 += 1;
            }
            if ($rate_data_send_meta['rate_data2'][25]['rd_data3'] != 'OFF') {
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':I' . ($current_row + $count1 + $count2 + $count3))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':I' . ($current_row + $count1 + $count2 + $count3))->getFont()->setBold(true);
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':Z' . ($current_row + $count1 + $count2 + $count3))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3) . ':Z' . ($current_row + $count1 + $count2 + $count3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->mergeCells('C' . ($current_row + $count1 + $count2 + $count3) . ':I' . ($current_row + $count1 + $count2 + $count3));
                $sheet->setCellValue('C' . ($current_row + $count1 + $count2 + $count3), '국내택배료');
                $sheet->mergeCells('J' . ($current_row + $count1 + $count2 + $count3) . ':M' . ($current_row + $count1 + $count2 + $count3));
                $sheet->setCellValue('J' . ($current_row + $count1 + $count2 + $count3), $rate_data_send_meta['rate_data2'][25]['rd_data1']);
                $sheet->getStyle('N' . ($current_row + $count1 + $count2 + $count3))->getNumberFormat()->setFormatCode('#,##0_-""');
                $sheet->mergeCells('N' . ($current_row + $count1 + $count2 + $count3) . ':Q' . ($current_row + $count1 + $count2 + $count3));
                $sheet->setCellValue('N' . ($current_row + $count1 + $count2 + $count3), $rate_data_send_meta['rate_data2'][25]['rd_data2']);
                $sheet->mergeCells('R' . ($current_row + $count1 + $count2 + $count3) . ':Z' . ($current_row + $count1 + $count2 + $count3));
                $sheet->setCellValue('R' . ($current_row + $count1 + $count2 + $count3), $rate_data_send_meta['rate_data2'][25]['rd_data3']);
                $count3 += 1;
            }
            if ($rate_data_send_meta['rate_data2'][26]['rd_data3'] != 'OFF') {
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4))->getFont()->setBold(true);
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->mergeCells('C' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4));
                $sheet->setCellValue('C' . ($current_row + $count1 + $count2 + $count3 + $count4), '해외운송료');
                $sheet->mergeCells('J' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':M' . ($current_row + $count1 + $count2 + $count3 + $count4));
                $sheet->setCellValue('J' . ($current_row + $count1 + $count2 + $count3 + $count4), $rate_data_send_meta['rate_data2'][26]['rd_data1']);
                $sheet->getStyle('N' . ($current_row + $count1 + $count2 + $count3 + $count4))->getNumberFormat()->setFormatCode('#,##0_-""');
                $sheet->mergeCells('N' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':Q' . ($current_row + $count1 + $count2 + $count3 + $count4));
                $sheet->setCellValue('N' . ($current_row + $count1 + $count2 + $count3 + $count4), $rate_data_send_meta['rate_data2'][26]['rd_data2']);
                $sheet->mergeCells('R' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4));
                $sheet->setCellValue('R' . ($current_row + $count1 + $count2 + $count3 + $count4), $rate_data_send_meta['rate_data2'][26]['rd_data3']);
                $count4 += 1;
            }
            if ($rate_data_send_meta['rate_data2'][27]['rd_data3'] != 'OFF') {
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4))->getFont()->setBold(true);
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->mergeCells('C' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4));
                $sheet->setCellValue('C' . ($current_row + $count1 + $count2 + $count3 + $count4), '기타');
                $sheet->mergeCells('J' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':M' . ($current_row + $count1 + $count2 + $count3 + $count4));
                $sheet->setCellValue('J' . ($current_row + $count1 + $count2 + $count3 + $count4), $rate_data_send_meta['rate_data2'][27]['rd_data1']);
                $sheet->getStyle('N' . ($current_row + $count1 + $count2 + $count3 + $count4))->getNumberFormat()->setFormatCode('#,##0_-""');
                $sheet->mergeCells('N' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':Q' . ($current_row + $count1 + $count2 + $count3 + $count4));
                $sheet->setCellValue('N' . ($current_row + $count1 + $count2 + $count3 + $count4), $rate_data_send_meta['rate_data2'][27]['rd_data2']);
                $sheet->mergeCells('R' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4));
                $sheet->setCellValue('R' . ($current_row + $count1 + $count2 + $count3 + $count4), $rate_data_send_meta['rate_data2'][27]['rd_data3']);
                $count4 += 1;
            }


            foreach ($rate_data_send_meta['rate_data2'] as $key => $row) {
                if ($row['rd_cate1'] == '보관' && ($key >= 14 && $key <= 19) && $row['rd_data3'] == 'ON') {
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->mergeCells('C' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5));
                    $sheet->setCellValue('C' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5), $row['rd_cate2']);
                    $sheet->mergeCells('J' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5) . ':M' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5));
                    $sheet->setCellValue('J' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5), $row['rd_data1']);
                    $sheet->getStyle('N' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5))->getNumberFormat()->setFormatCode('#,##0_-""');
                    $sheet->mergeCells('N' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5) . ':Q' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5));
                    $sheet->setCellValue('N' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5), $row['rd_data2']);
                    $sheet->mergeCells('R' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5));
                    $sheet->setCellValue('R' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5), $row['rd_data3']);
                    $count5 += 1;
                }
                if (($key >= 22 && $key <= 23) && $row['rd_data3'] == 'ON') {
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4FB');
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6))->getFont()->setBold(true);
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E3E6EB'));
                    $sheet->getStyle('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->mergeCells('C' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6) . ':I' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6));
                    $sheet->setCellValue('C' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6), $row['rd_cate2']);
                    $sheet->mergeCells('J' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6) . ':M' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6));
                    $sheet->setCellValue('J' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6), $row['rd_data1']);
                    $sheet->getStyle('N' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6))->getNumberFormat()->setFormatCode('#,##0_-""');
                    $sheet->mergeCells('N' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6) . ':Q' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6));
                    $sheet->setCellValue('N' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6), $row['rd_data2']);
                    $sheet->mergeCells('R' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6) . ':Z' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6));
                    $sheet->setCellValue('R' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6), $row['rd_data3']);
                    $count6 += 1;
                }
            }
            if ($count1 > 0) {
                $sheet->mergeCells('B' . ($current_row) . ':B' . ($current_row + $count1 - 1));
                $sheet->getStyle('B' . ($current_row))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('B' . ($current_row), '입고');
            }
            if ($count2 > 0) {
                $sheet->mergeCells('B' . ($current_row + $count1) . ':B' . ($current_row + $count1 + $count2 - 1));
                $sheet->getStyle('B' . ($current_row + $count1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('B' . ($current_row + $count1), '출고');
            }
            if ($count3 > 0) {
                $sheet->mergeCells('B' . ($current_row + $count1 + $count2) . ':B' . ($current_row + $count1 + $count2 + $count3 - 1));
                $sheet->getStyle('B' . ($current_row + $count1 + $count2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('B' . ($current_row + $count1 + $count2), '국내운송');
            }
            if ($count4 > 0) {
                $sheet->mergeCells('B' . ($current_row + $count1 + $count2 + $count3) . ':B' . ($current_row + $count1 + $count2 + $count3 + $count4 - 1));
                $sheet->getStyle('B' . ($current_row + $count1 + $count2  + $count3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('B' . ($current_row + $count1 + $count2  + $count3), '해외운송');
            }
            if ($count5 > 0) {
                $sheet->mergeCells('B' . ($current_row + $count1 + $count2 + $count3 + $count4) . ':B' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 - 1));
                $sheet->getStyle('B' . ($current_row + $count1 + $count2  + $count3 + $count4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('B' . ($current_row + $count1 + $count2  + $count3 + $count4), '해외운송');
            }
            if ($count6 > 0) {
                $sheet->mergeCells('B' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5) . ':B' . ($current_row + $count1 + $count2 + $count3 + $count4 + $count5 + $count6 - 1));
                $sheet->getStyle('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5), '부자재');
            }

            $sheet->getStyle('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)->setWrapText(true);
            $sheet->getStyle('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 1) . ':Z' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 +  7))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 1) . ':Z' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 +  7))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
            $sheet->mergeCells('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 1) . ':Z' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 +  7));

            $sheet->setCellValue('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 1), $rate_meta['rm_mail_detail2']);

            $sheet->setCellValue('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 9), '1. 이 요율표의 유효기간은 제출일자로부터 1개월 입니다.');
            $sheet->setCellValue('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 10), '2. 이 견적 금액은 부가가치세 별도 금액입니다.');
            $sheet->setCellValue('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 11), '3. 상세 업무 내역에 따라 제공 요율은 변경될 수 있습니다.');
            // $sheet->getStyle('B'. ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 5). ':Z'. ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 9))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EDEDED'));
            $sheet->getStyle('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 13) . ':R' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 17))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->mergeCells('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 13) . ':Z' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 13));
            $sheet->setCellValue('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 13), $co_info->co_name);
            $sheet->mergeCells('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 14) . ':Z' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 14));
            $sheet->setCellValue('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 14), $co_info->co_address . ' ' . $co_info->co_address_detail);
            $sheet->mergeCells('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 15) . ':Z' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 15));
            $sheet->setCellValue('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 15), $co_info->co_tel);
            $sheet->mergeCells('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 16) . ':Z' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 16));
            $sheet->setCellValue('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 16), $co_info->co_email);
            $sheet->mergeCells('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 17) . ':Z' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 17));
            $sheet->setCellValue('B' . ($current_row + $count1 + $count2  + $count3 + $count4 + $count5 + $count6 + 17), Date('Y-m-d', strtotime($rmd_last->created_at)));
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = 'storage/download/' . $user->mb_no . '/';
        } else {
            $path = 'storage/download/no-name/';
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
            'rate_data_send_meta' => $rate_data_send_meta,

        ], 200);
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
        ], 200);
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
            // CANCEL APPROVAL PROCESS
            if ($request->bill_type == 'casebill_final_issue') { //cancel approval casebill
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                    'rgd_status5' => NULL,
                    'rgd_confirmed_date' => NULL
                ]);
                $insert_cancel_bill = CancelBillHistory::insertGetId([
                    'mb_no' => Auth::user()->mb_no,
                    'rgd_no' => $request->rgd_no,
                    'cbh_status_before' => 'confirmed',
                    'cbh_status_after' =>  'cancel_approval',
                    'cbh_type' => 'cancel_approval',
                ]);
                $insert_cancel_bill = CancelBillHistory::insertGetId([
                    'mb_no' => Auth::user()->mb_no,
                    'rgd_no' => $request->rgd_no,
                    'cbh_status_before' => 'cancel_approval',
                    'cbh_status_after' =>  NULL,
                    'cbh_type' => 'revert_approval',
                ]);
            } else if ($request->bill_type == 'monthbill_final_issue') { //cancel approval monthbill
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->first();

                $settlement_number = $rgd->rgd_settlement_number;

                $rgds = ReceivingGoodsDelivery::where('rgd_settlement_number', $settlement_number)->get();
                foreach ($rgds as $rgd) {
                    $rgd_update = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                        'rgd_status5' => null,
                        'rgd_confirmed_date' => null,
                    ]);
                    $insert_cancel_bill = CancelBillHistory::insertGetId([
                        'mb_no' => Auth::user()->mb_no,
                        'rgd_no' => $rgd['rgd_no'],
                        'cbh_status_before' => 'confirmed',
                        'cbh_status_after' =>  'cancel_approval',
                        'cbh_type' => 'cancel_approval',
                    ]);
                    CancelBillHistory::insertGetId([
                        'mb_no' => Auth::user()->mb_no,
                        'rgd_no' => $rgd['rgd_no'],
                        'cbh_status_before' => 'cancel_approval',
                        'cbh_status_after' =>  null,
                        'cbh_type' => 'revert_approval',
                    ]);

                    // $rgd_update_parent = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_parent_no'])->update([
                    //     'rgd_status5' => 'issued',
                    // ]);

                    // CancelBillHistory::insertGetId([
                    //     'mb_no' => Auth::user()->mb_no,
                    //     'rgd_no' => $rgd['rgd_parent_no'],
                    //     'cbh_status_before' => 'confirmed',
                    //     'cbh_status_after' => null,
                    //     'cbh_type' => 'revert',
                    // ]);
                }
            } else if ($request->bill_type == 'case_bill_final') { //case_bill_final && month_bill_final
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

                    CancelBillHistory::insertGetId([
                        'mb_no' => Auth::user()->mb_no,
                        'rgd_no' => $rgd_parent->rgd_no,
                        'cbh_status_after' => 'revert',
                        'cbh_type' => 'revert',
                    ]);
                }

                if ($rgd_parent->rgd_status6 == 'paid' && $rgd_parent->is_expect_payment == 'y') {

                    $check_payment = Payment::where('rgd_no', $rgd->rgd_parent_no)->where('p_success_yn', 'y')->orderBy('p_no', 'desc')->first();

                    if (isset($check_payment) && $rgd_parent->is_expect_payment == 'y') {
                        Payment::where('p_no', $check_payment->p_no)->update([
                            // 'p_price' => $request->sumprice,
                            // 'p_method' => $request->p_method,
                            'p_success_yn' => null,
                            'p_cancel_yn' => 'y',
                            'p_cancel_time' => Carbon::now(),
                        ]);

                        ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->update([
                            'rgd_status6' => null,
                            'rgd_paid_date' => null,
                            'rgd_canceled_date' => Carbon::now(),
                        ]);

                        CancelBillHistory::insertGetId([
                            'mb_no' => Auth::user()->mb_no,
                            'rgd_no' => $rgd->rgd_parent_no,
                            'cbh_status_before' => 'paid',
                            'cbh_status_after' => 'cancel',
                            'cbh_type' => 'cancel_payment',
                        ]);

                        CancelBillHistory::insertGetId([
                            'mb_no' => Auth::user()->mb_no,
                            'rgd_no' => $rgd->rgd_parent_no,
                            'cbh_status_before' => 'cancel',
                            'cbh_status_after' => 'request_bill',
                            'cbh_type' => 'payment',
                        ]);
                    }
                }
            } else if ($request->bill_type == 'month_bill_final') { //final bill

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

                    if ($rgd_parent->rgd_status6 == 'paid' && $rgd_parent->is_expect_payment == 'y') {

                        $check_payment = Payment::where('rgd_no', $rgd['rgd_parent_no'])->where('p_success_yn', 'y')->orderBy('p_no', 'desc')->first();

                        if (isset($check_payment) && $rgd_parent->is_expect_payment == 'y') {
                            Payment::where('p_no', $check_payment->p_no)->update([
                                // 'p_price' => $request->sumprice,
                                // 'p_method' => $request->p_method,
                                'p_success_yn' => null,
                                'p_cancel_yn' => 'y',
                                'p_cancel_time' => Carbon::now(),
                            ]);

                            ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_parent_no'])->update([
                                'rgd_status6' => null,
                                'rgd_paid_date' => null,
                                'rgd_canceled_date' => Carbon::now(),
                            ]);

                            CancelBillHistory::insertGetId([
                                'mb_no' => Auth::user()->mb_no,
                                'rgd_no' => $rgd['rgd_parent_no'],
                                'cbh_status_before' => 'paid',
                                'cbh_status_after' => 'cancel',
                                'cbh_type' => 'cancel_payment',
                            ]);

                            CancelBillHistory::insertGetId([
                                'mb_no' => Auth::user()->mb_no,
                                'rgd_no' => $rgd['rgd_parent_no'],
                                'cbh_status_before' => 'cancel',
                                'cbh_status_after' => 'request_bill',
                                'cbh_type' => 'payment',
                            ]);
                        }
                    }
                }
            } else { //est_case_bill, est_monthly_bill, final_month_bill_fulfill
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
                $rgd_parent = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();

                if ($rgd_parent->rgd_status6 == 'paid' && $rgd_parent->is_expect_payment == 'y') {

                    $check_payment = Payment::where('rgd_no', $rgd->rgd_parent_no)->where('p_success_yn', 'y')->orderBy('p_no', 'desc')->first();

                    if (isset($check_payment) && $rgd_parent->is_expect_payment == 'y') {
                        Payment::where('p_no', $check_payment->p_no)->update([
                            // 'p_price' => $request->sumprice,
                            // 'p_method' => $request->p_method,
                            'p_success_yn' => null,
                            'p_cancel_yn' => 'y',
                            'p_cancel_time' => Carbon::now(),
                        ]);

                        ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->update([
                            'rgd_status6' => null,
                            'rgd_paid_date' => null,
                            'rgd_canceled_date' => Carbon::now(),
                        ]);

                        CancelBillHistory::insertGetId([
                            'mb_no' => Auth::user()->mb_no,
                            'rgd_no' => $rgd->rgd_parent_no,
                            'cbh_status_before' => 'paid',
                            'cbh_status_after' => 'cancel',
                            'cbh_type' => 'cancel_payment',
                        ]);

                        CancelBillHistory::insertGetId([
                            'mb_no' => Auth::user()->mb_no,
                            'rgd_no' => $rgd->rgd_parent_no,
                            'cbh_status_before' => 'cancel',
                            'cbh_status_after' => 'request_bill',
                            'cbh_type' => 'payment',
                        ]);
                    }
                }
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
            $list = CancelBillHistory::with('member')->where(function ($q) {
                $q->where('cbh_pay_method', '!=', 'deposit_without_bankbook')->where('cbh_pay_method', '!=', 'virtual_account')->orwhereNull('cbh_pay_method');
            })->where('rgd_no', '=', $request->rgd_no)->whereIn('cbh_type', ['payment', 'cancel_payment'])->orderBy('cbh_no', 'DESC')->paginate($per_page, ['*'], 'page', $page);

            return response()->json($list);
        } catch (\Exception $e) {
            return $e;
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function get_approval_history(CancelBillRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $list = CancelBillHistory::with(['member', 'rgd'])->where('rgd_no', '=', $request->rgd_no)->whereIn('cbh_type', ['approval', 'cancel_approval', 'revert_approval'])->orderBy('cbh_no', 'DESC')->paginate($per_page, ['*'], 'page', $page);

            return response()->json($list);
        } catch (\Exception $e) {
            return $e;
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
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

    public function payment_result(Request $request)
    {
        try {
            DB::beginTransaction();

            $rgd_no = $request->ETC1;

            $check_payment = Payment::where('rgd_no', $rgd_no)->orderBy('p_no', 'DESC')->first();
            $user = Member::where('mb_no', $request->ETC3)->first();

            $p_method_fee = $request->AMOUNT;

            if (isset($check_payment->p_no) && $check_payment->p_cancel_yn == 'y') {
                $rgd = ReceivingGoodsDelivery::with(['rate_data_general'])->where('rgd_no', $rgd_no)->first();

                Payment::insertGetId(
                    [
                        'mb_no' => $request->ETC3,
                        'rgd_no' => $check_payment->rgd_no,
                        'p_price' => $request->ETC2,
                        'p_method' => $request->ETC5,
                        'p_success_yn' => 'y',
                        'p_method_fee' => $request->AMOUNT,
                        'p_method_name' => null,
                        'p_method_number' => null,
                        'p_card_name' => $request->CARDNAME,

                        'p_resultmgs' => $request->RESULTMSG,
                        'p_orderno' => $request->ORDERNO,
                        'p_amount' => $request->AMOUNT,
                        'p_tid' => $request->TID,
                        'p_acceptdate' => $request->ACCEPTDATE,
                        'p_acceptno' => $request->ACCEPTNO,
                        'p_cardname' => $request->CARDNAME,
                        'p_accountno' => $request->ACCOUNTNO,
                        'p_cardno' => $request->ACCOUNTNO,
                        'p_receivername' => $request->RECEIVERNAME,
                        'p_depositenddate' => $request->DEPOSITENDDATE,
                        'p_cardcode' => $request->CARDCODE,
                    ]
                );
            } else if (isset($check_payment->p_no)) {
                $rgd = ReceivingGoodsDelivery::with(['rate_data_general'])->where('rgd_no', $rgd_no)->first();
                Payment::where('p_no', $check_payment->p_no)->update(
                    [
                        'mb_no' => $request->ETC3,
                        'rgd_no' => $rgd_no,
                        'p_price' => $request->ETC2,
                        'p_method' => $request->ETC5,
                        'p_success_yn' => 'y',
                        'p_method_fee' => $request->AMOUNT,
                        'p_method_name' => null,
                        'p_method_number' => null,
                        'p_card_name' => $request->CARDNAME,

                        'p_resultmgs' => $request->RESULTMSG,
                        'p_orderno' => $request->ORDERNO,
                        'p_amount' => $request->AMOUNT,
                        'p_tid' => $request->TID,
                        'p_acceptdate' => $request->ACCEPTDATE,
                        'p_acceptno' => $request->ACCEPTNO,
                        'p_cardname' => $request->CARDNAME,
                        'p_accountno' => $request->ACCOUNTNO,
                        'p_cardno' => $request->ACCOUNTNO,
                        'p_receivername' => $request->RECEIVERNAME,
                        'p_depositenddate' => $request->DEPOSITENDDATE,
                        'p_cardcode' => $request->CARDCODE,
                    ]
                );
            } else {
                $rgd = ReceivingGoodsDelivery::with(['rate_data_general'])->where('rgd_no', $rgd_no)->first();
                Payment::insertGetId(
                    [
                        'mb_no' => $request->ETC3,
                        'rgd_no' => $rgd_no,
                        'p_price' => $request->ETC2,
                        'p_method' => $request->ETC5,
                        'p_success_yn' => 'y',
                        'p_method_fee' => $request->AMOUNT,
                        'p_method_name' => null,
                        'p_method_number' => null,
                        'p_card_name' => $request->CARDNAME,

                        'p_resultmgs' => $request->RESULTMSG,
                        'p_orderno' => $request->ORDERNO,
                        'p_amount' => $request->AMOUNT,
                        'p_tid' => $request->TID,
                        'p_acceptdate' => $request->ACCEPTDATE,
                        'p_acceptno' => $request->ACCEPTNO,
                        'p_cardname' => $request->CARDNAME,
                        'p_accountno' => $request->ACCOUNTNO,
                        'p_cardno' => $request->ACCOUNTNO,
                        'p_receivername' => $request->RECEIVERNAME,
                        'p_depositenddate' => $request->DEPOSITENDDATE,
                        'p_cardcode' => $request->CARDCODE,
                    ]
                );
            }

            CancelBillHistory::insertGetId([
                'rgd_no' => $rgd_no,
                'mb_no' => $user->mb_no,
                'cbh_type' => 'payment',
                'cbh_status_before' => $rgd->rgd_status6,
                'cbh_status_after' => 'payment_bill',
                'cbh_pay_method' => $request->ETC5
            ]);

            if ($rgd->rgd_status7 == 'taxed' && $request->ETC5 != 'virtual_account') {
                CancelBillHistory::insertGetId([
                    'rgd_no' => $rgd_no,
                    'mb_no' => $rgd->mb_no,
                    'cbh_type' => 'tax',
                    'cbh_status_before' => $rgd->rgd_status7,
                    'cbh_status_after' => 'completed',
                ]);

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
                        'mb_no' => $rgd->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_before' => $est_rgd->rgd_status8,
                        'cbh_status_after' => 'completed'
                    ]);
                }
            }

            ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                // 'is_expect_payment' => $request->ETC5 != 'virtual_account' ? 'n' : 'y',
                'rgd_status6' => $request->ETC5 != 'virtual_account' ? 'paid' : 'virtual_account',
                'rgd_paid_date' => $request->ETC5 != 'virtual_account' ? Carbon::now() : null,
            ]);

            if ($rgd->service_korean_name == '보세화물') {
                $ad_tile = '[보세화물] 결제완료';
            } else if ($rgd->service_korean_name == '수입풀필먼트') {
                $ad_tile = '[수입풀필먼트] 결제완료';
            } else if ($rgd->service_korean_name == '유통가공') {
                $ad_tile = '[유통가공] 결제완료';
            }


            if ($request->ETC5 != 'virtual_account') {
                $sender = Member::where('mb_no', $rgd->mb_no)->first();
                CommonFunc::insert_alarm($ad_tile, $rgd, $sender, null, 'settle_payment', $p_method_fee);
            }

            DB::commit();
            return redirect($request->ETC4);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function check_payment()
    {

        try {
            DB::beginTransaction();
            $tokenheaders = array();
            array_push($tokenheaders, "content-type: application/json; charset=utf-8");
            $token_url = "https://www.cookiepayments.com/payAuth/token";
            $token_request_data = array(
                'pay2_id' => 'hfmkpjm2hnr',
                'pay2_key' => '619a3048e7e01eaabd23d2017ff5dce18e14431a2d69cd9d8c',
            );
            $req_json = json_encode($token_request_data, TRUE);
            $ch = curl_init(); // curl 초기화
            curl_setopt($ch, CURLOPT_URL, $token_url);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req_json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $tokenheaders);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $RES_STR = curl_exec($ch);

            curl_close($ch);
            $RES_STR = json_decode($RES_STR, TRUE);
            /* 여기 까지 */
            if ($RES_STR['RTN_CD'] == '0000') {

                $std = Carbon::now()->subDays(7)->format('Y-m-d');
                $end = Carbon::now()->format('Y-m-d');

                $headers = array();
                array_push($headers, "content-type: application/json; charset=utf-8");
                array_push($headers, "TOKEN:" . $RES_STR['TOKEN']);
                $cookiepayments_url = "https://www.cookiepayments.com/api/paysearch";
                $request_data_array = array(
                    'API_ID' => 'hfmkpjm2hnr',
                    'STD_DT' => $std,
                    'END_DT' => $end,
                );
                $cookiepayments_json = json_encode($request_data_array, TRUE);
                $ch = curl_init(); // curl 초기화
                curl_setopt($ch, CURLOPT_URL, $cookiepayments_url);
                curl_setopt($ch, CURLOPT_POST, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $cookiepayments_json);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $response = curl_exec($ch);
                curl_close($ch);

                $payments  = json_decode($response, TRUE);
                $ordernos = [];
                $tids = [];
                $acceptdates = [];

                foreach ($payments as $index => $payment) {
                    if ($payment['PAYMETHOD'] && $payment['CANCELDATE'] == '') {
                        $ordernos[] = $payment['ORDERNO'];

                        Payment::where('p_orderno', $payment['ORDERNO'])->update([
                            'p_tid' => $payment['TID'],
                            'p_acceptdate' => $payment['ACCEPTDATE']
                        ]);
                    }
                }

                $rgds = ReceivingGoodsDelivery::with(['payment', 'warehousing'])->whereHas('payment', function ($q) use ($ordernos) {
                    $q->where('p_method', 'virtual_account')->whereIn('p_orderno', $ordernos)->where('p_depositenddate', '>', Carbon::now()->format('YmdHis'));
                })->where('rgd_status6', 'virtual_account')->get();

                $rgds_expired = ReceivingGoodsDelivery::with(['payment'])
                    ->where(function ($q) {
                        $q->where('rgd_status6', '!=', 'cancel')->orWhereNull('rgd_status6');
                    })->whereHas('payment', function ($q) use ($ordernos) {
                        $q->where('p_method', 'virtual_account')->where('p_depositenddate', '<', Carbon::now()->format('YmdHis'));
                    })->where('rgd_status6', 'virtual_account')->get();


                foreach ($rgds_expired as $index => $rgd) {
                    $check_payment = Payment::where('rgd_no', $rgd->rgd_no)
                        ->where('p_success_yn', 'y')
                        ->where(function ($q) {
                            $q->where('p_cancel_yn', '!=', 'y')->orWhereNull('p_cancel_yn');
                        })
                        ->where('p_method', 'virtual_account')
                        ->orderBy('p_no', 'desc')
                        ->first();

                    if (isset($check_payment)) {

                        Payment::where('p_no', $check_payment->p_no)->update([
                            // 'p_price' => $request->sumprice,
                            // 'p_method' => $request->p_method,
                            'p_success_yn' => null,
                            'p_cancel_yn' => 'y',
                            'p_cancel_time' => Carbon::now(),
                        ]);

                        ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_no)->update([
                            'rgd_status6' => 'cancel',
                            'rgd_paid_date' => null,
                            'rgd_canceled_date' => Carbon::now(),
                        ]);

                        // CancelBillHistory::insertGetId([
                        //     'mb_no' => $rgd->mb_no,
                        //     'rgd_no' => $rgd->rgd_no,
                        //     'cbh_status_before' => 'paid',
                        //     'cbh_status_after' => 'cancel',
                        //     'cbh_type' => 'cancel_payment',
                        // ]);

                        // CancelBillHistory::insertGetId([
                        //     'mb_no' => $rgd->mb_no,
                        //     'rgd_no' => $rgd->rgd_no,
                        //     'cbh_status_before' => 'cancel',
                        //     'cbh_status_after' => 'request_bill',
                        //     'cbh_type' => 'payment',
                        // ]);

                        if ($rgd->rgd_status8 == 'completed') {
                            CancelBillHistory::insertGetId([
                                'rgd_no' => $rgd->rgd_no,
                                'mb_no' => $rgd->mb_no,
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
                }

                foreach ($rgds as $index => $rgd) {
                    if ($rgd->rgd_status7 == 'taxed') {
                        CancelBillHistory::insertGetId([
                            'rgd_no' => $rgd->rgd_no,
                            'mb_no' => $rgd->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_before' => $rgd->rgd_status7,
                            'cbh_status_after' => 'completed',
                        ]);

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
                                'mb_no' => $rgd->mb_no,
                                'cbh_type' => 'tax',
                                'cbh_status_before' => $est_rgd->rgd_status8,
                                'cbh_status_after' => 'completed'
                            ]);
                        }
                    }

                    ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                        // 'is_expect_payment' => $request->ETC5 != 'virtual_account' ? 'n' : 'y',
                        'rgd_status6' => 'paid',
                        'rgd_paid_date' => Carbon::now(),
                    ]);

                    CancelBillHistory::insertGetId([
                        'rgd_no' => $rgd['rgd_no'],
                        'mb_no' => isset($rgd['payment']) ? $rgd['payment']['mb_no'] : null,
                        'cbh_type' => 'payment',
                        'cbh_status_before' => $rgd['rgd_status6'],
                        'cbh_status_after' => 'payment_bill',
                        'cbh_pay_method' => null,
                    ]);

                    if ($rgd->service_korean_name == '보세화물') {
                        $ad_tile = '[보세화물] 결제완료';
                    } else if ($rgd->service_korean_name == '수입풀필먼트') {
                        $ad_tile = '[수입풀필먼트] 결제완료';
                    } else if ($rgd->service_korean_name == '유통가공') {
                        $ad_tile = '[유통가공] 결제완료';
                    }


                    $sender = Member::where('mb_no', $rgd->mb_no)->first();
                    CommonFunc::insert_alarm($ad_tile, $rgd, $sender, null, 'settle_payment', $rgd['payment']['p_price']);
                }
            }

            DB::commit();
            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function update_memo(Request $request)
    {
        try {
            DB::beginTransaction();

            $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                'rgd_memo_settle' => $request->memo
            ]);

            DB::commit();
            return response()->json(['message' => Messages::MSG_0007, 'rgd' => $rgd], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\RateData;

use App\Http\Controllers\Controller;
use App\Http\Requests\RateData\RateDataImportFulfillmentRequest;
use App\Http\Requests\RateData\RateDataRequest;
use App\Http\Requests\RateData\RateDataSendMailRequest;
use App\Models\RateData;
use App\Models\RateMeta;
use App\Models\RateMetaData;
use App\Utils\CommonFunc;
use App\Utils\Messages;
use App\Models\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            }else if(empty($validated['newRmd_no']) && isset($validated['co_no'])){
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
                        'rd_no' => (isset($rmd_no) || empty($val['rmd_no']) || ($val['rmd_no'] != $validated['newRmd_no']))? null : $val['rd_no'],
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

    public function getRateDataByRmno($rm_no)
    {
        try {
            $my_rate_data1 = RateData::where(['rm_no' => $rm_no, 'rd_cate_meta1' => '보세화물'])->get();
            $my_rate_data2 = RateData::where(['rm_no' => $rm_no, 'rd_cate_meta1' => '수입풀필먼트'])->get();
            $my_rate_data3 = RateData::where(['rm_no' => $rm_no, 'rd_cate_meta1' => '유통가공'])->get();
            return response()->json(['message' => Messages::MSG_0007, 'my_rate_data1' => $my_rate_data1, 'my_rate_data2' => $my_rate_data2, 'my_rate_data3' => $my_rate_data3], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function createOrUpdateImportFulfillment(RateDataImportFulfillmentRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();

            if (isset($validated['co_no'])) {
                $rm_no = RateMeta::insertGetId([
                    'co_no' => $validated['co_no'],
                    'mb_no' => Auth::user()->mb_no,
                ]);
            }
            if (empty($validated['newRmd_no'])) {
                $rmd_no = RateMetaData::insertGetId(
                    [
                        'mb_no' => Auth::user()->mb_no,
                        'rm_no' => $validated['rm_no'],
                    ]
                );
            }

            foreach ($validated['rate_data'] as $val) {
                RateData::updateOrCreate(
                    [
                        'rd_no' => isset($rmd_no) ? null : (isset($val['rd_no']) ?  $val['rd_no'] : null),
                        'rmd_no' => isset($rmd_no) ? $rmd_no : $validated['newRmd_no'],
                        'rm_no' => isset($validated['rm_no']) ? $validated['rm_no'] : $rm_no,
                    ],
                    [
                        'co_no' => isset($validated['co_no']) ? $validated['co_no'] : null,
                        'rd_cate_meta1' => $val['rd_cate_meta1'],
                        'rd_cate_meta2' => '',
                        'rd_cate1' => $val['rd_cate1'],
                        'rd_cate2' => $val['rd_cate2'],
                        'rd_cate3' => '',
                        'rd_data1' => $val['rd_data1'],
                        'rd_data2' => $val['rd_data2'],
                        'rd_data3' => $val['rd_data3'],
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

    public function getRateDataByImportFulfillmentByRmno($rm_no, $rmd_no)
    {
        $co_no = Auth::user()->co_no;
        try {
            $rate_data = RateData::where('rm_no', $rm_no)
                ->where('rmd_no', $rmd_no)
                ->where('rd_cate_meta1', '수입풀필먼트')
                ->get();
            $co_rate_data = RateData::where('co_no', $co_no)
                ->where('rd_cate_meta1', '수입풀필먼트')
                ->get();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rate_data' => $rate_data,
                'co_rate_data' => $co_rate_data,
            ], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getRateDataByImportFulfillmentByCono($co_no)
    {
        try {
            $rate_data = RateData::select([
                'rd_no',
                'rm_no',
                'rd_cate_meta1',
                'rd_cate1',
                'rd_cate2',
                'rd_data1',
                'rd_data2',
                'rd_data3',
            ])
                ->where('rd_cate_meta1', '수입풀필먼트')
                ->where('co_no', $co_no)
                ->get();
            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function spasysRegisterRateData(RateDataRequest $request)
    {
        $validated = $request->validated();
        $co_no = Auth::user()->co_no;
        // try {
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
        // } catch (\Exception $e) {
        //     DB::rollback();
        //     Log::error($e);
        //     return response()->json(['message' => Messages::MSG_0001], 500);
        // }
    }

    public function spasysRegisterRateData2(RateDataImportFulfillmentRequest $request)
    {
        $validated = $request->validated();
        $co_no = Auth::user()->co_no;
        try {
            DB::beginTransaction();
            foreach ($validated['rate_data'] as $val) {
                RateData::updateOrCreate(
                    [
                        'rd_no' => isset($val['rd_no']) ? $val['rd_no'] : null,
                        'co_no' => isset($co_no) ? $co_no : null,
                    ],
                    [
                        'rd_cate_meta1' => $val['rd_cate_meta1'],
                        'rd_cate_meta2' => '',
                        'rd_cate1' => $val['rd_cate1'],
                        'rd_cate2' => $val['rd_cate2'],
                        'rd_cate3' => '',
                        'rd_data1' => $val['rd_data1'],
                        'rd_data2' => $val['rd_data2'],
                        'rd_data3' => $val['rd_data3'],
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
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function getSpasysRateData()
    {
        $co_no = Auth::user()->co_no;
        try {
            $rate_data = RateData::select([
                'rd_no',
                'rd_cate_meta1',
                'rd_cate_meta2',
                'rd_cate1',
                'rd_cate2',
                'rd_cate3',
                'rd_data1',
                'rd_data2',
                'rd_data3',
            ])->where('co_no', $co_no)->get();
            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getSpasysRateData2()
    {
        $co_no = Auth::user()->co_no;
        try {
            $rate_data = RateData::select([
                'rd_no',
                'rd_cate_meta1',
                'rd_cate_meta2',
                'rd_cate1',
                'rd_cate2',
                'rd_cate3',
                'rd_data1',
                'rd_data2',
                'rd_data3',
            ])->where('co_no', $co_no)->where('rd_cate_meta1', '수입풀필먼트')->get();
            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getSpasysRateData3()
    {
        $co_no = Auth::user()->co_no;
        try {
            $rate_data = RateData::select([
                'rd_no',
                'rd_cate_meta1',
                'rd_cate_meta2',
                'rd_cate1',
                'rd_cate2',
                'rd_cate3',
                'rd_data1',
                'rd_data2',
                'rd_data3',
            ])->where('co_no', $co_no)->where('rd_cate_meta1', '유통가공')->get();
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
                'content' => $validated['content']
            ];

            $files = [];
            $urls = [];
            foreach ($validated['files'] as $file){
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
                    'file_url' => $url
                ];
            }
            File::insert($files);

            Mail::send('emails.rate_data', $content, function($message) use ($validated, $urls) {
                $message->to($validated["recipient_mail"])
                        ->subject($validated["subject"])
                        ->from(env('MAIL_FROM_ADDRESS'),  $validated['sender_name']);

                if(!empty($validated['cc'])) {
                    $message->cc($validated['cc']);
                }

                foreach ($urls as $file){
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
}

<?php

namespace App\Http\Controllers\RateData;

use App\Models\Member;
use App\Utils\Messages;
use Illuminate\Http\Request;
use App\Models\RateData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\RateData\RateDataRequest;

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
            foreach ($validated as $val) {
                Log::error($val);
                $rdsm_no = RateData::updateOrCreate(
                    [
                        'rd_no' => isset($val['rd_no']) ? $val['rd_no'] : null,
                    ],
                    [
                        'rm_no' => $val['rm_no'],
                        'rd_cate_meta1' => $val['rd_cate_meta1'],
                        'rd_cate_meta2' => $val['rd_cate_meta2'],
                        'rd_cate1' => $val['rd_cate1'],
                        'rd_cate2' => $val['rd_cate2'],
                        'rd_cate3' => $val['rd_cate3'],
                        'rd_data1' => $val['rd_data1'],
                        'rd_data2' => $val['rd_data2'],
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

    public function getRateData($rm_no)
    {
        try {
            $rate_data = RateData::select([
                'rd_no',
                'rm_no',
                'rd_cate_meta1',
                'rd_cate_meta2',
                'rd_cate1',
                'rd_cate2',
                'rd_cate3',
                'rd_data1',
                'rd_data2',
            ])->where('rm_no', $rm_no)->get();
            return response()->json(['message' => Messages::MSG_0007, 'rate_data' => $rate_data], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
}
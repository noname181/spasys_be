<?php

namespace App\Http\Controllers\RateDataSendMeta;

use App\Models\Member;
use App\Utils\Messages;
use Illuminate\Http\Request;
use App\Models\RateDataSendMeta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\RateDataSendMeta\RateDataSendMetaRequest;

class RateDataSendMetaController extends Controller
{
    /**
     * Register RateDataSendMetaCreate
     * @param  App\Http\Requests\RateDataSendMetaRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(RateDataSendMetaRequest $request)
    {
        $validated = $request->validated();
        try {
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $rdsm_no = RateDataSendMeta::insertGetId([
                'mb_no' => $member->mb_no,
                'rdsm_biz_name' => $validated['rdsm_biz_name'],
                'rdsm_owner_name' => $validated['rdsm_owner_name'],
                'rdsm_biz_number' => $validated['rdsm_biz_number'],
                'rdsm_biz_address' => $validated['rdsm_biz_address'],
                'rdsm_biz_address_detail' => $validated['rdsm_biz_address_detail'],
                'rdsm_biz_email' => $validated['rdsm_biz_email'],
                'rdsm_name' => $validated['rdsm_name'],
                'rdsm_hp' => $validated['rdsm_hp'],
            ]);
            return response()->json(['message' => Messages::MSG_0007, 'rdsm_no' => $rdsm_no], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function getRDSM($rdsm_no)
    {
        try {
            $rate_data_send_meta = RateDataSendMeta::where('rdsm_no', $rdsm_no)->first();
            return response()->json(['message' => Messages::MSG_0007, 'rate_data_send_meta' => $rate_data_send_meta], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function updateRDSM(RateDataSendMetaRequest $request, RateDataSendMeta $rdsm)
    {
        $validated = $request->validated();
        try {
            $update = [
                'rdsm_biz_name' => $validated['rdsm_biz_name'],
                'rdsm_owner_name' => $validated['rdsm_owner_name'],
                'rdsm_biz_number' => $validated['rdsm_biz_number'],
                'rdsm_biz_address' => $validated['rdsm_biz_address'],
                'rdsm_biz_address_detail' => $validated['rdsm_biz_address_detail'],
                'rdsm_biz_email' => $validated['rdsm_biz_email'],
                'rdsm_name' => $validated['rdsm_name'],
                'rdsm_hp' => $validated['rdsm_hp'],
            ];
            $rate_data_send_meta = RateDataSendMeta::where(['rdsm_no' => $rdsm->rdsm_no])
                ->update($update);
            return response()->json([
                'message' => Messages::MSG_0007,
                'rate_data_send_meta' => $rate_data_send_meta,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }
}

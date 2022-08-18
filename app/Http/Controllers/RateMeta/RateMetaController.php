<?php

namespace App\Http\Controllers\RateMeta;

use App\Models\Member;
use App\Utils\Messages;
use Illuminate\Http\Request;
use App\Models\RateMeta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\RateMeta\RateMetaRequest;

class RateMetaController extends Controller
{
    /**
     * Register RateDataSendMetaCreate
     * @param  App\Http\Requests\RateDataSendMetaRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(RateMetaRequest $request)
    {
        $validated = $request->validated();
        try {
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $rm_no = RateMeta::insertGetId([
                'mb_no' => $member->mb_no,
                'rm_biz_name' => $validated['rm_biz_name'],
                'rm_owner_name' => $validated['rm_owner_name'],
                'rm_biz_number' => $validated['rm_biz_number'],
                'rm_biz_address' => $validated['rm_biz_address'],
                // 'rm_biz_address_detail' => $validated['rm_biz_address_detail'],
                'rm_biz_email' => $validated['rm_biz_email'],
                'rm_name' => $validated['rm_name'],
                'rm_hp' => $validated['rm_hp'],
            ]);
            return response()->json(['message' => Messages::MSG_0007, 'rm_no' => $rm_no], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function getRDSM($rm_no)
    {
        try {
            $rate_data_send_meta = RateMeta::select([
                'rm_biz_name',
                'rm_owner_name',
                'rm_biz_number',
                'rm_biz_email',
                'rm_biz_address',
                'rm_biz_address_detail',
                'rm_name',
                'rm_hp',
            ])->where('rm_no', $rm_no)->first();
            return response()->json(['message' => Messages::MSG_0007, 'rate_data_send_meta' => $rate_data_send_meta], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function updateRDSM(RateMetaRequest $request, RateMeta $rm)
    {
        $validated = $request->validated();
        try {
            $update = [
                'rm_biz_name' => $validated['rm_biz_name'],
                'rm_owner_name' => $validated['rm_owner_name'],
                'rm_biz_number' => $validated['rm_biz_number'],
                'rm_biz_address' => $validated['rm_biz_address'],
                // 'rm_biz_address_detail' => $validated['rm_biz_address_detail'],
                'rm_biz_email' => $validated['rm_biz_email'],
                'rm_name' => $validated['rm_name'],
                'rm_hp' => $validated['rm_hp'],
            ];
            $rate_data_send_meta = RateMeta::where(['rm_no' => $rm->rm_no])
                ->update($update);
            return response()->json([
                'message' => Messages::MSG_0007,
                'rate_data_send_meta' => $rate_data_send_meta,
            ], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }
}

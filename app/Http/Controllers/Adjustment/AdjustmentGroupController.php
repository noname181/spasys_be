<?php

namespace App\Http\Controllers\Adjustment;

use App\Models\Member;
use App\Utils\Messages;
use App\Models\AdjustmentGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Adjustment\AdjustmentGroupCreateRequest;
use App\Http\Requests\Adjustment\AdjustmentGroupUpdateRequest;

class AdjustmentGroupController extends Controller
{

    /**
     * create AdjustmentGroup
     * @param  AdjustmentGroupCreateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function create(AdjustmentGroupCreateRequest $request)
    {

        try {

            $validated = $request->validated();
            $ag_no = AdjustmentGroup::insertGetId([
                'mb_no' => $validated['mb_no'],
                'co_no' => $validated['co_no'],
                'ag_name' => $validated['ag_name'],
                'ag_hp' => $validated['ag_hp'],
                'ag_manager' => $validated['ag_manager'],
                'ag_email' => $validated['ag_email'],
                'ag_regtime' =>  date('Y-m-d')
            ]);
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'ag_no' => $ag_no,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    /**
     * Update AdjustmentGroup by id
     * @param  AdjustmentGroup $adjustmentGroup
     * @param  AdjustmentGroupUpdateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(AdjustmentGroup $adjustmentGroup, AdjustmentGroupUpdateRequest $request)
    {
        try {
            $validated = $request->validated();
            $adjustmentGroup->update([
                'mb_no' => $validated['mb_no'],
                'co_no' => $validated['co_no'],
                'ag_name' => $validated['ag_name'],
                'ag_hp' => $validated['ag_hp'],
                'ag_manager' => $validated['ag_manager'],
                'ag_email' => $validated['ag_email'],
                'ag_regtime' =>  date('Y-m-d')
            ]);
            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }
}

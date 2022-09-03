<?php

namespace App\Http\Controllers\Alarm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alarm\AlarmSearchRequest;
use App\Http\Requests\Alarm\AlarmRequest;
use App\Models\Alarm;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlarmController extends Controller
{
    /**
     * Register and Update AlarmRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(AlarmRequest $request)
    {

        $validated = $request->validated();
        try {
            DB::beginTransaction();
            $alarm_no = $request->get('alarm_no');
            if (!isset($alarm_no)) {
                $alarm_no = Alarm::insertGetId([
                    'mb_no' => Auth::user()->mb_no,
                    'item_no' => 1, // FIXME hard set
                    'alarm_content' => $validated['alarm_content']
                ]);
            } else {
                // Update data
                $alarm = Alarm::where('alarm_no', $alarm_no)->first();
                if (is_null($alarm)) {
                    return response()->json(['message' => Messages::MSG_0020], 404);
                }

                $update = [
                    'mb_no' => Auth::user()->mb_no,
                    'item_no' => 1,
                    'alarm_content' => $validated['alarm_content']
                ];
                $alarm->update($update);
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }

    public function searchAlarms(AlarmSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $alarm = Alarm::with('item','warehousing_item','member')->paginate($per_page, ['*'], 'page', $page);
            return response()->json($alarm);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }


    public function getAlarmById(Alarm $alarm)
    {
        try {
            return response()->json($alarm);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
}

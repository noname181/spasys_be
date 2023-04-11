<?php

namespace App\Http\Controllers\AlarmData;

use App\Http\Requests\AlarmData\AlarmDataRequest;
use App\Http\Requests\AlarmData\AlarmDataRegisterRequest;
use App\Http\Requests\AlarmData\AlarmDatatUpdateRequest;
use App\Http\Controllers\Controller;
use App\Models\AlarmData;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AlarmDataController extends Controller
{
    /**
     * Fetch list alarmdata
     */
    public function __invoke(AlarmDataRequest $request)
    {
        
        try {
            $validated = $request->validated();
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            $alarmdatas = AlarmData::paginate($per_page, ['*'], 'page', $page);
            return response()->json($alarmdatas);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Get AlarmData detail by id
     */
    public function getAlarmDataDetail(AlarmData $alarmdata){
        return response()->json($alarmdata);
    }

    /**
     * Register Contract
     * @param  App\Http\Requests\AlarmData\AlarmDataRegisterRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createAlarmData(AlarmDataRegisterRequest $request)
    {
        $validated = $request->validated();
        try {
            $alarmdata = AlarmData::insertGetId([
                'mb_no' => Auth::user()->mb_no,
                'ad_category' => $validated['ad_category'],
                'ad_title' => $validated['ad_title'],
                'ad_content' => $validated['ad_content'],
                'ad_time' => $validated['ad_time'],
                'ad_must_yn' => $validated['ad_must_yn'],
                'ad_use_yn' => $validated['ad_use_yn']
            ]);

            return response()->json([
                'message' => Messages::MSG_0007,
                'alarmdata' => $alarmdata,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    /**
     * Update AlarmData
     * @param  App\Http\Requests\AlarmData\AlarmDatatUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAlarmData(AlarmDatatUpdateRequest $request, AlarmData $alarmdata)
    {
        $validated = $request->validated();
        try {
            $update = [
                'ad_category' => $validated['ad_category'],
                'ad_title' => $validated['ad_title'],
                'ad_content' => $validated['ad_content'],
                'ad_time' => $validated['ad_time'],
                'ad_must_yn' => $validated['ad_must_yn'],
                'ad_use_yn' => $validated['ad_use_yn'],
            ];

            $alarmdata = AlarmData::where(['ad_no' => $alarmdata->ad_no])
                ->update($update);
            return response()->json([
                'message' => Messages::MSG_0007,
                'alarmdata' => $alarmdata,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }
    public function searchAlarmData(AlarmDataRequest $request)
    {
        $validated = $request->validated();
        try {
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            $alarmdata = AlarmData::whereRaw('1 = 1');
            if(isset($validated['ad_category'])) {
                $alarmdata->where('ad_category', 'like', '%'.$validated['ad_category'].'%');
            }
            if(isset($validated['ad_title'])) {
                $alarmdata->where('ad_title', 'like', '%'.$validated['ad_title'].'%');
            }
            if(isset($validated['ad_must_yn'])) {
                $alarmdata->where('ad_must_yn', 'like', '%'.$validated['ad_must_yn'].'%');
            }
            if(isset($validated['ad_use_yn'])) {
                $alarmdata->where('ad_use_yn', 'like', '%'.$validated['ad_use_yn'].'%');
            }
            $alarmdata = $alarmdata->orderBy('ad_no', 'DESC')->paginate($per_page, ['*'], 'page', $page);
            
            return response()->json($alarmdata);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
}

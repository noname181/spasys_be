<?php

namespace App\Http\Controllers\AlarmData;

use App\Http\Requests\AlarmData\AlarmDataRequest;
use App\Http\Requests\AlarmData\AlarmDataRegisterRequest;
use App\Http\Requests\AlarmData\AlarmDataUpdateRequest;
use App\Http\Controllers\Controller;
use App\Models\AlarmData;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use \Carbon\Carbon;
use App\Utils\CommonFunc;
use App\Models\Company;
use App\Models\Member;

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
    public function getAlarmDataDetail(AlarmData $alarm_data)
    {
        return response()->json($alarm_data);
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
     * @param  App\Http\Requests\AlarmData\AlarmDataUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAlarmData(AlarmDataUpdateRequest $request, AlarmData $alarm_data)
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

            $alarm_data = AlarmData::where(['ad_no' => $alarm_data->ad_no])
                ->update($update);
            return response()->json([
                'message' => Messages::MSG_0007,
                'alarmdata' => $alarm_data,
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
            if (isset($validated['ad_category'])) {
                $alarmdata->where('ad_category', 'like', '%' . $validated['ad_category'] . '%');
            }
            if (isset($validated['ad_title'])) {
                $alarmdata->where('ad_title', 'like', '%' . $validated['ad_title'] . '%');
            }
            if (isset($validated['ad_must_yn'])) {
                $alarmdata->where('ad_must_yn', 'like', '%' . $validated['ad_must_yn'] . '%');
            }
            if (isset($validated['ad_use_yn'])) {
                $alarmdata->where('ad_use_yn', 'like', '%' . $validated['ad_use_yn'] . '%');
            }
            $alarmdata = $alarmdata->orderBy('ad_no', 'DESC')->paginate($per_page, ['*'], 'page', $page);

            return response()->json($alarmdata);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function insertDailyAlarm7()
    {
        try {
            DB::beginTransaction();

            $companies = Company::with(['contract', 'co_parent', 'co_childen', 'mb_no'])
                ->whereHas('contract', function ($q) {
                    $q->whereBetween('c_end_date', [Carbon::now()->startOfDay(), Carbon::now()->addDays(7)->endOfDay()]);
                })
                ->where('co_type', '!=', 'spasys')
                ->orderBy('co_no', 'DESC')->get();

            foreach ($companies as $company) {
                CommonFunc::insert_alarm_company_daily('계약 종료일', null, null, $company, 'alarm_daily7');
            }

            DB::commit();
            return response()->json([
                'companies' => $companies,
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function insertDailyAlarm30()
    {
        try {
            DB::beginTransaction();

            $companies = Company::with(['contract', 'co_parent', 'co_childen', 'mb_no'])
                ->whereHas('contract', function ($q) {
                    $q->whereBetween('c_end_date', [Carbon::now()->addDays(8)->startOfDay(), Carbon::now()->addDays(30)->endOfDay()]);
                })
                ->where('co_type', '!=', 'spasys')
                ->orderBy('co_no', 'DESC')->get();

            foreach ($companies as $company) {
                CommonFunc::insert_alarm_company_daily('계약 종료일', null, null, $company, 'alarm_daily30');
            }

            DB::commit();
            return response()->json([
                'companies' => $companies,
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function insertDailyAlarmInsulace7()
    {
        try {
            DB::beginTransaction();

            $companies = Company::with(['contract', 'co_parent', 'co_childen', 'mb_no'])
                ->whereHas('contract', function ($q) {
                    $q->whereBetween('c_deposit_return_expiry_date', [Carbon::now()->startOfDay(), Carbon::now()->addDays(7)->endOfDay()]);
                })
                ->where('co_type', '!=', 'spasys')
                ->orderBy('co_no', 'DESC')->get();

            foreach ($companies as $company) {
                CommonFunc::insert_alarm_insulace_company_daily('보증보험 만료일', null, null, $company, 'alarm_daily_insulace7');
            }

            DB::commit();
            return response()->json([
                'companies' => $companies,
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function insertDailyAlarmInsulace30()
    {
        try {
            DB::beginTransaction();

            $companies = Company::with(['contract', 'co_parent', 'co_childen', 'mb_no'])
                ->whereHas('contract', function ($q) {
                    $q->whereBetween('c_deposit_return_expiry_date', [Carbon::now()->addDays(8)->startOfDay(), Carbon::now()->addDays(30)->endOfDay()]);
                })
                ->where('co_type', '!=', 'spasys')
                ->orderBy('co_no', 'DESC')->get();

            foreach ($companies as $company) {
                CommonFunc::insert_alarm_insulace_company_daily('보증보험 만료일', null, null, $company, 'alarm_daily_insulace30');
            }

            DB::commit();
            return response()->json([
                'companies' => $companies,
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function alarmPw30day()
    {
        try {
            DB::beginTransaction();

            $companies = Member::with(['company'])->get();
            foreach ($companies as $company) {
                //CommonFunc::insert_alarm_insulace_company_daily('보증보험 만료일', null, null, $company, 'alarm_daily_insulace30');
            }

            DB::commit();
            return response()->json([
                'companies' => $companies,
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }
}

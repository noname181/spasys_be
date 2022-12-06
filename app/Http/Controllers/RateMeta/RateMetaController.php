<?php

namespace App\Http\Controllers\RateMeta;

use App\Models\Member;
use App\Utils\Messages;
use App\Models\RateMeta;
use App\Models\RateData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\RateMeta\RateMetaSearchRequest;
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
                'rm_mail_detail' => isset($validated['rm_mail_detail']) ? $validated['rm_mail_detail'] : '',
            ]);
            return response()->json(['message' => Messages::MSG_0007, 'rm_no' => $rm_no], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function getrm($rm_no)
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
                'rm_no',
                'rm_mail_detail'
            ])->with(['send_email'])->where('rm_no', $rm_no)->first();
            return response()->json(['message' => Messages::MSG_0007, 'rate_data_send_meta' => $rate_data_send_meta], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function updaterm(RateMetaRequest $request, RateMeta $rm)
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
                'rm_mail_detail' => $validated['rm_mail_detail'],
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

    public function getRateData(RateMetaSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $rm = RateMeta::whereRaw('1 = 1');
            if(isset($validated['from_date'])) {
                $rm->where('created_at', '>=' , date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }
            if(isset($validated['to_date'])) {
                $rm->where('created_at', '<=' , date('Y-m-d 23:59:59', strtotime($validated['to_date'])));
            }
            if(isset($validated['rm_biz_name'])) {
                $rm->where('rm_biz_name', 'like', '%'.$validated['rm_biz_name'].'%');
            }
            if(isset($validated['rm_biz_number'])) {
                $rm->where('rm_biz_number','like', '%'.$validated['rm_biz_number'].'%');
            }
            if(isset($validated['rm_owner_name'])) {
                $rm->where('rm_owner_name','like', '%'.$validated['rm_owner_name'].'%');
            }
            $rm = $rm->paginate($per_page, ['*'], 'page', $page);
            return response()->json($rm);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getRateDataCompany(RateMetaSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $rm = RateMeta::whereRaw('1 = 1');
            if(isset($validated['from_date'])) {
                $rm->where('created_at', '>=' , date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }
            if(isset($validated['to_date'])) {
                $rm->where('created_at', '<=' , date('Y-m-d 23:59:59', strtotime($validated['to_date'])));
            }
            if(isset($validated['rm_biz_name'])) {
                $rm->where('rm_biz_name', 'like', '%'.$validated['rm_biz_name'].'%');
            }
            if(isset($validated['rm_biz_number'])) {
                $rm->where('rm_biz_number','like', '%'.$validated['rm_biz_number'].'%');
            }
            if(isset($validated['rm_owner_name'])) {
                $rm->where('rm_owner_name','like', '%'.$validated['rm_owner_name'].'%');
            }
            $rm = $rm->paginate($per_page, ['*'], 'page', $page);
            return response()->json($rm);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\CustomsInfo;

use App\Models\Member;
use App\Utils\Messages;
use Illuminate\Http\Request;
use App\Models\CustomsInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CustomsInfo\CustomsInfoCreateRequest;
use App\Http\Requests\CustomsInfo\CustomsInfoUpdateRequest;

class CustomsInfoController extends Controller
{

    public function create($co_no, CustomsInfoCreateRequest $request)
    {

        try {
            DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $validated = $request->validated();

            foreach ($validated  as $value) {

                if(isset($value['ci_no'])){
                    CustomsInfo::where('ci_no', $value['ci_no'])->update([
                        'mb_no' => $member->mb_no,
                        'co_no' => $co_no,
                        'ci_name' => $value['ci_name'],
                        'ci_hp' => $value['ci_hp'],
                        'ci_manager' => $value['ci_manager'],
                        'ci_address' => $value['ci_address'],
                        'ci_address_detail' => $value['ci_address_detail'],
                    ]);
                }else {
                    $CustomsInfo = CustomsInfo::insertGetId([
                        'mb_no' => $member->mb_no,
                        'co_no' => $co_no,
                        'ci_name' => $value['ci_name'],
                        'ci_hp' => $value['ci_hp'],
                        'ci_manager' => $value['ci_manager'],
                        'ci_address' => $value['ci_address'],
                        'ci_address_detail' => $value['ci_address_detail'],
                    ]);
                }

            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'ci_no' =>  $request->all(),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            // return response()->json(['message' => Messages::MSG_0001], 500);
            return $e;
        }
    }

    public function create_with_co_no($co_no, CustomsInfoCreateRequest $request)
    {

        try {
            DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $validated = $request->validated();

            foreach ($validated  as $value) {


                $CustomsInfo = CustomsInfo::insertGetId([
                    'mb_no' => $member->mb_no,
                    'co_no' => $co_no,
                    'ci_name' => $value['ci_name'],
                    'ci_hp' => $value['ci_hp'],
                    'ci_manager' => $value['ci_manager'],
                    'ci_address' => $value['ci_address'],
                    'ci_address_detail' => $value['ci_address_detail'],
                ]);


            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'ci_no' =>  $request->all(),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            // return response()->json(['message' => Messages::MSG_0001], 500);
            return $e;
        }
    }

    public function get_all($co_no){
        try {
            $customs_info = CustomsInfo::select(['ci_no', 'co_no', 'ci_name', 'ci_manager', 'ci_hp', 'ci_address', 'ci_address_detail'])->where('co_no', $co_no)->get();

            return response()->json(['customs_info' => $customs_info], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
}

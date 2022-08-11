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

use App\Http\Requests\CustomsInfo\CustomsInfoCreatePopupRequest;
use App\Http\Requests\CustomsInfo\CustomsInfoUpdatePopupRequest;

class CustomsInfoController extends Controller
{

    public function create($co_no, CustomsInfoCreateRequest $request)
    {

        try {
            DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $validated = $request->validated();
            $ids = [];
            foreach ($validated  as $value) {

                if(isset($value['ci_no'])){
                    $ci = CustomsInfo::where('ci_no', $value['ci_no']);
                    $ci->update([
                        'mb_no' => $member->mb_no,
                        'co_no' => $co_no,
                        'ci_name' => $value['ci_name'],
                        'ci_hp' => $value['ci_hp'],
                        'ci_manager' => $value['ci_manager'],
                        'ci_address' => $value['ci_address'],
                        'ci_address_detail' => $value['ci_address_detail'],
                    ]);
                    $id = $ci->first()->ci_no;
                }else {
                    $id = CustomsInfo::insertGetId([
                        'mb_no' => $member->mb_no,
                        'co_no' => $co_no,
                        'ci_name' => $value['ci_name'],
                        'ci_hp' => $value['ci_hp'],
                        'ci_manager' => $value['ci_manager'],
                        'ci_address' => $value['ci_address'],
                        'ci_address_detail' => $value['ci_address_detail'],
                    ]);
                }
                $ids[] = $id;

            }
            CustomsInfo::where('co_no', $co_no)
            ->whereNotIn('ci_no', $ids)->delete();

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

	 public function create_with_popup($co_no, CustomsInfoCreatePopupRequest $request)
    {

        try {
            //DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $validated = $request->validated();
			$CustomsInfo = CustomsInfo::insertGetId([
				'mb_no' => $member->mb_no,
				'co_no' => $co_no,
				'ci_name' => $validated['ci_name'],
				'ci_hp' => $validated['ci_hp'],
				'ci_manager' => $validated['ci_manager'],
				'ci_address' => $validated['ci_address'],
				'ci_address_detail' => $validated['ci_address_detail'],
			]);

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'ci_no' =>  $CustomsInfo,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            // return response()->json(['message' => Messages::MSG_0001], 500);
            return $e;
        }
    }

	public function updateCI(CustomsInfo $customsInfo, CustomsInfoUpdatePopupRequest $request)
    {
        try {
            $validated = $request->validated();
            $CustomsInfo = CustomsInfo::where('ci_no', $validated['ci_no'])->where('mb_no', Auth::user()->mb_no)->update([
                'ci_name' => $validated['ci_name'],
                'ci_hp' => $validated['ci_hp'],
                'ci_manager' => $validated['ci_manager'],
				'ci_address' => $validated['ci_address'],
				'ci_address_detail' => $validated['ci_address_detail'],

            ]);
            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            Log::error($e);
			return $e;
            return response()->json(['message' => Messages::MSG_0002], 500);
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

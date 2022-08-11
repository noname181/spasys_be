<?php

namespace App\Http\Controllers\ForwarderInfo;

use App\Models\Member;
use App\Utils\Messages;
use Illuminate\Http\Request;
use App\Models\ForwarderInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ForwarderInfo\ForwarderInfoCreateRequest;
use App\Http\Requests\ForwarderInfo\ForwarderInfoUpdateRequest;
use App\Http\Requests\ForwarderInfo\ForwarderInfoCreatePopupRequest;
use App\Http\Requests\ForwarderInfo\ForwarderInfoUpdatePopupRequest;

class ForwarderInfoController extends Controller
{

    public function create($co_no, ForwarderInfoCreateRequest $request)
    {

        try {
            DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $validated = $request->validated();
            $ids = [];
            foreach ($validated  as $value) {

                if(isset($value['fi_no'])){
                    $fi = ForwarderInfo::where('fi_no', $value['fi_no']);
                    $fi->update([
                        'mb_no' => $member->mb_no,
                        'co_no' => $co_no,
                        'fi_name' => $value['fi_name'],
                        'fi_hp' => $value['fi_hp'],
                        'fi_manager' => $value['fi_manager'],
                        'fi_address' => $value['fi_address'],
                        'fi_address_detail' => $value['fi_address_detail'],
                    ]);
                    $id = $fi->first()->fi_no;
                }else {
                    $id = ForwarderInfo::insertGetId([
                        'mb_no' => $member->mb_no,
                        'co_no' => $co_no,
                        'fi_name' => $value['fi_name'],
                        'fi_hp' => $value['fi_hp'],
                        'fi_manager' => $value['fi_manager'],
                        'fi_address' => $value['fi_address'],
                        'fi_address_detail' => $value['fi_address_detail'],
                    ]);
                }

                $ids[] = $id;

            }
            ForwarderInfo::where('co_no', $co_no)
            ->whereNotIn('fi_no', $ids)->delete();

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'fi_no' =>  $request->all(),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            // return response()->json(['message' => Messages::MSG_0001], 500);
            return $e;
        }
    }

    public function create_with_co_no($co_no, ForwarderInfoCreateRequest $request)
    {

        try {
            DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $validated = $request->validated();

            foreach ($validated  as $value) {


                $ForwarderInfo = ForwarderInfo::insertGetId([
                    'mb_no' => $member->mb_no,
                    'co_no' => $co_no,
                    'fi_name' => $value['fi_name'],
                    'fi_hp' => $value['fi_hp'],
                    'fi_manager' => $value['fi_manager'],
                    'fi_address' => $value['fi_address'],
                    'fi_address_detail' => $value['fi_address_detail'],
                ]);


            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'fi_no' =>  $request->all(),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            // return response()->json(['message' => Messages::MSG_0001], 500);
            return $e;
        }
    }

	 public function create_with_popup($co_no, ForwarderInfoCreatePopupRequest $request)
    {

        try {
            //DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $validated = $request->validated();
			$ForwarderInfo = ForwarderInfo::insertGetId([
				'mb_no' => $member->mb_no,
				'co_no' => $co_no,
				'fi_name' => $validated['fi_name'],
				'fi_hp' => $validated['fi_hp'],
				'fi_manager' => $validated['fi_manager'],
				'fi_address' => $validated['fi_address'],
				'fi_address_detail' => $validated['fi_address_detail'],
			]);

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'fi_no' =>  $ForwarderInfo,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            // return response()->json(['message' => Messages::MSG_0001], 500);
            return $e;
        }
    }

	public function updateFI(ForwarderInfo $forwarderInfo, ForwarderInfoUpdatePopupRequest $request)
    {
        try {
            $validated = $request->validated();
            $ForwarderInfo = ForwarderInfo::where('fi_no', $validated['fi_no'])->where('mb_no', Auth::user()->mb_no)->update([
                'fi_name' => $validated['fi_name'],
                'fi_hp' => $validated['fi_hp'],
                'fi_manager' => $validated['fi_manager'],
				'fi_address' => $validated['fi_address'],
				'fi_address_detail' => $validated['fi_address_detail'],

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
            $forwarder_info = ForwarderInfo::select(['fi_no', 'co_no', 'fi_name', 'fi_manager', 'fi_hp', 'fi_address', 'fi_address_detail'])->where('co_no', $co_no)->get();

            return response()->json(['forwarder_info' => $forwarder_info], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
}

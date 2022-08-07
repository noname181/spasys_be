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

class ForwarderInfoController extends Controller
{

    public function create($co_no, ForwarderInfoCreateRequest $request)
    {

        try {
            DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $validated = $request->validated();

            foreach ($validated  as $value) {

                if(isset($value['fi_no'])){
                    ForwarderInfo::where('fi_no', $value['fi_no'])->update([
                        'mb_no' => $member->mb_no,
                        'co_no' => $co_no,
                        'fi_name' => $value['fi_name'],
                        'fi_hp' => $value['fi_hp'],
                        'fi_manager' => $value['fi_manager'],
                        'fi_address' => $value['fi_address'],
                        'fi_address_detail' => $value['fi_address_detail'],
                    ]);
                }else {
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

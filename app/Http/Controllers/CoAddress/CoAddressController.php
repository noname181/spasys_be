<?php

namespace App\Http\Controllers\CoAddress;

use App\Models\Member;
use App\Utils\Messages;
use Illuminate\Http\Request;
use App\Models\CoAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CoAddress\CoAddressCreateRequest;
use App\Http\Requests\CoAddress\CoAddressUpdateRequest;

class CoAddressController extends Controller
{

    /**
     *  getCoAddress
     * @param $co_no
     * @return \Illuminate\Http\Response
     */
    public function getCoAddress($co_no)
    {
        try {
            $coAddress = CoAddress::select([
                'ag_no',
                'ag_name',
                'ag_manager',
                'ag_email',
                'ag_hp',
            ])
            ->where('co_no', $co_no)
            ->get();

            return response()->json([
                'message' => Messages::MSG_0007,
                'coAddress' => $coAddress
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
    /**
     * create CoAddress
     * @param  CoAddressCreateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function create($co_no, CoAddressCreateRequest $request)
    {

        try {
            DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $validated = $request->validated();

            foreach ($validated  as $value) {

                if(isset($value['ca_no'])){
                    CoAddress::where('ca_no', $value['ca_no'])->update([
                        'mb_no' => $member->mb_no,
                        'co_no' => $co_no,
                        'ca_name' => $value['ca_name'],
                        'ca_hp' => $value['ca_hp'],
                        'ca_manager' => $value['ca_manager'],
                        'ca_address' => $value['ca_address'],
                        'ca_address_detail' => $value['ca_address_detail'],
                    ]);
                }else {
                    $CoAddress = CoAddress::insertGetId([
                        'mb_no' => $member->mb_no,
                        'co_no' => $co_no,
                        'ca_name' => $value['ca_name'],
                        'ca_hp' => $value['ca_hp'],
                        'ca_manager' => $value['ca_manager'],
                        'ca_address' => $value['ca_address'],
                        'ca_address_detail' => $value['ca_address_detail'],
                    ]);
                }

            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'ca_no' =>  $request->all(),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            // return response()->json(['message' => Messages::MSG_0001], 500);
            return $e;
        }
    }

    public function create_with_co_no($co_no, CoAddressCreateRequest $request)
    {

        try {
            DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $validated = $request->validated();

            foreach ($validated  as $value) {


                $CoAddress = CoAddress::insertGetId([
                    'mb_no' => $member->mb_no,
                    'co_no' => $co_no,
                    'ca_name' => $value['ca_name'],
                    'ca_hp' => $value['ca_hp'],
                    'ca_manager' => $value['ca_manager'],
                    'ca_address' => $value['ca_address'],
                    'ca_address_detail' => $value['ca_address_detail'],
                ]);


            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'ag_no' =>  $request->all(),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            // return response()->json(['message' => Messages::MSG_0001], 500);
            return $e;
        }
    }

    /**
     * Update CoAddress by id
     * @param  CoAddress $coAddress
     * @param  CoAddressUpdateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(CoAddress $coAddress, CoAddressUpdateRequest $request)
    {
        try {
            $validated = $request->validated();
            $coAddress->update([
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

    public function get_all($co_no){
        try {
            $co_address = CoAddress::select(['ca_no', 'co_no', 'ca_name', 'ca_manager', 'ca_hp', 'ca_address', 'ca_address_detail'])->where('co_no', $co_no)->get();

            return response()->json(['co_address' => $co_address], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
}

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
use App\Http\Requests\CoAddress\CoAddressUpdatePopupRequest;
use App\Http\Requests\CoAddress\CoAddressCreatePopupRequest;

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
            $ids = [];
            foreach ($validated  as $value) {

                if(isset($value['ca_no'])){
                    $ca = CoAddress::where('ca_no', $value['ca_no']);
                    $ca->update([
                        'mb_no' => $member->mb_no,
                        'co_no' => $co_no,
                        'ca_name' => $value['ca_name'],
                        'ca_hp' => $value['ca_hp'],
                        'ca_manager' => $value['ca_manager'],
                        'ca_address' => $value['ca_address'],
                        'ca_address_detail' => $value['ca_address_detail'],
                        'ca_is_default' => $value['ca_is_default'] == true ? 'y' : 'n',
                    ]);
                    $id = $ca->first()->ca_no;
                }else {
                    $id = CoAddress::insertGetId([
                        'mb_no' => $member->mb_no,
                        'co_no' => $co_no,
                        'ca_name' => $value['ca_name'],
                        'ca_hp' => $value['ca_hp'],
                        'ca_manager' => $value['ca_manager'],
                        'ca_address' => $value['ca_address'],
                        'ca_address_detail' => $value['ca_address_detail'],
                        'ca_is_default' => $value['ca_is_default'] == true ? 'y' : 'n',
                    ]);
                }
                $ids[] = $id;

            }

            CoAddress::where('co_no', $co_no)
            ->whereNotIn('ca_no', $ids)->delete();

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'ca_no' =>  $request->all(),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            // return response()->json(['message' => Messages::MSG_0001], 500);

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

        }
    }

	 public function create_with_popup($co_no, CoAddressCreatePopupRequest $request)
    {

        try {
            //DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $validated = $request->validated();
            if($validated['ca_is_default'] == 'true'){
                $coAddress2 = CoAddress::where('co_no', $validated['co_no'])->update(['ca_is_default'=>'n']);
             }
			$CoAddress = CoAddress::insertGetId([
				'mb_no' => $member->mb_no,
				'co_no' => $co_no,
				'ca_name' => $validated['ca_name'],
				'ca_hp' => $validated['ca_hp'],
				'ca_manager' => $validated['ca_manager'],
				'ca_address' => $validated['ca_address'],
				'ca_address_detail' => $validated['ca_address_detail'],
                'ca_is_default'=>$validated['ca_is_default'] == 'true' ? 'y' : 'n'
			]);

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'ca_no' =>  $CoAddress,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);

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

	public function updateCA(CoAddress $coAddress, CoAddressUpdatePopupRequest $request)
    {
        try {
            $validated = $request->validated();
            if($validated['ca_is_default'] == 'true'){
               $coAddress2 = CoAddress::where('co_no', $validated['co_no'])->update(['ca_is_default'=>'n']);
            }
            $coAddress = CoAddress::where('ca_no', $validated['ca_no'])->where('mb_no', Auth::user()->mb_no)->update([
                'ca_name' => $validated['ca_name'],
                'ca_hp' => $validated['ca_hp'],
                'ca_manager' => $validated['ca_manager'],
				'ca_address' => $validated['ca_address'],
				'ca_address_detail' => $validated['ca_address_detail'],
                'ca_is_default'=>$validated['ca_is_default'] == 'true' ? 'y' : 'n'
            ]);
            return response()->json(['message' => Messages::MSG_0007,'validated'=>$validated], 200);
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

    public function delete($ca_no){
        try {
            $co_address = CoAddress::where('ca_no', $ca_no)->delete();

            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
}

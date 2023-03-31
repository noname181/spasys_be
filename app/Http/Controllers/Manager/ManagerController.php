<?php

namespace App\Http\Controllers\Manager;

use id;
use App\Models\Member;
use App\Models\Manager;
use App\Utils\Messages;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\Manager\ManagerCreateRequest;
use App\Http\Requests\Manager\ManagerUpdateRequest;
use App\Http\Requests\Manager\ManagerUpdatePopupRequest;
use App\Http\Requests\Manager\ManagerMobileCreateRequest;

class ManagerController extends Controller
{
    /**
     * create Manager
     * @param  ManagerCreateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function create(ManagerCreateRequest $request)
    {

        try {

            // DB::beginTransaction();
            // $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            // $validated = $request->validated();
            // $m_no = Manager::insertGetId([
            //     'mb_no' => $member->mb_no,
            //     'm_position' => $validated['m_position'],
            //     'co_no' => 1,
            //     'm_name' => $validated['m_name'],
            //     'm_duty1' => $validated['m_duty1'],
            //     'm_duty2' => $validated['m_duty2'],
            //     'm_hp' => $validated['m_hp'],
            //     'm_email' => $validated['m_email'],
            //     'm_etc' => $validated['m_etc'],
            //     'm_regtime' => date('y-m-d h-i-s')
            // ]);
            // DB::commit();
            // return response()->json([
            //     'message' => Messages::MSG_0007,
            //     'm_no' => $m_no,
            // ], 201);

            DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $validated = $request->validated();

            foreach ($validated as $value) {
                if (isset($value['m_no'])) {
                    Manager::where('m_no', $value['m_no'])->update([
                        'mb_no' => $member->mb_no,
                        'm_position' => $value['m_position'],
                        'co_no' => $value['co_no'],
                        'm_name' => $value['m_name'],
                        'm_duty1' => isset($value['m_duty1']) ? $value['m_duty1'] : null ,
                        'm_duty2' => isset($value['m_duty2']) ? $value['m_duty2'] : null,
                        'm_hp' => $value['m_hp'],
                        'm_email' => $value['m_email'],
                        'm_etc' => $value['m_etc'],
                    ]);

                } else {
                    $Manager = Manager::insertGetId([
                        'mb_no' => $member->mb_no,
                        'm_position' => $value['m_position'],
                        'm_name' => $value['m_name'],
                        'm_duty1' => $value['m_duty1'],
                        'm_duty2' => $value['m_duty2'],
                        'm_hp' => $value['m_hp'],
                        'm_email' => $value['m_email'],
                        'm_etc' => $value['m_etc'],
                        'co_no' => $value['co_no'],
                    ]);

                }
            }

            DB::commit();
            return response()->json([
                'message' =>  Messages::MSG_0007,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function updateRM(Manager $manager, ManagerUpdatePopupRequest $request)
    {
        try {
            $validated = $request->validated();
            $m_no = Manager::where('m_no', $validated['m_no'])->where('mb_no', Auth::user()->mb_no)->update([
                'm_name' => $validated['m_name'],
                'm_hp' => $validated['m_hp'],
                'm_position' => $validated['m_position'],
				'm_duty1' => $validated['m_duty1'],
				'm_duty2' => $validated['m_duty2'],
                'm_email' => $validated['m_email'],
                'm_etc' => $validated['m_etc'],

            ]);
            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }

    public function create_with_popup($co_no,ManagerMobileCreateRequest $request)
    {

        try {

            //DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $validated = $request->validated();
            $m_no = Manager::insertGetId([
                'mb_no' => $member->mb_no,
                'm_position' => $validated['m_position'],
                'co_no' => $co_no,
                'm_name' => $validated['m_name'],
                'm_duty1' => $validated['m_duty1'],
                'm_duty2' => $validated['m_duty2'],
                'm_hp' => $validated['m_hp'],
                'm_email' => $validated['m_email'],
                'm_etc' => $validated['m_etc'],
                'created_at' => date('y-m-d h-i-s')
            ]);
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'm_no' => $m_no,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function create_mobile(ManagerMobileCreateRequest $request)
    {

        try {

            //DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $validated = $request->validated();
            $m_no = Manager::insertGetId([
                'mb_no' => $member->mb_no,
                'm_position' => $validated['m_position'],
                'co_no' => $validated['co_no'],
                'm_name' => $validated['m_name'],
                'm_duty1' => $validated['m_duty1'],
                'm_duty2' => $validated['m_duty2'],
                'm_hp' => $validated['m_hp'],
                'm_email' => $validated['m_email'],
                'm_etc' => $validated['m_etc'],
                'created_at' => date('y-m-d h-i-s')
            ]);
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'm_no' => $m_no,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    /**
     * Delete Manager by id
     * @param  Manager $manager
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {

        try {
            Manager::where('m_no', $request->m_no)->delete();
            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0003], 500);
        }

    }


    /**
     * Update Manager by id
     * @param  Manager $manager
     * @param  ManagerUpdateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(Manager $manager, ManagerUpdateRequest $request)
    {
        try {
            $validated = $request->validated();
            $manager->update([
                "m_position" => $validated['m_position'],
                "m_name" => $validated['m_name'],
                "m_duty1" => $validated['m_duty1'],
                "m_duty2" => $validated['m_duty2'],
                "m_hp" => $validated['m_hp'],
                "m_email" => $validated['m_email'],
                "m_etc" => $validated['m_etc'],
            ]);
            return response()->json(['message' => Messages::MSG_0007, 'm_no' => $manager->m_no], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }

     /**
     * Get Manager by id
     * @param  $co_no
     * @return \Illuminate\Http\Response
     */
    public function getManager($co_no)
    {
        try {
            $manager = Manager::select([
                'm_no',
                'co_no',
                'm_position',
                'm_name',
                'm_duty1',
                'm_duty2',
                'm_hp',
                'm_email',
                'm_etc',
            ])->where('co_no', $co_no)->get();

            return response()->json([
                'message' => Messages::MSG_0007,
                'manager' => $manager
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
}

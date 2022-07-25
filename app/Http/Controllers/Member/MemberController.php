<?php

namespace App\Http\Controllers\Member;

use App\Http\Requests\Member\MemberRegisterController\InvokeRequest;
use App\Http\Requests\Member\MemberUpdate\MemberUpdateRequest;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Utils\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{
    /**
     * Register member
     * @param  \App\Http\Requests\Member\MemberRegisterController\InvokeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(InvokeRequest $request)
    {
        try {
            $validated = $request->validated();
            $roleNoOfUserLogin = Auth::user()->role_no;
            if($roleNoOfUserLogin == Member::ROLE_ADMIN) {
                $validated['mb_type'] = Member::SPASYS;
                $validated['mb_parent'] = Member::ADMIN;
            }
            // FIXME hard set mb_language = ko and role_no = 1
            $validated['role_no'] = 1;
            $validated['mb_language'] = 'ko';

            $validated['mb_token'] = '';
            $validated['mb_pw'] = Hash::make($validated['mb_pw']);

            $mb_no = Member::insertGetId($validated);

            return response()->json([
                'message' => Messages::MSG_0007,
                'mb_no' => $mb_no,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    /**
     * Find user_id by name and email
     * @param  Illuminate\Http\Request $request;
     * @return \Illuminate\Http\JsonResponse
     */
    public function findUserId(Request $request)
    {
        try {
            $member = Member::where('mb_name', $request['mb_name'])
                ->where('mb_email', $request['mb_email'])->first();

            if (is_null($member)) {
                return response()->json([
                    'message' => Messages::MSG_0020
                ], 404);
            }
            return response()->json([
                'message' => Messages::MSG_0007,
                'mb_id' => $member->mb_id,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    /**
     * Get Profiles
     */
    public function getProfile() {
        try {
            $member = Member::where('mb_no', Auth::user()->mb_no)->first();
            return response()->json([
                'message' => Messages::MSG_0007,
                'profile' => $member,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    /**
     * Update Profiles
     */
    public function updateProfile(MemberUpdateRequest $request) {
        try {
            $validated = $request->validated();
            $member = Member::where('mb_no', Auth::user()->mb_no)->first();
            $member['mb_email'] = $validated['mb_email'];
            $member['mb_tel'] = $validated['mb_tel'];
            $member['mb_hp'] = $validated['mb_hp'];
            $member['mb_push_yn'] = $validated['mb_push_yn'];
            $member['mb_service_no_array'] = $validated['mb_service_no_array'];
            $member->save();
            return response()->json([
                'message' => Messages::MSG_0007,
                'profile' => $member,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

}

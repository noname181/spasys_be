<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthController\InvokeRequest;

use App\Models\Member;
use App\Models\Service;
use App\Utils\Messages;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Login
     * @param  \App\Http\Requests\Auth\AuthController\InvokeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(InvokeRequest $request)
    {
        $input = $request->validated();
        try {

            $member = Member::with('role')->where('mb_id', $input['mb_id'])->first();
            if (is_null($member)) {
                return response()->json([
                    'message' => Messages::MSG_0008,
                ], 401);
            }

            $token = "";
            if (Hash::check($input['mb_pw'], $member->mb_otp)) {
                $member->mb_pw = $member->mb_otp;
            } else if (!Hash::check($input['mb_pw'], $member->mb_pw)) {
                return response()->json([
                    'message' => Messages::MSG_0008,
                ], 401);
            }

            $member->mb_otp = null;
            if(!$member['mb_token'])
                $token = $member->generateAndSaveApiAuthToken();
            else
                $token = $member['mb_token'];

            $member->save();

            return response()->json([
                'message' => Messages::MSG_0007,
                'mb_token' => $token,
                'mb_name' => $member['mb_name'],
                'mb_email' => $member['mb_email'],
                'mb_hp' => $member['mb_hp'],
                'mb_tel' => $member['mb_tel'],
                'mb_language' => $member['mb_language'],
                'mb_type' => $member['mb_type'],
                'role_name' => $member['role']['role_name'],
                'role_no' => $member['role_no'],
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            return response()->json(['message' => Messages::MSG_0009], 500);
        }
    }
}

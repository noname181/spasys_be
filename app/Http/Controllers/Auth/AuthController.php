<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthController\InvokeRequest;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;
use App\Utils\Messages;
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

            $member = Member::where('mb_id', $input['mb_id'])->first();
            if (is_null($member)) {
                return response()->json([
                    'message' => Messages::MSG_0008,
                ], 401);
            }

            $token = "";
            if ($input['mb_pw'] === $member->mb_otp) {
                $member->mb_pw = Hash::make($member->mb_otp);
            } else if (!Hash::check($input['mb_pw'], $member->mb_pw)) {
                return response()->json([
                    'message' => Messages::MSG_0008,
                ], 401);
            }

            $member->mb_otp = null;
            $token = $member->generateAndSaveApiAuthToken();

            return response()->json([
                'message' => Messages::MSG_0007,
                'mb_token' => $token,
                'mb_name' => $member['mb_name'],
                'mb_email' => $member['mb_email'],
                'mb_hp' => $member['mb_hp'],
                'mb_tel' => $member['mb_tel'],
                'mb_language' => $member['mb_language'],
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            return response()->json(['message' => Messages::MSG_0009], 500);
        }
    }
}

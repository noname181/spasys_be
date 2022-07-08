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
            if(is_null($member)) {
                return response()->json([
                    'message' => Messages::MSG_0008,
                ], 401);
            }
            if (!Hash::check($input['mb_pw'], $member->mb_pw)) {
                return response()->json([
                    'message' => Messages::MSG_0008,
                ], 401);
            }
            $token =  $member->generateAndSaveApiAuthToken();


            return response()->json([
                'message' => Messages::MSG_0007,
                'mb_token' => $token
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            return response()->json(['message' => Messages::MSG_0009], 500);
        }
    }
}

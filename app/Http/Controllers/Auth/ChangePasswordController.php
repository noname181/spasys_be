<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordController\InvokeRequest;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChangePasswordController extends Controller
{
    /**
     * Change password
     * @param  \App\Http\Requests\Auth\ChangePasswordController\InvokeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(InvokeRequest $request)
    {
        $input = $request->validated();
        try {
            if ($input['mb_current_pw'] === $input['mb_new_pw']) {
                return response()->json([
                    'message' => Messages::MSG_0011,
                ], 400);
            }

            $member = Member::where('mb_id', Auth::user()->mb_id)->first();

            if (!Hash::check($input['mb_current_pw'], $member->mb_pw)) {
                return response()->json([
                    'message' => Messages::MSG_0010,
                ], 400);
            }

            $member['mb_pw'] = Hash::make($input['mb_new_pw']);
            // After change password . Remove token -> Login again
            $member->mb_token = '';
            $member->save();

            return response()->json([
                'message' => Messages::MSG_0007
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }
}

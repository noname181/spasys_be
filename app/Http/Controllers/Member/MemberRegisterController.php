<?php

namespace App\Http\Controllers\Member;

use App\Http\Requests\Member\MemberRegisterController\InvokeRequest;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Utils\Messages;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MemberRegisterController extends Controller
{
    /**
     * Register member
     * @param  \App\Http\Requests\Api\MemberRegisterController\InvokeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(InvokeRequest $request)
    {
        try {
            $validated = $request->validated();
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
}

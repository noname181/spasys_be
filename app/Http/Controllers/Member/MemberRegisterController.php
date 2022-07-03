<?php

namespace App\Http\Controllers\Member;

use App\Http\Requests\Member\MemberRegisterController\InvokeRequest;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Utils\Messages;
use Illuminate\Support\Facades\Log;

class MemberRegisterController extends Controller
{
    /**
     *
     * @param  \App\Http\Requests\Api\MemberRegisterController\InvokeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(InvokeRequest $request)
    {
        try {
            $validated = $request->validated();
            $member = Member::insert($validated);
            return response()->json(compact('member'));
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => [Messages::$MSG_0001]], 500);
        }
    }
}

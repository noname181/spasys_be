<?php

namespace App\Http\Controllers\Push;

use App\Http\Requests\Push\PushRegisterRequest;
use App\Http\Requests\Push\PushtUpdateRequest;
use App\Http\Controllers\Controller;
use App\Models\Push;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PushController extends Controller
{
    /**
     * Register Contract
     * @param  App\Http\Requests\Push\PushRegisterRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(PushRegisterRequest $request)
    {
        $validated = $request->validated();
        try {
            $push = Push::insertGetId([
                'mb_no' => Auth::user()->mb_no,
                'menu_no'=> $validated['menu_no'],
                'push_title'=> $validated['push_title'],
                'push_content'=> $validated['push_content'],
                'push_time_no'=> $validated['push_time'],
                'push_must_yn'=> $validated['push_must_yn'],
                'push_use_yn'=> $validated['push_use_yn'],
                'push_regtime'=> now(),
            ]);

            return response()->json([
                'message' => Messages::MSG_0007,
                'push' => $push,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    /**
     * Update Push
     * @param  App\Http\Requests\Push\PushtUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePush(PushtUpdateRequest $request, Push $push)
    {
        $validated = $request->validated();
        try {
            $update = [
                'menu_no'=> $validated['menu_no'],
                'push_title'=> $validated['push_title'],
                'push_content'=> $validated['push_content'],
                'push_time_no'=> $validated['push_time'],
                'push_must_yn'=> $validated['push_must_yn'],
                'push_use_yn'=> $validated['push_use_yn'],
            ];

            $push = Push::where(['mb_no' => Auth::user()->mb_no, 'push_no' => $push->push_no])
                ->update($update);
            return response()->json([
                'message' => Messages::MSG_0007,
                'push' => $push,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }
}

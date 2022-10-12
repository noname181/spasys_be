<?php

namespace App\Http\Controllers\Push;

use App\Http\Requests\Push\PushRequest;
use App\Http\Requests\Push\PushRegisterRequest;
use App\Http\Requests\Push\PushtUpdateRequest;
use App\Http\Controllers\Controller;
use App\Models\Push;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PushController extends Controller
{
   
   

    

    /**
     * Register SendEmail
     * @param  App\Http\Requests\Push\PushRegisterRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPush(PushRegisterRequest $request)
    {
        $validated = $request->validated();
        try {
            $push = Push::insertGetId([
                'mb_no' => Auth::user()->mb_no,
                'push_category' => $validated['push_category'],
                'push_title' => $validated['push_title'],
                'push_content' => $validated['push_content'],
                'push_time' => $validated['push_time'],
                'push_must_yn' => $validated['push_must_yn'],
                'push_use_yn' => $validated['push_use_yn']
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

  
    

}

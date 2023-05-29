<?php

namespace App\Http\Controllers\SendEmail;


use App\Http\Requests\SendEmail\SendEmailRegisterRequest;
use App\Http\Controllers\Controller;
use App\Models\SendEmail;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
class SendEmailController extends Controller
{
   
   

    

    /**
     * Register SendEmail
     * @param  App\Http\Requests\Push\PushRegisterRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createSendEmail(SendEmailRegisterRequest $request)
    {
        $validated = $request->validated();
        try {
            $push = SendEmail::insertGetId([
                'mb_no' => Auth::user()->mb_no,
                'rm_no' => isset($validated['rm_no']) ? $validated['rm_no'] : null,
                'rmd_no' => isset($validated['rmd_no']) ? $validated['rmd_no'] : null,
                'se_email_cc' => $validated['se_email_cc'],
                'se_email_receiver' => $validated['se_email_receiver'],
                'se_name_receiver' => $validated['se_name_receiver'],
                'se_title' => $validated['se_title'],
                'se_content' => $validated['se_content'],
                'se_rmd_number'=>isset($validated['rmd_number']) ? $validated['rmd_number'] : null,
                'created_at'=>Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at'=>Carbon::now()->format('Y-m-d H:i:s')
            ]);

            return response()->json([
                'message' => Messages::MSG_0007,
                'push' => $push,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

  
    

}

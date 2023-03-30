<?php

namespace App\Http\Controllers\SendEmail;


use App\Http\Requests\SendEmail\SendEmailRegisterRequest;
use App\Http\Controllers\Controller;
use App\Models\SendEmail;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
                'rm_no' => $validated['rm_no'],
                'se_email_cc' => $validated['se_email_cc'],
                'se_email_receiver' => $validated['se_email_receiver'],
                'se_name_receiver' => $validated['se_name_receiver'],
                'se_title' => $validated['se_title'],
                'se_content' => $validated['se_content'],
                'se_rmd_number'=>$validated['rmd_number']
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

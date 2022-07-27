<?php

namespace App\Http\Controllers\API;

use App\Models\Member;
use App\Utils\Messages;
use App\Utils\sendEmail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\SendMail\SendMailOtpRequest;
use App\Http\Requests\SendMail\ValidateOtpRequest;
use App\Http\Requests\SendMail\ForgotPasswordRequest;
use App\Http\Requests\SendMail\ForgotPasswordPatchRequest;
use Illuminate\Support\Str;

class SendMailController extends Controller
{

    public function sendEmailOtp(SendMailOtpRequest $request)
    {
        try {
            $validated = $request->validated();
            $mb_otp = Str::lower(Str::random(6));
            $member = Member::where([['mb_email', '=', $validated['mb_email']], ['mb_id', '=', $validated['mb_id']]])->first();
    
            if (!empty($member)) {
                // send otp in the email
                $mail_details = [
                    'title' => 'Verify email OTP',
                    'body' => 'Your OTP is : ' . $mb_otp,
                ];
    
                Member::where('mb_email', '=', $validated['mb_email'])->update(['mb_otp' => Hash::make($mb_otp)]);
    
                Mail::to($member->mb_email)->send(new sendEmail($mail_details));
    
                return response()->json(['message' => Messages::MSG_0007], 200);
            } else {
                return response()->json(['message' => Messages::MSG_0013], 400);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }

    public function validateOtp(ValidateOtpRequest $request)
    {
        try {
            $validated = $request->validated();
            $member = Member::where('mb_no', '=', $validated['mb_no'])->first();

            if (!empty($member)) {
    
                if ($validated['mb_no'] !== $member->mb_otp) {
                    return response()->json(['message' => Messages::MSG_0014], 400);
                }
    
                Member::where('mb_no', '=', $member->mb_no)->update(['mb_otp' => null]);
    
                return response()->json(['message' => Messages::MSG_0007], 200);
            } else {
                return response()->json(['message' => Messages::MSG_0013], 400);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0019], 500);
        }      
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $validated = $request->validated();
            $mb_otp = rand(1000, 9999);
            $member = Member::where('mb_email', '=', $validated['mb_email'])->first();
    
            if (!empty($member)) {
                // send otp in the email
                $mail_details = [
                    'title' => 'Forgot Password OTP',
                    'body' => 'Your OTP is : ' . $mb_otp,
                ];

                Member::where('mb_email', '=', $member->mb_email)->update(['mb_otp' =>  Hash::make($mb_otp)]);
                Mail::to($member->mb_email)->send(new sendEmail($mail_details));
    
                return response()->json(['message' => Messages::MSG_0007], 200);
            } else {
                return response()->json(['message' => Messages::MSG_0012], 400);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }

    public function sendPassword(ForgotPasswordPatchRequest $request)
    {
        try {
            $validated = $request->validated();
            $member = Member::where([['mb_email', '=', $validated['mb_email']],['mb_otp', '=', $validated['mb_otp']]])->first();
    
            if (!empty($member)) {
                // send otp in the email
                $mail_details = [
                    'title' => 'Forgot Password',
                    'body' => 'Forgot Password is : ' . $validated['mb_pw'],
                ];

                Mail::to($member->mb_email)->send(new sendEmail($mail_details));
    
                $validated['mb_pw'] = Hash::make($validated['mb_pw']);
                Member::where([['mb_email', '=', $validated['mb_email']],['mb_otp', '=', $validated['mb_otp']]])->update(['mb_pw' => $validated['mb_pw']]);;
                return response()->json(['message' => Messages::MSG_0007], 200);
            } else {
                return response()->json(['message' => Messages::MSG_0013], 400);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }
}

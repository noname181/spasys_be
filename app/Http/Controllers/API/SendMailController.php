<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Utils\Messages;
use App\Utils\sendEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SendMailController extends Controller
{

    public function sendEmailOtp(Request $request)
    {

        $mb_otp = rand(1000, 9999);
        $member = Member::where('mb_no', '=', $request->mb_no)->first();

        if (!empty($member)) {
            // send otp in the email
            $mail_details = [
                'title' => 'Verify email OTP',
                'body' => 'Your OTP is : ' . $mb_otp,
            ];

            Member::where('mb_no', '=', $request->mb_no)->update(['mb_otp' => $mb_otp]);

            Mail::to($member->mb_email)->send(new sendEmail($mail_details));

            return response()->json(['status' => 200, 'message' => Messages::MSG_0007]);
        } else {
            return response()->json(['status' => 400, 'message' => Messages::MSG_0012]);
        }
    }

    public function validateOtp(Request $request)
    {

        $member = Member::where([['mb_no', '=', $request->mb_no], ['mb_otp', '=', $request->mb_otp]])->first();

        if (!empty($member)) {

            Member::where('mb_no', '=', $request->mb_no)->update(['mb_otp' => null]);

            return response()->json(['status' => 200, 'message' => Messages::MSG_0007]);
        } else {
            return response()->json(['status' => 400, 'message' => Messages::MSG_0012]);
        }
    }
}

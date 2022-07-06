<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Utils\Messages;
use App\Utils\sendEmail;
use App\Utils\CommonFunc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
class SendMailController extends Controller
{

    public function sendEmailOtp(Request $request)
    {
        if (empty($request->mb_no)) {
            return response()->json(['message' => CommonFunc::renderMessage(Messages::MSG_0015, ['mb_no'])], 400);
        }
        
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

            return response()->json(['message' => Messages::MSG_0007], 200);
        } else {
            return response()->json(['message' => Messages::MSG_0013], 400);
        }
    }

    public function validateOtp(Request $request)
    {
        if (empty($request->mb_no)) {
            return response()->json(['message' => CommonFunc::renderMessage(Messages::MSG_0015, ['mb_no'])], 400);
        }
        if (empty($request->mb_otp)) {
            return response()->json(['message' => CommonFunc::renderMessage(Messages::MSG_0015, ['mb_otp'])], 400);
        }
        $member = Member::where('mb_no', '=', $request->mb_no)->first();

        if (!empty($member)) {

            if ($request->mb_otp !== $member->mb_otp) {
                return response()->json(['message' => Messages::MSG_0014], 400);
            }

            Member::where('mb_no', '=', $member->mb_no)->update(['mb_otp' => null]);

            return response()->json(['message' => Messages::MSG_0007], 200);
        } else {
            return response()->json(['message' => Messages::MSG_0013], 400);
        }
    }

    public function forgotPassword(Request $request)
    {

        if (empty($request->mb_email)) {
            return response()->json(['message' => CommonFunc::renderMessage(Messages::MSG_0015, ['mb_email'])], 400);
        }

        if (CommonFunc::isMail($request->mb_email) == false) {
            return response()->json(['message' => CommonFunc::renderMessage(Messages::MSG_0016, ['mb_email'])], 400);
        }

        $mb_otp = rand(1000, 9999);
        $member = Member::where('mb_email', '=', $request->mb_email)->first();

        if (!empty($member)) {
            // send otp in the email
            $mail_details = [
                'title' => 'Forgot Password OTP',
                'body' => 'Your OTP is : ' . $mb_otp,
            ];

            Member::where('mb_email', '=', $member->mb_email)->update(['mb_otp' => $mb_otp]);
            Mail::to($member->mb_email)->send(new sendEmail($mail_details));

            return response()->json(['message' => Messages::MSG_0007], 200);
        } else {
            return response()->json(['message' => Messages::MSG_0012], 400);
        }
    }
}

<?php

namespace App\Http\Controllers\SendEmail;


use App\Http\Requests\SendEmail\SendEmailRegisterRequest;
use App\Http\Controllers\Controller;
use App\Models\SendEmailHistory;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Utils\sendEmail2;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// use App\Models\File;
use App\Models\RateData;
use App\Models\RateMetaData;
use App\Models\Company;
use App\Models\RateDataGeneral;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use App\Http\Requests\SendMail\SendMailOtpRequest;
use Illuminate\Support\Str;
use App\Models\Member;
use App\Models\RateMeta;

class SendEmailController extends Controller
{





    /**
     * Register SendEmail
     * @param  App\Http\Requests\Push\PushRegisterRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendEmailOtp(SendMailOtpRequest $request)
    {

        try {

            $validated = $request->validated();
            $mb_otp = Str::lower(Str::random(6));
            $member = Member::where('mb_email', '=', $validated['mb_email'])
                ->where(function ($query)  use ($validated) {
                    $query->where('mb_id', '=', strtoupper($validated['mb_id']))
                        ->orWhere('mb_id', '=', strtolower($validated['mb_id']));
                })
                ->first();

            if (!empty($member)) {
                // send otp in the email
                $mail_details = [
                    'title' => '비밀번호 찾기',
                    'body' => '임시 비밀번호를 드립니다. 로그인하셔서 새 비밀번호로 변경하세요: ',
                    'otp' => $mb_otp,
                ];

                // Member::where('mb_email', '=', $validated['mb_email'])->update(['mb_otp' => Hash::make($mb_otp)]);

                // $check =  Mail::to($member->mb_email)->send(new sendEmail($mail_details));
                Mail::send('emails.mailOTP', ['details' => $mail_details], function ($message) use ($validated, $member) {
                    $message->to($member->mb_email)->from('Bonded_Logistics_Platform@spasysone.com');

                    $message->subject('비밀번호 찾기');
                });

                return response()->json(['message' => Messages::MSG_0007], 200);
            } else {
                return response()->json(['message' => Messages::MSG_0013], 400);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }
    public function createSendEmail(SendEmailRegisterRequest $request)
    {
        $validated = $request->validated();
        DB::beginTransaction();
        $co_no = Auth::user()->co_no;


        $rmd_last = RateMetaData::where('rm_no', $validated['rm_no'])->orderBy('rmd_no', 'desc')->first();
        $rate_data_send_meta = $this->getRateDataRaw($validated['rm_no'], $rmd_last['rmd_no']);

        $member = Member::where('mb_no', $rmd_last->mb_no)->first();
        $co_info = Company::where('co_no', $member->co_no)->first();
        DB::commit();
        $user = Auth::user();
        $rmd = RateMetaData::where('rm_no', $validated['rm_no'])->first();
        $rate_meta = RateMeta::where('rm_no', $validated['rm_no'])->first();
        // $data_sheet3 = $data_sheet2 = $rate_data = array();

        // $spreadsheet = new Spreadsheet();
        // $sheet = $spreadsheet->getActiveSheet(0);
        // $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        // $sheet->setTitle('보세화물');

        // $sheet->setCellValue('A1', '창고화물');
        // $sheet->setCellValue('H1', '온도화물');
        // $sheet->setCellValue('O1', '위험물');
        // $sheet->mergeCells('A1:E1');
        // $sheet->mergeCells('H1:L1');
        // $sheet->mergeCells('O1:S1');

        // $sheet->setCellValue('A2', '구분');
        // $sheet->setCellValue('B2', '내역');
        // $sheet->mergeCells('B2:E2');
        // $sheet->setCellValue('H2', '구분');
        // $sheet->setCellValue('I2', '내역');
        // $sheet->mergeCells('I2:L2');
        // $sheet->setCellValue('O2', '구분');
        // $sheet->setCellValue('P2', '내역');
        // $sheet->mergeCells('P2:S2');

        // $sheet->setCellValue('A3', '');
        // $sheet->setCellValue('B3', '항목');
        // $sheet->setCellValue('C3', '상세');
        // $sheet->setCellValue('D3', '기본료');
        // $sheet->setCellValue('E3', '단가/KG');

        // $sheet->setCellValue('H3', '');
        // $sheet->setCellValue('I3', '항목');
        // $sheet->setCellValue('J3', '상세');
        // $sheet->setCellValue('K3', '기본료');
        // $sheet->setCellValue('L3', '단가/KG');

        // $sheet->setCellValue('O3', '');
        // $sheet->setCellValue('P3', '항목');
        // $sheet->setCellValue('Q3', '상세');
        // $sheet->setCellValue('R3', '기본료');
        // $sheet->setCellValue('S3', '단가/KG');

        // if (!empty($rate_data_send_meta['rate_data1'])) {
        //     $data_sheet1 = $rate_data_send_meta['rate_data1'];
        //     $sheet_row1 = 4;
        //     $sheet_row2 = 4;
        //     $sheet_row3 = 4;
        //     foreach ($data_sheet1 as $dt1) {
        //         if ($dt1['rd_cate_meta2'] == '창고화물') {
        //             $sheet->setCellValue('A' . $sheet_row1, !empty($dt1['rd_cate1']) ? $dt1['rd_cate1'] : '');
        //             $sheet->setCellValue('B' . $sheet_row1, !empty($dt1['rd_cate2']) ? $dt1['rd_cate2'] : '');
        //             $sheet->setCellValue('C' . $sheet_row1, !empty($dt1['rd_cate3']) ? $dt1['rd_cate3'] : '');
        //             $sheet->setCellValue('D' . $sheet_row1, !empty($dt1['rd_data1']) ? $dt1['rd_data1'] : '');
        //             $sheet->setCellValue('E' . $sheet_row1, !empty($dt1['rd_data2']) ? $dt1['rd_data2'] : '');
        //             $sheet_row1++;
        //         } else if ($dt1['rd_cate_meta2'] == '온도화물') {
        //             $sheet->setCellValue('H' . $sheet_row2, !empty($dt1['rd_cate1']) ? $dt1['rd_cate1'] : '');
        //             $sheet->setCellValue('I' . $sheet_row2, !empty($dt1['rd_cate2']) ? $dt1['rd_cate2'] : '');
        //             $sheet->setCellValue('J' . $sheet_row2, !empty($dt1['rd_cate3']) ? $dt1['rd_cate3'] : '');
        //             $sheet->setCellValue('K' . $sheet_row2, !empty($dt1['rd_data1']) ? $dt1['rd_data1'] : '');
        //             $sheet->setCellValue('L' . $sheet_row2, !empty($dt1['rd_data2']) ? $dt1['rd_data2'] : '');
        //             $sheet_row2++;
        //         } else if ($dt1['rd_cate_meta2'] == '위험물') {
        //             $sheet->setCellValue('O' . $sheet_row3, !empty($dt1['rd_cate1']) ? $dt1['rd_cate1'] : '');
        //             $sheet->setCellValue('P' . $sheet_row3, !empty($dt1['rd_cate2']) ? $dt1['rd_cate2'] : '');
        //             $sheet->setCellValue('Q' . $sheet_row3, !empty($dt1['rd_cate3']) ? $dt1['rd_cate3'] : '');
        //             $sheet->setCellValue('R' . $sheet_row3, !empty($dt1['rd_data1']) ? $dt1['rd_data1'] : '');
        //             $sheet->setCellValue('S' . $sheet_row3, !empty($dt1['rd_data2']) ? $dt1['rd_data2'] : '');
        //             $sheet_row3++;
        //         }
        //     }
        // }

        // $spreadsheet->createSheet();
        // $sheet2 = $spreadsheet->getSheet(1);

        // $sheet2->setTitle('수입풀필먼트');

        // $sheet2->setCellValue('A1', '기준');
        // $sheet2->mergeCells('A1:B1');
        // $sheet2->setCellValue('C1', '단위');
        // $sheet2->setCellValue('D1', '단가');
        // $sheet2->setCellValue('E1', 'ON/OFF');

        // $sheet2_row = 2;
        // if (!empty($rate_data_send_meta['rate_data2'])) {
        //     $data_sheet2 = $rate_data_send_meta['rate_data2'];
        //     foreach ($data_sheet2 as $dt2) {
        //         $sheet2->setCellValue('A' . $sheet2_row, !empty($dt2['rd_cate1']) ? $dt2['rd_cate1'] : '');
        //         $sheet2->setCellValue('B' . $sheet2_row, !empty($dt2['rd_cate2']) ? $dt2['rd_cate2'] : '');
        //         $sheet2->setCellValue('C' . $sheet2_row, !empty($dt2['rd_cate3']) ? $dt2['rd_cate3'] : '');
        //         $sheet2->setCellValue('D' . $sheet2_row, !empty($dt2['rd_data1']) ? $dt2['rd_data1'] : '');
        //         $sheet2->setCellValue('E' . $sheet2_row, !empty($dt2['rd_data2']) ? $dt2['rd_data2'] : '');
        //         $sheet2_row++;
        //     }
        // }

        // $spreadsheet->createSheet();
        // $sheet3 = $spreadsheet->getSheet(2);

        // $sheet3->setTitle('유통가공');

        // $sheet3->setCellValue('A1', '기준');
        // $sheet3->mergeCells('A1:B1');
        // $sheet3->setCellValue('C1', '단위');
        // $sheet3->setCellValue('D1', '단가');
        // $sheet3->setCellValue('E1', 'ON/OFF');

        // $sheet3_row = 2;
        // if (!empty($rate_data_send_meta['rate_data3'])) {
        //     $data_sheet3 = $rate_data_send_meta['rate_data3'];
        //     foreach ($data_sheet3 as $dt3) {
        //         $sheet3->setCellValue('A' . $sheet3_row, !empty($dt3['rd_cate1']) ? $dt3['rd_cate1'] : '');
        //         $sheet3->setCellValue('B' . $sheet3_row, !empty($dt3['rd_cate2']) ? $dt3['rd_cate2'] : '');
        //         $sheet3->setCellValue('C' . $sheet3_row, !empty($dt3['rd_cate3']) ? $dt3['rd_cate3'] : '');
        //         $sheet3->setCellValue('D' . $sheet3_row, !empty($dt3['rd_data1']) ? $dt3['rd_data1'] : '');
        //         $sheet3->setCellValue('E' . $sheet3_row, !empty($dt3['rd_data2']) ? $dt3['rd_data2'] : '');
        //         $sheet3_row++;
        //     }
        // }

        // $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = 'storage/download/' . $user->mb_no . '/';
        } else {
            $path = 'storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . '요율발송_*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . '요율발송_' . $rmd['rmd_number'] . '.pdf';
        // $check_status = $Excel_writer->save($file_name_download);
        // $pdf = Pdf::loadView('pdf.test',['rate_data_send_meta'=>$rate_data_send_meta]);
        // $pdf->save($file_name_download);
        $count1 = 0;
        $count2 = 0;
        $count3 = 1;
        $array1 = [];

        $count1_2 = 0;
        $count2_2 = 0;
        $count3_2 = 1;
        $array2 = [];

        $count1_3 = 0;
        $count2_3 = 0;
        $count3_3 = 1;
        $array3 = [];
        if (count($rate_data_send_meta['rate_data1']) > 0) {
            foreach ($rate_data_send_meta['rate_data1'] as $key => $row) {
                if ($key <= 9 && $key != 2 && $key != 4 && $key != 6 && $key != 8 && ($row['rd_data2'] || $row['rd_data1'])) {
                    $array1[] = $key;
                    $count1 += 1;
                }
                if ($key == 11 && ($row['rd_data2'] || $row['rd_data1'])) {
                    $count2 = 2;
                    $array1[] = $key;
                }
                if (($key == 14 && ($row['rd_data1'])) ||  ($key == 13 || $key == 12) && ($row['rd_data2'] || $row['rd_data1'])) {
                    $count3 += 1;
                    $array1[] = $key;
                }
                if ($key >= 15 && $key <= 24 && $key != 17 && $key != 19 && $key != 21 && $key != 23 && ($row['rd_data2'] || $row['rd_data1'])) {
                    $array2[] = $key;
                    $count1_2 += 1;
                }
                if ($key >= 15 && $key == 26 && ($row['rd_data2'] || $row['rd_data1'])) {
                    $count2_2 = 2;
                    $array2[] = $key;
                }
                if (($key == 29 && ($row['rd_data1'])) ||  ($key == 27 || $key == 28) && ($row['rd_data2'] || $row['rd_data1'])) {
                    $count3_2 += 1;
                    $array2[] = $key;
                }
                if ($key >= 30 && $key <= 39 && $key != 32 && $key != 34 && $key != 36 && $key != 38 && ($row['rd_data2'] || $row['rd_data1'])) {
                    $array3[] = $key;
                    $count1_3 += 1;
                }
                if ($key >= 30 && $key == 41 && ($row['rd_data2'] || $row['rd_data1'])) {
                    $count2_3 = 2;
                    $array3[] = $key;
                }
                if (($key == 44 && ($row['rd_data1'])) ||  ($key == 42 || $key == 43) && ($row['rd_data2'] || $row['rd_data1'])) {
                    $count3_3 += 1;
                    $array3[] = $key;
                }
            }
        }
        $count_row2 = $count2 + $count3;
        $count_row2_2 = $count2_2 + $count3_2;
        $count_row2_3 = $count2_3 + $count3_3;
        $count_service2_1 = 0;
        $count_service2_2 = 0;
        $count_service2_3 = 0;
        $count_service2_4 = 0;
        $count_service2_5 = 0;
        $count_service2_6 = 0;
        if (count($rate_data_send_meta['rate_data2']) > 0) {
            foreach ($rate_data_send_meta['rate_data2'] as $key => $row) {
                if (($key == 1 || $key == 2 || $key == 3 || $key == 4) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service2_1 += 1;
                }
                if (($key == 5 || $key == 6 || $key == 7 || $key == 8 || $key == 9 || $key == 10) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service2_2 += 1;
                }
                if (($key == 0 || $key == 24 || $key == 25) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service2_3 += 1;
                }
                if (($key == 27 || $key == 26) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service2_4 += 1;
                }
                if (($key == 14 || $key == 15 || $key == 16 || $key == 17 || $key == 18 || $key == 19) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service2_5 += 1;
                }
                if (($key == 22 || $key == 23) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service2_6 += 1;
                }
            }
        }
        $count_service3_1 = 0;
        $count_service3_2 = 0;
        $count_service3_3 = 0;
        if (count($rate_data_send_meta['rate_data3']) > 0) {
            foreach ($rate_data_send_meta['rate_data3'] as $key => $row) {
                if (($key == 0 || $key == 1 || $key == 2 || $key == 3) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service3_1 += 1;
                }
                if (($key == 5 || $key == 6 || $key == 4) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service3_2 += 1;
                }
                if (($key == 7 || $key == 8) && $row['rd_data3'] == 'ON' && $row['rd_data2'] != '0' && $row['rd_data2']) {
                    $count_service3_3 += 1;
                }
            }
        }

        $pdf = Pdf::loadView('pdf.test', [
            'rate_data_send_meta' => $rate_data_send_meta,
            'count1' => $count1, 'array1' => $array1, 'count2' => $count2, 'count_row2' => $count_row2, 'count3' => $count3,
            'count1_2' => $count1_2, 'array2' => $array2, 'count2_2' => $count2_2, 'count_row2_2' => $count_row2_2, 'count3_2' => $count3_2,
            'count1_3' => $count1_3, 'array3' => $array3, 'count2_3' => $count2_3, 'count_row2_3' => $count_row2_3, 'count3_3' => $count3_3,
            'count_service2_1' => $count_service2_1, 'count_service2_2' => $count_service2_2, 'count_service2_3' => $count_service2_3,
            'count_service2_4' => $count_service2_4, 'count_service2_5' => $count_service2_5, 'count_service2_6' => $count_service2_6,
            'count_service3_1' => $count_service3_1, 'count_service3_2' => $count_service3_2, 'count_service3_3' => $count_service3_3,
            'rm_biz_name' => $rate_meta['rm_biz_name'], 'rm_biz_number' => $rate_meta['rm_biz_number'], 'rm_biz_address' => $rate_meta['rm_biz_address'],
            'rm_owner_name' => $rate_meta['rm_owner_name'], 'rm_biz_email' => $rate_meta['rm_biz_email'], 'rmd_mail_detail1a' => nl2br($rmd_last['rmd_mail_detail1a']),
            'rmd_mail_detail1b' => nl2br($rmd_last['rmd_mail_detail1b']), 'rmd_mail_detail1c' => nl2br($rmd_last['rmd_mail_detail1c']),
            'co_name' => $co_info['co_name'], 'co_address' => $co_info['co_address'], 'co_address_detail' => $co_info['co_address_detail'], 'co_tel' => $co_info['co_tel'], 'co_email' => $co_info['co_email'], 'date' => Date('Y-m-d', strtotime($rmd_last['created_at']))
        ]);
        $pdf->save($file_name_download);
        try {
            $push = SendEmailHistory::insertGetId([
                'mb_no' => Auth::user()->mb_no,
                'rm_no' => isset($validated['rm_no']) ? $validated['rm_no'] : null,
                'rmd_no' => isset($validated['rmd_no']) ? $validated['rmd_no'] : null,
                'se_email_cc' => $validated['se_email_cc'],
                'se_email_receiver' => $validated['se_email_receiver'],
                'se_name_receiver' => $validated['se_name_receiver'],
                'se_title' => $validated['se_title'],
                'se_content' => $validated['se_content'],
                'se_rmd_number' => isset($validated['rmd_number']) ? $validated['rmd_number'] : null,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
            $mail_details = [
                'title' => $validated['se_title'],
                'body' => nl2br($validated['se_content']),
            ];
            $path2 = '/var/www/html/' . $file_name_download;
            Mail::send('emails.quotation', ['details' => $mail_details], function ($message) use ($validated, $path2) {
                $message->to($validated['se_email_receiver'])->from('Bonded_Logistics_Platform@spasysone.com');
                if ($validated['se_email_cc']) {
                    $message->cc([$validated['se_email_cc']]);
                }
                $message->subject($validated['se_title']);


                $message->attach($path2);
            });

            return response()->json([
                'message' => Messages::MSG_0007,
                // 'push' => $push,
                'rate_data_send_meta' => $rate_data_send_meta
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }
    public function getRateDataRaw($rm_no, $rmd_no)
    {
        $co_no = Auth::user()->co_no;
        try {
            $rate_data1 = RateData::where('rm_no', $rm_no)->where('rmd_no', $rmd_no)->where('rd_cate_meta1', '보세화물')->get();
            $rate_data2 = RateData::where('rm_no', $rm_no)->where('rmd_no', $rmd_no)->where('rd_cate_meta1', '수입풀필먼트')->get();
            $rate_data3 = RateData::where('rm_no', $rm_no)->where('rmd_no', $rmd_no)->where('rd_cate_meta1', '유통가공')->get();
            $co_rate_data1 = RateData::where('rd_cate_meta1', '보세화물');
            $co_rate_data2 = RateData::where('rd_cate_meta1', '수입풀필먼트');
            $co_rate_data3 = RateData::where('rd_cate_meta1', '유통가공');

            if (Auth::user()->mb_type == 'spasys') {
                $co_rate_data1 = $co_rate_data1->where('co_no', $co_no)->get();
                $co_rate_data2 = $co_rate_data2->where('co_no', $co_no)->get();
                $co_rate_data3 = $co_rate_data3->where('co_no', $co_no)->get();
            } else if (Auth::user()->mb_type == 'shop') {
                $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
                $co_rate_data1 = $co_rate_data1->where('rd_co_no', $co_no);
                $co_rate_data2 = $co_rate_data2->where('rd_co_no', $co_no);
                $co_rate_data3 = $co_rate_data3->where('rd_co_no', $co_no);
                if (isset($rmd->rmd_no)) {
                    $co_rate_data1 = $co_rate_data1->where('rmd_no', $rmd->rmd_no)->get();
                    $co_rate_data2 = $co_rate_data2->where('rmd_no', $rmd->rmd_no)->get();
                    $co_rate_data3 = $co_rate_data3->where('rmd_no', $rmd->rmd_no)->get();
                } else {
                    $co_rate_data1 = [];
                    $co_rate_data2 = [];
                    $co_rate_data3 = [];
                }
            }

            return [
                'message' => Messages::MSG_0007,
                'rate_data1' => $rate_data1,
                'rate_data2' => $rate_data2,
                'rate_data3' => $rate_data3,
                'co_rate_data1' => $co_rate_data1,
                'co_rate_data2' => $co_rate_data2,
                'co_rate_data3' => $co_rate_data3,
            ];
        } catch (\Exception $e) {
            DB::rollback();
            return [];
        }
    }

    public function SendEmailPrecalculate(SendEmailRegisterRequest $request)
    {
        $validated = $request->validated();
        DB::beginTransaction();
        DB::commit();
        $user = Auth::user();
        $co_no = Company::with(['contract'])->where('co_no', $validated['co_no_services'])->first();
        $rmd_last = RateMetaData::where('rmd_no', $validated['rmd_no'])->first();
        $rate_data = [];
        $rate_data_general = [];
        $bonded1a = [];
        $bonded2a = [];
        $bonded3a = [];
        $bonded4a = [];
        $bonded5a = [];
        $bonded1b = [];
        $bonded2b = [];
        $bonded3b = [];
        $bonded4b = [];
        $bonded5b = [];
        $bonded1c = [];
        $bonded2c = [];
        $bonded3c = [];
        $bonded4c = [];
        $bonded5c = [];
        $count1 = 0;
        $count2 = 0;
        $count3 = 0;
        $count1_2 = 0;
        $count2_2 = 0;
        $count3_2 = 0;
        $count4_2 = 0;
        $total_1 = 0;
        $total_2 = 0;
        $total_3 = 0;
        $arr2 = [];
        $count_arr2 = [];
        $arr3 = [];
        $count_arr3 = [];
        $arr4 = [];
        $count_arr4 = [];
        $arr5 = [];
        $count_arr5 = [];
        $sum3 = [];
        $sum4 = [];
        $sum5 = [];
        $sum2 = [];
        if (isset($validated['rmd_service']) && $validated['rmd_service'] == 1) {

            $service = '수입풀필먼트';
            if (isset($request->rmd_no)) {
                $rate_data = RateData::where('rd_cate_meta1', '수입풀필먼트')->where('rmd_no', $validated['rmd_no']);
            }
            $rate_data = $rate_data->get();

            $rate_data_general = RateDataGeneral::where('rmd_no', $validated['rmd_no'])->where('rdg_set_type', 'estimated_costs')->first();
        } else if (isset($validated['rmd_service']) && $validated['rmd_service'] == 2) {
            $service = '유통가공';
            if (isset($request->rmd_no)) {
                $rate_data = RateData::where('rd_cate_meta1', '유통가공')->where('rmd_no', $validated['rmd_no']);
            }
            $rate_data = $rate_data->get();

            $rate_data_general = RateDataGeneral::where('rmd_no', $validated['rmd_no'])->where('rdg_set_type', 'estimated_costs')->first();
        } else if ((isset($validated['rmd_service']) && $validated['rmd_service'] == 0) || (!isset($validated['rmd_service']) || !$validated['rmd_service'])) {
            $service = '보세화물';
            if ($validated['rmd_tab_child'] == '창고화물') {
                if (Auth::user()->mb_type == 'spasys' || $co_no['contract']['c_integrated_calculate_yn'] == 'n' || $co_no['contract']['c_integrated_calculate_yn'] == '') {
                    $bonded1a = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded1a');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

                    foreach ($bonded1a as $row) {
                        if ($row['rd_cate1'] == '하역비용') {
                            if ($row['rd_cate2'] != '하역비용') {
                                $count1 = $count1 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '센터 작업료') {
                            if ($row['rd_cate2'] != '센터 작업료') {
                                $count2 = $count2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '기타 비용') {
                            if ($row['rd_cate2'] != '기타 비용') {
                                $count3 = $count3 + 1;
                            }
                        }
                        if ($row['rd_cate2'] == '소계') {
                            $total_1 = $total_1 + $row['rd_data1'];
                            $total_2 = $total_2 + $row['rd_data2'];
                            $total_3 = $total_3 + $row['rd_data4'];
                        }
                    }
                } else if (Auth::user()->mb_type != 'spasys' && $co_no['contract']['c_integrated_calculate_yn'] == 'y') {

                    $bonded1a = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded1a');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

                    foreach ($bonded1a as $row) {
                        if ($row['rd_cate1'] == '하역비용') {
                            if ($row['rd_cate2'] != '하역비용') {
                                $count1 = $count1 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '센터 작업료') {
                            if ($row['rd_cate2'] != '센터 작업료') {
                                $count2 = $count2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '기타 비용') {
                            if ($row['rd_cate2'] != '기타 비용') {
                                $count3 = $count3 + 1;
                            }
                        }
                        if ($row['rd_cate2'] == '소계') {
                            $total_1 = $total_1 + $row['rd_data1'];
                            $total_2 = $total_2 + $row['rd_data2'];
                            $total_3 = $total_3 + $row['rd_data4'];
                        }
                    }


                    $bonded2a = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded2a');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();
                    $total1_2 = 0;
                    $total2_2 = 0;
                    $total3_2 = 0;
                    $total5_2 = 0;
                    $total6_2 = 0;
                    $total7_2 = 0;

                    foreach ($bonded2a as $row) {
                        if ($row['rd_cate1'] == '세금') {
                            if ($row['rd_cate2'] != '세금') {
                                $count1_2 = $count1_2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '운임') {
                            if ($row['rd_cate2'] != '운임') {
                                $count2_2 = $count2_2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '창고료') {
                            if ($row['rd_cate2'] != '창고료') {
                                $count3_2 = $count3_2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '수수료') {
                            if ($row['rd_cate2'] != '수수료') {
                                $count4_2 = $count4_2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] != '수수료' && $row['rd_cate1'] != '창고료' && $row['rd_cate1'] != '운임' && $row['rd_cate1'] != '세금') {
                            if (!in_array($row['rd_cate1'], $arr2)) {
                                $arr2[] =  $row['rd_cate1'];
                            }
                        }
                        if ($row['rd_cate2'] == '소계') {
                            $total1_2 = $total1_2 + $row['rd_data1'];
                            $total2_2 = $total2_2 + $row['rd_data2'];
                            $total3_2 = $total3_2 + $row['rd_data4'];
                            $total5_2 = $total5_2 + $row['rd_data5'];
                            $total6_2 = $total6_2 + $row['rd_data6'];
                            $total7_2 = $total7_2 + $row['rd_data7'];
                        }
                    }
                    $sum2[] = $total1_2;
                    $sum2[] = $total2_2;
                    $sum2[] = $total3_2;
                    $sum2[] = $total5_2;
                    $sum2[] = $total6_2;
                    $sum2[] = $total7_2;
                    if (count($arr2) > 0) {
                        for ($i = 0; $i < count($arr2); $i++) {
                            $check = 0;
                            foreach ($bonded2a as $row) {
                                if ($row['rd_cate1'] == $arr2[$i]) {
                                    if ($row['rd_cate2'] != 'bonded2') {
                                        $check = $check + 1;
                                    }
                                }
                            }
                            $count_arr2[] = $check;
                        }
                    }

                    $bonded3a = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded3a');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

                    foreach ($bonded3a as $row) {

                        if (!in_array($row['rd_cate1'], $arr3)) {
                            $arr3[] =  $row['rd_cate1'];
                        }
                    }
                    if (count($arr3) > 0) {
                        $total1_3 = 0;
                        $total2_3 = 0;
                        $total3_3 = 0;
                        $total5_3 = 0;
                        $total6_3 = 0;
                        $total7_3 = 0;
                        for ($i = 0; $i < count($arr3); $i++) {
                            $check = 0;

                            foreach ($bonded3a as $row) {
                                if ($row['rd_cate1'] == $arr3[$i]) {
                                    if ($row['rd_cate2'] != 'bonded345') {
                                        $check = $check + 1;
                                    }
                                    if ($row['rd_cate2'] == '소계') {
                                        $total1_3 = $total1_3 + $row['rd_data1'];
                                        $total2_3 = $total2_3 + $row['rd_data2'];
                                        $total3_3 = $total3_3 + $row['rd_data4'];
                                        $total5_3 = $total5_3 + $row['rd_data5'];
                                        $total6_3 = $total6_3 + $row['rd_data6'];
                                        $total7_3 = $total7_3 + $row['rd_data7'];
                                    }
                                }
                            }

                            $count_arr3[] = $check;
                        }

                        $sum3[] = $total1_3;
                        $sum3[] = $total2_3;
                        $sum3[] = $total3_3;
                        $sum3[] = $total5_3;
                        $sum3[] = $total6_3;
                        $sum3[] = $total7_3;
                    }

                    $bonded4a = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded4a');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

                    foreach ($bonded4a as $row) {

                        if (!in_array($row['rd_cate1'], $arr4)) {
                            $arr4[] =  $row['rd_cate1'];
                        }
                    }
                    if (count($arr4) > 0) {
                        $total1_4 = 0;
                        $total2_4 = 0;
                        $total3_4 = 0;
                        $total5_4 = 0;
                        $total6_4 = 0;
                        $total7_4 = 0;
                        for ($i = 0; $i < count($arr4); $i++) {
                            $check = 0;

                            foreach ($bonded4a as $row) {
                                if ($row['rd_cate1'] == $arr4[$i]) {
                                    if ($row['rd_cate2'] != 'bonded345') {
                                        $check = $check + 1;
                                    }
                                    if ($row['rd_cate2'] == '소계') {
                                        $total1_4 = $total1_4 + $row['rd_data1'];
                                        $total2_4 = $total2_4 + $row['rd_data2'];
                                        $total3_4 = $total3_4 + $row['rd_data4'];
                                        $total5_4 = $total5_4 + $row['rd_data5'];
                                        $total6_4 = $total6_4 + $row['rd_data6'];
                                        $total7_4 = $total7_4 + $row['rd_data7'];
                                    }
                                }
                            }

                            $count_arr4[] = $check;
                        }

                        $sum4[] = $total1_4;
                        $sum4[] = $total2_4;
                        $sum4[] = $total3_4;
                        $sum4[] = $total5_4;
                        $sum4[] = $total6_4;
                        $sum4[] = $total7_4;
                    }

                    $bonded5a = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded5a');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

                    foreach ($bonded5a as $row) {

                        if (!in_array($row['rd_cate1'], $arr5)) {
                            $arr5[] =  $row['rd_cate1'];
                        }
                    }
                    if (count($arr5) > 0) {
                        $total1_5 = 0;
                        $total2_5 = 0;
                        $total3_5 = 0;
                        $total5_5 = 0;
                        $total6_5 = 0;
                        $total7_5 = 0;
                        for ($i = 0; $i < count($arr5); $i++) {
                            $check = 0;

                            foreach ($bonded5a as $row) {
                                if ($row['rd_cate1'] == $arr5[$i]) {
                                    if ($row['rd_cate2'] != 'bonded345') {
                                        $check = $check + 1;
                                    }
                                    if ($row['rd_cate2'] == '소계') {
                                        $total1_5 = $total1_5 + $row['rd_data1'];
                                        $total2_5 = $total2_5 + $row['rd_data2'];
                                        $total3_5 = $total3_5 + $row['rd_data4'];
                                        $total5_5 = $total5_5 + $row['rd_data5'];
                                        $total6_5 = $total6_5 + $row['rd_data6'];
                                        $total7_5 = $total7_5 + $row['rd_data7'];
                                    }
                                }
                            }

                            $count_arr5[] = $check;
                        }

                        $sum5[] = $total1_5;
                        $sum5[] = $total2_5;
                        $sum5[] = $total3_5;
                        $sum5[] = $total5_5;
                        $sum5[] = $total6_5;
                        $sum5[] = $total7_5;
                    }
                }
            } else if ($validated['rmd_tab_child'] == '온도화물') {
                if (Auth::user()->mb_type == 'spasys' || $co_no['contract']['c_integrated_calculate_yn'] == 'n' || $co_no['contract']['c_integrated_calculate_yn'] == '') {
                    $bonded1b = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded1b');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();
                    foreach ($bonded1b as $row) {
                        if ($row['rd_cate1'] == '하역비용') {
                            if ($row['rd_cate2'] != '하역비용') {
                                $count1 = $count1 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '센터 작업료') {
                            if ($row['rd_cate2'] != '센터 작업료') {
                                $count2 = $count2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '기타 비용') {
                            if ($row['rd_cate2'] != '기타 비용') {
                                $count3 = $count3 + 1;
                            }
                        }
                        if ($row['rd_cate2'] == '소계') {
                            $total_1 = $total_1 + $row['rd_data1'];
                            $total_2 = $total_2 + $row['rd_data2'];
                            $total_3 = $total_3 + $row['rd_data4'];
                        }
                    }
                } else if (Auth::user()->mb_type != 'spasys' && $co_no['contract']['c_integrated_calculate_yn'] == 'y') {

                    $bonded1b = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded1b');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

                    foreach ($bonded1b as $row) {
                        if ($row['rd_cate1'] == '하역비용') {
                            if ($row['rd_cate2'] != '하역비용') {
                                $count1 = $count1 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '센터 작업료') {
                            if ($row['rd_cate2'] != '센터 작업료') {
                                $count2 = $count2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '기타 비용') {
                            if ($row['rd_cate2'] != '기타 비용') {
                                $count3 = $count3 + 1;
                            }
                        }
                        if ($row['rd_cate2'] == '소계') {
                            $total_1 = $total_1 + $row['rd_data1'];
                            $total_2 = $total_2 + $row['rd_data2'];
                            $total_3 = $total_3 + $row['rd_data4'];
                        }
                    }


                    $bonded2b = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded2b');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();
                    $total1_2 = 0;
                    $total2_2 = 0;
                    $total3_2 = 0;
                    $total5_2 = 0;
                    $total6_2 = 0;
                    $total7_2 = 0;

                    foreach ($bonded2b as $row) {
                        if ($row['rd_cate1'] == '세금') {
                            if ($row['rd_cate2'] != '세금') {
                                $count1_2 = $count1_2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '운임') {
                            if ($row['rd_cate2'] != '운임') {
                                $count2_2 = $count2_2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '창고료') {
                            if ($row['rd_cate2'] != '창고료') {
                                $count3_2 = $count3_2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '수수료') {
                            if ($row['rd_cate2'] != '수수료') {
                                $count4_2 = $count4_2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] != '수수료' && $row['rd_cate1'] != '창고료' && $row['rd_cate1'] != '운임' && $row['rd_cate1'] != '세금') {
                            if (!in_array($row['rd_cate1'], $arr2)) {
                                $arr2[] =  $row['rd_cate1'];
                            }
                        }
                        if ($row['rd_cate2'] == '소계') {
                            $total1_2 = $total1_2 + $row['rd_data1'];
                            $total2_2 = $total2_2 + $row['rd_data2'];
                            $total3_2 = $total3_2 + $row['rd_data4'];
                            $total5_2 = $total5_2 + $row['rd_data5'];
                            $total6_2 = $total6_2 + $row['rd_data6'];
                            $total7_2 = $total7_2 + $row['rd_data7'];
                        }
                    }
                    $sum2[] = $total1_2;
                    $sum2[] = $total2_2;
                    $sum2[] = $total3_2;
                    $sum2[] = $total5_2;
                    $sum2[] = $total6_2;
                    $sum2[] = $total7_2;
                    if (count($arr2) > 0) {
                        for ($i = 0; $i < count($arr2); $i++) {
                            $check = 0;
                            foreach ($bonded2b as $row) {
                                if ($row['rd_cate1'] == $arr2[$i]) {
                                    if ($row['rd_cate2'] != 'bonded2') {
                                        $check = $check + 1;
                                    }
                                }
                            }
                            $count_arr2[] = $check;
                        }
                    }

                    $bonded3b = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded3b');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

                    foreach ($bonded3b as $row) {

                        if (!in_array($row['rd_cate1'], $arr3)) {
                            $arr3[] =  $row['rd_cate1'];
                        }
                    }
                    if (count($arr3) > 0) {
                        $total1_3 = 0;
                        $total2_3 = 0;
                        $total3_3 = 0;
                        $total5_3 = 0;
                        $total6_3 = 0;
                        $total7_3 = 0;
                        for ($i = 0; $i < count($arr3); $i++) {
                            $check = 0;

                            foreach ($bonded3b as $row) {
                                if ($row['rd_cate1'] == $arr3[$i]) {
                                    if ($row['rd_cate2'] != 'bonded345') {
                                        $check = $check + 1;
                                    }
                                    if ($row['rd_cate2'] == '소계') {
                                        $total1_3 = $total1_3 + $row['rd_data1'];
                                        $total2_3 = $total2_3 + $row['rd_data2'];
                                        $total3_3 = $total3_3 + $row['rd_data4'];
                                        $total5_3 = $total5_3 + $row['rd_data5'];
                                        $total6_3 = $total6_3 + $row['rd_data6'];
                                        $total7_3 = $total7_3 + $row['rd_data7'];
                                    }
                                }
                            }

                            $count_arr3[] = $check;
                        }

                        $sum3[] = $total1_3;
                        $sum3[] = $total2_3;
                        $sum3[] = $total3_3;
                        $sum3[] = $total5_3;
                        $sum3[] = $total6_3;
                        $sum3[] = $total7_3;
                    }

                    $bonded4b = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded4b');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

                    foreach ($bonded4b as $row) {

                        if (!in_array($row['rd_cate1'], $arr4)) {
                            $arr4[] =  $row['rd_cate1'];
                        }
                    }
                    if (count($arr4) > 0) {
                        $total1_4 = 0;
                        $total2_4 = 0;
                        $total3_4 = 0;
                        $total5_4 = 0;
                        $total6_4 = 0;
                        $total7_4 = 0;
                        for ($i = 0; $i < count($arr4); $i++) {
                            $check = 0;

                            foreach ($bonded4b as $row) {
                                if ($row['rd_cate1'] == $arr4[$i]) {
                                    if ($row['rd_cate2'] != 'bonded345') {
                                        $check = $check + 1;
                                    }
                                    if ($row['rd_cate2'] == '소계') {
                                        $total1_4 = $total1_4 + $row['rd_data1'];
                                        $total2_4 = $total2_4 + $row['rd_data2'];
                                        $total3_4 = $total3_4 + $row['rd_data4'];
                                        $total5_4 = $total5_4 + $row['rd_data5'];
                                        $total6_4 = $total6_4 + $row['rd_data6'];
                                        $total7_4 = $total7_4 + $row['rd_data7'];
                                    }
                                }
                            }

                            $count_arr4[] = $check;
                        }

                        $sum4[] = $total1_4;
                        $sum4[] = $total2_4;
                        $sum4[] = $total3_4;
                        $sum4[] = $total5_4;
                        $sum4[] = $total6_4;
                        $sum4[] = $total7_4;
                    }

                    $bonded5b = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded5b');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

                    foreach ($bonded5b as $row) {

                        if (!in_array($row['rd_cate1'], $arr5)) {
                            $arr5[] =  $row['rd_cate1'];
                        }
                    }
                    if (count($arr5) > 0) {
                        $total1_5 = 0;
                        $total2_5 = 0;
                        $total3_5 = 0;
                        $total5_5 = 0;
                        $total6_5 = 0;
                        $total7_5 = 0;
                        for ($i = 0; $i < count($arr5); $i++) {
                            $check = 0;

                            foreach ($bonded5b as $row) {
                                if ($row['rd_cate1'] == $arr5[$i]) {
                                    if ($row['rd_cate2'] != 'bonded345') {
                                        $check = $check + 1;
                                    }
                                    if ($row['rd_cate2'] == '소계') {
                                        $total1_5 = $total1_5 + $row['rd_data1'];
                                        $total2_5 = $total2_5 + $row['rd_data2'];
                                        $total3_5 = $total3_5 + $row['rd_data4'];
                                        $total5_5 = $total5_5 + $row['rd_data5'];
                                        $total6_5 = $total6_5 + $row['rd_data6'];
                                        $total7_5 = $total7_5 + $row['rd_data7'];
                                    }
                                }
                            }

                            $count_arr5[] = $check;
                        }

                        $sum5[] = $total1_5;
                        $sum5[] = $total2_5;
                        $sum5[] = $total3_5;
                        $sum5[] = $total5_5;
                        $sum5[] = $total6_5;
                        $sum5[] = $total7_5;
                    }
                }
            } else if ($validated['rmd_tab_child'] == '위험물') {
                if (Auth::user()->mb_type == 'spasys' || $co_no['contract']['c_integrated_calculate_yn'] == 'n' || $co_no['contract']['c_integrated_calculate_yn'] == '') {
                    $bonded1c = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded1c');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();
                    foreach ($bonded1c as $row) {
                        if ($row['rd_cate1'] == '하역비용') {
                            if ($row['rd_cate2'] != '하역비용') {
                                $count1 = $count1 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '센터 작업료') {
                            if ($row['rd_cate2'] != '센터 작업료') {
                                $count2 = $count2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '기타 비용') {
                            if ($row['rd_cate2'] != '기타 비용') {
                                $count3 = $count3 + 1;
                            }
                        }
                        if ($row['rd_cate2'] == '소계') {
                            $total_1 = $total_1 + $row['rd_data1'];
                            $total_2 = $total_2 + $row['rd_data2'];
                            $total_3 = $total_3 + $row['rd_data4'];
                        }
                    }
                } else if (Auth::user()->mb_type != 'spasys' && $co_no['contract']['c_integrated_calculate_yn'] == 'y') {

                    $bonded1c = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded1c');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

                    foreach ($bonded1c as $row) {
                        if ($row['rd_cate1'] == '하역비용') {
                            if ($row['rd_cate2'] != '하역비용') {
                                $count1 = $count1 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '센터 작업료') {
                            if ($row['rd_cate2'] != '센터 작업료') {
                                $count2 = $count2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '기타 비용') {
                            if ($row['rd_cate2'] != '기타 비용') {
                                $count3 = $count3 + 1;
                            }
                        }
                        if ($row['rd_cate2'] == '소계') {
                            $total_1 = $total_1 + $row['rd_data1'];
                            $total_2 = $total_2 + $row['rd_data2'];
                            $total_3 = $total_3 + $row['rd_data4'];
                        }
                    }


                    $bonded2c = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded2c');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();
                    $total1_2 = 0;
                    $total2_2 = 0;
                    $total3_2 = 0;
                    $total5_2 = 0;
                    $total6_2 = 0;
                    $total7_2 = 0;

                    foreach ($bonded2c as $row) {
                        if ($row['rd_cate1'] == '세금') {
                            if ($row['rd_cate2'] != '세금') {
                                $count1_2 = $count1_2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '운임') {
                            if ($row['rd_cate2'] != '운임') {
                                $count2_2 = $count2_2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '창고료') {
                            if ($row['rd_cate2'] != '창고료') {
                                $count3_2 = $count3_2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] == '수수료') {
                            if ($row['rd_cate2'] != '수수료') {
                                $count4_2 = $count4_2 + 1;
                            }
                        }
                        if ($row['rd_cate1'] != '수수료' && $row['rd_cate1'] != '창고료' && $row['rd_cate1'] != '운임' && $row['rd_cate1'] != '세금') {
                            if (!in_array($row['rd_cate1'], $arr2)) {
                                $arr2[] =  $row['rd_cate1'];
                            }
                        }
                        if ($row['rd_cate2'] == '소계') {
                            $total1_2 = $total1_2 + $row['rd_data1'];
                            $total2_2 = $total2_2 + $row['rd_data2'];
                            $total3_2 = $total3_2 + $row['rd_data4'];
                            $total5_2 = $total5_2 + $row['rd_data5'];
                            $total6_2 = $total6_2 + $row['rd_data6'];
                            $total7_2 = $total7_2 + $row['rd_data7'];
                        }
                    }
                    $sum2[] = $total1_2;
                    $sum2[] = $total2_2;
                    $sum2[] = $total3_2;
                    $sum2[] = $total5_2;
                    $sum2[] = $total6_2;
                    $sum2[] = $total7_2;
                    if (count($arr2) > 0) {
                        for ($i = 0; $i < count($arr2); $i++) {
                            $check = 0;
                            foreach ($bonded2c as $row) {
                                if ($row['rd_cate1'] == $arr2[$i]) {
                                    if ($row['rd_cate2'] != 'bonded2') {
                                        $check = $check + 1;
                                    }
                                }
                            }
                            $count_arr2[] = $check;
                        }
                    }

                    $bonded3c = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded3c');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

                    foreach ($bonded3c as $row) {

                        if (!in_array($row['rd_cate1'], $arr3)) {
                            $arr3[] =  $row['rd_cate1'];
                        }
                    }
                    if (count($arr3) > 0) {
                        $total1_3 = 0;
                        $total2_3 = 0;
                        $total3_3 = 0;
                        $total5_3 = 0;
                        $total6_3 = 0;
                        $total7_3 = 0;
                        for ($i = 0; $i < count($arr3); $i++) {
                            $check = 0;

                            foreach ($bonded3c as $row) {
                                if ($row['rd_cate1'] == $arr3[$i]) {
                                    if ($row['rd_cate2'] != 'bonded345') {
                                        $check = $check + 1;
                                    }
                                    if ($row['rd_cate2'] == '소계') {
                                        $total1_3 = $total1_3 + $row['rd_data1'];
                                        $total2_3 = $total2_3 + $row['rd_data2'];
                                        $total3_3 = $total3_3 + $row['rd_data4'];
                                        $total5_3 = $total5_3 + $row['rd_data5'];
                                        $total6_3 = $total6_3 + $row['rd_data6'];
                                        $total7_3 = $total7_3 + $row['rd_data7'];
                                    }
                                }
                            }

                            $count_arr3[] = $check;
                        }

                        $sum3[] = $total1_3;
                        $sum3[] = $total2_3;
                        $sum3[] = $total3_3;
                        $sum3[] = $total5_3;
                        $sum3[] = $total6_3;
                        $sum3[] = $total7_3;
                    }

                    $bonded4c = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded4c');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

                    foreach ($bonded4c as $row) {

                        if (!in_array($row['rd_cate1'], $arr4)) {
                            $arr4[] =  $row['rd_cate1'];
                        }
                    }
                    if (count($arr4) > 0) {
                        $total1_4 = 0;
                        $total2_4 = 0;
                        $total3_4 = 0;
                        $total5_4 = 0;
                        $total6_4 = 0;
                        $total7_4 = 0;
                        for ($i = 0; $i < count($arr4); $i++) {
                            $check = 0;

                            foreach ($bonded4c as $row) {
                                if ($row['rd_cate1'] == $arr4[$i]) {
                                    if ($row['rd_cate2'] != 'bonded345') {
                                        $check = $check + 1;
                                    }
                                    if ($row['rd_cate2'] == '소계') {
                                        $total1_4 = $total1_4 + $row['rd_data1'];
                                        $total2_4 = $total2_4 + $row['rd_data2'];
                                        $total3_4 = $total3_4 + $row['rd_data4'];
                                        $total5_4 = $total5_4 + $row['rd_data5'];
                                        $total6_4 = $total6_4 + $row['rd_data6'];
                                        $total7_4 = $total7_4 + $row['rd_data7'];
                                    }
                                }
                            }

                            $count_arr4[] = $check;
                        }

                        $sum4[] = $total1_4;
                        $sum4[] = $total2_4;
                        $sum4[] = $total3_4;
                        $sum4[] = $total5_4;
                        $sum4[] = $total6_4;
                        $sum4[] = $total7_4;
                    }

                    $bonded5c = $rate_data = RateData::where('rmd_no', $validated['rmd_no'])->where(function ($q) {
                        $q->where('rd_cate_meta1', 'bonded5c');
                    })->orderBy('rd_index', 'ASC')->orderBy('rd_no')->get();

                    foreach ($bonded5c as $row) {

                        if (!in_array($row['rd_cate1'], $arr5)) {
                            $arr5[] =  $row['rd_cate1'];
                        }
                    }
                    if (count($arr5) > 0) {
                        $total1_5 = 0;
                        $total2_5 = 0;
                        $total3_5 = 0;
                        $total5_5 = 0;
                        $total6_5 = 0;
                        $total7_5 = 0;
                        for ($i = 0; $i < count($arr5); $i++) {
                            $check = 0;

                            foreach ($bonded5c as $row) {
                                if ($row['rd_cate1'] == $arr5[$i]) {
                                    if ($row['rd_cate2'] != 'bonded345') {
                                        $check = $check + 1;
                                    }
                                    if ($row['rd_cate2'] == '소계') {
                                        $total1_5 = $total1_5 + $row['rd_data1'];
                                        $total2_5 = $total2_5 + $row['rd_data2'];
                                        $total3_5 = $total3_5 + $row['rd_data4'];
                                        $total5_5 = $total5_5 + $row['rd_data5'];
                                        $total6_5 = $total6_5 + $row['rd_data6'];
                                        $total7_5 = $total7_5 + $row['rd_data7'];
                                    }
                                }
                            }

                            $count_arr5[] = $check;
                        }

                        $sum5[] = $total1_5;
                        $sum5[] = $total2_5;
                        $sum5[] = $total3_5;
                        $sum5[] = $total5_5;
                        $sum5[] = $total6_5;
                        $sum5[] = $total7_5;
                    }
                }
            }
        }




        if (isset($user->mb_no)) {
            $path = 'storage/download/' . $user->mb_no . '/';
        } else {
            $path = 'storage/download/no-name/';
        }

        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }

        $mask = $path . '예상비용 미리보기_*.*';
        array_map('unlink', glob($mask) ?: []);
        $rmd_name_file = isset($validated['rmd_number']) ? $validated['rmd_number'] : '';
        $file_name_download = $path . '예상비용 미리보기_' . $rmd_name_file . '.pdf';
        $pdf = Pdf::loadView('pdf.test2', [
            'rate_data_general' => $rate_data_general, 'rate_data' => $rate_data, 'service' => $service, 'bonded1a' => $bonded1a, 'bonded1b' => $bonded1b, 'bonded1c' => $bonded1c, 'tab_child' => $validated['rmd_tab_child'] ? $validated['rmd_tab_child'] : '',
            'count3' => $count3, 'count2' => $count2, 'count1' => $count1, 'total_1' => $total_1, 'total_2' => $total_2, 'total_3' => $total_3, 'mb_type' => Auth::user()->mb_type, 'bonded2a' => $bonded2a, 'count4_2' => $count4_2, 'count3_2' => $count3_2, 'count1_2' => $count1_2, 'count2_2' => $count2_2, 'count_arr2' => $count_arr2, 'arr2' => $arr2,
            'count_arr3' => $count_arr3, 'arr3' => $arr3, 'bonded3a' => $bonded3a, 'sum3' => $sum3, 'sum2' => $sum2,
            'count_arr4' => $count_arr4, 'arr4' => $arr4, 'bonded4a' => $bonded4a, 'sum4' => $sum4,
            'count_arr5' => $count_arr5, 'arr5' => $arr5, 'bonded5a' => $bonded5a, 'sum5' => $sum5,
            'bonded5b' => $bonded5b, 'bonded5c' => $bonded5c, 'bonded4b' => $bonded4b, 'bonded4c' => $bonded4c,
            'bonded3b' => $bonded3b, 'bonded3c' => $bonded3c, 'bonded2b' => $bonded2b, 'bonded2c' => $bonded2c,
            'co_name' => $co_no['co_name'], 'co_address' => $co_no['co_address'], 'co_address_detail' => $co_no['co_address_detail'], 'co_tel' => $co_no['co_tel'], 'co_email' => $co_no['co_email'], 'date' => Date('Y-m-d', strtotime($rmd_last['created_at']))
        ]);
        $pdf->save($file_name_download);

        try {
            $push = SendEmailHistory::insertGetId([
                'mb_no' => Auth::user()->mb_no,
                'rm_no' => isset($validated['rm_no']) ? $validated['rm_no'] : null,
                'rmd_no' => isset($validated['rmd_no']) ? $validated['rmd_no'] : null,
                'se_email_cc' => $validated['se_email_cc'],
                'se_email_receiver' => $validated['se_email_receiver'],
                'se_name_receiver' => $validated['se_name_receiver'],
                'se_title' => $validated['se_title'],
                'se_content' => $validated['se_content'],
                'se_rmd_number' => isset($validated['rmd_number']) ? $validated['rmd_number'] : null,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
            $mail_details = [
                'title' => $validated['se_title'],
                'body' => nl2br($validated['se_content']),
            ];
            $path2 = '/var/www/html/' . $file_name_download;
            Mail::send('emails.quotation', ['details' => $mail_details], function ($message) use ($validated, $path2) {
                $message->to($validated['se_email_receiver'])->from('Bonded_Logistics_Platform@spasysone.com');
                if ($validated['se_email_cc']) {
                    $message->cc([$validated['se_email_cc']]);
                }
                $message->subject($validated['se_title']);


                $message->attach($path2);
            });

            return response()->json([
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }
}

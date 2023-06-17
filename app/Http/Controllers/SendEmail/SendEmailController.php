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
use App\Models\File;
use App\Models\RateData;
use App\Models\RateMetaData;
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
        DB::beginTransaction();
        $co_no = Auth::user()->co_no;
        $rate_data_send_meta = $this->getRateDataRaw($validated['rm_no'], $validated['rmd_no']);
        DB::commit();
        $user = Auth::user();

        $data_sheet3 = $data_sheet2 = $rate_data = array();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(0);
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

        $sheet->setTitle('보세화물');

        $sheet->setCellValue('A1', '창고화물');
        $sheet->setCellValue('H1', '온도화물');
        $sheet->setCellValue('O1', '위험물');
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('H1:L1');
        $sheet->mergeCells('O1:S1');

        $sheet->setCellValue('A2', '구분');
        $sheet->setCellValue('B2', '내역');
        $sheet->mergeCells('B2:E2');
        $sheet->setCellValue('H2', '구분');
        $sheet->setCellValue('I2', '내역');
        $sheet->mergeCells('I2:L2');
        $sheet->setCellValue('O2', '구분');
        $sheet->setCellValue('P2', '내역');
        $sheet->mergeCells('P2:S2');

        $sheet->setCellValue('A3', '');
        $sheet->setCellValue('B3', '항목');
        $sheet->setCellValue('C3', '상세');
        $sheet->setCellValue('D3', '기본료');
        $sheet->setCellValue('E3', '단가/KG');

        $sheet->setCellValue('H3', '');
        $sheet->setCellValue('I3', '항목');
        $sheet->setCellValue('J3', '상세');
        $sheet->setCellValue('K3', '기본료');
        $sheet->setCellValue('L3', '단가/KG');

        $sheet->setCellValue('O3', '');
        $sheet->setCellValue('P3', '항목');
        $sheet->setCellValue('Q3', '상세');
        $sheet->setCellValue('R3', '기본료');
        $sheet->setCellValue('S3', '단가/KG');

        if (!empty($rate_data_send_meta['rate_data1'])) {
            $data_sheet1 = $rate_data_send_meta['rate_data1'];
            $sheet_row1 = 4;
            $sheet_row2 = 4;
            $sheet_row3 = 4;
            foreach ($data_sheet1 as $dt1) {
                if ($dt1['rd_cate_meta2'] == '창고화물') {
                    $sheet->setCellValue('A' . $sheet_row1, !empty($dt1['rd_cate1']) ? $dt1['rd_cate1'] : '');
                    $sheet->setCellValue('B' . $sheet_row1, !empty($dt1['rd_cate2']) ? $dt1['rd_cate2'] : '');
                    $sheet->setCellValue('C' . $sheet_row1, !empty($dt1['rd_cate3']) ? $dt1['rd_cate3'] : '');
                    $sheet->setCellValue('D' . $sheet_row1, !empty($dt1['rd_data1']) ? $dt1['rd_data1'] : '');
                    $sheet->setCellValue('E' . $sheet_row1, !empty($dt1['rd_data2']) ? $dt1['rd_data2'] : '');
                    $sheet_row1++;
                } else if ($dt1['rd_cate_meta2'] == '온도화물') {
                    $sheet->setCellValue('H' . $sheet_row2, !empty($dt1['rd_cate1']) ? $dt1['rd_cate1'] : '');
                    $sheet->setCellValue('I' . $sheet_row2, !empty($dt1['rd_cate2']) ? $dt1['rd_cate2'] : '');
                    $sheet->setCellValue('J' . $sheet_row2, !empty($dt1['rd_cate3']) ? $dt1['rd_cate3'] : '');
                    $sheet->setCellValue('K' . $sheet_row2, !empty($dt1['rd_data1']) ? $dt1['rd_data1'] : '');
                    $sheet->setCellValue('L' . $sheet_row2, !empty($dt1['rd_data2']) ? $dt1['rd_data2'] : '');
                    $sheet_row2++;
                } else if ($dt1['rd_cate_meta2'] == '위험물') {
                    $sheet->setCellValue('O' . $sheet_row3, !empty($dt1['rd_cate1']) ? $dt1['rd_cate1'] : '');
                    $sheet->setCellValue('P' . $sheet_row3, !empty($dt1['rd_cate2']) ? $dt1['rd_cate2'] : '');
                    $sheet->setCellValue('Q' . $sheet_row3, !empty($dt1['rd_cate3']) ? $dt1['rd_cate3'] : '');
                    $sheet->setCellValue('R' . $sheet_row3, !empty($dt1['rd_data1']) ? $dt1['rd_data1'] : '');
                    $sheet->setCellValue('S' . $sheet_row3, !empty($dt1['rd_data2']) ? $dt1['rd_data2'] : '');
                    $sheet_row3++;
                }
            }
        }

        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);

        $sheet2->setTitle('수입풀필먼트');

        $sheet2->setCellValue('A1', '기준');
        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('C1', '단위');
        $sheet2->setCellValue('D1', '단가');
        $sheet2->setCellValue('E1', 'ON/OFF');

        $sheet2_row = 2;
        if (!empty($rate_data_send_meta['rate_data2'])) {
            $data_sheet2 = $rate_data_send_meta['rate_data2'];
            foreach ($data_sheet2 as $dt2) {
                $sheet2->setCellValue('A' . $sheet2_row, !empty($dt2['rd_cate1']) ? $dt2['rd_cate1'] : '');
                $sheet2->setCellValue('B' . $sheet2_row, !empty($dt2['rd_cate2']) ? $dt2['rd_cate2'] : '');
                $sheet2->setCellValue('C' . $sheet2_row, !empty($dt2['rd_cate3']) ? $dt2['rd_cate3'] : '');
                $sheet2->setCellValue('D' . $sheet2_row, !empty($dt2['rd_data1']) ? $dt2['rd_data1'] : '');
                $sheet2->setCellValue('E' . $sheet2_row, !empty($dt2['rd_data2']) ? $dt2['rd_data2'] : '');
                $sheet2_row++;
            }
        }

        $spreadsheet->createSheet();
        $sheet3 = $spreadsheet->getSheet(2);

        $sheet3->setTitle('유통가공');

        $sheet3->setCellValue('A1', '기준');
        $sheet3->mergeCells('A1:B1');
        $sheet3->setCellValue('C1', '단위');
        $sheet3->setCellValue('D1', '단가');
        $sheet3->setCellValue('E1', 'ON/OFF');

        $sheet3_row = 2;
        if (!empty($rate_data_send_meta['rate_data3'])) {
            $data_sheet3 = $rate_data_send_meta['rate_data3'];
            foreach ($data_sheet3 as $dt3) {
                $sheet3->setCellValue('A' . $sheet3_row, !empty($dt3['rd_cate1']) ? $dt3['rd_cate1'] : '');
                $sheet3->setCellValue('B' . $sheet3_row, !empty($dt3['rd_cate2']) ? $dt3['rd_cate2'] : '');
                $sheet3->setCellValue('C' . $sheet3_row, !empty($dt3['rd_cate3']) ? $dt3['rd_cate3'] : '');
                $sheet3->setCellValue('D' . $sheet3_row, !empty($dt3['rd_data1']) ? $dt3['rd_data1'] : '');
                $sheet3->setCellValue('E' . $sheet3_row, !empty($dt3['rd_data2']) ? $dt3['rd_data2'] : '');
                $sheet3_row++;
            }
        }

        $Excel_writer = new Xlsx($spreadsheet);
        if (isset($user->mb_no)) {
            $path = 'storage/download/' . $user->mb_no . '/';
        } else {
            $path = 'storage/download/no-name/';
        }
        if (!is_dir($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $mask = $path . 'Excel-Quotation-Send-Details-*.*';
        array_map('unlink', glob($mask) ?: []);
        $file_name_download = $path . 'Excel-Quotation-Send-Details-' . date('YmdHis') . '.Xlsx';
        $check_status = $Excel_writer->save($file_name_download);
      
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
                'se_rmd_number'=>isset($validated['rmd_number']) ? $validated['rmd_number'] : null,
                'created_at'=>Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at'=>Carbon::now()->format('Y-m-d H:i:s')
            ]);
            $mail_details = [ 
                'title' => $validated['se_title'],
                'body' => $validated['se_content'],
            ];
            $path2 = '/var/www/html/'.$file_name_download;
            Mail::send('emails.mailOTP',['details'=>$mail_details], function($message)use($validated,$path2) {
                $message->to($validated['se_email_receiver'])->from('bonded_logistics_platform@spasysone.com');
                if($validated['se_email_cc']){
                    $message->cc([$validated['se_email_cc']]);
                }
                $message->subject($validated['se_title']);
     
               
                $message->attach($path2);
                         
            });

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
                }else {
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
  
    

}

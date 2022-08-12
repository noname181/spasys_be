<?php

namespace App\Http\Controllers\Report;

use App\Models\File;
use App\Models\Report;
use App\Utils\Messages;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Report\ReportRequest;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Register Report
     * @param  App\Http\Requests\ReportRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
     
            $data = [];
            $i = 0;
            foreach($request->rp_content as $rp_content){
                $report_no = Report::insertGetId([
                    'mb_no' => Auth::user()->mb_no,
                    'item_no' => $request->item_no,
                    'rp_cate' => $request->rp_cate,
                    'rp_content' => $rp_content
                ]);
                $file = 'file' . (string)$i;
                $files = [];
                $path = join('/', ['files', 'report', $report_no]);
                $index = 0;
                foreach($request->$file as $file){
                    $url = Storage::disk('public')->put($path, $file);
                    $files[] = [
                        'file_table' => 'report',
                        'file_table_key' => $report_no,
                        'file_name_old' => $file->getClientOriginalName(),
                        'file_name' => basename($url),
                        'file_size' => $file->getSize(),
                        'file_extension' => $file->extension(),
                        'file_position' => $index,
                        'file_url' => $url
                    ];
                    $index++;
                    File::insert($files);
                }
                $i++;
            }
        
        // $validated = $request->validated();
        // // try {

        //     $files = [];
        //     $reports = [];
        //     DB::beginTransaction();
        //     foreach ($validated['reports'] as $val) {
        //         $report_no = Report::insertGetId([
        //             'mb_no' => Auth::user()->mb_no,
        //             'item_no' => 1,
        //             'rp_cate' => 1,
        //         ]);


           
        //     $path = join('/', ['files', 'report', $report_no]);
            
        //     foreach ($val['files'] as $key => $file) {
        //         $url = Storage::disk('public')->put($path, $file);
        //         $files[] = [
        //             'file_table' => 'report',
        //             'file_table_key' => $report_no,
        //             'file_name_old' => $file['file_name_old'],
        //             'file_name' => $file['file_name'],
        //             'file_size' => $file['file_size'],
        //             'file_extension' => $file['file_extension'],
        //             'file_position' => $key,
        //             'file_url' => $url
        //         ];
        //     }
        //     File::insert($files);
        // };
        //     DB::commit();
        //     return response()->json([
        //         'message' => Messages::MSG_0007,
        //         'validated' => $validated
        //     ]);
        return $data;
        // } catch (\Exception $e) {
        //     DB::rollback();
        //     Log::error($e);
        //     return response()->json(['message' => Messages::MSG_0001], 500);
        // }
    }

    public function getReports()
    {
        // try {
            $reports = DB::table('report')
                ->select(['report.*'])
                ->join('file', 'file.file_table_key', '=', 'report.rp_no') 
                ->where([
                    'report.mb_no' => Auth::user()->mb_no,
                    'file.file_table' => 'report'
                ])
                ->distinct()
                ->get();
            $files = DB::table('report')
                ->select(['file.*'])
                ->join('file', 'file.file_table_key', '=', 'report.rp_no') 
                ->where([
                    'report.mb_no' => Auth::user()->mb_no,
                    'file.file_table' => 'report'
                ])
                ->get();

            $responses = [];

            foreach ($reports as $report) {
                $response = [];
                foreach ($files as $file) {
                    if ( $file->file_table_key == $report->rp_no) {
                    $response['rp_no'] = $report->rp_no;
                    $response['rp_content'] = $report->rp_content;
                    $response['files'][] = [
                        'file_no' => $file->file_no,
                        'file_table' => $file->file_table,
                        'file_table_key' => $file->file_table_key,
                        'file_name_old' => $file->file_name_old,
                        'file_name' => $file->file_name,
                        'file_size' => $file->file_size,
                        'file_position' => $file->file_position,
                        'file_extension' => $file->file_extension,
                        'file_url' => $file->file_url,
                    ];          
                }
               
                }
                $responses[] =  $response;  
            }

            // if (count($reports) > 0) {
            //     //$response['rp_no'] = $rp_no;
            //     $response['rp_cate'] = $reports[0]->rp_cate;

            //     foreach ($reports as $value) {

            //         $response['files'][] = [
            //             'rp_content' => $value->rp_content,
            //             'file_no' => $value->file_no,
            //             'file_table' => $value->file_table,
            //             'file_table_key' => $value->file_table_key,
            //             'file_name_old' => $value->file_name_old,
            //             'file_name' => $value->file_name,
            //             'file_size' => $value->file_size,
            //             'file_position' => $value->file_position,
            //             'file_extension' => $value->file_extension,
            //             'file_url' => $value->file_url,
            //         ];
            //     }
            // }

            return response()->json([
                'message' => Messages::MSG_0007,
                'reports' => $responses
            ]);
        // } catch (\Exception $e) {
        //     Log::error($e);
        //     return response()->json(['message' => Messages::MSG_0020], 500);
        // }
    }

    public function deleteReport(Report $Report)
    {
        try {
            $Report->delete();
            return response()->json([
                'message' => Messages::MSG_0007
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0006], 500);
        }
    }
}

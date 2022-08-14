<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ReportRequest;
use App\Models\File;
use App\Models\Report;
use App\Utils\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * Register Report
     * @param  App\Http\Requests\ReportRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        try {
            $reports = [];
            $i = 0;
            $parent_no;
            DB::beginTransaction();
            if (!$request->rp_no) {
                foreach ($request->rp_content as $rp_content) {
                    $report_no = Report::insertGetId([
                        'mb_no' => Auth::user()->mb_no,
                        'item_no' => $request->item_no,
                        'rp_cate' => $request->rp_cate,
                        'rp_content' => $rp_content,
                        'rp_parent_no' => empty($parent_no) ? null : $parent_no,
                    ]);
                    if ($i == 0) {
                        $parent_no = $report_no;
                    }
                    $reports[] = $report_no;
                    $filename = 'file' . (string) $i;
                    $files = [];
                    $path = join('/', ['files', 'report', $report_no]);
                    $index = 0;
                    foreach ($request->$filename as $file) {
                        if ($file != 'undefined') {
                            $url = Storage::disk('public')->put($path, $file);
                            $files[] = [
                                'file_table' => 'report',
                                'file_table_key' => $report_no,
                                'file_name_old' => $file->getClientOriginalName(),
                                'file_name' => basename($url),
                                'file_size' => $file->getSize(),
                                'file_extension' => $file->extension(),
                                'file_position' => $index,
                                'file_url' => $url,
                            ];
                            $index++;
                            File::insert($files);
                            $files = [];
                        }

                    }
                    $i++;
                }
            } else {

                foreach ($request->rp_content as $rp_content) {
                    $report = Report::where('rp_no', $request->rp_file_no[$i])->update([
                        'item_no' => $request->item_no,
                        'rp_cate' => $request->rp_cate,
                        'rp_content' => $request->rp_content[$i],
                    ]);

                    $files = [];
                    $path = join('/', ['files', 'report', $request->rp_file_no[$i]]);

                    $files_no = [];
                    $file_name = 'file_no' . (string) $i;

                    if ($request->$file_name) {
                        foreach ($request->$file_name as $file_no) {
                            $files_no[] = $file_no;
                        }
                    }

                    $files = File::where('file_table', 'report')->where('file_table_key', $request->rp_file_no[$i])->get();
                    $path = join('/', ['files', 'report', $request->rp_file_no[$i]]);

                    foreach ($files as $file) {
                        if (!in_array($file->file_no, $files_no)) {
                            Storage::disk('public')->delete($path . '/' . $file->file_name);
                        }
                    }

                    File::where('file_table', 'report')->where('file_table_key', $request->rp_file_no[$i])
                        ->whereNotIn('file_no', $files_no)->delete();

                    $file_position = File::where('file_table', 'report')->where('file_table_key', $request->rp_file_no[$i])->orderBy('file_position', 'DESC')->first();

                    $index = $file_position ? $file_position->file_position : 0;
                    $files = [];

                    $filename = 'file' . (string) $i;

                    foreach ($request->$filename as $file) {
                        if ($file != 'undefined') {
                            $url = Storage::disk('public')->put($path, $file);
                            $files[] = [
                                'file_table' => 'report',
                                'file_table_key' => $request->rp_file_no[$i],
                                'file_name_old' => $file->getClientOriginalName(),
                                'file_name' => basename($url),
                                'file_size' => $file->getSize(),
                                'file_extension' => $file->extension(),
                                'file_position' => $index,
                                'file_url' => $url,
                            ];
                            $index++;
                            File::insert($files);
                            $files = [];
                        }

                    }


                    $i++;
                }

                // $report = Report::where('rp_no', $request->rp_no)->update([
                //     'item_no' => $request->item_no,
                //     'rp_cate' => $request->rp_cate,
                //     'rp_content' => $request->rp_content[0],
                // ]);

                // $files_no = [];
                // foreach ($request->file_no0 as $file_no) {
                //     $files_no[] = $file_no;
                // }
                // $files = File::where('file_table', 'report')->where('file_table_key', $request->rp_no)->get();
                // $path = join('/', ['files', 'report', $request->rp_no]);

                // foreach ($files as $file) {
                //     if (!in_array($file->file_no, $files_no)){
                //         Storage::disk('public')->delete($path. '/' . $file->file_name);
                //     }
                // }
                // File::where('file_table', 'report')->where('file_table_key', $request->rp_no)
                // ->whereNotIn('file_no', $files_no)->delete();

                // $i = File::where('file_table', 'report')->where('file_table_key', $request->rp_no)->orderBy('file_position', 'DESC')->first()->file_position;
                // $files = [];
                // foreach ($request->file0 as $file) {
                //     if($file != 'undefined'){
                //         $url = Storage::disk('public')->put($path, $file);
                //         $files[] = [
                //             'file_table' => 'report',
                //             'file_table_key' => $request->rp_no,
                //             'file_name_old' => $file->getClientOriginalName(),
                //             'file_name' => basename($url),
                //             'file_size' => $file->getSize(),
                //             'file_extension' => $file->extension(),
                //             'file_position' => $i,
                //             'file_url' => $url,
                //         ];
                //         $i++;
                //         File::insert($files);
                //         $files = [];
                //     }

                // }
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'reports' => $reports,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function getReport($rp_no)
    {
        $report = Report::with('files')->where('rp_no', $rp_no)->orWhere('rp_parent_no', $rp_no)->get();

        return response()->json(['report' => $report]);

    }

    public function getReports()
    {
        // try {
        $reports = DB::table('report')
            ->select(['report.*'])
            ->join('file', 'file.file_table_key', '=', 'report.rp_no')
            ->where([
                'report.mb_no' => Auth::user()->mb_no,
                'file.file_table' => 'report',
            ])
            ->distinct()
            ->get();
        $files = DB::table('report')
            ->select(['file.*'])
            ->join('file', 'file.file_table_key', '=', 'report.rp_no')
            ->where([
                'report.mb_no' => Auth::user()->mb_no,
                'file.file_table' => 'report',
            ])
            ->get();

        $responses = [];

        foreach ($reports as $report) {
            $response = [];
            foreach ($files as $file) {
                if ($file->file_table_key == $report->rp_no) {
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
            $responses[] = $response;
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
            'reports' => $responses,
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
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0006], 500);
        }
    }
}

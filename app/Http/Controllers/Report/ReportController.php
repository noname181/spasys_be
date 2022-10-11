<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ReportRequest;
use App\Http\Requests\Report\ReportSearchRequest;
use App\Models\File;
use App\Models\Report;
use App\Utils\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

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
                        'w_no' => $request->w_no,
                        'rp_cate' => $request->rp_cate,
                        'rp_content' => $rp_content,
                        'rp_parent_no' => empty($parent_no) ? null : $parent_no,
                    ]);
                    if ($i == 0) {
                        $parent_no = $report_no;
                        Report::where('rp_no', $report_no)->update(['rp_parent_no'=> $report_no]);
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
                $rp_file_no = [];
                foreach ($request->rp_content as $rp_content) {
                    $rp_parent_no = Report::where('rp_no', $request->rp_no)->first()->rp_parent_no;
                    $report = Report::where('rp_no', $request->rp_file_no[$i])->update([
                        'w_no' => $request->w_no,
                        'rp_cate' => $request->rp_cate,
                        'rp_content' => $request->rp_content[$i],
                    ]);
                    $rp_file_no[] = $request->rp_file_no[$i];
                    $files = [];
                    $path = join('/', ['files', 'report', $request->rp_file_no[$i]]);

                    $files_no = [];
                    $file_name = 'file_no' . (string) $i;

                    if ($request->$file_name) {
                        foreach ($request->$file_name as $file_no) {
                            $files_no[] = $file_no;
                        }
                    }


                    if($request->rp_file_no[$i] != 'undefined'){

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
                    }else {

                        $report_no = Report::insertGetId([
                            'mb_no' => Auth::user()->mb_no,
                            'w_no' => $request->w_no,
                            'rp_cate' => $request->rp_cate,
                            'rp_content' => $rp_content,
                            'rp_parent_no' => $rp_parent_no,
                        ]);
                        $index =  0;
                        $rp_file_no[] = $report_no;

                        $path = join('/', ['files', 'report', $report_no]);

                    }


                    $files = [];

                    $filename = 'file' . (string) $i;

                    foreach ($request->$filename as $file) {
                        if ($file != 'undefined') {
                            $url = Storage::disk('public')->put($path, $file);
                            $files[] = [
                                'file_table' => 'report',
                                'file_table_key' => $request->rp_file_no[$i] != 'undefined' ? $request->rp_file_no[$i] : $report_no,
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

                $delete_reports = Report::where('rp_parent_no', $rp_parent_no)->whereNotIn('rp_no', $rp_file_no)->get();

                foreach($delete_reports as $delete_report){
                    $path = join('/', ['files', 'report', $delete_report->rp_no]);
                    $file = File::where('file_table', 'report')->where('file_table_key', $delete_report->rp_no)->first();
                    Storage::disk('public')->delete($path . '/' . $file->file_name);
                    $file->delete();
                    $delete_report->delete();
                }

                $rp_parent_new = Report::where('rp_parent_no', $rp_parent_no)->first()->rp_no;

                Report::where('rp_parent_no', $rp_parent_no)->update(['rp_parent_no' => $rp_parent_new]);

                // $report = Report::where('rp_no', $request->rp_no)->update([
                //     'w_no' => $request->w_no,
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
                'rp_no' => isset($rp_parent_new) ? $rp_parent_new : $report_no,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function getReport($rp_no)
    {
        $rp_parent_no = Report::where('rp_no', $rp_no)->first()->rp_parent_no;
        $report = Report::with('files','warehousing')->where('rp_parent_no', $rp_parent_no)->get();

        return response()->json(['report' => $report]);

    }

    public function getReportsMobi(ReportSearchRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $reports = Report::with(['files', 'reports_child_mobi','warehousing'])->whereRaw('rp_no = rp_parent_no')->orderBy('rp_parent_no', 'DESC');

            if (isset($validated['from_date'])) {
                $reports->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $reports->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['rp_cate'])) {
                $reports->where(function($query) use ($validated) {
                    $query->where('rp_cate', 'like', '%' . $validated['rp_cate'] . '%')->where('rp_parent_no', NULL);
                });
            }
            if (isset($validated['co_parent_name'])) {
                $reports->whereHas('warehousing.co_no.co_parent',function($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                });
            }
            if (isset($validated['co_name'])) {
                $reports->whereHas('warehousing.co_no', function($q) use($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['w_schedule_number'])) {
                $reports->whereHas('warehousing', function($q) use($validated) {
                    return $q->where(DB::raw('lower(w_schedule_number)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%');
                });
            }
            if (isset($validated['logistic_manage_number'])) {
                $reports->whereHas('warehousing', function($q) use($validated) {
                    return $q->where(DB::raw('lower(logistic_manage_number)'), 'like', '%' . strtolower($validated['logistic_manage_number']) . '%');
                });
            }
            if (isset($validated['m_bl'])) {
                $reports->whereHas('warehousing', function($q) use($validated) {
                    return $q->where(DB::raw('lower(m_bl)'), 'like', '%' . strtolower($validated['m_bl']) . '%');
                });
            }
            if (isset($validated['h_bl'])) {
                $reports->whereHas('warehousing', function($q) use($validated) {
                    return $q->where(DB::raw('lower(h_bl)'), 'like', '%' . strtolower($validated['h_bl']) . '%');
                });
            }
            if (isset($validated['rgd_status1_1']) || isset($validated['rgd_status1_2']) || isset($validated['rgd_status1_3'])) {
                $reports->whereHas('warehousing.receving_goods_delivery', function($q) use($validated) {
                $q->Where('rgd_status1', '=', $validated['rgd_status1_1'] ? $validated['rgd_status1_1'] : "")
                ->orWhere('rgd_status1', '=', $validated['rgd_status1_2'] ? $validated['rgd_status1_2'] : "")
                ->orWhere('rgd_status1', '=', $validated['rgd_status1_3'] ? $validated['rgd_status1_3'] : "");
                });
            }
            $reports = $reports->paginate($per_page, ['*'], 'page', $page);

            $data = new Collection();



            return response()->json($reports);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);

        }
    }

    public function getReports(ReportSearchRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 5;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if($user->mb_type == 'shop'){
                $reports = Report::with(['files', 'reports_child','warehousing'])->whereHas('warehousing.co_no.co_parent',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->orderBy('rp_parent_no', 'DESC');
            }else if($user->mb_type == 'shipper'){
                $reports = Report::with(['files', 'reports_child','warehousing'])->whereHas('warehousing.co_no',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->orderBy('rp_parent_no', 'DESC');
            }else if($user->mb_type == 'spasys'){
                $reports = Report::with(['files', 'reports_child','warehousing'])->whereHas('warehousing.co_no.co_parent.co_parent',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->orderBy('rp_parent_no', 'DESC');
            }

            if (isset($validated['from_date'])) {
                $reports->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $reports->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['rp_cate']) && $validated['rp_cate'] != '전체') {
                    $reports->where('rp_cate', '=', $validated['rp_cate']);

            }
            if (isset($validated['co_parent_name'])) {
                $reports->whereHas('warehousing.co_no.co_parent',function($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                });
            }
            if (isset($validated['co_name'])) {
                $reports->whereHas('warehousing.co_no', function($q) use($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['w_schedule_number'])) {
                $reports->whereHas('warehousing', function($q) use($validated) {
                    return $q->where(DB::raw('lower(w_schedule_number)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%');
                });
            }
            if (isset($validated['logistic_manage_number'])) {
                $reports->whereHas('warehousing.import_schedule', function($q) use($validated) {
                    return $q->where(DB::raw('lower(logistic_manage_number)'), 'like', '%' . strtolower($validated['logistic_manage_number']) . '%');
                });
            }
            if (isset($validated['m_bl'])) {
                $reports->whereHas('warehousing.import_schedule', function($q) use($validated) {
                    return $q->where(DB::raw('lower(m_bl)'), 'like', '%' . strtolower($validated['m_bl']) . '%');
                });
            }
            if (isset($validated['h_bl'])) {
                $reports->whereHas('warehousing.import_schedule', function($q) use($validated) {
                    return $q->where(DB::raw('lower(h_bl)'), 'like', '%' . strtolower($validated['h_bl']) . '%');
                });
            }

            $reports = $reports->paginate($per_page, ['*'], 'page', $page);

            $data = new Collection();



            return response()->json($reports);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);

        }
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

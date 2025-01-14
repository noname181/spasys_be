<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ReportRequest;
use App\Http\Requests\Report\ReportSearchRequest;
use App\Models\File;
use App\Models\Report;
use App\Utils\Messages;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use App\Utils\CommonFunc;

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
            $user = Auth::user();
        
            if (!$request->rp_no) {
                foreach ($request->rp_content as $rp_content) {
                    $report_no = Report::insertGetId([
                        'mb_no' => Auth::user()->mb_no,
                        'w_no' => $request->w_no,
                        'rp_cate' => $request->rp_cate,
                        'rp_content' => $rp_content,
                        'rp_parent_no' => empty($parent_no) ? null : $parent_no,
                        'rp_h_bl' => $request->w_schedule_number,
                        'created_at'=>Carbon::now()->format('Y-m-d H:i:s')
                    ]);
                    if ($i == 0) {
                        $parent_no = $report_no;
                        Report::where('rp_no', $report_no)->update([
                            'rp_parent_no'=> $report_no,
                            'rp_number' =>  CommonFunc::report_number($report_no),
                            'updated_at'=>Carbon::now()->format('Y-m-d H:i:s')
                        ]);

                    }
                    $reports[] = $report_no;
                    $filename = 'file' . (string) $i;
                    $files = [];
                    $path = join('/', ['files', 'report', $report_no]);
                    $index = 0;
                    if (isset($request->$filename)) {
                    if($request->$filename){
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
                    }
                    }
                    $i++;
                }
                
                if($request->type == "보세화물"){
                    $title = "[보세화물] 사진등록";
                }elseif($request->type == "수입풀필먼트"){
                    $title = "[수입풀필먼트] 사진등록";
                }else{
                    $title = "[유통가공] 사진등록";
                }
            
                CommonFunc::insert_alarm_photo($title, null, $user, $request, 'photo');

            } else {
                $rp_file_no = [];
                foreach ($request->rp_content as $rp_content) {
                    $rp_parent_no = Report::where('rp_no', $request->rp_no)->first()->rp_parent_no;
                    $report = Report::where('rp_no', $request->rp_file_no[$i])->update([
                        'w_no' => $request->w_no,
                        'rp_cate' => $request->rp_cate,
                        'rp_h_bl' => $request->w_schedule_number,
                        'rp_content' => $request->rp_content[$i],
                        'updated_at'=>Carbon::now()->format('Y-m-d H:i:s'),
                        'rp_update' => 'y'
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
                            'rp_h_bl' => $request->w_schedule_number,
                            'created_at'=>Carbon::now()->format('Y-m-d H:i:s'),
                            'updated_at'=>Carbon::now()->format('Y-m-d H:i:s')
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
                'user' => $user
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
        $rp_parent_no = Report::where('rp_no', $rp_no)->first()->rp_parent_no;
        $report = Report::with('files','warehousing','export')->where('rp_parent_no', $rp_parent_no)->get();

        return response()->json(['report' => $report]);

    }

    public function getReportsMobi(ReportSearchRequest $request)
    {
         try {
            $validated = $request->validated();
            ini_set('memory_limit', '-1');
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 5;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            DB::statement("set session sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            if($user->mb_type == 'shop'){
                // $reports = Report::with(['files', 'reports_child','warehousing','import_expect','member'])->where(function($q) use($validated,$user) {
                // // $q->whereHas('export.import_expected.company.co_parent',function ($q) use ($user){
                // //     $q->where('co_no', $user->co_no);
                // // })
                // // $q->WhereHas('export.import_expected.company.co_parent',function ($q) use ($user){
                // //     $q->where('co_parent_no', $user->co_no);
                // // })->
                // $q->WhereHas('import_expect.company.co_parent',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })->orwhereHas('import_expect.company_spasys',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })
                // // ->orwhereHas('import.import_expected.company.co_parent',function ($q) use ($user){
                // //     $q->where('co_no', $user->co_no);
                // // })
                // ->orwhereHas('warehousing.co_no.co_parent',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // });
                // })->orderBy('created_at', 'DESC')->orderBy('rp_parent_no', 'DESC');


                $reports = Report::select('t_import_expected.tie_is_name_eng','t_import.ti_no','t_export.te_no','report.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with(['files','reports_child','warehousing','member','reports_parent'])->leftjoin('t_import_expected', function ($join) {
                    $join->on('report.rp_h_bl', '=', 't_import_expected.tie_h_bl');
                })->leftjoin('company as company_spasys', function ($join) {
                    $join->on('company_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                })->leftjoin('company as company_shop', function ($join) {
                    $join->on('company_shop.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as company_spasys_parent', function ($join) {
                    $join->on('company_spasys_parent.co_no', '=', 'company_spasys.co_parent_no');
                })->leftjoin('company as company_shop_parent', function ($join) {
                    $join->on('company_shop_parent.co_no', '=', 'company_shop.co_parent_no');
                })->leftjoin('t_export', function ($join) {
                    $join->on('t_export.te_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
                })->leftjoin('t_import', function ($join) {
                    $join->on('t_import.ti_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
                })->where(function($q) use($validated,$user) {
                // $q->whereHas('import_expect.company.co_parent',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })->orwhereHas('import_expect.company_spasys',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })
                // ->orwhereHas('import.import_expected.company.co_parent',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })
                $q->where('company_shop_parent.co_no','=', $user->co_no)->orWhere('company_spasys.co_no','=', $user->co_no)->orwhereHas('warehousing.co_no.co_parent',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                });})->orderBy('rp_parent_no', 'DESC');
                


            }else if($user->mb_type == 'shipper'){
                // $reports = Report::with(['files', 'reports_child','warehousing','import_expect','member'])->where(function($q) use($validated,$user) {
                //     $q->whereHas('export.import_expected.company.co_parent',function ($q) use ($user){
                //         $q->where('co_no', $user->co_no);
                //     })->orwhereHas('export.import_expected.company.co_parent',function ($q) use ($user){
                //         $q->where('co_parent_no', $user->co_no);
                //     })->orwhereHas('import_expect.company',function ($q) use ($user){
                //         $q->where('co_no', $user->co_no);
                //     })
                //     // ->orwhereHas('import.import_expected.company',function ($q) use ($user){
                //     //     $q->where('co_no', $user->co_no);
                //     // })
                //     ->orwhereHas('warehousing.co_no',function ($q) use ($user){
                //         $q->where('co_no', $user->co_no);
                //     });
                // })->orderBy('created_at', 'DESC')->orderBy('rp_parent_no', 'DESC');
                $reports = Report::select('t_import_expected.tie_is_name_eng','t_import.ti_no','t_export.te_no','report.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with(['files','reports_child','warehousing','member','reports_parent'])->leftjoin('t_import_expected', function ($join) {
                    $join->on('report.rp_h_bl', '=', 't_import_expected.tie_h_bl');
                })->leftjoin('company as company_spasys', function ($join) {
                    $join->on('company_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                })->leftjoin('company as company_shop', function ($join) {
                    $join->on('company_shop.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as company_spasys_parent', function ($join) {
                    $join->on('company_spasys_parent.co_no', '=', 'company_spasys.co_parent_no');
                })->leftjoin('company as company_shop_parent', function ($join) {
                    $join->on('company_shop_parent.co_no', '=', 'company_shop.co_parent_no');
                })->leftjoin('t_export', function ($join) {
                    $join->on('t_export.te_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
                })->leftjoin('t_import', function ($join) {
                    $join->on('t_import.ti_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
                })->where(function($q) use($validated,$user) {
                    $q->where('company_shop.co_no','=', $user->co_no)->orwhereHas('warehousing.co_no',function ($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rp_parent_no', 'DESC');
            }else if($user->mb_type == 'spasys'){
                $reports = Report::select('t_import_expected.tie_is_name_eng','t_import.ti_no','t_export.te_no','report.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with(['files','reports_child','warehousing','member','reports_parent'])->leftjoin('t_import_expected', function ($join) {
                    $join->on('report.rp_h_bl', '=', 't_import_expected.tie_h_bl');
                })->leftjoin('company as company_spasys', function ($join) {
                    $join->on('company_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                })->leftjoin('company as company_shop', function ($join) {
                    $join->on('company_shop.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as company_spasys_parent', function ($join) {
                    $join->on('company_spasys_parent.co_no', '=', 'company_spasys.co_parent_no');
                })->leftjoin('company as company_shop_parent', function ($join) {
                    $join->on('company_shop_parent.co_no', '=', 'company_shop.co_parent_no');
                })->leftjoin('t_export', function ($join) {
                    $join->on('t_export.te_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
                })->leftjoin('t_import', function ($join) {
                    $join->on('t_import.ti_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
                })->where(function($q) use($validated,$user) {
                // $q->whereHas('import_expect.company.co_parent',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })->orwhereHas('import_expect.company_spasys',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })
                // ->orwhereHas('import.import_expected.company.co_parent',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })
                $q->where('company_shop_parent.co_no','=', $user->co_no)->orWhere('company_spasys.co_no','=', $user->co_no)->orwhereHas('warehousing.co_no.co_parent.co_parent',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                });})->orderBy('rp_parent_no', 'DESC');
            }

            if (isset($validated['from_date'])) {
                $reports->where('report.created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $reports->where('report.created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['rp_cate']) && $validated['rp_cate'] != '전체') {
                    $reports->where('rp_cate', '=', $validated['rp_cate']);

            }
            if (isset($validated['rp_cate1']) || isset($validated['rp_cate2']) || isset($validated['rp_cate3']) || isset($validated['rp_cate4'])) {
                $wherein = '';

                if(isset($validated['rp_cate1'])){
                    $wherein .= $validated['rp_cate1'];
                }
                if(isset($validated['rp_cate2'])){
                    $wherein .= $validated['rp_cate2'];
                }
                if(isset($validated['rp_cate3'])){
                    $wherein .= $validated['rp_cate3'];
                }
                if(isset($validated['rp_cate4'])){
                    $wherein .= $validated['rp_cate4'];
                }
                $wherein2 = [$wherein];
                $reports->whereIn('rp_cate', $wherein2);

            }
            if (isset($validated['co_parent_name'])) {
                $reports->where(function($q) use($validated) {
                    $q->whereHas('warehousing.co_no.co_parent',function($q2) use ($validated) {
                        $q2->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                    })->orwhereHas('import_expect.company.co_parent', function($q3) use($validated) {
                        return $q3->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    })->orwhereHas('import_expect.company_spasys', function($q4) use($validated) {
                        return $q4->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    });
                });
            }
            if (isset($validated['co_name'])) {
                $reports->where(function($q) use($validated) {
                    $q->whereHas('warehousing.co_no', function($q2) use($validated) {
                        return $q2->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('import_expect.company', function($q3) use($validated) {
                        return $q3->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('import_expect.company_spasys', function($q4) use($validated) {
                        return $q4->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    });
                });
            }
            if (isset($validated['w_schedule_number'])) {
                // $reports->whereHas('warehousing', function($q) use($validated) {
                //     return $q->where(DB::raw('lower(w_schedule_number)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%');
                // });
                $reports->where(function($q) use($validated) {
                    $q->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(w_schedule_number)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%')->orWhere(DB::raw('lower(w_schedule_number2)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%');
                    })->orWhereHas('export.import_expected', function ($q2) use ($validated){
                        $q2->where('tie_h_bl', 'like', '%' . $validated['w_schedule_number'] . '%');
                    });
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

            if (isset($validated['service_name'])) {
               
                if($validated['service_name'] == '보세화물'){
                    $reports->where(function($q) use($validated) {
                        $q->whereHas('warehousing', function($q2) use($validated) {
                            return $q2->where('w_category_name', '=', $validated['service_name']);
                        })->orwhereHas('import_expect', function($q3) use($validated) {
                            return $q3->where('tie_h_bl', '!=', '')->orWhereNotNull('tie_h_bl');
                        });
                    });
                } else {
                    $reports->where(function($q) use($validated) {
                        $q->whereHas('warehousing', function($q2) use($validated) {
                            return $q2->where('w_category_name', '=', $validated['service_name']);
                        });
                    });
                }
            }
            if (isset($validated['service_name1']) || isset($validated['service_name2']) || isset($validated['service_name3'])) {
                    
                    $wherein = '';

                    if(isset($validated['service_name1'])){
                        $wherein .= $validated['service_name1'];
                    }
                    if(isset($validated['service_name2'])){
                        $wherein .= $validated['service_name2'];
                    }
                    if(isset($validated['service_name3'])){
                        $wherein .= $validated['service_name3'];
                    }
                    $wherein2 = [$wherein];
               
                    if(isset($validated['service_name1'])){
                    $reports->where(function($q) use($validated,$wherein2) {
                        $q->whereHas('warehousing', function($q2) use($validated,$wherein2) {
                            return $q2->whereIn('w_category_name',$wherein2);
                        })->orwhereHas('import_expect', function($q3) use($validated) {
                            return $q3->where('tie_h_bl', '!=', '')->orWhereNotNull('tie_h_bl');
                        });
                    });
                    } else {
                        $reports->where(function($q) use($validated,$wherein2) {
                            $q->whereHas('warehousing', function($q2) use($validated,$wherein2) {
                                return $q2->whereIn('w_category_name',$wherein2);
                            });
                        });
                    }
                
            }

            $reports = $reports->groupBy('rp_parent_no')->paginate($per_page, ['*'], 'page', $page);
            DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY'");
            //$data = new Collection();



            return response()->json($reports);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);

        }
    }

    public function getReports(ReportSearchRequest $request)
    {
        try {
            $validated = $request->validated();
            ini_set('memory_limit', '-1');
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 5;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            DB::statement("set session sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            if($user->mb_type == 'shop'){
                // $reports = Report::with(['files', 'reports_child','warehousing','import_expect','member'])->where(function($q) use($validated,$user) {
                // // $q->whereHas('export.import_expected.company.co_parent',function ($q) use ($user){
                // //     $q->where('co_no', $user->co_no);
                // // })
                // // $q->WhereHas('export.import_expected.company.co_parent',function ($q) use ($user){
                // //     $q->where('co_parent_no', $user->co_no);
                // // })->
                // $q->WhereHas('import_expect.company.co_parent',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })->orwhereHas('import_expect.company_spasys',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })
                // // ->orwhereHas('import.import_expected.company.co_parent',function ($q) use ($user){
                // //     $q->where('co_no', $user->co_no);
                // // })
                // ->orwhereHas('warehousing.co_no.co_parent',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // });
                // })->orderBy('created_at', 'DESC')->orderBy('rp_parent_no', 'DESC');


                $reports = Report::select('t_import.ti_no','t_export.te_no','report.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with(['files','reports_child','warehousing','member','reports_parent'])->leftjoin('t_import_expected', function ($join) {
                    $join->on('report.rp_h_bl', '=', 't_import_expected.tie_h_bl');
                })->leftjoin('company as company_spasys', function ($join) {
                    $join->on('company_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                })->leftjoin('company as company_shop', function ($join) {
                    $join->on('company_shop.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as company_spasys_parent', function ($join) {
                    $join->on('company_spasys_parent.co_no', '=', 'company_spasys.co_parent_no');
                })->leftjoin('company as company_shop_parent', function ($join) {
                    $join->on('company_shop_parent.co_no', '=', 'company_shop.co_parent_no');
                })->leftjoin('t_export', function ($join) {
                    $join->on('t_export.te_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
                })->leftjoin('t_import', function ($join) {
                    $join->on('t_import.ti_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
                })->where(function($q) use($validated,$user) {
                // $q->whereHas('import_expect.company.co_parent',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })->orwhereHas('import_expect.company_spasys',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })
                // ->orwhereHas('import.import_expected.company.co_parent',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })
                $q->where('company_shop_parent.co_no','=', $user->co_no)->orWhere('company_spasys.co_no','=', $user->co_no)->orwhereHas('warehousing.co_no.co_parent',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                });})->orderBy('rp_parent_no', 'DESC');
                


            }else if($user->mb_type == 'shipper'){
                // $reports = Report::with(['files', 'reports_child','warehousing','import_expect','member'])->where(function($q) use($validated,$user) {
                //     $q->whereHas('export.import_expected.company.co_parent',function ($q) use ($user){
                //         $q->where('co_no', $user->co_no);
                //     })->orwhereHas('export.import_expected.company.co_parent',function ($q) use ($user){
                //         $q->where('co_parent_no', $user->co_no);
                //     })->orwhereHas('import_expect.company',function ($q) use ($user){
                //         $q->where('co_no', $user->co_no);
                //     })
                //     // ->orwhereHas('import.import_expected.company',function ($q) use ($user){
                //     //     $q->where('co_no', $user->co_no);
                //     // })
                //     ->orwhereHas('warehousing.co_no',function ($q) use ($user){
                //         $q->where('co_no', $user->co_no);
                //     });
                // })->orderBy('created_at', 'DESC')->orderBy('rp_parent_no', 'DESC');
                $reports = Report::select('t_import.ti_no','t_export.te_no','report.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with(['files','reports_child','warehousing','member','reports_parent'])->leftjoin('t_import_expected', function ($join) {
                    $join->on('report.rp_h_bl', '=', 't_import_expected.tie_h_bl');
                })->leftjoin('company as company_spasys', function ($join) {
                    $join->on('company_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                })->leftjoin('company as company_shop', function ($join) {
                    $join->on('company_shop.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as company_spasys_parent', function ($join) {
                    $join->on('company_spasys_parent.co_no', '=', 'company_spasys.co_parent_no');
                })->leftjoin('company as company_shop_parent', function ($join) {
                    $join->on('company_shop_parent.co_no', '=', 'company_shop.co_parent_no');
                })->leftjoin('t_export', function ($join) {
                    $join->on('t_export.te_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
                })->leftjoin('t_import', function ($join) {
                    $join->on('t_import.ti_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
                })->where(function($q) use($validated,$user) {
                    $q->where('company_shop.co_no','=', $user->co_no)->orwhereHas('warehousing.co_no',function ($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rp_parent_no', 'DESC');
            }else if($user->mb_type == 'spasys'){
                $reports = Report::select('t_import.ti_no','t_export.te_no','report.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with(['files','reports_child','warehousing','member','reports_parent'])->leftjoin('t_import_expected', function ($join) {
                    $join->on('report.rp_h_bl', '=', 't_import_expected.tie_h_bl');
                })->leftjoin('company as company_spasys', function ($join) {
                    $join->on('company_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                })->leftjoin('company as company_shop', function ($join) {
                    $join->on('company_shop.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as company_spasys_parent', function ($join) {
                    $join->on('company_spasys_parent.co_no', '=', 'company_spasys.co_parent_no');
                })->leftjoin('company as company_shop_parent', function ($join) {
                    $join->on('company_shop_parent.co_no', '=', 'company_shop.co_parent_no');
                })->leftjoin('t_export', function ($join) {
                    $join->on('t_export.te_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
                })->leftjoin('t_import', function ($join) {
                    $join->on('t_import.ti_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
                })->where(function($q) use($validated,$user) {
                // $q->whereHas('import_expect.company.co_parent',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })->orwhereHas('import_expect.company_spasys',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })
                // ->orwhereHas('import.import_expected.company.co_parent',function ($q) use ($user){
                //     $q->where('co_no', $user->co_no);
                // })
                $q->where('company_shop_parent.co_no','=', $user->co_no)->orWhere('company_spasys.co_no','=', $user->co_no)->orwhereHas('warehousing.co_no.co_parent.co_parent',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                });})->orderBy('rp_parent_no', 'DESC');
            }

            if (isset($validated['from_date'])) {
                $reports->where('report.created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $reports->where('report.created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['rp_cate']) && $validated['rp_cate'] != '전체') {
                    $reports->where('rp_cate', '=', $validated['rp_cate']);

            }
            if (isset($validated['rp_cate1']) || isset($validated['rp_cate2']) || isset($validated['rp_cate3']) || isset($validated['rp_cate4'])) {
                $wherein = '';

                if(isset($validated['rp_cate1'])){
                    $wherein .= $validated['rp_cate1'];
                }
                if(isset($validated['rp_cate2'])){
                    $wherein .= $validated['rp_cate2'];
                }
                if(isset($validated['rp_cate3'])){
                    $wherein .= $validated['rp_cate3'];
                }
                if(isset($validated['rp_cate4'])){
                    $wherein .= $validated['rp_cate4'];
                }
                $wherein2 = [$wherein];
                $reports->whereIn('rp_cate', $wherein2);

            }
            if (isset($validated['co_parent_name'])) {
                $reports->where(function($q) use($validated) {
                    $q->whereHas('warehousing.co_no.co_parent',function($q2) use ($validated) {
                        $q2->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                    })->orwhereHas('import_expect.company.co_parent', function($q3) use($validated) {
                        return $q3->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    })->orwhereHas('import_expect.company_spasys', function($q4) use($validated) {
                        return $q4->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    });
                });
            }
            if (isset($validated['co_name'])) {
                $reports->where(function($q) use($validated) {
                    $q->whereHas('warehousing.co_no', function($q2) use($validated) {
                        return $q2->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('import_expect.company', function($q3) use($validated) {
                        return $q3->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('import_expect.company_spasys', function($q4) use($validated) {
                        return $q4->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    });
                });
            }
            if (isset($validated['w_schedule_number'])) {
                // $reports->whereHas('warehousing', function($q) use($validated) {
                //     return $q->where(DB::raw('lower(w_schedule_number)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%');
                // });
                $reports->where(function($q) use($validated) {
                    $q->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(w_schedule_number)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%')->orWhere(DB::raw('lower(w_schedule_number2)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%');
                    })->orWhereHas('export.import_expected', function ($q2) use ($validated){
                        $q2->where('tie_h_bl', 'like', '%' . $validated['w_schedule_number'] . '%');
                    });
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

            if (isset($validated['service_name'])) {
               
                if($validated['service_name'] == '보세화물'){
                    $reports->where(function($q) use($validated) {
                        $q->whereHas('warehousing', function($q2) use($validated) {
                            return $q2->where('w_category_name', '=', $validated['service_name']);
                        })->orwhereHas('import_expect', function($q3) use($validated) {
                            return $q3->where('tie_h_bl', '!=', '')->orWhereNotNull('tie_h_bl');
                        });
                    });
                } else {
                    $reports->where(function($q) use($validated) {
                        $q->whereHas('warehousing', function($q2) use($validated) {
                            return $q2->where('w_category_name', '=', $validated['service_name']);
                        });
                    });
                }
            }
            if (isset($validated['service_name1']) || isset($validated['service_name2']) || isset($validated['service_name3'])) {
                    
                    $wherein = '';

                    if(isset($validated['service_name1'])){
                        $wherein .= $validated['service_name1'];
                    }
                    if(isset($validated['service_name2'])){
                        $wherein .= $validated['service_name2'];
                    }
                    if(isset($validated['service_name3'])){
                        $wherein .= $validated['service_name3'];
                    }
                    $wherein2 = [$wherein];
               
                    if(isset($validated['service_name1'])){
                    $reports->where(function($q) use($validated,$wherein2) {
                        $q->whereHas('warehousing', function($q2) use($validated,$wherein2) {
                            return $q2->whereIn('w_category_name',$wherein2);
                        })->orwhereHas('import_expect', function($q3) use($validated) {
                            return $q3->where('tie_h_bl', '!=', '')->orWhereNotNull('tie_h_bl');
                        });
                    });
                    } else {
                        $reports->where(function($q) use($validated,$wherein2) {
                            $q->whereHas('warehousing', function($q2) use($validated,$wherein2) {
                                return $q2->whereIn('w_category_name',$wherein2);
                            });
                        });
                    }
                
            }

            $reports = $reports->groupBy('rp_parent_no')->paginate($per_page, ['*'], 'page', $page);
            DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY'");
            //$data = new Collection();
            return response()->json($reports);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
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

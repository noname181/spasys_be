<?php

namespace App\Http\Controllers\ImportSchedule;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportSchedule\ImportScheduleRequest;
use App\Http\Requests\ImportSchedule\ImportScheduleSearchRequest;

use App\Models\Import;
use App\Models\ImportExpected;
use App\Models\Export;
use App\Models\ExportConfirm;

use App\Models\ImportSchedule;
use App\Models\Member;
use App\Utils\Messages;
use App\Models\Warehousing;
use App\Models\ReceivingGoodsDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ImportScheduleController extends Controller
{
    /**
     * Fetch data
     * @param  \App\Http\Requests\ImportSchedule\ImportScheduleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(ImportScheduleRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $import_schedule = ImportSchedule::with('co_no')->with('files')->paginate($per_page, ['*'], 'page', $page);

            return response()->json($import_schedule);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Get ImportSchedule
     * @param  ImportScheduleSearchRequest $request
     */
    public function getImportSchedule(ImportScheduleSearchRequest $request)
    {
        try {

            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $import_schedule = ImportSchedule::with('co_no')->with('files')->orderBy('i_no', 'DESC');

            if (isset($validated['from_date'])) {
                $import_schedule->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $import_schedule->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_name'])) {
                $import_schedule->whereHas('co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['m_bl'])) {
                $import_schedule->where('m_bl', 'like', '%' . $validated['m_bl'] . '%');
            }

            if (isset($validated['h_bl'])) {
                $import_schedule->where('h_bl', 'like', '%' . $validated['h_bl'] . '%');
            }

            if (isset($validated['logistic_manage_number'])) {
                $import_schedule->where('logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
            }

            $members = Member::where('mb_no', '!=', 0)->get();

            $import_schedule = $import_schedule->paginate($per_page, ['*'], 'page', $page);

            return response()->json($import_schedule);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getImportAPI(ImportScheduleSearchRequest $request)
    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            $sql = DB::select(DB::raw("select * from
            (select tie_logistic_manage_number from t_import_expected where tie_is_date >= '2022-01-04' and tie_is_date <= '2022-10-04' group by tie_logistic_manage_number) as aaa
            left outer join
            (SELECT te_logistic_manage_number,te_carry_out_number FROM t_export group by te_logistic_manage_number, te_carry_out_number ) as bbb
            on
            aaa.tie_logistic_manage_number = bbb.te_logistic_manage_number"));

            $sql2 = "select * from (select tie_logistic_manage_number,tie_is_number 
            from t_import_expected where tie_is_date >= '2022-01-04' and tie_is_date <= '2022-10-20' and 1 = 1 group by tie_logistic_manage_number, tie_is_number) as aaa 
            left outer join (SELECT ti_logistic_manage_number, ti_i_confirm_number, ti_i_date, ti_i_order, ti_i_number, ti_carry_in_number 
            FROM t_import group by ti_logistic_manage_number, ti_i_confirm_number, ti_i_date, ti_i_order, ti_i_number, ti_carry_in_number ) as bbb on bbb.ti_logistic_manage_number = aaa.tie_logistic_manage_number
            left outer join (SELECT tec_logistic_manage_number, tec_ec_confirm_number, tec_ec_date, tec_ec_number 
            FROM t_export_confirm group by tec_logistic_manage_number, tec_ec_confirm_number, tec_ec_date, tec_ec_number ) as ccc on ccc.tec_logistic_manage_number = bbb.ti_logistic_manage_number
            left outer join (SELECT te_logistic_manage_number, te_carry_out_number, te_e_date, te_carry_in_number, te_e_order, te_e_number 
            FROM t_export group by te_logistic_manage_number, te_carry_out_number, te_e_date, te_carry_in_number, te_e_order, te_e_number ) as ddd on ddd.te_logistic_manage_number = ccc.tec_logistic_manage_number and ddd.te_carry_in_number = bbb.ti_carry_in_number;";

            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            $user = Auth::user();

            if ($user->mb_type == 'shop') {
                $import_schedule = ImportExpected::with(['import', 'company', 'receiving_goods_delivery'])->whereHas('company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->groupBy('t_import_expected.tie_logistic_manage_number')->leftjoin('t_export', 't_import_expected.tie_logistic_manage_number', '=', 't_export.te_logistic_manage_number')
                    ->select(['t_import_expected.*', 't_export.te_logistic_manage_number', 't_export.te_carry_out_number'])
                    ->where('tie_is_date', '>=', '2022-01-04')->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy('t_export.te_logistic_manage_number', 't_export.te_carry_out_number')->orderBy('t_export.te_carry_out_number', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $import_schedule = ImportExpected::with(['import', 'company', 'receiving_goods_delivery'])->whereHas('company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->groupBy('t_import_expected.tie_logistic_manage_number')->leftjoin('t_export', 't_import_expected.tie_logistic_manage_number', '=', 't_export.te_logistic_manage_number')
                    ->select(['t_import_expected.*', 't_export.te_logistic_manage_number', 't_export.te_carry_out_number'])
                    ->where('tie_is_date', '>=', '2022-01-04')->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy('t_export.te_logistic_manage_number', 't_export.te_carry_out_number')->orderBy('t_export.te_carry_out_number', 'DESC');
            } else if ($user->mb_type == 'spasys') {

                $import = Import::groupBy(['t_import.ti_logistic_manage_number', 't_import.ti_i_confirm_number', 't_import.ti_i_date', 't_import.ti_i_order', 't_import.ti_i_number', 't_import.ti_carry_in_number']);

                // $import_schedule = ImportExpected::with(['import','company','receiving_goods_delivery'])->where('tie_is_date', '>=', '2022-01-04')->where('tie_is_date', '<=', '2022-10-20')->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number'])->whereHas('import', function ($q) use ($import) {
                //     $q->whereNotNull('ti_logistic_manage_number')->union($import);
                // })->get();

                // $b = Import::groupBy('ti_logistic_manage_number, ti_i_confirm_number, ti_i_date, ti_i_order, ti_i_number, ti_carry_in_number');

                $import_schedule = ImportExpected::with(['company', 'receiving_goods_delivery'])->select('tie_status_2','tie_status','tie_m_bl','tie_h_bl','tie_no','tie_logistic_manage_number','tie_co_license','tie_is_number','ti_logistic_manage_number','ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number','tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number','te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                ->leftjoin(DB::raw('(SELECT ti_logistic_manage_number, ti_i_confirm_number, ti_i_date, ti_i_order, ti_i_number, ti_carry_in_number 
                FROM t_import group by ti_logistic_manage_number, ti_i_confirm_number, ti_i_date, ti_i_order, ti_i_number, ti_carry_in_number)
                bbb'), function ($leftJoin) {

                    $leftJoin->on('t_import_expected.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })->leftjoin(DB::raw('(SELECT tec_logistic_manage_number, tec_ec_confirm_number, tec_ec_date, tec_ec_number 
                FROM t_export_confirm group by tec_logistic_manage_number, tec_ec_confirm_number, tec_ec_date, tec_ec_number)
                ccc'), function ($leftjoin) {

                    $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                })->leftjoin(DB::raw('(SELECT te_logistic_manage_number, te_carry_out_number, te_e_date, te_carry_in_number, te_e_order, te_e_number 
                FROM t_export group by te_logistic_manage_number, te_carry_out_number, te_e_date, te_carry_in_number, te_e_order, te_e_number)
                ddd'), function ($leftjoin) {

                    $leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->where('tie_is_date', '>=', '2022-01-04')->where('tie_is_date', '<=', '2022-10-20')
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number'])->orderBy('te_carry_out_number', 'DESC');

                // $import_schedule = ImportExpected::with(['import', 'company','receiving_goods_delivery'])->whereHas('company.co_parent.co_parent', function ($q) use ($user) {
                //     $q->where('co_no', $user->co_no);
                // })->groupBy('t_import_expected.tie_logistic_manage_number')->leftjoin('t_export', 't_import_expected.tie_logistic_manage_number', '=', 't_export.te_logistic_manage_number')
                //     ->select(['t_import_expected.*', 't_export.te_logistic_manage_number', 't_export.te_carry_out_number'])
                //     ->where('tie_is_date', '>=', '2022-01-04')->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                //     ->groupBy('t_export.te_logistic_manage_number', 't_export.te_carry_out_number')->orderBy('t_export.te_carry_out_number', 'DESC');

            }

            //return $import_schedule;

            //$sql2 = DB::table('t_export')->select('te_logistic_manage_number','te_carry_out_number')->groupBy('te_logistic_manage_number','te_carry_out_number')->get();

            //$import_schedule = ImportExpected::with(['import','company'])->orderBy('tie_no', 'DESC');

            if (isset($validated['from_date'])) {
                $import_schedule->where('t_import_expected.created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $import_schedule->where('t_import_expected.created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $import_schedule->whereHas('company.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }

            if (isset($validated['co_name'])) {
                $import_schedule->whereHas('company', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['m_bl'])) {
                $import_schedule->where(DB::raw('tie_m_bl'), 'like', '%' . strtolower($validated['m_bl']) . '%');
            }

            if (isset($validated['h_bl'])) {
                $import_schedule->where(DB::raw('tie_h_bl'), 'like', '%' . strtolower($validated['h_bl']) . '%');
            }

            if (isset($validated['logistic_manage_number'])) {
                $import_schedule->where('tie_logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
            }
            if (isset($validated['tie_status'])) {
                $import_schedule->where('tie_status', '=', $validated['tie_status']);
            }
            if (isset($validated['tie_status_2'])) {
                $import_schedule->where('tie_status_2', '=', $validated['tie_status_2']);
            }
            // if (isset($validated['import_schedule_status1']) || isset($validated['import_schedule_status2'])) {
            //     $import_schedule->where(function($query) use ($validated) {
            //         $query->orwhere('import_schedule_status', '=', $validated['import_schedule_status1']);
            //         $query->orWhere('import_schedule_status', '=', $validated['import_schedule_status2']);
            //     });
            // }

            //$members = Member::where('mb_no', '!=', 0)->get();

            $import_schedule = $import_schedule->paginate($per_page, ['*'], 'page', $page);

            $status = DB::table('t_import_expected')
                ->select('tie_status_2')
                ->groupBy('tie_status_2')
                ->get();

            $custom = collect(['status_filter' => $status]);

            $import_schedule = $custom->merge($import_schedule);

            DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

            // foreach ($import_schedule['data'] as $item) {
            //     if (!empty($item['import']['ti_no'])) {
            //         $warehousing = Warehousing::updateOrCreate(
            //             [
            //                 'w_category_name' => '보세화물',
            //                 'tie_no' => $item['tie_no'],
            //             ],
            //             [
            //                 'mb_no' => $user->mb_no,
            //                 // 'w_completed_day' => $item['import']['ti_i_date'] ? $item['import']['ti_i_date'] : NULL,
            //                 // 'w_schedule_day' => $item['tie_is_date'] ? $item['tie_is_date'] : NULL,
            //                 'logistic_manage_number' => $item['tie_logistic_manage_number'],
            //                 'w_schedule_amount' => $item['tie_is_number'],
            //                 'w_amount' => $item['import']['ti_i_number'],
            //                 'w_type' => 'IW',
            //                 'co_no' => $item['company']['co_no'],
            //             ]
            //         );

            //         //THUONG EDIT TO MAKE SETTLEMENT
            //         $rgd_no = ReceivingGoodsDelivery::updateOrCreate(
            //             [
            //                 'w_no' => $warehousing->w_no,
            //             ],
            //             [
            //                 'mb_no' => $user->mb_no,
            //                 'service_korean_name' => '보세화물',
            //                 'rgd_status1' => '입고',
            //                 'rgd_tracking_code' => $item['te_carry_out_number']
            //             ]
            //         );
            //     }
            // }

            return response()->json($import_schedule);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ImportSchedule  $importSchedule
     * @return \Illuminate\Http\Response
     */
    public function show(ImportSchedule $importSchedule)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ImportSchedule  $importSchedule
     * @return \Illuminate\Http\Response
     */
    public function edit(ImportSchedule $importSchedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ImportSchedule  $importSchedule
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ImportSchedule $importSchedule)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ImportSchedule  $importSchedule
     * @return \Illuminate\Http\Response
     */
    public function destroy(ImportSchedule $importSchedule)
    {
        //
    }
}

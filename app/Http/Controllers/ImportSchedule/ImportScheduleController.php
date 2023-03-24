<?php

namespace App\Http\Controllers\ImportSchedule;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportSchedule\ImportScheduleRequest;
use App\Http\Requests\ImportSchedule\ImportScheduleSearchRequest;

use App\Models\Import;
use App\Models\ImportExpected;
use App\Models\Export;
use App\Models\ExportConfirm;

use App\Models\Company;
use App\Models\ImportSchedule;
use App\Models\Member;
use App\Utils\Messages;
use App\Models\Warehousing;
use App\Models\ReceivingGoodsDelivery;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PhpParser\Node\Expr;

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
               
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('parent_shop.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
                $sub_5 = ReceivingGoodsDelivery::select('is_no', 'rgd_status3', 'rgd_status1')->groupBy('is_no');

                $sub_6 = File::select('file_no', 'file_url','file_table_key');

                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
                // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                // })
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {
                    $leftjoin->on('ddd.te_carry_out_number', '=', 'nnn.is_no')->where('ddd.te_carry_out_number', '!=', null);
                    $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                })->leftJoinSub($sub_6, 'mmm', function ($leftjoin) {
                    $leftjoin->on('ddd.te_carry_out_number', '=', 'mmm.file_table_key')->where('ddd.te_carry_out_number', '!=', null);
                    $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'mmm.file_table_key')->whereNull('ddd.te_carry_out_number');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'mmm.file_table_key')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                })->orderBy('tie_is_date', 'DESC');
            } else if ($user->mb_type == 'shipper') {
               
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('company.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
                $sub_5 = ReceivingGoodsDelivery::select('is_no', 'rgd_status3', 'rgd_status1')->groupBy('is_no');
                $sub_6 = File::select('file_no', 'file_url','file_table_key');

                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
                // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                // })
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {
                    $leftjoin->on('ddd.te_carry_out_number', '=', 'nnn.is_no')->where('ddd.te_carry_out_number', '!=', null);
                    $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                })->leftJoinSub($sub_6, 'mmm', function ($leftjoin) {
                    $leftjoin->on('ddd.te_carry_out_number', '=', 'mmm.file_table_key')->where('ddd.te_carry_out_number', '!=', null);
                    $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'mmm.file_table_key')->whereNull('ddd.te_carry_out_number');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'mmm.file_table_key')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                })->orderBy('tie_is_date', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                
                //FIX NOT WORK 'with'
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                    })
                   ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })
                    // ->leftjoin('company', function ($join) {
                    //     $join->on('company.co_license', '=', 't_import_expected.tie_co_license');

                    // })->leftjoin('company as parent_shop', function ($join) {
                    //     $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    // })->leftjoin('company as parent_spasys', function ($join) {
                    //     $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    //     $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                    // })
                    //->where('parent_spasys.co_no', $user->co_no)

                    ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                    ->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);

                $sub_5 = ReceivingGoodsDelivery::select('is_no', 'rgd_status3', 'rgd_status1')->groupBy('is_no');
                $sub_6 = File::select('file_no', 'file_url','file_table_key');


                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
                // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                // })
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {
                    $leftjoin->on('ddd.te_carry_out_number', '=', 'nnn.is_no')->where('ddd.te_carry_out_number', '!=', null);
                    $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                })->leftJoinSub($sub_6, 'mmm', function ($leftjoin) {
                    $leftjoin->on('ddd.te_carry_out_number', '=', 'mmm.file_table_key')->where('ddd.te_carry_out_number', '!=', null);
                    $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'mmm.file_table_key')->whereNull('ddd.te_carry_out_number');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'mmm.file_table_key')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                })->orderBy('tie_is_date', 'DESC');


                
                //return DB::getQueryLog();
                //END FIX NOT WORK 'with'
            }
            
            if (isset($validated['from_date'])) {
                $import_schedule->where('aaa.tie_is_date', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $import_schedule->where('aaa.tie_is_date', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
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

            // if (isset($validated['tie_status'])) {

            //     if ($validated['tie_status'] == '반출') {
            //         $import_schedule->whereNotNull('te_logistic_manage_number');
            //     } else if ($validated['tie_status'] == '반출승인') {
            //         $import_schedule->whereNotNull('tec_logistic_manage_number')->whereNull('te_logistic_manage_number');
            //     } else if ($validated['tie_status'] == '반입') {
            //         $import_schedule->whereNotNull('ti_logistic_manage_number')->whereNull('tec_logistic_manage_number')->whereNull('te_logistic_manage_number');
            //     } else if ($validated['tie_status'] == '반입예정') {
            //         $import_schedule->whereNotNull('tie_logistic_manage_number')->whereNull('ti_logistic_manage_number')->whereNull('tec_logistic_manage_number')->whereNull('te_logistic_manage_number');
            //     }
            // }
            // if (isset($validated['tie_status_2'])) {
            //     if ($validated['tie_status'] == '반출') {
            //         $import_schedule->where('te_status_2', '=', $validated['tie_status_2']);
            //     } else if ($validated['tie_status'] == '반출승인') {
            //         $import_schedule->where('tec_status_2', '=', $validated['tie_status_2']);
            //     } else if ($validated['tie_status'] == '반입') {
            //         $import_schedule->where('ti_status_2', '=', $validated['tie_status_2']);
            //     } else if ($validated['tie_status'] == '반입예정') {
            //         $import_schedule->where('tie_status_2', '=', $validated['tie_status_2']);
            //     }
            // }

            if (isset($validated['tie_status'])) {
                if ($validated['tie_status'] == '반출') {
                     
                    $tie_logistic_manage_number = $this->SQL($validated);
                    $import_schedule->whereNotIn('tie_logistic_manage_number', $tie_logistic_manage_number);
                    
                } else if ($validated['tie_status'] == '반입') {
                    $import_schedule->whereNotNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number');
                } else if ($validated['tie_status'] == '반입예정') {
                    $import_schedule->whereNotNull('aaa.tie_logistic_manage_number')->whereNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number');
                }
            }
            
            if (isset($validated['tie_status_2'])) {
                    $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
            }

            if (isset($validated['rgd_status3'])) {

                if ($validated['rgd_status3'] == "배송준비") {
                    $import_schedule->where(function ($query) {
                        $query->whereNull('rgd_status3')->orWhere('rgd_status3', '=', '배송준비');
                        $query->where('rgd_status1', '!=', '반입');
                    });
                } else {
                    $import_schedule->where(function ($query) use ($validated) {
                        $query->where('rgd_status3', '=', $validated['rgd_status3']);
                        $query->where('rgd_status1', '!=', '반입');
                    });
                }
            }

            $import_schedule = $import_schedule->paginate($per_page, ['*'], 'page', $page);

            $import_schedule->setCollection(
                $import_schedule->getCollection()->map(function ($item) {


                    //foreach($te_carry_out_number as $te_carry_out_number_){
                    $file = File::where('file_table_key', $item->te_carry_out_number)->get();
                    //$files[] = $file;
                    //}
                    $item->files = $file;
                    return $item;
                })
            );
            $status = DB::table('t_import_expected')
                ->select('tie_status_2')
                ->groupBy('tie_status_2')
                ->get();

            $custom = collect(['status_filter' => $status]);

            $import_schedule = $custom->merge($import_schedule);

            DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

            return response()->json($import_schedule);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getImportAPI(ImportScheduleSearchRequest $request)
    {
        try {

            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            $user = Auth::user();

            DB::enableQueryLog();
            if ($user->mb_type == 'shop') {
               
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('parent_shop.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
                $sub_5 = ReceivingGoodsDelivery::select('is_no', 'rgd_status3', 'rgd_status1')->groupBy('is_no');

                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
                // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                // })
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {
                    $leftjoin->on('ddd.te_carry_out_number', '=', 'nnn.is_no')->where('ddd.te_carry_out_number', '!=', null);
                    $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                })->orderBy('tie_is_date', 'DESC');
            } else if ($user->mb_type == 'shipper') {
               
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('company.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
                $sub_5 = ReceivingGoodsDelivery::select('is_no', 'rgd_status3', 'rgd_status1')->groupBy('is_no');

                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
                // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                // })
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {
                    $leftjoin->on('ddd.te_carry_out_number', '=', 'nnn.is_no')->where('ddd.te_carry_out_number', '!=', null);
                    $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                })->orderBy('tie_is_date', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                
                //FIX NOT WORK 'with'
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                    })
                   ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })
                    // ->leftjoin('company', function ($join) {
                    //     $join->on('company.co_license', '=', 't_import_expected.tie_co_license');

                    // })->leftjoin('company as parent_shop', function ($join) {
                    //     $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    // })->leftjoin('company as parent_spasys', function ($join) {
                    //     $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    //     $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                    // })
                    //->where('parent_spasys.co_no', $user->co_no)

                    ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                    ->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);

                $sub_5 = ReceivingGoodsDelivery::select('is_no', 'rgd_status3', 'rgd_status1')->groupBy('is_no');


                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
                // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                // })
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {
                    $leftjoin->on('ddd.te_carry_out_number', '=', 'nnn.is_no')->where('ddd.te_carry_out_number', '!=', null);
                    $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                })->orderBy('tie_is_date', 'DESC');


                
               
                //END FIX NOT WORK 'with'
            }
            //return $import_schedule->get();

            //$sql2 = DB::table('t_export')->select('te_logistic_manage_number','te_carry_out_number')->groupBy('te_logistic_manage_number','te_carry_out_number')->get();

            //$import_schedule = ImportExpected::with(['import','company'])->orderBy('tie_no', 'DESC');
            // if (isset($validated['connection'])) {
             
            //     $import_schedule->wherenull('ddd.connection_number');
            // }

            // $import_schedule = $import_schedule->leftjoin('receiving_goods_delivery', function ($join) {
            //     $join->on('te_carry_out_number', '=', 'receiving_goods_delivery.is_no')->where('te_carry_out_number', '!=', null);
            //     $join->orOn('ti_carry_in_number', '=', 'receiving_goods_delivery.is_no')->whereNull('te_carry_out_number');
            //     $join->orOn('tie_logistic_manage_number', '=', 'receiving_goods_delivery.is_no')->whereNull('te_carry_out_number')->whereNull('ti_carry_in_number');
            // })->groupBy('receiving_goods_delivery.is_no');

            if (isset($validated['status'])) {
                // $import_schedule->whereHas('export.receiving_goods_delivery', function ($query) use ($validated) {
                //     $query->where('rgd_status1', '=', $validated['status']);
                // });

                $import_schedule->where('aaa.rgd_status1', '=', $validated['status']);
            }
            if (isset($validated['from_date'])) {
                $import_schedule->where('aaa.tie_is_date', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $import_schedule->where('aaa.tie_is_date', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }
            
            //return DB::getQueryLog();

            if (isset($validated['co_parent_name'])) {
                // $import_schedule->whereHas('company.co_parent', function ($query) use ($validated) {
                //     $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                // });

                $import_schedule->where(DB::raw('lower(aaa.co_name_shop)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
            }

            if (isset($validated['co_name'])) {
                // $import_schedule->whereHas('company', function ($q) use ($validated) {
                //     return $q->where(DB::raw('lower(aaa.co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                // });
                $import_schedule->where(DB::raw('lower(aaa.co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
            }
            if (isset($validated['co_no'])) {
                // $import_schedule->whereHas('company', function ($q) use ($validated) {
                //     return $q->where(DB::raw('lower(aaa.co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                // });
                $import_schedule->where('aaa.co_no','=',$validated['co_no']);
            }

            if (isset($validated['m_bl'])) {
                $import_schedule->where(DB::raw('aaa.tie_m_bl'), 'like', '%' . strtolower($validated['m_bl']) . '%');
            }

            if (isset($validated['h_bl'])) {
                $import_schedule->where(DB::raw('aaa.tie_h_bl'), 'like', '%' . strtolower($validated['h_bl']) . '%');
            }

            if (isset($validated['logistic_manage_number'])) {
                $import_schedule->where('aaa.tie_logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
            }

            if (isset($validated['tie_status'])) {
                if ($validated['tie_status'] == '반출') {
                     
                    $tie_logistic_manage_number = $this->SQL($validated);
                    $import_schedule->whereNotIn('tie_logistic_manage_number', $tie_logistic_manage_number);
                    //$import_schedule->whereNotNull('ddd.te_logistic_manage_number');
                    //return DB::getQueryLog();
                } else if ($validated['tie_status'] == '반입') {
                    $import_schedule->whereNotNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number');
                } else if ($validated['tie_status'] == '반입예정') {
                    $import_schedule->whereNotNull('aaa.tie_logistic_manage_number')->whereNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number');
                }
            }
            
            if (isset($validated['tie_status_2'])) {
                // if ($validated['tie_status'] == '반출') {
                //     $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                // } else if ($validated['tie_status'] == '반출승인') {
                //     $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                // } else if ($validated['tie_status'] == '반입') {
                //     $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                // } else if ($validated['tie_status'] == '반입예정') {
                    $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                //}
            }

            if (isset($validated['rgd_status3'])) {

                if ($validated['rgd_status3'] == "배송준비") {
                    $import_schedule->where(function ($query) {
                        $query->whereNull('rgd_status3')->orWhere('rgd_status3', '=', '배송준비');
                        $query->where('rgd_status1', '!=', '반입');
                    });
                } else {
                    $import_schedule->where(function ($query) use ($validated) {
                        $query->where('rgd_status3', '=', $validated['rgd_status3']);
                        $query->where('rgd_status1', '!=', '반입');
                    });
                }
            }

            // if (isset($validated['import_schedule_status1']) || isset($validated['import_schedule_status2'])) {
            //     $import_schedule->where(function($query) use ($validated) {
            //         $query->orwhere('import_schedule_status', '=', $validated['import_schedule_status1']);
            //         $query->orWhere('import_schedule_status', '=', $validated['import_schedule_status2']);
            //     });
            // }

            //$members = Member::where('mb_no', '!=', 0)->get();

            $import_schedule = $import_schedule->paginate($per_page, ['*'], 'page', $page);

            $import_schedule->setCollection(
                $import_schedule->getCollection()->map(function ($item) {
                    if (isset($item->te_e_number)) {
                      $item->number = $item->te_e_number;
                      } else if (isset($item->ti_i_number)) {
                        $item->number =  $item->ti_i_number;
                      } else if (isset($item->tie_is_number)) {
                         $item->number =  $item->tie_is_number;
                      }

                    return $item;
                })
            );
            $status = DB::table('t_import_expected')
                ->select('tie_status_2')
                ->groupBy('tie_status_2')
                ->get();

            $custom = collect(['status_filter' => $status]);

            $import_schedule = $custom->merge($import_schedule);

            DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

            return response()->json($import_schedule);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getImportAPI2(ImportScheduleSearchRequest $request)
    {
        try {

            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            $user = Auth::user();

            DB::enableQueryLog();
            if ($user->mb_type == 'shop') {
               
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('parent_shop.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1','ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    ->leftjoin('receiving_goods_delivery', function ($join) {
                        $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })

                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('ti_i_date', 'DESC');
            } else if ($user->mb_type == 'shipper') {
               
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('company.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    ->leftjoin('receiving_goods_delivery', function ($join) {
                        $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

   

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')

                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })

                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('ti_i_date', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                
                //FIX NOT WORK 'with'
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                    })
                   ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })


                    ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                    ->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('receiving_goods_delivery.rgd_no','receiving_goods_delivery.rgd_status3','receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    ->leftjoin('receiving_goods_delivery', function ($join) {
                        $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);


                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')

     
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })

                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('ti_i_date', 'DESC');


                
                //return DB::getQueryLog();
                //END FIX NOT WORK 'with'
            }

            if (isset($validated['status'])) {


                $import_schedule->where('aaa.rgd_status1', '=', $validated['status']);
            }
            if (isset($validated['from_date'])) {
                $import_schedule->where('aaa.tie_is_date', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $import_schedule->where('aaa.tie_is_date', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {


                $import_schedule->where(DB::raw('lower(aaa.co_name_shop)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
            }

            if (isset($validated['co_name'])) {

                $import_schedule->where(DB::raw('lower(aaa.co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
            }
            if (isset($validated['co_no'])) {

                $import_schedule->where('aaa.co_no','=',$validated['co_no']);
            }

            if (isset($validated['m_bl'])) {
                $import_schedule->where(DB::raw('aaa.tie_m_bl'), 'like', '%' . strtolower($validated['m_bl']) . '%');
            }

            if (isset($validated['h_bl'])) {
                $import_schedule->where(DB::raw('aaa.tie_h_bl'), 'like', '%' . strtolower($validated['h_bl']) . '%');
            }

            if (isset($validated['logistic_manage_number'])) {
                $import_schedule->where('aaa.tie_logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
            }

            if (isset($validated['tie_status'])) {
                if ($validated['tie_status'] == '반출') {
                     
                    $tie_logistic_manage_number = $this->SQL($validated);
                    $import_schedule->whereNotIn('tie_logistic_manage_number', $tie_logistic_manage_number);
                    //$import_schedule->whereNotNull('ddd.te_logistic_manage_number');
                    //return DB::getQueryLog();
                } else if ($validated['tie_status'] == '반입') {
                    $import_schedule->whereNotNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number');
                } else if ($validated['tie_status'] == '반입예정') {
                    $import_schedule->whereNotNull('aaa.tie_logistic_manage_number')->whereNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number');
                }
            }
            
            if (isset($validated['tie_status_2'])) {
                // if ($validated['tie_status'] == '반출') {
                //     $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                // } else if ($validated['tie_status'] == '반출승인') {
                //     $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                // } else if ($validated['tie_status'] == '반입') {
                //     $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                // } else if ($validated['tie_status'] == '반입예정') {
                    $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                //}
            }

            // if (isset($validated['import_schedule_status1']) || isset($validated['import_schedule_status2'])) {
            //     $import_schedule->where(function($query) use ($validated) {
            //         $query->orwhere('import_schedule_status', '=', $validated['import_schedule_status1']);
            //         $query->orWhere('import_schedule_status', '=', $validated['import_schedule_status2']);
            //     });
            // }

            //$members = Member::where('mb_no', '!=', 0)->get();

            $import_schedule = $import_schedule->paginate($per_page, ['*'], 'page', $page);

            $import_schedule->setCollection(
                $import_schedule->getCollection()->map(function ($item) {
                    if (isset($item->te_e_number)) {
                      $item->number = $item->te_e_number;
                      } else if (isset($item->ti_i_number)) {
                        $item->number =  $item->ti_i_number;
                      } else if (isset($item->tie_is_number)) {
                         $item->number =  $item->tie_is_number;
                      }

                    return $item;
                })
            );
            $status = DB::table('t_import_expected')
                ->select('tie_status_2')
                ->groupBy('tie_status_2')
                ->get();

            $custom = collect(['status_filter' => $status]);

            $import_schedule = $custom->merge($import_schedule);

            DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

            return response()->json($import_schedule);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function SQL($validated)
    {
           
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            //DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
               
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('parent_shop.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1','ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    ->leftjoin('receiving_goods_delivery', function ($join) {
                        $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                  
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
                
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                    
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('te_carry_out_number', 'DESC');
            } else if ($user->mb_type == 'shipper') {
               
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('company.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    ->leftjoin('receiving_goods_delivery', function ($join) {
                        $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

               

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
             
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('te_carry_out_number', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                
                //FIX NOT WORK 'with'
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                    })
                   ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })

                    ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                    ->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    ->leftjoin('receiving_goods_delivery', function ($join) {
                        $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                  
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
             
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('ti_logistic_manage_number', 'DESC')->orderBy('te_logistic_manage_number', 'DESC');           
            }
          
            if (isset($validated['status'])) {
                $import_schedule->where('rgd_status1', '=', $validated['status']);
            }
            if (isset($validated['from_date'])) {
                $import_schedule->where('aaa.tie_is_date', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $import_schedule->where('aaa.tie_is_date', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $import_schedule->where(DB::raw('lower(aaa.co_name_shop)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
            }

            if (isset($validated['co_name'])) {
                $import_schedule->where(DB::raw('lower(aaa.co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
            }

            if (isset($validated['m_bl'])) {
                $import_schedule->where(DB::raw('aaa.tie_m_bl'), 'like', '%' . strtolower($validated['m_bl']) . '%');
            }

            if (isset($validated['h_bl'])) {
                $import_schedule->where(DB::raw('aaa.tie_h_bl'), 'like', '%' . strtolower($validated['h_bl']) . '%');
            }

            if (isset($validated['logistic_manage_number'])) {
                $import_schedule->where('aaa.tie_logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
            }

            $import_schedule = $import_schedule->whereNull('ddd.te_logistic_manage_number')->get();


            //DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            $id = [];
            foreach($import_schedule as $te){
                $id[] = $te->tie_logistic_manage_number;
            }
            return $id;
        
    }

    public function getImportAPIPOPUP(ImportScheduleSearchRequest $request)
    {
        try {

            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            $user = Auth::user();

            DB::enableQueryLog();
            if ($user->mb_type == 'shop') {
               
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('parent_shop.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
                $sub_5 = ReceivingGoodsDelivery::select('is_no', 'rgd_status3', 'rgd_status1')->groupBy('is_no');

                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
                // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                // })
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {
                    $leftjoin->on('ddd.te_carry_out_number', '=', 'nnn.is_no')->where('ddd.te_carry_out_number', '!=', null);
                    $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                })->orderBy('tie_is_date', 'DESC');
            } else if ($user->mb_type == 'shipper') {
               
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('company.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
                $sub_5 = ReceivingGoodsDelivery::select('is_no', 'rgd_status3', 'rgd_status1')->groupBy('is_no');

                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
                // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                // })
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {
                    $leftjoin->on('ddd.te_carry_out_number', '=', 'nnn.is_no')->where('ddd.te_carry_out_number', '!=', null);
                    $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                })->orderBy('tie_is_date', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                
                //FIX NOT WORK 'with'
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                    })
                   ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })
                    // ->leftjoin('company', function ($join) {
                    //     $join->on('company.co_license', '=', 't_import_expected.tie_co_license');

                    // })->leftjoin('company as parent_shop', function ($join) {
                    //     $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    // })->leftjoin('company as parent_spasys', function ($join) {
                    //     $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    //     $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                    // })
                    //->where('parent_spasys.co_no', $user->co_no)

                    ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                    ->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);

                $sub_5 = ReceivingGoodsDelivery::select('is_no', 'rgd_status3', 'rgd_status1')->groupBy('is_no');


                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
                // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                // })
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {
                    $leftjoin->on('ddd.te_carry_out_number', '=', 'nnn.is_no')->where('ddd.te_carry_out_number', '!=', null);
                    $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                })->orderBy('tie_is_date', 'DESC');


                
                //return DB::getQueryLog();
                //END FIX NOT WORK 'with'
            }
            //return $import_schedule->get();

            //$sql2 = DB::table('t_export')->select('te_logistic_manage_number','te_carry_out_number')->groupBy('te_logistic_manage_number','te_carry_out_number')->get();

            //$import_schedule = ImportExpected::with(['import','company'])->orderBy('tie_no', 'DESC');
            // if (isset($validated['connection'])) {
             
            //     $import_schedule->wherenull('ddd.connection_number');
            // }

            // $import_schedule = $import_schedule->leftjoin('receiving_goods_delivery', function ($join) {
            //     $join->on('te_carry_out_number', '=', 'receiving_goods_delivery.is_no')->where('te_carry_out_number', '!=', null);
            //     $join->orOn('ti_carry_in_number', '=', 'receiving_goods_delivery.is_no')->whereNull('te_carry_out_number');
            //     $join->orOn('tie_logistic_manage_number', '=', 'receiving_goods_delivery.is_no')->whereNull('te_carry_out_number')->whereNull('ti_carry_in_number');
            // })->groupBy('receiving_goods_delivery.is_no');

            // if (isset($validated['status'])) {
            //     // $import_schedule->whereHas('export.receiving_goods_delivery', function ($query) use ($validated) {
            //     //     $query->where('rgd_status1', '=', $validated['status']);
            //     // });

            //     $import_schedule->where('aaa.rgd_status1', '=', $validated['status']);
            // }
            if (isset($validated['from_date'])) {
                $import_schedule->where('aaa.tie_is_date', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $import_schedule->where('aaa.tie_is_date', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                // $import_schedule->whereHas('company.co_parent', function ($query) use ($validated) {
                //     $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                // });

                $import_schedule->where(DB::raw('lower(aaa.co_name_shop)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
            }

            if (isset($validated['co_name'])) {
                // $import_schedule->whereHas('company', function ($q) use ($validated) {
                //     return $q->where(DB::raw('lower(aaa.co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                // });
                $import_schedule->where(DB::raw('lower(aaa.co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
            }
            if (isset($validated['co_no'])) {
                // $import_schedule->whereHas('company', function ($q) use ($validated) {
                //     return $q->where(DB::raw('lower(aaa.co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                // });
                $import_schedule->where('aaa.co_no','=',$validated['co_no']);
            }

            if (isset($validated['m_bl'])) {
                $import_schedule->where(DB::raw('aaa.tie_m_bl'), 'like', '%' . strtolower($validated['m_bl']) . '%');
            }

            if (isset($validated['h_bl'])) {
                $import_schedule->where(DB::raw('aaa.tie_h_bl'), 'like', '%' . strtolower($validated['h_bl']) . '%');
            }

            if (isset($validated['logistic_manage_number'])) {
                $import_schedule->where('aaa.tie_logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
            }

            if (isset($validated['tie_status'])) {
                if ($validated['tie_status'] == '반출') {
                     
                    $tie_logistic_manage_number = $this->SQL($validated);
                    $import_schedule->whereNotIn('tie_logistic_manage_number', $tie_logistic_manage_number);
                    //$import_schedule->whereNotNull('ddd.te_logistic_manage_number');
                    //return DB::getQueryLog();
                } else if ($validated['tie_status'] == '반입') {
                    $import_schedule->whereNotNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number');
                } else if ($validated['tie_status'] == '반입예정') {
                    $import_schedule->whereNotNull('aaa.tie_logistic_manage_number')->whereNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number');
                }
            }
            
            if (isset($validated['tie_status_2'])) {
                // if ($validated['tie_status'] == '반출') {
                //     $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                // } else if ($validated['tie_status'] == '반출승인') {
                //     $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                // } else if ($validated['tie_status'] == '반입') {
                //     $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                // } else if ($validated['tie_status'] == '반입예정') {
                    $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                //}
            }

            if (isset($validated['rgd_status3'])) {

                if ($validated['rgd_status3'] == "배송준비") {
                    $import_schedule->where(function ($query) {
                        $query->whereNull('rgd_status3')->orWhere('rgd_status3', '=', '배송준비');
                        $query->where('rgd_status1', '!=', '반입');
                    });
                } else {
                    $import_schedule->where(function ($query) use ($validated) {
                        $query->where('rgd_status3', '=', $validated['rgd_status3']);
                        $query->where('rgd_status1', '!=', '반입');
                    });
                }
            }

            // if (isset($validated['import_schedule_status1']) || isset($validated['import_schedule_status2'])) {
            //     $import_schedule->where(function($query) use ($validated) {
            //         $query->orwhere('import_schedule_status', '=', $validated['import_schedule_status1']);
            //         $query->orWhere('import_schedule_status', '=', $validated['import_schedule_status2']);
            //     });
            // }

            //$members = Member::where('mb_no', '!=', 0)->get();

            $import_schedule = $import_schedule->paginate($per_page, ['*'], 'page', $page);

            $import_schedule->setCollection(
                $import_schedule->getCollection()->map(function ($item) {
                    if (isset($item->te_e_number)) {
                      $item->number = $item->te_e_number;
                      } else if (isset($item->ti_i_number)) {
                        $item->number =  $item->ti_i_number;
                      } else if (isset($item->tie_is_number)) {
                         $item->number =  $item->tie_is_number;
                      }

                    return $item;
                })
            );
            $status = DB::table('t_import_expected')
                ->select('tie_status_2')
                ->groupBy('tie_status_2')
                ->get();

            $custom = collect(['status_filter' => $status]);

            $import_schedule = $custom->merge($import_schedule);

            DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

            return response()->json($import_schedule);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
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

<?php

namespace App\Http\Controllers\Alarm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alarm\AlarmSearchRequest;
use App\Http\Requests\Alarm\AlarmRequest;
use App\Http\Requests\Alarm\AlarmHeaderRequest;
use App\Models\Alarm;
use App\Models\Member;
use App\Models\Warehousing;
use App\Models\ImportExpected;
use App\Models\ScheduleShipment;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlarmController extends Controller
{
    /**
     * Register and Update AlarmRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(AlarmRequest $request)
    {

        $validated = $request->validated();
        try {
            DB::beginTransaction();
            $alarm_no = $request->get('alarm_no');
            $w_no_alert = FALSE;
            if(isset($validated['w_no'])){
            $w_no_alert = Warehousing::with(['receving_goods_delivery'])->where('w_no', $validated['w_no'])->first();
            }
            if($w_no_alert){
                    $receiver_spasys = $w_no_alert->company->co_parent->co_parent;
                    $receiver_shop = $w_no_alert->company->co_parent;
                    $receiver_shipper = $w_no_alert->company;
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
            } else {
                if(isset($validated['ss_no'])){
                    $schedule_shipment = ScheduleShipment::with(['ContractWms'])->where('ss_no',$validated['ss_no'])->first();
                    $receiver_spasys = $schedule_shipment->ContractWms->company->co_parent->co_parent;
                    $receiver_shop = $schedule_shipment->ContractWms->company->co_parent;
                    $receiver_shipper = $schedule_shipment->ContractWms->company;
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                } else {
                $schedule_number = isset($validated['w_schedule_number']) ? $validated['w_schedule_number'] : $validated['alarm_h_bl'];
                $import_expected = ImportExpected::with(['company','company_spasys'])->where('tie_h_bl',$schedule_number)->first();
                $receiver_spasys = isset($import_expected->company->co_parent->co_parent) ? $import_expected->company->co_parent->co_parent : $import_expected->company_spasys;
                $receiver_shop = isset($import_expected->company->co_parent) ? $import_expected->company->co_parent : '';
                $receiver_shipper = isset($import_expected->company) ?  $import_expected->company : '';
                if($receiver_shop != '' && $receiver_shipper != ''){
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->orwhere('co_no', $receiver_shop->co_no)->orwhere('co_no', $receiver_shipper->co_no)->get();
                } else {
                    $receiver_list = Member::where('co_no', $receiver_spasys->co_no)->get();
                }
                }
            }

            
            if (!isset($alarm_no)) {
       
                foreach ($receiver_list as $receiver) {
                    Alarm::insertGetId(
                        [
                            'receiver_no' => $receiver->mb_no,
                            'mb_no' => Auth::user()->mb_no,
                            'ss_no' => isset($validated['ss_no']) ? $validated['ss_no'] : null,
                            'w_no' => isset($validated['w_no']) ? $validated['w_no'] : null, // FIXME hard set
                            'alarm_content' => $validated['alarm_content'],
                            'alarm_h_bl' => isset($validated['w_schedule_number']) ? $validated['w_schedule_number'] : $validated['alarm_h_bl'],
                        ]
                    );
    
               
                }
                // $alarm_no = Alarm::insertGetId([
                //     'mb_no' => Auth::user()->mb_no,
                //     'ss_no' => isset($validated['ss_no']) ? $validated['ss_no'] : null,
                //     'w_no' => isset($validated['w_no']) ? $validated['w_no'] : null, // FIXME hard set
                //     'alarm_content' => $validated['alarm_content'],
                //     'alarm_h_bl' => isset($validated['w_schedule_number']) ? $validated['w_schedule_number'] : $validated['alarm_h_bl'],
                // ]);
            } else {
                // Update data
                $alarm = Alarm::where('alarm_no', $alarm_no)->first();
                if (is_null($alarm)) {
                    return response()->json(['message' => Messages::MSG_0020], 404);
                }

                $update = [
                    'mb_no' => Auth::user()->mb_no,
                    'w_no' => isset($validated['w_no']) ? $validated['w_no'] : null,
                    'ss_no' => isset($validated['ss_no']) ? $validated['ss_no'] : null,
                    'alarm_content' => $validated['alarm_content'],
                    'alarm_h_bl' => isset($validated['w_schedule_number']) ? $validated['w_schedule_number'] : $validated['alarm_h_bl'],
                ];
                $alarm->update($update);
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
        
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }
    public function newAlarms(AlarmSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            $user = Auth::user();
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            // if($user->mb_type == 'shop'){
            //     // ->whereHas('member.company',function($q) use ($user){
            //     //     $q->where('co_no', $user->company->co_parent->co_no);
            //     //     //->orWhere('co_no', $user->company->co_parent->co_parent->co_no);
            //     // })
            //     $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('warehousing','member')->leftjoin('t_import_expected', function ($join) {
            //         $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
            //     })->leftjoin('company as company_spasys', function ($join) {
            //         $join->on('company_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
            //     })->leftjoin('company as company_shop', function ($join) {
            //         $join->on('company_shop.co_license', '=', 't_import_expected.tie_co_license');
            //     })->leftjoin('company as company_spasys_parent', function ($join) {
            //         $join->on('company_spasys_parent.co_no', '=', 'company_spasys.co_parent_no');
            //     })->leftjoin('company as company_shop_parent', function ($join) {
            //         $join->on('company_shop_parent.co_no', '=', 'company_shop.co_parent_no');
            //     })->leftjoin('t_export', function ($join) {
            //         $join->on('t_export.te_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
            //     })->leftjoin('t_import', function ($join) {
            //         $join->on('t_import.ti_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
            //     })->where(function($q) use($validated, $user) {
            //         $q->whereNull('alarm_type')->where(function($q) use($validated,$user) {
            //             $q->where('company_shop_parent.co_no','=', $user->co_no)->orWhere('company_spasys.co_no','=', $user->co_no)->orwhereHas('warehousing.co_no.co_parent',function ($q) use ($user){
            //                 $q->where('co_no', $user->co_no);
            //             });
            //         })
            //         ->orwhere(function($q) use ($user) {
            //             $q->whereNotNull('alarm_type')
            //             ->where(function($q) use ($user) {
            //                 if($user->mb_push_yn == 'y'){
            //                     $q->whereHas('warehousing.company.co_parent', function ($q) use ($user) {
            //                         $q->where('co_no', $user->co_no);
            //                     })->whereHas('member', function ($q) {
            //                         $q->where('mb_type', 'spasys');
            //                     })->orwhere(function($q) use($user){
            //                         $q->whereNotNull('alarm_type')
            //                         ->whereHas('warehousing', function($q) {
            //                             $q->where('w_category_name', '수입풀필먼트');
            //                         })->whereHas('warehousing.company', function($q) use ($user){
            //                             $q->where('co_no', $user->co_no);
            //                         });
            //                     });
            //                 }else {
            //                     $q->whereNull('alarm_no');
            //                 }

            //             });

            //         });
            //     })->orderBy('alarm_no', 'DESC');
            // }
            // else if($user->mb_type == 'shipper'){
            //     // ->whereHas('member.company',function($q) use ($user){
            //     //     $q->where('co_no', $user->company->co_parent->co_no)
            //     //     ->orWhere('co_no', $user->company->co_parent->co_parent->co_no);
            //     // })
            //     $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('warehousing','member')->leftjoin('t_import_expected', function ($join) {
            //         $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
            //     })->leftjoin('company as company_spasys', function ($join) {
            //         $join->on('company_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
            //     })->leftjoin('company as company_shop', function ($join) {
            //         $join->on('company_shop.co_license', '=', 't_import_expected.tie_co_license');
            //     })->leftjoin('company as company_spasys_parent', function ($join) {
            //         $join->on('company_spasys_parent.co_no', '=', 'company_spasys.co_parent_no');
            //     })->leftjoin('company as company_shop_parent', function ($join) {
            //         $join->on('company_shop_parent.co_no', '=', 'company_shop.co_parent_no');
            //     })->leftjoin('t_export', function ($join) {
            //         $join->on('t_export.te_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
            //     })->leftjoin('t_import', function ($join) {
            //         $join->on('t_import.ti_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
            //     })->where(function($q) use($validated,$user) {
            //         $q->whereNull('alarm_type')->where(function($q) use($validated,$user) {
            //             $q->where('company_shop.co_no','=', $user->co_no)->orwhereHas('warehousing.co_no',function ($q) use ($user){
            //                 $q->where('co_no', $user->co_no);
            //             });
            //         })
            //         ->orwhere(function($q) use ($user) {
            //             $q->whereNotNull('alarm_type')
            //             ->where(function($q) use ($user) {
            //                 if($user->mb_push_yn == 'y'){
            //                     $q->whereHas('warehousing.company', function ($q) use ($user) {
            //                         $q->where('co_no', $user->co_no);
            //                     })->whereHas('member', function ($q) {
            //                         $q->where('mb_type', 'shop');
            //                     })->orwhere(function($q) use($user){
            //                         $q->whereNotNull('alarm_type')
            //                         ->whereHas('warehousing', function($q) {
            //                             $q->where('w_category_name', '수입풀필먼트');
            //                         })->whereHas('warehousing.company', function($q) use ($user){
            //                             $q->where('co_no', $user->co_no);
            //                         });
            //                     });
            //                 }else {
            //                     $q->whereNull('alarm_no');
            //                 }

            //             });

            //         });
            //     })
            //     ->orderBy('alarm_no', 'DESC');

            // } else if ($user->mb_type == 'spasys'){
            //     $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('warehousing','member')->leftjoin('t_import_expected', function ($join) {
            //         $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
            //     })->leftjoin('company as company_spasys', function ($join) {
            //         $join->on('company_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
            //     })->leftjoin('company as company_shop', function ($join) {
            //         $join->on('company_shop.co_license', '=', 't_import_expected.tie_co_license');
            //     })->leftjoin('company as company_spasys_parent', function ($join) {
            //         $join->on('company_spasys_parent.co_no', '=', 'company_spasys.co_parent_no');
            //     })->leftjoin('company as company_shop_parent', function ($join) {
            //         $join->on('company_shop_parent.co_no', '=', 'company_shop.co_parent_no');
            //     })->leftjoin('t_export', function ($join) {
            //         $join->on('t_export.te_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
            //     })->leftjoin('t_import', function ($join) {
            //         $join->on('t_import.ti_logistic_manage_number', '=', 't_import_expected.tie_logistic_manage_number');
            //     })->where(function($q) use($validated,$user) {
            //         $q->whereNull('alarm_type')->where(function($q) use($validated,$user) {
            //             $q->whereHas('member',function ($q) use ($user){
            //                 $q->where('mb_no',$user->mb_no);
            //             });
            //         });
            //     })
            //     ->orderBy('alarm_no', 'DESC');
            // }
            if($user->mb_type == 'shop'){
                // ->whereHas('member.company',function($q) use ($user){
                //     $q->where('co_no', $user->company->co_parent->co_no);
                //     //->orWhere('co_no', $user->company->co_parent->co_parent->co_no);
                // })
                $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('schedule_shipment','warehousing','member', 'company','alarm_data')->leftjoin('t_import_expected', function ($join) {
                    $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
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
                })->where(function($q) use($validated, $user) {
                    $q->whereNull('alarm_type')->where(function($q) use($validated,$user) {
                        $q->where('receiver_no','=', $user->mb_no);
                    })
                    ->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_delivery%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_request%')
                        ->where('receiver_no', $user->mb_no);
                    });
                })->whereHas('alarm_data',function ($query) use ($validated){
                    $query->where('ad_must_yn','=','y');
                })->orderBy('alarm_no', 'DESC');
            }
            else if($user->mb_type == 'shipper'){
                // ->whereHas('member.company',function($q) use ($user){
                //     $q->where('co_no', $user->company->co_parent->co_no)
                //     ->orWhere('co_no', $user->company->co_parent->co_parent->co_no);
                // })
                $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('schedule_shipment','warehousing','member', 'company','alarm_data')->leftjoin('t_import_expected', function ($join) {
                    $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
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
                    $q->whereNull('alarm_type')->where(function($q) use($validated,$user) {
                        $q->where('receiver_no','=', $user->mb_no);
                    })
                    ->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_delivery%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_request%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%alarm_daily%')
                        ->where('receiver_no', $user->mb_no);
                    });
                })->whereHas('alarm_data',function ($query) use ($validated){
                    $query->where('ad_must_yn','=','y');
                })
                ->orderBy('alarm_no', 'DESC');

            } else if ($user->mb_type == 'spasys'){
                $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('schedule_shipment','warehousing','member', 'company','alarm_data')->leftjoin('t_import_expected', function ($join) {
                    $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
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
                    $q->whereNull('alarm_type')->where(function($q) use($validated,$user) {
                        
                            $q->where('receiver_no',$user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%photo%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%update_company%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_delivery%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_request%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%alarm_daily%')
                        ->where('receiver_no', $user->mb_no);
                    });
                })->whereHas('alarm_data',function ($query) use ($validated){
                    $query->where('ad_must_yn','=','y');
                })
                ->orderBy('alarm_no', 'DESC');
            }

            $alarm = $alarm->groupBy('alarm_no')->limit(3)->get();
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            return response()->json($alarm);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    
    public function updatePushRead(){
        try{
            $user = Auth::user();
            Alarm::where('receiver_no',$user->mb_no)->update(['alarm_read_yn'=>'y']);
            return response()->json([
                'message' => Messages::MSG_0007
            ]);
        }catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function AlarmHeaderList(AlarmHeaderRequest $request){
        $validated = $request->validated();
        try {
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            $user = Auth::user();
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            if($user->mb_type == 'shop'){
                // ->whereHas('member.company',function($q) use ($user){
                //     $q->where('co_no', $user->company->co_parent->co_no);
                //     //->orWhere('co_no', $user->company->co_parent->co_parent->co_no);
                // })
                $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('schedule_shipment','warehousing','member', 'company','alarm_data')->leftjoin('t_import_expected', function ($join) {
                    $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
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
                })->where(function($q) use($validated, $user) {
                    $q->whereNull('alarm_type')->where(function($q) use($validated,$user) {
                        $q->where('receiver_no','=', $user->mb_no);
                    })
                    ->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_delivery%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_request%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%alarm_daily%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%alarm_pw_company_3m%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_IW')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_EW')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_status3_EW')
                        ->where('receiver_no', $user->mb_no);
                    });
                })->orderBy('alarm_no', 'DESC');
            }
            else if($user->mb_type == 'shipper'){
                // ->whereHas('member.company',function($q) use ($user){
                //     $q->where('co_no', $user->company->co_parent->co_no)
                //     ->orWhere('co_no', $user->company->co_parent->co_parent->co_no);
                // })
                $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('schedule_shipment','warehousing','member', 'company','alarm_data')->leftjoin('t_import_expected', function ($join) {
                    $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
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
                    $q->whereNull('alarm_type')->where(function($q) use($validated,$user) {
                        $q->where('receiver_no','=', $user->mb_no);
                    })
                    ->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_delivery%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_request%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%alarm_daily%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%alarm_pw_company_3m%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_IW')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_EW')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_status3_EW')
                        ->where('receiver_no', $user->mb_no);
                    });
                })
                ->orderBy('alarm_no', 'DESC');

            } else if ($user->mb_type == 'spasys'){
                $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('schedule_shipment','warehousing','member', 'company','alarm_data')->leftjoin('t_import_expected', function ($join) {
                    $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
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
                    $q->whereNull('alarm_type')->where(function($q) use($validated,$user) {
                        
                            $q->where('receiver_no',$user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%photo%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%update_company%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_delivery%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_request%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%alarm_daily%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%alarm_pw_company_3m%')
                        ->where('receiver_no', $user->mb_no);
                    });
                })
                ->orderBy('alarm_no', 'DESC');
            }



           if(isset($validated['mb_push_yn'])){
                if($validated['mb_push_yn'] == 'y'){
                    $alarm->where(function($q) use($validated,$user) {
                        $q->whereNull('ad_no')->orWhereHas('alarm_data',function ($query) use ($validated){
                        $query->where('ad_must_yn','=','y');
                    }); });
                } else {
                    $alarm->where(function($q) use($validated,$user) {
                        $q->whereNull('ad_no')->orWhereHas('alarm_data',function ($query) use ($validated){
                        $query->where('ad_must_yn','=','y')->orwhere('ad_must_yn','=','n')->orWhereNull('ad_must_yn');
                    }); });
                }
           }

            $alarm = $alarm->groupBy('alarm_no')->paginate($per_page, ['*'], 'page', $page);
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            return response()->json($alarm);
        }catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function searchAlarms(AlarmSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            $user = Auth::user();
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            if($user->mb_type == 'shop'){
                // ->whereHas('member.company',function($q) use ($user){
                //     $q->where('co_no', $user->company->co_parent->co_no);
                //     //->orWhere('co_no', $user->company->co_parent->co_parent->co_no);
                // })
                $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('schedule_shipment','warehousing','member', 'company','alarm_data')->leftjoin('t_import_expected', function ($join) {
                    $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
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
                })->where(function($q) use($validated, $user) {
                    $q->whereNull('alarm_type')->where(function($q) use($validated,$user) {
                        $q->where('receiver_no','=', $user->mb_no);
                    })
                    ->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_delivery%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_request%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%alarm_daily%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%alarm_pw_company_3m%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_IW')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_EW')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_status3_EW')
                        ->where('receiver_no', $user->mb_no);
                    });
                })->orderBy('alarm_no', 'DESC');
            }
            else if($user->mb_type == 'shipper'){
                // ->whereHas('member.company',function($q) use ($user){
                //     $q->where('co_no', $user->company->co_parent->co_no)
                //     ->orWhere('co_no', $user->company->co_parent->co_parent->co_no);
                // })
                $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('schedule_shipment','warehousing','member', 'company','alarm_data')->leftjoin('t_import_expected', function ($join) {
                    $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
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
                    $q->whereNull('alarm_type')->where(function($q) use($validated,$user) {
                        $q->where('receiver_no','=', $user->mb_no);
                    })
                    ->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_delivery%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_request%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%alarm_daily%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%alarm_pw_company_3m%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_IW')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_EW')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_status3_EW')
                        ->where('receiver_no', $user->mb_no);
                    });
                })
                ->orderBy('alarm_no', 'DESC');

            } else if ($user->mb_type == 'spasys'){
                $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('schedule_shipment','warehousing','member', 'company','alarm_data')->leftjoin('t_import_expected', function ($join) {
                    $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
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
                    $q->whereNull('alarm_type')->where(function($q) use($validated,$user) {
                        
                            $q->where('receiver_no',$user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%photo%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%update_company%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_delivery%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_request%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%alarm_daily%')
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%alarm_pw_company_3m%')
                        ->where('receiver_no', $user->mb_no);
                    });
                })
                ->orderBy('alarm_no', 'DESC');
            }



            if (isset($validated['w_no'])) {
                $alarm->where('w_no', $validated['w_no']);
            }

            if (isset($validated['from_date'])) {
                $alarm->where('alarm.created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $alarm->where('alarm.created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }
            // if (isset($validated['co_parent_name'])) {
            //     $alarm->whereHas('member.company.co_parent', function ($query) use ($validated) {
            //         $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
            //     });
            // }
            if (isset($validated['co_parent_name'])) {
                $alarm->where(function($q) use($validated,$user) {
                    $q->whereHas('export.import_expected.company.co_parent',function ($query) use ($validated){
                        $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                    })->orwhereHas('import_expect.company.co_parent', function($q3) use($validated) {
                        return $q3->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    })->orwhereHas('import_expect.company_spasys', function($q4) use($validated) {
                        return $q4->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    })->orwhereHas('warehousing.company.co_parent',function($query) use ($validated) {
                        $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                    })->orwhereHas('schedule_shipment.ContractWms.company.co_parent',function($query) use ($validated) {
                        $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                    })->orWhere(function($q) use($validated,$user){
                        $q->whereHas('company', function($q) use($validated,$user){
                            $q->where('co_type', 'shop')->where('co_name', 'like','%'. strtolower($validated['co_parent_name']) .'%');
                        })->orwhereHas('company.co_parent', function($q) use($validated,$user){
                            $q->where('co_type', 'shop')->where('co_name', 'like','%'. strtolower($validated['co_parent_name']) .'%');
                        });
                    });
                });
            }
            if (isset($validated['co_name'])) {
                $alarm->where(function($q) use($validated,$user) {
                    $q->whereHas('warehousing.company', function($q) use($validated) {
                        return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('schedule_shipment.ContractWms.company', function($q) use($validated) {
                        return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('import_expect.company', function($q3) use($validated) {
                        return $q3->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('import_expect.company_spasys', function($q4) use($validated) {
                        return $q4->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('export.import_expected.company',function ($q) use ($validated,$user){
                        return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orWhere(function($q) use($validated,$user){
                        $q->whereHas('company', function($q) use($validated,$user){
                            $q->where('co_type', 'shipper')->where('co_name', 'like','%'. strtolower($validated['co_name']) .'%');
                        });
                    }); 
                });
            }
            if (isset($validated['service'])) {
                // if($validated['service'] == '보세화물'){
                //     $alarm->where(function($q) use($validated) {
                //         $q->whereHas('warehousing', function($q2) use($validated) {
                //             return $q2->where('w_category_name', '=', $validated['service']);
                //         })->orwhereHas('import_expect', function($q3) use($validated) {
                //             return $q3->where('tie_h_bl', '!=', '')->orWhereNotNull('tie_h_bl');
                //         });
                //     });
                // } else {
                //     $alarm->where(function($q) use($validated) {
                //         $q->whereHas('warehousing', function($q2) use($validated) {
                //             return $q2->where('w_category_name', '=', $validated['service']);
                //         });
                //     });
                // }
                if($validated['service'] == '보세화물'){
                    $alarm->where(function($q) use($validated) {
                        $q->whereHas('warehousing', function($q2) use($validated) {
                            return $q2->where('w_category_name', '=', $validated['service']);
                        })->orwhereHas('import_expect', function($q3) use($validated) {
                            return $q3->where('tie_h_bl', '!=', '')->orWhereNotNull('tie_h_bl');
                        });
                    });
                } if($validated['service'] == '수입풀필먼트'){
                    $alarm->where(function($q) use($validated) {
                        $q->whereHas('warehousing', function($q2) use($validated) {
                            return $q2->where('w_category_name', '=', $validated['service']);
                        })->orwhere('ss_no', '!=','')->orWhereNotNull('ss_no') ;
                    });
                } else {
                    $alarm->where(function($q) use($validated) {
                        $q->whereHas('warehousing', function($q2) use($validated) {
                            return $q2->where('w_category_name', '=', $validated['service']);
                        });
                    });
                }
                // if($validated['service'] == '보세화물'){
                //     $alarm->where(function($q) use($validated) {
                //         $q->whereHas('warehousing', function ($q) use ($validated) {
                //             return $q->where(DB::raw('lower(w_category_name)'), 'like', '%' . strtolower($validated['service']) . '%');
                //         })->orwhereHas('export.import_expected', function($q3) use($validated) {
                //             return $q3->where('tie_h_bl', '!=', '')->orWhereNotNull('tie_h_bl');
                //         });
                //     });
                // } else {
                //     $alarm->where(function($q) use($validated) {
                //         $q->whereHas('warehousing', function ($q) use ($validated) {
                //             return $q->where(DB::raw('lower(w_category_name)'), 'like', '%' . strtolower($validated['service']) . '%');
                //         });
                //     });
                // }
            }
            if (isset($validated['w_schedule_number'])) {
                $alarm->where(function($q) use($validated) {

                    $q->whereHas('warehousing', function($q) use($validated) {
                        return $q->where(DB::raw('lower(w_schedule_number)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%')->orWhere(DB::raw('lower(w_schedule_number2)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%');
                    })->orWhereHas('import_expect', function ($q2) use ($validated){
                        $q2->where('tie_h_bl', 'like', '%' . $validated['w_schedule_number'] . '%');
                    });

                });
            }
            if (isset($validated['service_name'])) {
                if($validated['service_name'] == "입고예정번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','IW')->whereNull('w_schedule_number2');
                    });
                }elseif($validated['service_name'] == "입고화물번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','IW')->whereNotNull('w_schedule_number2');
                    });
                }elseif($validated['service_name'] == "출고예정번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','EW')->whereNull('w_schedule_number2');
                    });
                }elseif($validated['service_name'] == "출고화물번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','EW')->whereNotNull('w_schedule_number2');
                    });
                }elseif($validated['service_name'] == "BL번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','IW')->whereNull('w_schedule_number2');
                    });
                }

            }
            if(isset($validated['mb_push_yn'])){
                if($validated['mb_push_yn'] == 'y'){
                    $alarm->where(function($q) use($validated,$user) {
                        $q->whereNull('ad_no')->orWhereHas('alarm_data',function ($query) use ($validated){
                        $query->where('ad_must_yn','=','y');
                    }); });
                } else {
                    $alarm->where(function($q) use($validated,$user) {
                        $q->whereNull('ad_no')->orWhereHas('alarm_data',function ($query) use ($validated){
                        $query->where('ad_must_yn','=','y')->orwhere('ad_must_yn','=','n')->orWhereNull('ad_must_yn');
                    }); });
                }
           }
            $alarm = $alarm->groupBy('alarm_no')->paginate($per_page, ['*'], 'page', $page);
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            return response()->json($alarm);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function searchAlarmsRequest(AlarmSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            $user = Auth::user();
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            if($user->mb_type == 'shop'){
                $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('schedule_shipment','warehousing','member','alarm_data')->leftjoin('t_import_expected', function ($join) {
                    $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
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
                    $q->where(function($q) use ($validated,$user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_request%')->where('w_no',$validated['w_no'])
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($validated,$user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_delivery%')->where('w_no',$validated['w_no'])
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($validated,$user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_IW')->where('w_no',$validated['w_no'])
                        ->where('receiver_no', $user->mb_no)->whereHas('alarm_data', function ($query) {
                            $query->where('ad_no','!=',31);
                        });
                    })->orwhere(function($q) use ($validated,$user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_EW')->where('w_no',$validated['w_no'])
                        ->where('receiver_no', $user->mb_no);
                    });
                })->orderBy('alarm_no', 'DESC');
            }
            else if($user->mb_type == 'shipper'){
                $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('schedule_shipment','warehousing','member','alarm_data')->leftjoin('t_import_expected', function ($join) {
                    $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
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
                    $q->where(function($q) use ($validated,$user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_request%')->where('w_no',$validated['w_no'])
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($validated,$user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_delivery%')->where('w_no',$validated['w_no'])
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($validated,$user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_IW')->where('w_no',$validated['w_no'])
                        ->where('receiver_no', $user->mb_no)->whereHas('alarm_data', function ($query) {
                            $query->where('ad_no','!=',31);
                        });
                    })->orwhere(function($q) use ($validated,$user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_EW')->where('w_no',$validated['w_no'])
                        ->where('receiver_no', $user->mb_no);
                    });
                })->orderBy('alarm_no', 'DESC');

            } else if ($user->mb_type == 'spasys'){
                $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('schedule_shipment','warehousing','member','alarm_data')->leftjoin('t_import_expected', function ($join) {
                    $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
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
                    $q->where(function($q) use ($validated,$user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_request%')->where('w_no',$validated['w_no'])
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($validated,$user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','%cargo_delivery%')->where('w_no',$validated['w_no'])
                        ->where('receiver_no', $user->mb_no);
                    })->orwhere(function($q) use ($validated,$user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_IW')->where('w_no',$validated['w_no'])
                        ->where('receiver_no', $user->mb_no)->whereHas('alarm_data', function ($query) {
                            $query->where('ad_no','!=',31);
                        });
                    })->orwhere(function($q) use ($validated,$user) {
                        $q->whereNotNull('receiver_no')->where('alarm_type','like','cargo_EW')->where('w_no',$validated['w_no'])
                        ->where('receiver_no', $user->mb_no);
                    });
                })
                ->orderBy('alarm_no', 'DESC');
            }



            if (isset($validated['w_no'])) {
                $alarm->where('w_no', $validated['w_no']);
            }

            if (isset($validated['from_date'])) {
                $alarm->where('alarm.created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $alarm->where('alarm.created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }
            
            if (isset($validated['co_parent_name'])) {
                $alarm->where(function($q) use($validated,$user) {
                    $q->whereHas('export.import_expected.company.co_parent',function ($query) use ($validated){
                        $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                    })->orwhereHas('import_expect.company.co_parent', function($q3) use($validated) {
                        return $q3->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    })->orwhereHas('import_expect.company_spasys', function($q4) use($validated) {
                        return $q4->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    })->orwhereHas('warehousing.company.co_parent',function($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                })->orwhereHas('schedule_shipment.ContractWms.company.co_parent',function($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                });});
            }

            if (isset($validated['co_name'])) {
                $alarm->where(function($q) use($validated,$user) {
                    $q->whereHas('warehousing.company', function($q) use($validated) {
                        return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('schedule_shipment.ContractWms.company', function($q) use($validated) {
                        return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('import_expect.company', function($q3) use($validated) {
                        return $q3->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('import_expect.company_spasys', function($q4) use($validated) {
                        return $q4->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('export.import_expected.company',function ($q) use ($validated,$user){
                        return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    }); });
            }

            if (isset($validated['service'])) {
                if($validated['service'] == '보세화물'){
                    $alarm->where(function($q) use($validated) {
                        $q->whereHas('warehousing', function($q2) use($validated) {
                            return $q2->where('w_category_name', '=', $validated['service']);
                        })->orwhereHas('import_expect', function($q3) use($validated) {
                            return $q3->where('tie_h_bl', '!=', '')->orWhereNotNull('tie_h_bl');
                        });
                    });
                } if($validated['service'] == '수입풀필먼트'){
                    $alarm->where(function($q) use($validated) {
                        $q->whereHas('warehousing', function($q2) use($validated) {
                            return $q2->where('w_category_name', '=', $validated['service']);
                        })->orwhere('ss_no', '!=','')->orWhereNotNull('ss_no') ;
                    });
                } else {
                    $alarm->where(function($q) use($validated) {
                        $q->whereHas('warehousing', function($q2) use($validated) {
                            return $q2->where('w_category_name', '=', $validated['service']);
                        });
                    });
                }
            }
            if (isset($validated['w_schedule_number'])) {
                $alarm->where(function($q) use($validated) {
                    $q->whereHas('warehousing', function($q) use($validated) {
                        return $q->where(DB::raw('lower(w_schedule_number)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%')->orWhere(DB::raw('lower(w_schedule_number2)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%');
                    })->orWhereHas('import_expect', function ($q2) use ($validated){
                        $q2->where('tie_h_bl', 'like', '%' . $validated['w_schedule_number'] . '%');
                    });

                });
            }
            if (isset($validated['service_name'])) {
                if($validated['service_name'] == "입고예정번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','IW')->whereNull('w_schedule_number2');
                    });
                }elseif($validated['service_name'] == "입고화물번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','IW')->whereNotNull('w_schedule_number2');
                    });
                }elseif($validated['service_name'] == "출고예정번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','EW')->whereNull('w_schedule_number2');
                    });
                }elseif($validated['service_name'] == "출고화물번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','EW')->whereNotNull('w_schedule_number2');
                    });
                }elseif($validated['service_name'] == "BL번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','IW')->whereNull('w_schedule_number2');
                    });
                }

            }
            $alarm = $alarm->groupBy('alarm_no')->paginate($per_page, ['*'], 'page', $page);
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            return response()->json($alarm);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function searchAlarms_send(AlarmSearchRequest $request)
    {
        $validated = $request->validated();
         DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        try {
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            $user = Auth::user();

            if($user->mb_type == 'shop'){
                $alarm = Alarm::with('warehousing','member','export')->where('alarm.mb_no','=',$user->mb_no)->orderBy('alarm_no', 'DESC');
            }
            else if($user->mb_type == 'shipper'){
                $alarm = Alarm::with('warehousing','member','export')->where('alarm.mb_no','=',$user->mb_no)->orderBy('alarm_no', 'DESC');

            } else if ($user->mb_type == 'spasys'){
                $alarm = Alarm::select('t_import.ti_no','t_export.te_no','alarm.*','t_import_expected.tie_h_bl','company_spasys.co_name as company_spasys_coname','company_shop.co_name as company_shop_coname','company_shop_parent.co_name as shop_parent_name','company_spasys_parent.co_name as spasys_parent_name')->with('warehousing','member','schedule_shipment')->leftjoin('t_import_expected', function ($join) {
                    $join->on('alarm.alarm_h_bl', '=', 't_import_expected.tie_h_bl');
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
                })->where('alarm.receiver_no','=',$user->mb_no)->whereNull('alarm.alarm_type')->orderBy('alarm_no', 'DESC');
            }



            if (isset($validated['w_no'])) {
                $alarm->where('w_no', $validated['w_no']);
            }

            if (isset($validated['from_date'])) {
                $alarm->where('alarm.created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $alarm->where('alarm.created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }
            // if (isset($validated['co_parent_name'])) {
            //     $alarm->whereHas('member.company.co_parent', function ($query) use ($validated) {
            //         $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
            //     });
            // }
            if (isset($validated['co_parent_name'])) {
                $alarm->where(function($q) use($validated,$user) {
                    $q->whereHas('export.import_expected.company.co_parent',function ($query) use ($validated){
                        $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                    })->orwhereHas('import_expect.company.co_parent', function($q3) use($validated) {
                        return $q3->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    })->orwhereHas('import_expect.company_spasys', function($q4) use($validated) {
                        return $q4->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    })->orwhereHas('warehousing.company.co_parent',function($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                })->orwhereHas('schedule_shipment.ContractWms.company.co_parent',function($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                });});
            }
            if (isset($validated['co_name'])) {
                $alarm->where(function($q) use($validated,$user) {
                    $q->whereHas('warehousing.company', function($q) use($validated) {
                        return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('schedule_shipment.ContractWms.company',function($query) use ($validated) {
                        $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_name']) .'%');
                    })->orwhereHas('import_expect.company', function($q3) use($validated) {
                        return $q3->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('import_expect.company_spasys', function($q4) use($validated) {
                        return $q4->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    })->orwhereHas('export.import_expected.company',function ($q) use ($validated,$user){
                        return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    }); });
            }
            if (isset($validated['service'])) {
                if($validated['service'] == '보세화물'){
                    $alarm->where(function($q) use($validated) {
                        $q->whereHas('warehousing', function($q2) use($validated) {
                            return $q2->where('w_category_name', '=', $validated['service']);
                        })->orwhereHas('import_expect', function($q3) use($validated) {
                            return $q3->where('tie_h_bl', '!=', '')->orWhereNotNull('tie_h_bl');
                        });
                    });
                } if($validated['service'] == '수입풀필먼트'){
                    $alarm->where(function($q) use($validated) {
                        $q->whereHas('warehousing', function($q2) use($validated) {
                            return $q2->where('w_category_name', '=', $validated['service']);
                        })->orwhere('ss_no', '!=','')->orWhereNotNull('ss_no') ;
                    });
                } else {
                    $alarm->where(function($q) use($validated) {
                        $q->whereHas('warehousing', function($q2) use($validated) {
                            return $q2->where('w_category_name', '=', $validated['service']);
                        });
                    });
                }
                // if($validated['service'] == '보세화물'){
                //     $alarm->where(function($q) use($validated) {
                //         $q->whereHas('warehousing', function ($q) use ($validated) {
                //             return $q->where(DB::raw('lower(w_category_name)'), 'like', '%' . strtolower($validated['service']) . '%');
                //         })->orwhereHas('export.import_expected', function($q3) use($validated) {
                //             return $q3->where('tie_h_bl', '!=', '')->orWhereNotNull('tie_h_bl');
                //         });
                //     });
                // } else {
                //     $alarm->where(function($q) use($validated) {
                //         $q->whereHas('warehousing', function ($q) use ($validated) {
                //             return $q->where(DB::raw('lower(w_category_name)'), 'like', '%' . strtolower($validated['service']) . '%');
                //         });
                //     });
                // }
            }
            if (isset($validated['w_schedule_number'])) {
                $alarm->where(function($q) use($validated) {

                    $q->where(DB::raw('lower(alarm_h_bl)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%');

                });
            }
            if (isset($validated['service_name'])) {
                if($validated['service_name'] == "입고예정번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','IW')->whereNull('w_schedule_number2');
                    });
                }elseif($validated['service_name'] == "입고화물번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','IW')->whereNotNull('w_schedule_number2');
                    });
                }elseif($validated['service_name'] == "출고예정번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','EW')->whereNull('w_schedule_number2');
                    });
                }elseif($validated['service_name'] == "출고화물번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','EW')->whereNotNull('w_schedule_number2');
                    });
                }elseif($validated['service_name'] == "BL번호"){
                    $alarm->whereHas('warehousing', function ($q) use ($validated) {
                        return $q->where('w_type','IW')->whereNull('w_schedule_number2');
                    });
                }

            }
            $alarm = $alarm->groupBy('alarm_no')->paginate($per_page, ['*'], 'page', $page);
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

            return response()->json($alarm);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function searchAlarmsMobile(AlarmSearchRequest $request)
    {
        $validated = $request->validated();
        try {

            $alarm = Alarm::with('warehousing','member')->orderBy('alarm_no', 'DESC');

            if (isset($validated['w_no'])) {
                $alarm->where('w_no', $validated['w_no']);
            }

            if (isset($validated['from_date'])) {
                $alarm->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $alarm->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }
            if (isset($validated['co_parent_name'])) {
                $alarm->whereHas('member.company.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $alarm->whereHas('member.company', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            $alarm = $alarm->get();

            return response()->json($alarm);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getAlarmById($alarm_no)
    {
        $alarm = Alarm::with('warehousing','export')->where('alarm_no', $alarm_no )->first();
        return response()->json(['alarm' => $alarm]);
    }
}

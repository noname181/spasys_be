<?php

namespace App\Http\Controllers\Alarm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alarm\AlarmSearchRequest;
use App\Http\Requests\Alarm\AlarmRequest;
use App\Models\Alarm;
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
            if (!isset($alarm_no)) {
                $alarm_no = Alarm::insertGetId([
                    'mb_no' => Auth::user()->mb_no,
                    'w_no' => $validated['w_no'], // FIXME hard set
                    'alarm_content' => $validated['alarm_content']
                ]);
            } else {
                // Update data
                $alarm = Alarm::where('alarm_no', $alarm_no)->first();
                if (is_null($alarm)) {
                    return response()->json(['message' => Messages::MSG_0020], 404);
                }

                $update = [
                    'mb_no' => Auth::user()->mb_no,
                    'w_no' => $validated['w_no'],
                    'alarm_content' => $validated['alarm_content']
                ];
                $alarm->update($update);
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0019], 500);
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

            if($user->mb_type == 'shop'){
                $alarm = Alarm::with('warehousing','member','export')->whereHas('member.company',function($q) use ($user){
                    $q->where('co_no', $user->company->co_parent->co_no);
                    //->orWhere('co_no', $user->company->co_parent->co_parent->co_no);
                })->orderBy('alarm_no', 'DESC');
            }
            else if($user->mb_type == 'shipper'){
                $alarm = Alarm::with('warehousing','member','export')->whereHas('member.company',function($q) use ($user){
                    $q->where('co_no', $user->company->co_parent->co_no)
                    ->orWhere('co_no', $user->company->co_parent->co_parent->co_no);
                })->orderBy('alarm_no', 'DESC');
               
            } else if ($user->mb_type == 'spasys'){
                $alarm = Alarm::with('warehousing','member','export')->whereHas('member',function ($q) use ($user){
                    $q->where('mb_no',$user->mb_no);
                })->orderBy('alarm_no', 'DESC');
            }

            

            if (isset($validated['w_no'])) {
                $alarm->where('w_no', $validated['w_no']);
            }

            if (isset($validated['from_date'])) {
                $alarm->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $alarm->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }
            // if (isset($validated['co_parent_name'])) {
            //     $alarm->whereHas('member.company.co_parent', function ($query) use ($validated) {
            //         $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
            //     });
            // }
            if (isset($validated['co_parent_name'])) {
                $alarm->whereHas('warehousing.co_no.co_parent',function($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                });
            }
            // if (isset($validated['co_name'])) {
            //     $alarm->whereHas('member.company', function ($q) use ($validated) {
            //         return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
            //     });
            // }
            if (isset($validated['co_name'])) {
                $alarm->whereHas('warehousing.co_no', function($q) use($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['service'])) {
                $alarm->whereHas('warehousing', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(w_category_name)'), 'like', '%' . strtolower($validated['service']) . '%');
                });
            }
            if (isset($validated['w_schedule_number'])) {
                $alarm->whereHas('warehousing', function($q) use($validated) {
                    return $q->where(DB::raw('lower(w_schedule_number)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%')->orWhere(DB::raw('lower(w_schedule_number2)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%');
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
            $alarm = $alarm->paginate($per_page, ['*'], 'page', $page);

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
                $alarm = Alarm::with('warehousing','member','export')->where('alarm.mb_no','=',$user->mb_no)->orderBy('alarm_no', 'DESC');
            }

            

            if (isset($validated['w_no'])) {
                $alarm->where('w_no', $validated['w_no']);
            }

            if (isset($validated['from_date'])) {
                $alarm->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $alarm->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }
            // if (isset($validated['co_parent_name'])) {
            //     $alarm->whereHas('member.company.co_parent', function ($query) use ($validated) {
            //         $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
            //     });
            // }
            if (isset($validated['co_parent_name'])) {
                $alarm->whereHas('warehousing.co_no.co_parent',function($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_parent_name']) .'%');
                });
            }
            if (isset($validated['co_name'])) {
                $alarm->whereHas('warehousing.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['service'])) {
                $alarm->whereHas('warehousing', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(w_category_name)'), 'like', '%' . strtolower($validated['service']) . '%');
                });
            }
            if (isset($validated['w_schedule_number'])) {
                $alarm->whereHas('warehousing', function($q) use($validated) {
                    return $q->where(DB::raw('lower(w_schedule_number)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%')->orWhere(DB::raw('lower(w_schedule_number2)'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%');
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
            $alarm = $alarm->paginate($per_page, ['*'], 'page', $page);

            return response()->json($alarm);
        } catch (\Exception $e) {
            Log::error($e);
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

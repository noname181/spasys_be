<?php

namespace App\Http\Controllers\Warehousing;

use DateTime;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehousing\WarehousingRequest;
use App\Http\Requests\Warehousing\WarehousingSearchRequest;
use App\Http\Requests\Warehousing\WarehousingDataValidate;
use App\Http\Requests\Warehousing\WarehousingItemValidate;
use App\Models\Member;
use App\Models\ReceivingGoodsDelivery;
use App\Models\Warehousing;
use App\Models\WarehousingItem;
use App\Models\CompanySettlement;
use App\Models\Service;
use App\Models\RateData;
use App\Models\RateDataGeneral;
use App\Utils\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;

class WarehousingController extends Controller
{
    /**
     * Fetch data
     * @param  \App\Http\Requests\Warehousing\WarehousingRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(WarehousingRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $warehousing = Warehousing::with('mb_no')->with('co_no')->paginate($per_page, ['*'], 'page', $page);

            return response()->json($warehousing);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getWarehousingById($w_no)
    {
        try {
            $warehousing = Warehousing::with(['co_no', 'warehousing_request'])->find($w_no);
            //$warehousings = Warehousing::where('w_import_no', $w_no)->get();
            if (isset($warehousing->w_import_no) && $warehousing->w_import_no) {
                $warehousing_import = Warehousing::where('w_no', $warehousing->w_import_no)->first();
            } else {
                $warehousing_import = '';
            }
            if($warehousing->w_import_no){
                $warehousings = Warehousing::where('w_import_no', $warehousing->w_import_no)->get();
            }else{
                $warehousings = [];
            }
            if (!empty($warehousing)) {
                return response()->json(
                    ['message' => Messages::MSG_0007,
                        'data' => $warehousing,
                        'datas' => $warehousings,
                        'warehousing_import' => $warehousing_import,
                    ], 200);
            } else {
                return response()->json(['message' => Messages::MSG_0018], 400);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Get Warehousing
     * @param  WarehousingSearchRequest $request
     */
    public function getWarehousing(WarehousingSearchRequest $request) // page 710

    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if($user->mb_type == 'shop'){
                $warehousing2 = Warehousing::join(DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                'm.w_no', '=', 'warehousing.w_no')->where('warehousing.w_type','=','EW')->whereHas('co_no.co_parent',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q){

                    return $q -> w_import_no;

                });
                $w_no_in = collect($warehousing2)->map(function ($q){

                    return $q -> w_no;

                });
                $warehousing = Warehousing::with('mb_no')
                ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no',$w_import_no)->where('w_type','IW')
                ->whereHas('co_no.co_parent',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->orWhereIn('w_no',$w_no_in)->orderBy('w_no', 'DESC');
            }else if($user->mb_type == 'shipper'){
                $warehousing2 = Warehousing::join(DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                'm.w_no', '=', 'warehousing.w_no')->where('warehousing.w_type','=','EW')->whereHas('co_no',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q){

                    return $q -> w_import_no;

                });
                $w_no_in = collect($warehousing2)->map(function ($q){

                    return $q -> w_no;

                });
                $warehousing = Warehousing::with('mb_no')
                ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no',$w_import_no)->where('w_type','IW')
                ->whereHas('co_no',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->orWhereIn('w_no',$w_no_in)->orderBy('w_no', 'DESC');
            }else if($user->mb_type == 'spasys'){

                $warehousing2 = Warehousing::join(DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                'm.w_no', '=', 'warehousing.w_no')->where('warehousing.w_type','=','EW')->whereHas('co_no.co_parent.co_parent',function($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q){

                    return $q -> w_import_no;

                });
                $w_no_in = collect($warehousing2)->map(function ($q){

                    return $q -> w_no;

                });


                $warehousing = Warehousing::with('mb_no')
                ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent','warehousing_child'])->whereNotIn('w_no',$w_import_no)->where('w_type','IW')
                ->whereHas('co_no.co_parent.co_parent',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->orWhereIn('w_no',$w_no_in)->orderBy('w_no', 'DESC');
            }

            if (isset($validated['page_type']) && $validated['page_type'] == "page130") {
                $warehousing->where('w_type', '=', 'IW');
            }

            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['mb_name'])) {
                $warehousing->whereHas('mb_no', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(mb_name)'), 'like', '%' . strtolower($validated['mb_name']) . '%');
                });
            }
            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['w_schedule_number'])) {
                $warehousing->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
            }
            if (isset($validated['w_schedule_number_iw'])) {
                $warehousing->whereHas('w_import_parent', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_iw'] . '%', 'and', 'w_type', '=', 'IW');});
            }
            if (isset($validated['w_schedule_number_ew'])) {
                $warehousing->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_ew'] . '%', 'and', 'w_type', '=', 'EW');
            }
            if (isset($validated['logistic_manage_number'])) {
                $warehousing->where('logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
            }
            if (isset($validated['m_bl'])) {
                $warehousing->where('m_bl', 'like', '%' . $validated['m_bl'] . '%');
            }
            if (isset($validated['h_bl'])) {
                $warehousing->where('h_bl', 'like', '%' . $validated['h_bl'] . '%');
            }
            if (isset($validated['rgd_status1'])) {
                $warehousing->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                    $query->where('rgd_status1', '=', $validated['rgd_status1']);
                });
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                    $query->where('rgd_status2', '=', $validated['rgd_status2']);
                });
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                    $query->where('rgd_status3', '=', $validated['rgd_status3']);
                });
            }

            // if (isset($validated['warehousing_status1']) || isset($validated['warehousing_status2'])) {
            //     $warehousing->where(function($query) use ($validated) {
            //         $query->orwhere('warehousing_status', '=', $validated['warehousing_status1']);
            //         $query->orWhere('warehousing_status', '=', $validated['warehousing_status2']);
            //     });
            // }

            $members = Member::where('mb_no', '!=', 0)->get();

            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);

            return response()->json($warehousing);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function getWarehousingExport(WarehousingSearchRequest $request)
    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no']);
            // $warehousing = Warehousing::with('mb_no')->with(['co_no','warehousing_item','receving_goods_delivery'])->orderBy('w_no', 'DESC');

            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['mb_name'])) {
                $warehousing->whereHas('mb_no', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(mb_name)'), 'like', '%' . strtolower($validated['mb_name']) . '%');
                });
            }
            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('w_no.co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('w_no.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }

            if (isset($validated['w_type'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_type', 'like', '%' . $validated['w_type'] . '%');
                });
            }
            if (isset($validated['rgd_status1'])) {
                $warehousing->where('rgd_status1', '=', $validated['rgd_status1']);
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing->where('rgd_status2', '=', $validated['rgd_status2']);
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing->where('rgd_status3', '=', $validated['rgd_status3']);
            }
            if (isset($validated['item_brand'])) {
                $warehousing->whereHas('w_no.warehousing_item', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }
            if (isset($validated['rgd_status1_1']) || isset($validated['rgd_status1_2']) || isset($validated['rgd_status1_3'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->Where('rgd_status1', '=', $validated['rgd_status1_1'] ? $validated['rgd_status1_1'] : "")
                        ->orWhere('rgd_status1', '=', $validated['rgd_status1_2'] ? $validated['rgd_status1_2'] : "")
                        ->orWhere('rgd_status1', '=', $validated['rgd_status1_3'] ? $validated['rgd_status1_3'] : "");
                });
            }
            if (isset($validated['rgd_status2_1']) || isset($validated['rgd_status2_2']) || isset($validated['rgd_status2_3'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->Where('rgd_status2', '=', $validated['rgd_status2_1'] ? $validated['rgd_status2_1'] : "")
                        ->orWhere('rgd_status2', '=', $validated['rgd_status2_2'] ? $validated['rgd_status2_2'] : "")
                        ->orWhere('rgd_status2', '=', $validated['rgd_status2_3'] ? $validated['rgd_status2_3'] : "");
                });

            }
            if (isset($validated['rgd_status3_1']) || isset($validated['rgd_status3_2']) || isset($validated['rgd_status3_3'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->Where('rgd_status3', '=', $validated['rgd_status3_1'] ? $validated['rgd_status3_1'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_2'] ? $validated['rgd_status3_2'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_3'] ? $validated['rgd_status3_3'] : "");
                });
            }
            // if (isset($validated['logistic_manage_number'])) {
            //     $warehousing->where('logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
            // }
            // if (isset($validated['m_bl'])) {
            //     $warehousing->where('m_bl', 'like', '%' . $validated['m_bl'] . '%');
            // }
            // if (isset($validated['h_bl'])) {
            //     $warehousing->where('h_bl', 'like', '%' . $validated['h_bl'] . '%');
            // }

            // $members = Member::where('mb_no', '!=', 0)->get();

            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();

            return response()->json($warehousing);

        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function warehousingImport(Request $request){
        try{
            DB::beginTransaction();
            $f = Storage::disk('public')->put('files/tmp', $request['file']);

            $path = storage_path('app/public') . '/' . $f;
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);

            $sheet = $spreadsheet->getSheet(0);
            $warehousing_data = $sheet->toArray(null, true, true, true);

            $sheet2 = $spreadsheet->getSheet(1);
            $warehousing_item_data = $sheet2->toArray(null, true, true, true);

            $amount_total = array_sum(array_column($warehousing_item_data,'D'));

            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $results[$sheet->getTitle()] = [];
            $errors[$sheet->getTitle()] = [];
            $key_schedule = Warehousing::latest()->orderBy('w_no', 'DESC')->first();
            $check_key = 1;

            $rows_warehousing_add = 0;
            $rows_number_item_add = 0;
            $check_error = false;
            foreach($warehousing_data as $key=> $warehouse){
                if($key <= 2){
                    continue;
                }
                $validator = Validator::make($warehouse, WarehousingDataValidate::rules());
                if ($validator->fails()) {
                    $errors[$sheet->getTitle()][] = $validator->errors();
                    $check_error = true;
                } else {
                    $w_schedule_day = date('Y-m-d', strtotime($warehouse['B']));
                    $schedule_number = 'SPA_'.date('Ymd').((int)$key_schedule->w_no + $check_key).'_IW';
                    $rows_warehousing_add = $rows_warehousing_add + 1;
                    $warehousing_id = Warehousing::insertGetId([
                        'mb_no' => Auth::user()->mb_no,
                        'w_schedule_number' => $schedule_number,
                        'w_type' => 'IW',
                        'w_category_name' => '유통가공',
                        'mb_no' => $member->mb_no,
                        'co_no' => $warehouse['I'],
                        'w_schedule_day' => $w_schedule_day,
                        'w_schedule_amount' => $amount_total,
                        'w_cancel_yn' => 'n'
                    ]);
                    if($warehousing_id){
                        ReceivingGoodsDelivery::insert([
                            'mb_no' => $member->mb_no,
                            'w_no' => $warehousing_id,
                            'service_korean_name' => '유통가공',
                            'rgd_contents' => $warehouse['C'],
                            'rgd_address' => $warehouse['D'],
                            'rgd_address_detail' => $warehouse['E'],
                            'rgd_receiver' => $warehouse['F'],
                            'rgd_hp' => $warehouse['G'],
                            'rgd_memo' => $warehouse['H'],
                            'rgd_status1' => '입고예정',
                            'rgd_status2' => '작업대기',
                            'rgd_status3' => '배송준비',
                            'rgd_delivery_company' => '택배',
                            'rgd_delivery_schedule_day' => date('Y-m-d'),
                            'rgd_arrive_day' => date('Y-m-d'),
                        ]);
                        foreach($warehousing_item_data as $key => $warehouse_item){
                            if($key <= 2){
                                continue;
                            }

                            $validator_item = Validator::make($warehouse_item, WarehousingItemValidate::rules());
                            if ($validator_item->fails()) {
                                $errors[$sheet->getTitle()][] = $validator_item->errors();
                                $check_error = true;
                            } else {
                                if($warehouse['A'] === $warehouse_item['A']){
                                    $rows_number_item_add = $rows_number_item_add + 1;
                                    $item_no = WarehousingItem::insert([
                                        'item_no' => $warehouse_item['B'],
                                        'w_no' => $warehousing_id,
                                        'wi_number' => $warehouse_item['C'],
                                        'wi_type' => '입고_shipper'
                                    ]);
                                }
                            }
                        }
                    }
                }
                $check_key++;
            }
            if($check_error == true){
                DB::rollback();
                return response()->json([
                    'message' => Messages::MSG_0007,
                    'status' => 2,
                    'errors' => $errors,
                    'rows_warehousing_add' => $rows_warehousing_add,
                    'rows_number_item_add' => $rows_number_item_add
                ], 201);
            }else{
                DB::commit();
                return response()->json([
                    'message' => Messages::MSG_0007,
                    'errors' => $errors,
                    'status' => 1,
                    'rows_warehousing_add' => $rows_warehousing_add,
                    'rows_number_item_add' => $rows_number_item_add
                ], 201);
            }

        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }


    public function getWarehousingImport(WarehousingSearchRequest $request) //page 129 show IW

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if($user->mb_type == 'shop'){
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1','!=','입고')->whereHas('co_no.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            }else if ($user->mb_type == 'shipper'){
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1','!=','입고')->whereHas('co_no',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            }else if ($user->mb_type == 'spasys'){
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1','!=','입고')->whereHas('co_no.co_parent.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            }


            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('w_no.co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('w_no.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }
            if (isset($validated['rgd_status1'])) {
                $warehousing->where('rgd_status1', '=', $validated['rgd_status1']);
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing->where('rgd_status2', '=', $validated['rgd_status2']);
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing->where('rgd_status3', '=', $validated['rgd_status3']);
            }
            if (isset($validated['m_bl'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('m_bl', 'like', '%' . $validated['m_bl'] . '%');
                });
            }
            if (isset($validated['h_bl'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('h_bl', 'like', '%' . $validated['h_bl'] . '%');
                });
            }
            if (isset($validated['rgd_status1_1']) || isset($validated['rgd_status1_2']) || isset($validated['rgd_status1_3'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->Where('rgd_status1', '=', $validated['rgd_status1_1'] ? $validated['rgd_status1_1'] : "")
                        ->orWhere('rgd_status1', '=', $validated['rgd_status1_2'] ? $validated['rgd_status1_2'] : "")
                        ->orWhere('rgd_status1', '=', $validated['rgd_status1_3'] ? $validated['rgd_status1_3'] : "");
                });
            }

            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();

            return response()->json($warehousing);

        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function getWarehousingImportStatus1(WarehousingSearchRequest $request) //page 134 show IW,rgd_status1 = complete

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if($user->mb_type =='shop'){
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->whereHas('co_no.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            }else if($user->mb_type == 'shipper' ){
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->whereHas('co_no',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            }else if($user->mb_type == 'spasys'){
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->whereHas('co_no.co_parent.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            }


            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('w_no.co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('w_no.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['w_schedule_number_iw'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_iw'] . '%');
                });
            }
            if (isset($validated['w_schedule_number_ew'])) {
                $warehousing->whereHas('w_no.w_import_parent', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_ew'] . '%');
                });
            }

            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) {
                    // if(!empty($item->w_no)){
                    //     $item->w_amount_left = $item->w_no->w_amount - $item->w_no->w_schedule_amount;
                    // }
                    $warehousing = Warehousing::where('w_no', $item->w_no)->first();
                    $item->w_amount_left = $warehousing->w_amount - $warehousing->w_schedule_amount;
                    return $item;
                })
            );
            return response()->json($warehousing);

        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function getWarehousingStatus1(WarehousingSearchRequest $request) //page 140 show IW and EW,rgd_status1 = complete

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if($user->mb_type == 'shop'){
                $warehousing2 = Warehousing::where('w_type','=','EW')->whereNull('w_children_yn')->whereHas('co_no.co_parent',function($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q){

                    return $q -> w_import_no;

                });
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereNotIn('w_no', $w_import_no)->whereHas('w_no', function ($query) use ($user) {
                    $query->where('rgd_status1', '=', '입고')->whereNull('w_children_yn')->whereHas('co_no.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    })->orwhere('rgd_status1', '=', '출고예정')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            }else if($user->mb_type == 'shipper'){
                $warehousing2 = Warehousing::where('w_type','=','EW')->whereNull('w_children_yn')->whereHas('co_no',function($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q){

                    return $q -> w_import_no;

                });
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereNotIn('w_no', $w_import_no)->whereHas('w_no', function ($query) use ($user) {
                    $query->where('rgd_status1', '=', '입고')->whereNull('w_children_yn')->whereHas('co_no',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    })->orwhere('rgd_status1', '=', '출고예정')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            }else if($user->mb_type == 'spasys'){
                $warehousing2 = Warehousing::where('w_type','=','EW')->whereNull('w_children_yn')->whereHas('co_no.co_parent.co_parent',function($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q){

                    return $q -> w_import_no;

                });

                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereNotIn('w_no', $w_import_no)->whereHas('w_no', function ($query) use ($user) {
                    $query->where('rgd_status1', '=', '입고')->whereNull('w_children_yn')->whereHas('co_no.co_parent.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    })->orwhere('rgd_status1', '=', '출고예정')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no.co_parent.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            }


            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('w_no.co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('w_no.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['w_type'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_type', 'like', '%' . $validated['w_type'] . '%');
                });
            }
            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }
            if (isset($validated['rgd_status1'])) {
                $warehousing->where('rgd_status1', '=', $validated['rgd_status1']);
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing->where('rgd_status2', '=', $validated['rgd_status2']);
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing->where('rgd_status3', '=', $validated['rgd_status3']);
            }

            if (isset($validated['rgd_status1_1']) || isset($validated['rgd_status1_2']) || isset($validated['rgd_status1_3'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->Where('rgd_status1', '=', $validated['rgd_status1_1'] ? $validated['rgd_status1_1'] : "")
                        ->orWhere('rgd_status1', '=', $validated['rgd_status1_2'] ? $validated['rgd_status1_2'] : "")
                        ->orWhere('rgd_status1', '=', $validated['rgd_status1_3'] ? $validated['rgd_status1_3'] : "");
                });
            }
            if (isset($validated['rgd_status2_1']) || isset($validated['rgd_status2_2']) || isset($validated['rgd_status2_3'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->Where('rgd_status2', '=', $validated['rgd_status2_1'] ? $validated['rgd_status2_1'] : "")
                        ->orWhere('rgd_status2', '=', $validated['rgd_status2_2'] ? $validated['rgd_status2_2'] : "")
                        ->orWhere('rgd_status2', '=', $validated['rgd_status2_3'] ? $validated['rgd_status2_3'] : "");
                });

            }

            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();

            return response()->json($warehousing);

        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function getWarehousingExportStatus12(WarehousingSearchRequest $request) //page 144 show EW,rgd_status1 and rgd_status2 = complete

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if($user->mb_type == 'shop'){
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')
                    ->where('rgd_status1', '=', '출고')
                    ->where('rgd_status2', '=', '작업완료')
                    ->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })->whereHas('co_no.co_parent', function ($q2) use ($user){
                        $q2->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            }else if ($user->mb_type == 'shipper'){
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')
                    ->where('rgd_status1', '=', '출고')
                    ->where('rgd_status2', '=', '작업완료')
                    ->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })->whereHas('co_no', function ($q2) use ($user){
                        $q2->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            }else if($user->mb_type == 'spasys'){
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')
                    ->where('rgd_status1', '=', '출고')
                    ->where('rgd_status2', '=', '작업완료')
                    ->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })->whereHas('co_no.co_parent.co_parent', function ($q2) use ($user){
                        $q2->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            }


            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('w_no.co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('w_no.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['w_type'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_type', 'like', '%' . $validated['w_type'] . '%');
                });
            }
            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }
            if (isset($validated['rgd_status1'])) {
                $warehousing->where('rgd_status1', '=', $validated['rgd_status1']);
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing->where('rgd_status2', '=', $validated['rgd_status2']);
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing->where('rgd_status3', '=', $validated['rgd_status3']);
            }
            if (isset($validated['item_brand'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }
            if (isset($validated['item_bar_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_bar_code)'), 'like', '%' . strtolower($validated['item_bar_code']) . '%');
                });
            }
            if (isset($validated['item_cargo_bar_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_cargo_bar_code)'), 'like', '%' . strtolower($validated['item_cargo_bar_code']) . '%');
                });
            }
            if (isset($validated['item_upc_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_upc_code)'), 'like', '%' . strtolower($validated['item_upc_code']) . '%');
                });
            }
            if (isset($validated['rgd_status3_1']) || isset($validated['rgd_status3_2']) || isset($validated['rgd_status3_3'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->Where('rgd_status3', '=', $validated['rgd_status3_1'] ? $validated['rgd_status3_1'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_2'] ? $validated['rgd_status3_2'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_3'] ? $validated['rgd_status3_3'] : "");
                });
            }

            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();

            return response()->json($warehousing);

        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
   
    public function getWarehousingByRgd($rgd_no, $type)
    {
        try {
            $check_cofirm = 0;
            $check_paid = 0;
            if($type == 'monthly'){
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();
                $w_no = $rgd->w_no;
                $updated_at = Carbon::createFromFormat('Y.m.d H:i:s',  $rgd->updated_at->format('Y.m.d H:i:s'));

                $start_date = $updated_at->startOfMonth()->toDateString();
                $end_date = $updated_at->endOfMonth()->toDateString();

                $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general'])
                ->where('updated_at', '>=', date('Y-m-d 00:00:00', strtotime($start_date)))
                ->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($end_date)))
                ->where('rgd_status1', '=', '출고')
                ->where('rgd_status2', '=', '작업완료')
                ->where('rgd_bill_type', 'expectation_monthly')
                ->where(function($q){
                    $q->where('rgd_status4', '=', '예상경비청구서')->orWhereNull('rgd_status4');
                })
                ->get();
                $warehousing = Warehousing::with(['co_no', 'warehousing_request', 'w_import_parent'])->find($w_no);
                $time = str_replace('-', '.', $start_date) . ' ~ ' . str_replace('-', '.', $end_date);

            }else {
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();
                $w_no = $rgd->w_no;
                $check_cofirm = ReceivingGoodsDelivery::where('rgd_status5', 'confirmed')->where('rgd_bill_type','final')->where('w_no',$w_no)->get()->count();
                $check_paid = ReceivingGoodsDelivery::where('rgd_status5', 'paid')->where('rgd_bill_type','additional')->where('w_no',$w_no)->get()->count();
                $warehousing = Warehousing::with(['co_no', 'warehousing_request', 'w_import_parent'])->find($w_no);

            }

            $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();

            return response()->json(
                ['message' => Messages::MSG_0007,
                    'data' => isset($rgds) ? $rgds : $warehousing,
                    'warehousing' => isset($warehousing) ? $warehousing : null,
                    'rgd'  => $rgd,
                    'check_cofirm'=>$check_cofirm,
                    'rdg'  => $rdg,
                    'time' => isset($time) ? $time : '',
                    'check_paid'=>$check_paid
                ], 200);

        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }


    }


    public function getWarehousingExportStatusComplete(WarehousingSearchRequest $request) //page 263

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if($user->mb_type =='shop'){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }else if($user->mb_type == 'shipper' ){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }else if($user->mb_type == 'spasys'){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }
            $warehousing->where('rgd_status1', '=', '출고')
            ->where('rgd_status2', '=', '작업완료')
            ->whereHas('w_no', function ($query) {
                $query->where('w_type', '=', 'EW')->where(function ($q) {
                    $q->where(function ($query) {
                        $query->where('rgd_status4', '!=', '예상경비청구서')
                        ->where('rgd_status4', '!=', '확정청구서')->where('rgd_status4', '!=', '추가청구서');
                    })
                        ->orWhereNull('rgd_status4');
                })
                ->where('w_category_name', '=', '유통가공');
            });

            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('w_no.co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('w_no.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['w_type'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_type', 'like', '%' . $validated['w_type'] . '%');
                });
            }
            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }
            if (isset($validated['rgd_status1'])) {
                $warehousing->where('rgd_status1', '=', $validated['rgd_status1']);
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing->where('rgd_status2', '=', $validated['rgd_status2']);
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing->where('rgd_status3', '=', $validated['rgd_status3']);
            }
            if (isset($validated['item_brand'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }
            if (isset($validated['item_bar_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_bar_code)'), 'like', '%' . strtolower($validated['item_bar_code']) . '%');
                });
            }
            if (isset($validated['item_cargo_bar_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_cargo_bar_code)'), 'like', '%' . strtolower($validated['item_cargo_bar_code']) . '%');
                });
            }
            if (isset($validated['item_upc_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_upc_code)'), 'like', '%' . strtolower($validated['item_upc_code']) . '%');
                });
            }
            if (isset($validated['rgd_status3_1']) || isset($validated['rgd_status3_2']) || isset($validated['rgd_status3_3'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->Where('rgd_status3', '=', $validated['rgd_status3_1'] ? $validated['rgd_status3_1'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_2'] ? $validated['rgd_status3_2'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_3'] ? $validated['rgd_status3_3'] : "");
                });
            }
            $warehousing->orderBy('updated_at', 'DESC');
            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item){
                    $service_name = $item->service_korean_name;
                    $w_no = $item->w_no;
                    $co_no = Warehousing::where('w_no', $w_no)->first()->co_no;
                    $service_no = Service::where('service_name', $service_name)->first()->service_no;

                    $company_settlement = CompanySettlement::where([
                        'co_no' => $co_no,
                        'service_no' => $service_no
                    ])->first();
                    $item->settlement_cycle = $company_settlement ? $company_settlement->cs_payment_cycle : "";

                    return $item;
                })
            );

            return response()->json($warehousing);

        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function getWarehousingExportStatus4(WarehousingSearchRequest $request) //page 144 show EW,rgd_status1 and rgd_status2 = complete

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if($user->mb_type =='shop'){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }else if($user->mb_type == 'shipper' ){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }else if($user->mb_type == 'spasys'){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }
            $warehousing->whereHas('w_no', function ($query) {
                $query->where('w_type', '=', 'EW')
                ->where('rgd_status1', '=', '출고')
                ->where('rgd_status2', '=', '작업완료')
                ->where(function ($q) {
                    $q->where('rgd_status5','!=','cancel')
                    ->orWhereNull('rgd_status5');
                })
                ->where(function ($q) {
                    $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서')
                    ->orWhere('rgd_status4', '=', '추가청구서');
                });
            })->orderBy('updated_at', 'DESC');
            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('w_no.co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('w_no.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['w_type'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_type', 'like', '%' . $validated['w_type'] . '%');
                });
            }
            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }
            if (isset($validated['rgd_status1'])) {
                $warehousing->where('rgd_status1', '=', $validated['rgd_status1']);
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing->where('rgd_status2', '=', $validated['rgd_status2']);
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing->where('rgd_status3', '=', $validated['rgd_status3']);
            }
            if (isset($validated['item_brand'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }
            if (isset($validated['item_bar_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_bar_code)'), 'like', '%' . strtolower($validated['item_bar_code']) . '%');
                });
            }
            if (isset($validated['item_cargo_bar_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_cargo_bar_code)'), 'like', '%' . strtolower($validated['item_cargo_bar_code']) . '%');
                });
            }
            if (isset($validated['item_upc_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_upc_code)'), 'like', '%' . strtolower($validated['item_upc_code']) . '%');
                });
            }
            if (isset($validated['rgd_status3_1']) || isset($validated['rgd_status3_2']) || isset($validated['rgd_status3_3'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->Where('rgd_status3', '=', $validated['rgd_status3_1'] ? $validated['rgd_status3_1'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_2'] ? $validated['rgd_status3_2'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_3'] ? $validated['rgd_status3_3'] : "");
                });
            }
            if (isset($validated['service_korean_name'])) {
                $warehousing->where('service_korean_name', '=', $validated['service_korean_name']);

            }
            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item){
                    $service_name = $item->service_korean_name;
                    $w_no = $item->w_no;
                    $co_no = Warehousing::where('w_no', $w_no)->first()->co_no;
                    $service_no = Service::where('service_name', $service_name)->first()->service_no;

                    $company_settlement = CompanySettlement::where([
                        'co_no' => $co_no,
                        'service_no' => $service_no
                    ])->first();
                    $item->settlement_cycle = $company_settlement ? $company_settlement->cs_payment_cycle : "";

                    return $item;
                })
            );
            //return DB::getQueryLog();
           // return DB::getQueryLog();
            return response()->json($warehousing);

        } catch (\Exception $e) {
            Log::error($e);
            //return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getFulfillmentExportStatusComplete(WarehousingSearchRequest $request) //page 263

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if($user->mb_type =='shop'){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }else if($user->mb_type == 'shipper' ){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no' , 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }else if($user->mb_type == 'spasys'){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }
            $warehousing->where('rgd_status1', '=', '출고')
            ->where('rgd_status2', '=', '작업완료')
            ->where(function ($q) {
                $q->where(function ($query) {
                    $query->where('rgd_status4', '!=', '예상경비청구서')
                    ->where('rgd_status4', '!=', '확정청구서');
                })
                    ->orWhereNull('rgd_status4');
            })
            ->whereHas('w_no', function ($query) {
                $query->where('w_type', '=', 'EW')
                ->where('w_category_name', '=', '수입풀필먼트');
            });
            // ->whereHas('mb_no', function ($q) {
            //     $q->whereHas('company', function ($q) {
            //         $q->where('co_type', 'spasys');
            //     });
            // });

            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('w_no.co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('w_no.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['w_type'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_type', 'like', '%' . $validated['w_type'] . '%');
                });
            }
            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }
            if (isset($validated['rgd_status1'])) {
                $warehousing->where('rgd_status1', '=', $validated['rgd_status1']);
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing->where('rgd_status2', '=', $validated['rgd_status2']);
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing->where('rgd_status3', '=', $validated['rgd_status3']);
            }
            if (isset($validated['item_brand'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }
            if (isset($validated['item_bar_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_bar_code)'), 'like', '%' . strtolower($validated['item_bar_code']) . '%');
                });
            }
            if (isset($validated['item_cargo_bar_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_cargo_bar_code)'), 'like', '%' . strtolower($validated['item_cargo_bar_code']) . '%');
                });
            }
            if (isset($validated['item_upc_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_upc_code)'), 'like', '%' . strtolower($validated['item_upc_code']) . '%');
                });
            }
            if (isset($validated['rgd_status3_1']) || isset($validated['rgd_status3_2']) || isset($validated['rgd_status3_3'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->Where('rgd_status3', '=', $validated['rgd_status3_1'] ? $validated['rgd_status3_1'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_2'] ? $validated['rgd_status3_2'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_3'] ? $validated['rgd_status3_3'] : "");
                });
            }
            $warehousing->orderBy('updated_at', 'DESC');
            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();

            return response()->json($warehousing);

        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getFulfillmentExportStatus4(WarehousingSearchRequest $request) 

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if($user->mb_type =='shop'){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }else if($user->mb_type == 'shipper' ){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no' , 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }else if($user->mb_type == 'spasys'){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }
            $warehousing->where('rgd_status1', '=', '출고')
            ->where('rgd_status2', '=', '작업완료')
            ->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                ->orWhere('rgd_status4', '=', '확정청구서')
                ->orWhere('rgd_status4', '=', '추가청구서');
            })
            ->whereHas('w_no', function ($query) {
                $query->where('w_type', '=', 'EW')
                ->where('w_category_name', '=', '수입풀필먼트');
            })
            // ->whereHas('mb_no', function ($q) {
            //     $q->whereHas('company', function ($q) {
            //         $q->where('co_type', 'spasys');
            //     });
            // })
            ->orderBy('rgd_no', 'DESC');
            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('w_no.co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('w_no.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['w_type'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_type', 'like', '%' . $validated['w_type'] . '%');
                });
            }
            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }
            if (isset($validated['rgd_status1'])) {
                $warehousing->where('rgd_status1', '=', $validated['rgd_status1']);
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing->where('rgd_status2', '=', $validated['rgd_status2']);
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing->where('rgd_status3', '=', $validated['rgd_status3']);
            }
            if (isset($validated['item_brand'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }
            if (isset($validated['item_bar_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_bar_code)'), 'like', '%' . strtolower($validated['item_bar_code']) . '%');
                });
            }
            if (isset($validated['item_cargo_bar_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_cargo_bar_code)'), 'like', '%' . strtolower($validated['item_cargo_bar_code']) . '%');
                });
            }
            if (isset($validated['item_upc_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_upc_code)'), 'like', '%' . strtolower($validated['item_upc_code']) . '%');
                });
            }
            if (isset($validated['rgd_status3_1']) || isset($validated['rgd_status3_2']) || isset($validated['rgd_status3_3'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->Where('rgd_status3', '=', $validated['rgd_status3_1'] ? $validated['rgd_status3_1'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_2'] ? $validated['rgd_status3_2'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_3'] ? $validated['rgd_status3_3'] : "");
                });
            }
            if (isset($validated['service_korean_name'])) {
                $warehousing->where('service_korean_name', '=', $validated['service_korean_name']);

            }
            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();

            return response()->json($warehousing);

        } catch (\Exception $e) {
            Log::error($e);
            //return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }


    public function getTaxInvoiceList(WarehousingSearchRequest $request) //page277

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if($user->mb_type =='shop'){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }else if($user->mb_type == 'shipper' ){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no' , 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }else if($user->mb_type == 'spasys'){
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent.co_parent',function($q) use ($user){
                        $q->where('co_no', $user->co_no);
                    });
                });
            }
            $warehousing->where('rgd_status1', '=', '출고')
            ->where('rgd_status2', '=', '작업완료')
            ->where(function ($q) {
                $q->where('rgd_status4', '=', '확정청구서')
                ->orWhere('rgd_status4', '=', '추가청구서');
            })
            ->where('rgd_status5', '=', 'confirmed')
            ->whereHas('w_no', function ($query) {
                $query->where('w_type', '=', 'EW')
                ->where('w_category_name', '=', '유통가공');
            })
            ->orderBy('updated_at', 'DESC');
            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('w_no.co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('w_no.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['w_type'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_type', 'like', '%' . $validated['w_type'] . '%');
                });
            }
            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }
            if (isset($validated['rgd_status1'])) {
                $warehousing->where('rgd_status1', '=', $validated['rgd_status1']);
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing->where('rgd_status2', '=', $validated['rgd_status2']);
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing->where('rgd_status3', '=', $validated['rgd_status3']);
            }
            if (isset($validated['item_brand'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }
            if (isset($validated['item_bar_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_bar_code)'), 'like', '%' . strtolower($validated['item_bar_code']) . '%');
                });
            }
            if (isset($validated['item_cargo_bar_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_cargo_bar_code)'), 'like', '%' . strtolower($validated['item_cargo_bar_code']) . '%');
                });
            }
            if (isset($validated['item_upc_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_upc_code)'), 'like', '%' . strtolower($validated['item_upc_code']) . '%');
                });
            }
            if (isset($validated['rgd_status3_1']) || isset($validated['rgd_status3_2']) || isset($validated['rgd_status3_3'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->Where('rgd_status3', '=', $validated['rgd_status3_1'] ? $validated['rgd_status3_1'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_2'] ? $validated['rgd_status3_2'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_3'] ? $validated['rgd_status3_3'] : "");
                });
            }
            if (isset($validated['service_korean_name'])) {
                $warehousing->where('service_korean_name', '=', $validated['service_korean_name']);

            }
            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();

            return response()->json($warehousing);

        } catch (\Exception $e) {
            Log::error($e);
            //return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
}

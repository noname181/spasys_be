<?php

namespace App\Http\Controllers\Import;

use App\Http\Requests\Import\ImportRequest;
use App\Http\Controllers\Controller;

use App\Models\Member;
use App\Models\Warehousing;
use App\Models\WarehousingRequest;
use App\Models\Item;
use App\Models\File;
use App\Models\ItemChannel;
use App\Models\ReceivingGoodsDelivery;
use App\Models\WarehousingItem;
use App\Models\Company;
use App\Models\Import;
use App\Models\ImportExpected;
use App\Utils\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function get_import_data(ImportRequest $request)
    {
        //return $request;

        $validated = $request->validated();
        try {

            DB::enableQueryLog();
            //fetchWarehousing

            if (isset($validated['w_no'])) {
                $warehousing = Warehousing::find($validated['w_no']);
                $warehousings = Warehousing::where('w_import_no', $validated['w_no'])->get();

                if (isset($warehousing->w_import_no) && $warehousing->w_import_no) {
                    $warehousing_import = Warehousing::where('w_no', $warehousing->w_import_no)->first();
                } else {
                    $warehousing_import = '';
                }

                //fetchReceivingGoodsDeliveryRequests

                $rgd = ReceivingGoodsDelivery::with('mb_no')->with('w_no')->whereNull("rgd_parent_no")->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_no', $validated['w_no']);
                })->get();

                $rgds = ReceivingGoodsDelivery::with('mb_no')->with('w_no')->whereNull("rgd_parent_no")->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_import_no', $validated['w_no']);
                })->get();
            } else {
                $warehousing = [];
                $warehousings = [];
                $rgd = [];
                $rgds = [];
                $warehousing_import = [];
            }

            //fetchWarehousingRequests
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            if (isset($validated['w_no'])) {
                $warehousing = Warehousing::where('w_no', '=', $validated['w_no'])->first();

                $warehousing_request = WarehousingRequest::with(['mb_no', 'warehousing'])->orderBy('wr_no', 'DESC');

                $members = Member::where('mb_no', '!=', 0)->get();

                // if ($warehousing) {
                //     $warehousing_request = $warehousing_request->where('w_no', '=', $validated['w_no'])->orwhere('w_no', '=', $warehousing->w_import_no);
                // } else {
                //     $warehousing_request = $warehousing_request->where('w_no', '=', $validated['w_no']);
                // }
                if($warehousing){
                    if(isset($validated['w_category_name']) && $validated['w_category_name'] == '수입풀필먼트'){
                        $warehousing_request = $warehousing_request->where('ss_no', '=', $validated['w_no']);
                    }elseif(isset($validated['w_category_name']) && $validated['w_category_name'] == '유통가공'){
                        $warehousing_request = $warehousing_request->where('w_no', '=', $validated['w_no']);
                    }else{
                        $warehousing_request = $warehousing_request->where('is_no', '=', $validated['w_no']);
                    }
                    //->orwhere('w_no', '=', $warehousing->w_import_no);
                }else{
                    if(isset($validated['w_category_name']) && $validated['w_category_name'] == '수입풀필먼트'){
                        $warehousing_request = $warehousing_request->where('ss_no', '=', $validated['w_no']);
                    }elseif(isset($validated['w_category_name']) && $validated['w_category_name'] == '유통가공'){
                        $warehousing_request = $warehousing_request->where('w_no', '=', $validated['w_no']);
                    }else{
                        $warehousing_request = $warehousing_request->where('is_no', '=', $validated['w_no']);
                    }
                }
                $warehousing_request = $warehousing_request->paginate($per_page, ['*'], 'page', $page);
            } else {
                $warehousing_request = [];
            }

            //fetchItems
            if (isset($validated['items'])) {
                $item_no =  array_column($validated['items'], 'item_no');
            }

            $items = Item::with(['item_channels', 'file'])->orderBy('item_no', 'DESC');

            if (isset($validated['items'])) {
                $items->whereIn('item_no', $item_no);
            }

            if (isset($validated['w_no']) && !isset($validated['items'])) {
                $warehousing = Warehousing::find($validated['w_no']);

                $items->with(['warehousing_item' => function ($query) use ($validated) {
                    $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_shipper');
                }]);

                $items->whereHas('warehousing_item', function ($query) use ($validated) {
                    if ($validated['type'] == 'IW') {
                        $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_shipper');
                    } else {
                        $query->where('w_no', '=', $validated['w_no']);
                    }
                });

                $sql_count = WarehousingItem::where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys')->get();
                $count = $sql_count->count();

                if ($count != 0) {
                    $items->with(['warehousing_item2' => function ($query) use ($validated) {
                        if ($validated['type'] == 'IW') {
                            $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys');
                        } else {
                            $query->where('w_no', '=', $validated['w_no']);
                        }
                        //$query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys');
                    }]);

                    // $items->whereHas('warehousing_item2', function ($query) use ($validated) {
                    //     if ($validated['type'] == 'IW') {
                    //         $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys');
                    //     } else {
                    //         $query->where('w_no', '=', $validated['w_no']);
                    //     }
                    // });
                }
            }

            if (!isset($validated['w_no']) && !isset($validated['items'])) {
                $items->where(DB::raw('1'), '=', '2');
            }

            $items->where('item_service_name', '유통가공');

            $items = $items->get();


            return response()->json(
                [
                    'message' => Messages::MSG_0007,
                    'data' => $warehousing,
                    'datas' => $warehousings,
                    'warehousing_import' => $warehousing_import,
                    'rgd' => $rgd,
                    'rgds' => $rgds,
                    'warehousing_request' => $warehousing_request,
                    'items' => $items,
                    //'sql' => DB::getQueryLog()
                ],


                200
            );
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function get_import_data_api(ImportRequest $request)
    {
        //return $request;

        $validated = $request->validated();
        try {

            DB::enableQueryLog();
            //fetchWarehousing

            if (isset($validated['w_no'])) {
                $warehousing = Warehousing::find($validated['w_no']);
                $warehousings = Warehousing::where('w_import_no', $validated['w_no'])->get();

                if (isset($warehousing->w_import_no) && $warehousing->w_import_no) {
                    $warehousing_import = Warehousing::where('w_no', $warehousing->w_import_no)->first();
                } else {
                    $warehousing_import = '';
                }

                //fetchReceivingGoodsDeliveryRequests

                $rgd = ReceivingGoodsDelivery::with('mb_no')->with('w_no')->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_no', $validated['w_no']);
                })->get();
            } else {
                $warehousing = [];
                $warehousings = [];
                $rgd = [];
                $warehousing_import = [];
            }

            //fetchWarehousingRequests
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $warehousing_request = WarehousingRequest::with('mb_no')->orderBy('wr_no', 'DESC');

            $members = Member::where('mb_no', '!=', 0)->get();

            $warehousing_request = $warehousing_request->paginate($per_page, ['*'], 'page', $page);

            //fetchItems
            if (isset($validated['items'])) {
                $item_no =  array_column($validated['items'], 'item_no');
            }

            $items = Item::with(['item_channels', 'file']);

            if (isset($validated['items'])) {
                $items->whereIn('item_no', $item_no);
            }

            if (isset($validated['w_no']) && !isset($validated['items'])) {
                $warehousing = Warehousing::find($validated['w_no']);

                $items->with(['warehousing_item' => function ($query) use ($validated) {
                    $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_shipper');
                }]);

                $items->whereHas('warehousing_item', function ($query) use ($validated) {
                    if ($validated['type'] == 'IW') {
                        $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_shipper');
                    } else {
                        $query->where('w_no', '=', $validated['w_no']);
                    }
                });

                $sql_count = WarehousingItem::where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys')->get();
                $count = $sql_count->count();

                if ($count != 0) {
                    $items->with(['warehousing_item2' => function ($query) use ($validated) {
                        $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys');
                    }]);

                    $items->whereHas('warehousing_item2', function ($query) use ($validated) {
                        if ($validated['type'] == 'IW') {
                            $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys');
                        } else {
                            $query->where('w_no', '=', $validated['w_no']);
                        }
                    });
                }
            }

            if (!isset($validated['w_no']) && !isset($validated['items'])) {
                $items->where(DB::raw('1'), '=', '2');
            }

            $items->where('item_service_name', '수입풀필먼트');

            $items = $items->get();
            return response()->json(
                [
                    'message' => Messages::MSG_0007,
                    'data' => $warehousing,
                    'datas' => $warehousings,
                    'warehousing_import' => $warehousing_import,
                    'rgd' => $rgd,
                    'warehousing_request' => $warehousing_request,
                    'items' => $items,
                    'sql' => DB::getQueryLog()
                ],


                200
            );
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function save_import_storeday(Request $request)
    {
        try {

            DB::beginTransaction();
            $company = Company::where('co_no', $request->co_no)->first();
            Import::where('ti_logistic_manage_number', $request->ti_logistic_manage_number)->update([
                'ti_logistic_type' => $request->ti_logistic_type,
                'ti_i_storeday' => $request->storagedays,
            ]);

            ImportExpected::where('tie_logistic_manage_number', $request->tie_logistic_manage_number)->update([
                'tie_co_license' => isset($company->co_license) ? $company->co_license : null,
            ]);
            // Company::where('co_no', $request->co_no)->update([
            //     'co_name' => $request->company_name,
            // ]);

            //THUONG ADDED

            $rgd = ReceivingGoodsDelivery::whereHas('warehousing', function($q) use($request){
                $q->where('logistic_manage_number', $request->ti_logistic_manage_number);
            })->whereNull('rgd_status4')->whereNull('rgd_status5')->first();

            if(isset($rgd->rgd_no)){
                Warehousing::where('logistic_manage_number', $request->ti_logistic_manage_number)->update(
                    [
                        'co_no' => $company->co_no
                    ]
                    );
    
            }
            //END THUONG ADDED
            
            DB::commit();
            return response()->json(['message' => 'ok']);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
}

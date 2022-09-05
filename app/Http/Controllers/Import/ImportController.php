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
        try{

        DB::enableQueryLog();
        //fetchWarehousing

        if(isset($validated['w_no'])){
            $warehousing = Warehousing::find($validated['w_no']);
            $warehousings = Warehousing::where('w_import_no',$validated['w_no'])->get();

            if(isset($warehousing->w_import_no) && $warehousing->w_import_no){
                $warehousing_import = Warehousing::where('w_no',$warehousing->w_import_no)->first();
            }else{
                $warehousing_import = '';
            }

            //fetchReceivingGoodsDeliveryRequests
        
            $rgd = ReceivingGoodsDelivery::with('mb_no')->with('w_no')->whereHas('w_no', function($q) use ($validated) {
                return $q->where('w_no', $validated['w_no']);
            })->get();
        }else{
            $warehousing = [];
            $warehousings = [];
            $rgd= [];
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
        if(isset($validated['items'])){
            $item_no =  array_column($validated['items'], 'item_no');
        }

        $items = Item::with('item_channels');

        if (isset($validated['items'])) {
            $items->whereIn('item_no', $item_no);
        }

        if (isset($validated['w_no']) && !isset($validated['items'])) {
            $warehousing = Warehousing::find($validated['w_no']);

            $items->with(['warehousing_item' => fn($query) => $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_shipper')]);

            $items->whereHas('warehousing_item',function($query) use ($validated) {
                if($validated['type'] == 'IW'){
                    $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_shipper');
                }else{
                    $query->where('w_no', '=', $validated['w_no']);
                }
            });

            $sql_count = WarehousingItem::where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys')->get();
            $count = $sql_count->count();

            if($count != 0){
                $items->with(['warehousing_item2' => fn($query) => $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys')]);

                $items->whereHas('warehousing_item2',function($query) use ($validated) {
                    if($validated['type'] == 'IW'){
                        $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys');
                    }else{
                        $query->where('w_no', '=', $validated['w_no']);
                    }
                });
            }

        }

        if (!isset($validated['w_no']) && !isset($validated['items'])) {
            $items->where(DB::raw('1'),'=','2');
        }

        $items->where('item_service_name', '유통가공');

        $items = $items->get();
            return response()->json(['message' => Messages::MSG_0007,
                                    'data' => $warehousing,
                                    'datas' => $warehousings,
                                    'warehousing_import' => $warehousing_import,
                                    'rgd' => $rgd,
                                    'warehousing_request' => $warehousing_request,
                                    'items' => $items,
                                    'sql' => DB::getQueryLog()
                                ],
                                    

                                    200);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
}

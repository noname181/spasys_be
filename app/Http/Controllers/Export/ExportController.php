<?php

namespace App\Http\Controllers\Export;

use App\Http\Requests\Export\ExportRequest;
use App\Http\Controllers\Controller;

use App\Models\Member;
use App\Models\Warehousing;
use App\Models\WarehousingRequest;
use App\Models\Item;
use App\Models\File;
use App\Models\ItemChannel;
use App\Models\ReceivingGoodsDelivery;
use App\Models\WarehousingItem;

use App\Utils\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ExportController extends Controller
{
    public function get_export_data(ExportRequest $request)

    {
        //return $request;
        
        $validated = $request->validated();
        try{

        DB::enableQueryLog();
        //fetchWarehousing
        $warehousing = Warehousing::find($validated['w_no']);


        $type = $warehousing->w_type;
        $warehousings = Warehousing::where('w_import_no',$validated['w_no'])->get();
        

        
        if(isset($validated['page_type']) && ($validated['page_type'] == 'Page146_1' || $validated['page_type'] == 'Page146') && $type=='IW'){
            $w_no = array();
            foreach($warehousings as $o) {
                $w_no[] = $o->w_no;
            }
        }

        //fetchReceivingGoodsDeliveryRequests
       
        
        if(isset($validated['page_type']) && ($validated['page_type'] == 'Page146_1' || $validated['page_type'] == 'Page146') && $type=='IW'){
            $rgd = ReceivingGoodsDelivery::with('mb_no')->with('w_no')->whereHas('w_no', function($q) use ($w_no) {
                return $q->whereIn('w_no', $w_no);
            })->get();
        }else{
            $rgd = ReceivingGoodsDelivery::with('mb_no')->with('w_no')->whereHas('w_no', function($q) use ($validated) {
                return $q->where('w_no', $validated['w_no']);
            })->get();
        }

        //fetchWarehousingRequests
        // If per_page is null set default data = 15
        $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
        // If page is null set default data = 1
        $page = isset($validated['page']) ? $validated['page'] : 1;

        $warehousing_request = WarehousingRequest::with('mb_no')->orderBy('wr_no', 'DESC');

        $members = Member::where('mb_no', '!=', 0)->get();

        $warehousing_request = $warehousing_request->paginate($per_page, ['*'], 'page', $page);


        if(isset($validated['items'])){
            $item_no =  array_column($validated['items'], 'item_no');
        }

        

        //fetchItems
        if (isset($validated['w_no'])) {
            if (isset($item_no)) {
                $warehousing_items = WarehousingItem::where('w_no', $validated['w_no'])->whereIn('item_no', $item_no)->get();
            }else if(isset($w_no) && $type=='IW'){
                $warehousing_items = WarehousingItem::whereIn('w_no', $w_no)->get();
            }else{
                $warehousing_items = WarehousingItem::where('w_no', $validated['w_no'])->get();
            }

            $items = [];
            foreach($warehousing_items as $warehousing_item){
                $item = Item::with(['item_channels', 'company'])->where('item_no', $warehousing_item->item_no)->first();
                $item->warehousing_item = $warehousing_item;
                $items[] = $item;
            }

        }

            return response()->json(['message' => Messages::MSG_0007,
                                     'warehousing' => $warehousing,
                                     'warehousings' => $warehousings,
                                     'warehousing_items' => $warehousing_items,
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
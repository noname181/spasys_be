<?php

namespace App\Http\Controllers\StockItem;

use App\Http\Controllers\Controller;
use App\Models\StockItem;
use Illuminate\Http\Request;
use App\Http\Requests\Item\StockItemSearchRequest;
use App\Models\Item;
use App\Models\File;
use App\Models\ItemChannel;
use App\Utils\Messages;
use App\Http\Requests\Item\ExcelRequest;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StockItemController extends Controller
{

    public function postStockItems(StockItemSearchRequest $request)
    {

        $validated = $request->validated();
       // return  $validated;
        try {
            DB::enableQueryLog();
            if(isset($validated['items'])){
                $item_no =  array_column($validated['items'], 'item_no');
            }

            $items = Item::with('item_channels');

            if (isset($item_no)) {
                $items->whereIn('item_no', $item_no);
            }

            if (isset($validated['w_no']) && !isset($validated['items'])) {
                $items->with('warehousing_item');
                $items->whereHas('warehousing_item.w_no',function($query) use ($validated) {
                    $query->where('w_no', '=', $validated['w_no']);
                });

            }

            if (!isset($validated['w_no']) && !isset($validated['items'])) {
                $items->where('1','=','2');
            }

            $items->where('item_service_name', '유통가공');

            $items = $items->get();
            return response()->json([
                'message' => Messages::MSG_0007,
                'items' => $items,
                'sql' => DB::getQueryLog()
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param  \App\Models\StockItem  $stockItem
     * @return \Illuminate\Http\Response
     */
    public function show(StockItem $stockItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\StockItem  $stockItem
     * @return \Illuminate\Http\Response
     */
    public function edit(StockItem $stockItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StockItem  $stockItem
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, StockItem $stockItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StockItem  $stockItem
     * @return \Illuminate\Http\Response
     */
    public function destroy(StockItem $stockItem)
    {
        //
    }
}

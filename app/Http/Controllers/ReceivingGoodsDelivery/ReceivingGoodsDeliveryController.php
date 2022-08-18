<?php

namespace App\Http\Controllers\ReceivingGoodsDelivery;

use DateTime;
use App\Models\Warehousing;
use App\Models\Member;
use App\Models\WarehousingRequest;
use App\Models\ReceivingGoodsDelivery;
use App\Utils\Messages;
use App\Utils\CommonFunc;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;
use App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryRequest;
use App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryCreateRequest;

class ReceivingGoodsDeliveryController extends Controller
{
    /**
     * Fetch data
     * @param  \App\Http\Requests\ReceivingGoodsDelivery\ReceivingGoodsDeliveryRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(ReceivingGoodsDeliveryRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $rgd = ReceivingGoodsDelivery::with('mb_no')->with('w_no')->paginate($per_page, ['*'], 'page', $page);

            return response()->json($rgd);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create_warehousing(ReceivingGoodsDeliveryCreateRequest $request)
    {
        $validated = $request->validated();

        try {
            //DB::beginTransaction();
            $warehousing = Warehousing::where('w_no', $validated['w_no'])->first();
            $warehousing_item = WarehousingItem::where('wi_no', $validated['wi_no'])->first();
            $warehousing_request = WarehousingRequest::where('wr_no', $validated['wr_no'])->first();
            $item = Item::where('item_no', $validated['item_no'])->first();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();

            $rgd_no = ReceivingGoodsDelivery::insertGetId([
                'mb_no' => $member->mb_no,
                'w_no' => $warehousing->w_no,
                'rgd_contents' => $validated['rgd_contents'],
                'rgd_address' => $validated['rgd_address'],
                'rgd_address_detail' => $validated['rgd_address_detail'],
                'rgd_receiver' => $validated['rgd_receiver'],
                'rgd_hp' => $validated['rgd_hp'],
                'rgd_memo' => $validated['rgd_memo'],
                'rgd_status1' => $validated['rgd_status1'],
                'rgd_status2' => $validated['rgd_status2'],
                'rgd_status3' => $validated['rgd_status3'],
                'rgd_delivery_company' => $validated['rgd_delivery_company'],
                'rgd_tracking_code' => $validated['rgd_tracking_code'],
                'rgd_delivery_man' => $validated['rgd_delivery_man'],
                'rgd_delivery_man_hp' => $validated['rgd_delivery_man_hp'],
                'rgd_delivery_schedule_day' => DateTime::createFromFormat('Y-m-d', $validated['rgd_delivery_schedule_day']),
                'rgd_arrive_day' => DateTime::createFromFormat('Y-m-d', $validated['rgd_arrive_day']),
            ]);

            Warehousing::insert([
                'mb_no' => $member->mb_no,
                'w_schedule_number' => 'w_schedule_number',
                'w_schedule_day' => 'w_schedule_day',
                'connection_number' => 'connection_number',
            ]);

            WarehousingRequest::insert([
                'mb_no' => $member->mb_no,
                'wr_contents' => 'wr_contents',
            ]);
            
            $warehousing_items = [];
            foreach ($validated['warehousing_items'] as $warehousing_item) {
                if (isset($warehousing_item['wi_number'])) {
                    $warehousing_items[] = [
                        'item_no' => $item_no,
                        'w_no' => $warehousing->w_no,
                        'wi_number' => $warehousing_item['wi_number'],
                    ];
                }
            }
            WarehousingItem::insert($warehousing_items);

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'notice_no' => $notice_no
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function create_import_schedule(ReceivingGoodsDeliveryCreateRequest $request)
    {
        $validated = $request->validated();

        try {
            //DB::beginTransaction();

            $member = Member::where('mb_id', Auth::user()->mb_id)->first(); 

           
                foreach ($validated['location'] as $rgd) {
                    if (!$rgd['rgd_no']) {
                        $rgd_no = ReceivingGoodsDelivery::insertGetId([
                            'mb_no' => $member->mb_no,
                            //'w_no' => $warehousing->w_no,
                            'service_korean_name' => '보세화물',
                            'rgd_contents' => $rgd['rgd_contents'],
                            'rgd_address' => $rgd['rgd_address'],
                            'rgd_address_detail' => $rgd['rgd_address_detail'],
                            'rgd_receiver' => $rgd['rgd_receiver'],
                            'rgd_hp' => $rgd['rgd_hp'],
                            'rgd_memo' => $rgd['rgd_memo'],
                            'rgd_status1' => $rgd['rgd_status1'],
                            'rgd_status2' => $rgd['rgd_status2'],
                            'rgd_status3' => $rgd['rgd_status3'],
                            'rgd_delivery_company' => $rgd['rgd_delivery_company'],
                            'rgd_tracking_code' => $rgd['rgd_tracking_code'],
                            'rgd_delivery_man' => $rgd['rgd_delivery_man'],
                            'rgd_delivery_man_hp' => $rgd['rgd_delivery_man_hp'],
                            'rgd_delivery_schedule_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']),
                            'rgd_arrive_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']),
                        ]);
                    }else{
                        $rgd_no = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                            'rgd_contents' => $rgd['rgd_contents'],
                            'rgd_address' => $rgd['rgd_address'],
                            'rgd_address_detail' => $rgd['rgd_address_detail'],
                            'rgd_receiver' => $rgd['rgd_receiver'],
                            'rgd_hp' => $rgd['rgd_hp'],
                            'rgd_memo' => $rgd['rgd_memo'],
                            'rgd_status1' => $rgd['rgd_status1'],
                            'rgd_status2' => $rgd['rgd_status2'],
                            'rgd_status3' => $rgd['rgd_status3'],
                            'rgd_delivery_company' => $rgd['rgd_delivery_company'],
                            'rgd_tracking_code' => $rgd['rgd_tracking_code'],
                            'rgd_delivery_man' => $rgd['rgd_delivery_man'],
                            'rgd_delivery_man_hp' => $rgd['rgd_delivery_man_hp'],
                            'rgd_delivery_schedule_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_delivery_schedule_day']),
                            'rgd_arrive_day' => DateTime::createFromFormat('Y-m-d', $rgd['rgd_arrive_day']),
                        ]);
                    }
                }

                if($validated['wr_contents']){
                    WarehousingRequest::insert([
                        'mb_no' => $member->mb_no,
                        'wr_contents' => $validated['wr_contents'],
                    ]);
                }
                
           
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rgd_no' => $rgd_no
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
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
     * @param  \App\Models\ReceivingGoodsDelivery  $receivingGoodsDelivery
     * @return \Illuminate\Http\Response
     */
    public function show(ReceivingGoodsDelivery $receivingGoodsDelivery)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ReceivingGoodsDelivery  $receivingGoodsDelivery
     * @return \Illuminate\Http\Response
     */
    public function edit(ReceivingGoodsDelivery $receivingGoodsDelivery)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ReceivingGoodsDelivery  $receivingGoodsDelivery
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ReceivingGoodsDelivery $receivingGoodsDelivery)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ReceivingGoodsDelivery  $receivingGoodsDelivery
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReceivingGoodsDelivery $receivingGoodsDelivery)
    {
        //
    }
}

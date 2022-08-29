<?php

namespace App\Http\Controllers\Warehousing;

use DateTime;
use App\Models\File;
use App\Models\Member;
use App\Models\Warehousing;
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
use App\Http\Requests\Warehousing\WarehousingRequest;
use App\Http\Requests\Warehousing\WarehousingSearchRequest;

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
       
        $warehousing = Warehousing::find($w_no);
        if (!empty($warehousing)) {
            return response()->json(
                ['message' => Messages::MSG_0007,
                 'data' => $warehousing
                ], 200);
        } else {
            return response()->json(['message' => Messages::MSG_0018], 400);
        }
    }

    
    /**
     * Get Warehousing
     * @param  WarehousingSearchRequest $request
     */
    public function getWarehousing(WarehousingSearchRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $warehousing = Warehousing::with('mb_no')->with(['co_no','warehousing_item','receving_goods_delivery'])->orderBy('w_no', 'DESC');

            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['mb_name'])) {
                $warehousing->whereHas('mb_no',function($query) use ($validated) {
                    $query->where(DB::raw('lower(mb_name)'), 'like','%'. strtolower($validated['mb_name']) .'%');
                });
            }

            if (isset($validated['co_name'])) {
                $warehousing->whereHas('co_no', function($q) use($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['w_schedule_number'])) {
                $warehousing->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
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
                $warehousing->whereHas('receving_goods_delivery',function($query) use ($validated) {              
                    $query->where('rgd_status1', '=', $validated['rgd_status1']);
                });
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing->whereHas('receving_goods_delivery',function($query) use ($validated) {              
                    $query->where('rgd_status2', '=', $validated['rgd_status2']);
                });
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing->whereHas('receving_goods_delivery',function($query) use ($validated) {              
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
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $warehousing = ReceivingGoodsDelivery::with('w_no') -> with(['mb_no']);
            // $warehousing = Warehousing::with('mb_no')->with(['co_no','warehousing_item','receving_goods_delivery'])->orderBy('w_no', 'DESC');

            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['mb_name'])) {
                $warehousing->whereHas('mb_no',function($query) use ($validated) {
                    $query->where(DB::raw('lower(mb_name)'), 'like','%'. strtolower($validated['mb_name']) .'%');
                });
            }

            if (isset($validated['co_name'])) {
                $warehousing->whereHas('w_no.co_no', function($q) use($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('w_no', function($q) use($validated) {
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
                $warehousing->whereHas('w_no.warehousing_item', function($q) use($validated) {
                    return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }
            if (isset($validated['rgd_status1_1']) || isset($validated['rgd_status1_2']) || isset($validated['rgd_status1_3'])) {
                $warehousing->where('rgd_status1', '=', $validated['rgd_status1_1']);
                $warehousing->orWhere('rgd_status1', '=', $validated['rgd_status1_2']);
                $warehousing->orWhere('rgd_status1', '=', $validated['rgd_status1_3']);
            }
            if (isset($validated['rgd_status2_1']) || isset($validated['rgd_status2_2']) || isset($validated['rgd_status2_3'])) {
                $warehousing->where('rgd_status2', '=', $validated['rgd_status2_1']);
                $warehousing->orWhere('rgd_status2', '=', $validated['rgd_status2_2']);
                $warehousing->orWhere('rgd_status2', '=', $validated['rgd_status2_3']);
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

            return response()->json($warehousing);
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
     * @param  \App\Models\Warehousing  $warehousing
     * @return \Illuminate\Http\Response
     */
    public function show(Warehousing $warehousing)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Warehousing  $warehousing
     * @return \Illuminate\Http\Response
     */
    public function edit(Warehousing $warehousing)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Warehousing  $warehousing
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Warehousing $warehousing)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Warehousing  $warehousing
     * @return \Illuminate\Http\Response
     */
    public function destroy(Warehousing $warehousing)
    {
        //
    }
}

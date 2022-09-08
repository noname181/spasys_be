<?php

namespace App\Http\Controllers\Warehousing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehousing\WarehousingRequest;
use App\Http\Requests\Warehousing\WarehousingSearchRequest;
use App\Models\Member;
use App\Models\ReceivingGoodsDelivery;
use App\Models\Warehousing;
use App\Utils\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $warehousings = Warehousing::where('w_import_no', $w_no)->get();
            if (isset($warehousing->w_import_no) && $warehousing->w_import_no) {
                $warehousing_import = Warehousing::where('w_no', $warehousing->w_import_no)->first();
            } else {
                $warehousing_import = '';
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
            $warehousing = Warehousing::with('mb_no')->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->orderBy('w_no', 'DESC');

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
    public function getWarehousingImport(WarehousingSearchRequest $request) //page 129 show IW

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) {
                $query->where('w_type', '=', 'IW');
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
            $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고');
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
            $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) {
                $query->where('rgd_status1', '=', '입고');
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
            $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) {
                $query->where('w_type', '=', 'EW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업완료')->where(function ($q) {
                    $q->where(function ($query) {
                        $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                    })
                        ->orWhereNull('rgd_status4');
                });
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

            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();

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
            $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])
            ->whereHas('w_no', function ($query) {
                $query->where('w_type', '=', 'EW')
                ->where('rgd_status1', '=', '입고')
                ->where('rgd_status2', '=', '작업완료')
                ->where(function ($q) {
                    $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
                });
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

            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();

            return response()->json($warehousing);

        } catch (\Exception $e) {
            Log::error($e);
            //return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getWarehousingByRgd($rgd_no)
    {
        try {
            $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();
            $w_no = $rgd->w_no;

            $warehousing = Warehousing::with(['co_no', 'warehousing_request'])->find($w_no);


            return response()->json(
                ['message' => Messages::MSG_0007,
                    'data' => $warehousing,
                ], 200);

        } catch (\Exception $e) {
            Log::error($e);
            //return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }


    }

}

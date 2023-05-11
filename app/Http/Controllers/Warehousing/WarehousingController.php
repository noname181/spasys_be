<?php

namespace App\Http\Controllers\Warehousing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehousing\WarehousingDataValidate;
use App\Http\Requests\Warehousing\WarehousingItemValidate;
use App\Http\Requests\Warehousing\WarehousingRequest;
use App\Http\Requests\Warehousing\WarehousingSearchRequest;
use App\Models\StockHistory;
use App\Models\ScheduleShipmentInfo;
use App\Models\AdjustmentGroup;
use App\Models\CompanySettlement;
use App\Models\Company;
use App\Models\Contract;
use App\Models\RateData;
use App\Models\CancelBillHistory;
use App\Models\TaxInvoiceDivide;
use App\Models\CashReceipt;
use App\Models\Import;
use App\Models\ImportExpected;
use App\Models\Export;
use App\Models\ExportConfirm;
use App\Models\Member;
use App\Models\RateDataGeneral;
use App\Models\RateMetaData;
use App\Models\ReceivingGoodsDelivery;
use App\Models\ScheduleShipment;
use App\Models\Service;
use App\Models\Warehousing;
use App\Models\WarehousingItem;
use App\Models\WarehousingSettlement;
use App\Models\Item;
use App\Utils\CommonFunc;
use App\Utils\Messages;
use Carbon\Carbon;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Requests\Warehousing\ExcelRequest;
use Illuminate\Support\Str;

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
            if ($warehousing->w_import_no) {
                $warehousings = Warehousing::with('receving_goods_delivery')->whereHas('receving_goods_delivery', function ($q) {
                    $q->whereNull("rgd_parent_no");
                })->where('w_import_no', $warehousing->w_import_no)->get();
            } else {
                $warehousings = [];
            }
            if (!empty($warehousing)) {
                return response()->json(
                    [
                        'message' => Messages::MSG_0007,
                        'data' => $warehousing,
                        'datas' => $warehousings,
                        'warehousing_import' => $warehousing_import,
                    ],
                    200
                );
            } else {
                return response()->json(['message' => Messages::MSG_0018], 400);
            }
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Get Warehousing
     * @param  WarehousingSearchRequest $request
     */
    public function getWarehousing2(WarehousingSearchRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop') {

                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->where('w_type', 'IW')->whereNotNull('w_schedule_number2')->where('w_schedule_number2', '!=', '')
                    ->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('w_completed_day', 'DESC');
            } else if ($user->mb_type == 'shipper') {

                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->where('w_type', 'IW')->whereNotNull('w_schedule_number2')->where('w_schedule_number2', '!=', '')
                    ->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('w_completed_day', 'DESC');
            } else if ($user->mb_type == 'spasys') {

                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child'])->where('w_type', 'IW')->whereNotNull('w_schedule_number2')->where('w_schedule_number2', '!=', '')
                    ->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('w_completed_day', 'DESC');
            }

            if (isset($validated['page_type']) && $validated['page_type'] == "page130") {
                $warehousing->where('w_type', '=', 'IW');
            }

            if (isset($validated['from_date'])) {
                $warehousing->where('w_completed_day', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('w_completed_day', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
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
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_iw'] . '%', 'and', 'w_type', '=', 'IW');
                });
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
                    $query->where('rgd_status1', '=', $validated['rgd_status1'])->whereNull('rgd_parent_no');
                });
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                    $query->where('rgd_status2', '=', $validated['rgd_status2'])->whereNull('rgd_parent_no');
                });
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                    $query->where('rgd_status3', '=', $validated['rgd_status3'])->whereNull('rgd_parent_no');
                });
            }

            $members = Member::where('mb_no', '!=', 0)->get();

            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) {

                    $item->total_item = WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_spasys')->sum('wi_number');
                    if ($item['warehousing_item'][0]['item']) {
                        $first_name_item = $item['warehousing_item'][0]['item']['item_name'];
                        $total_item = $item['warehousing_item']->count();
                        $final_total = (($total_item / 2)  - 1);
                        if ($final_total <= 0) {
                            $item->first_item_name_total = $first_name_item;
                        } else {
                            $item->first_item_name_total = $first_name_item . '외' . ' ' . $final_total . '건';
                        }
                    } else {
                        $item->first_item_name_total = '';
                    }

                    return $item;
                })
            );

            return response()->json($warehousing);
        } catch (\Exception $e) {
            Log::error($e);

            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function getWarehousing(WarehousingSearchRequest $request) // page 710

    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $warehousing2 = Warehousing::join(
                    DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                    'm.w_no',
                    '=',
                    'warehousing.w_no'
                )->where('warehousing.w_type', '=', 'EW')->where('warehousing.w_category_name', '=', '유통가공')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });
                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'package'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '유통가공')
                    ->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orWhereIn('w_no', $w_no_in)->orderBy('w_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $warehousing2 = Warehousing::join(
                    DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                    'm.w_no',
                    '=',
                    'warehousing.w_no'
                )->where('warehousing.w_type', '=', 'EW')->where('warehousing.w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });
                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'package'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '유통가공')
                    ->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orWhereIn('w_no', $w_no_in)->orderBy('w_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                // join(
                //     DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                //     'm.w_no',
                //     '=',
                //     'warehousing.w_no'
                // )

                $warehousing2 = Warehousing::with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child'])->where('warehousing.w_type', '=', 'EW')->where('warehousing.w_category_name', '=', '유통가공')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });

                if (isset($validated['from_date'])) {
                    $warehousing2->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                }

                if (isset($validated['to_date'])) {
                    $warehousing2->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                }

                if (isset($validated['co_parent_name'])) {
                    $warehousing2->whereHas('co_no.co_parent', function ($query) use ($validated) {
                        $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    });
                }

                if (isset($validated['co_name'])) {
                    $warehousing2->whereHas('co_no', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    });
                }

                if (isset($validated['w_schedule_number_iw'])) {
                    $warehousing2->where(function ($q) use ($validated) {
                        $q->whereHas('w_import_parent', function ($q) use ($validated) {
                            $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_iw'] . '%', 'and', 'w_type', '=', 'EW');
                        })->orWhere('w_schedule_number', 'like', '%' . $validated['w_schedule_number_iw'] . '%', 'and', 'w_type', '=', 'IW');
                    });
                }

                if (isset($validated['rgd_status1'])) {
                    $warehousing2->where(function ($q) use ($validated) {
                        $q->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                            $query->where('rgd_status1', '=', $validated['rgd_status1'])->whereNull('rgd_parent_no');
                        })->orwhereHas('w_import_parent.receving_goods_delivery_parent', function ($q) use ($validated) {
                            $q->where('rgd_status1', '=', $validated['rgd_status1'])->whereNull('rgd_parent_no');
                        });
                    });
                }

                if (isset($validated['rgd_status2'])) {
                    if (isset($validated['rgd_status1'])) {
                        if ($validated['rgd_status1'] == "입고" || $validated['rgd_status1'] == "입고예정취소" || $validated['rgd_status1'] == "입고예정") {
                            $warehousing2->whereHas('w_import_parent.receving_goods_delivery_parent', function ($q) use ($validated) {
                                $q->where('rgd_status2', '=', $validated['rgd_status2'])->whereNull('rgd_parent_no');
                            });
                        } else {
                            $warehousing2->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                                $query->where('rgd_status2', '=', $validated['rgd_status2'])->whereNull('rgd_parent_no');
                            });
                        }
                    } else {
                        $warehousing2->where(function ($q) use ($validated) {
                            $q->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                                $query->where('rgd_status2', '=', $validated['rgd_status2'])->whereNull('rgd_parent_no');
                            })->orwhereHas('w_import_parent.receving_goods_delivery_parent', function ($q) use ($validated) {
                                $q->where('rgd_status2', '=', $validated['rgd_status2'])->whereNull('rgd_parent_no');
                            });
                        });
                    }
                }

                if (isset($validated['rgd_status3'])) {
                    $warehousing2->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                        $query->where('rgd_status3', '=', $validated['rgd_status3'])->whereNull('rgd_parent_no');
                    });
                }
                if (isset($validated['w_schedule_number_ew'])) {
                    $warehousing2->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_ew'] . '%', 'and', 'w_type', '=', 'EW');
                }
                if (isset($validated['logistic_manage_number'])) {
                    $warehousing2->where('logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
                }
                if (isset($validated['m_bl'])) {
                    $warehousing2->where('m_bl', 'like', '%' . $validated['m_bl'] . '%');
                }
                if (isset($validated['h_bl'])) {
                    $warehousing2->where('h_bl', 'like', '%' . $validated['h_bl'] . '%');
                }

                $warehousing2 = $warehousing2->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });

                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });

                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child', 'package'])->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '유통가공')
                    ->where(function ($query) use ($user, $w_no_in) {
                        $query->whereHas('co_no.co_parent.co_parent', function ($q) use ($user, $w_no_in) {
                            $q->where('co_no', $user->co_no);
                        });
                    });
                // ->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                //     $q->where('co_no', $user->co_no);
                // })->orWhereIn('w_no', $w_no_in)->orderBy('w_no', 'DESC');
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
                $warehousing->where(function ($q) use ($validated) {
                    $q->whereHas('w_import_parent', function ($q) use ($validated) {
                        $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_iw'] . '%', 'and', 'w_type', '=', 'EW');
                    })->orWhere('w_schedule_number', 'like', '%' . $validated['w_schedule_number_iw'] . '%', 'and', 'w_type', '=', 'IW');
                });
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
                    $query->where('rgd_status1', '=', $validated['rgd_status1'])->whereNull('rgd_parent_no');
                });
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                    $query->where('rgd_status2', '=', $validated['rgd_status2'])->whereNull('rgd_parent_no');
                });
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                    $query->where('rgd_status3', '=', $validated['rgd_status3'])->whereNull('rgd_parent_no');
                });
            }

            // if (isset($validated['warehousing_status1']) || isset($validated['warehousing_status2'])) {
            //     $warehousing->where(function($query) use ($validated) {
            //         $query->orwhere('warehousing_status', '=', $validated['warehousing_status1']);
            //         $query->orWhere('warehousing_status', '=', $validated['warehousing_status2']);
            //     });
            // }

            $members = Member::where('mb_no', '!=', 0)->get();
            $warehousing->orWhereIn('w_no', $w_no_in)->orderBy('w_no', 'DESC');
            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);

            return response()->json($warehousing);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getWarehousingApi(WarehousingSearchRequest $request) // page 7102

    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $warehousing2 = Warehousing::join(
                    DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                    'm.w_no',
                    '=',
                    'warehousing.w_no'
                )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });
                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                    ->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
            } else if ($user->mb_type == 'shipper') {
                $warehousing2 = Warehousing::join(
                    DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                    'm.w_no',
                    '=',
                    'warehousing.w_no'
                )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });
                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                    ->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
            } else if ($user->mb_type == 'spasys') {

                $warehousing2 = Warehousing::join(
                    DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                    'm.w_no',
                    '=',
                    'warehousing.w_no'
                )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });

                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child', 'rate_data_general'])->where('w_category_name', '=', '수입풀필먼트')->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')
                    ->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
            }
            $warehousing->whereDoesntHave('rate_data_general');

            if (isset($validated['connection_number_type'])) {
                $warehousing->whereNull('connection_number');
            }

            if (isset($validated['page_type']) && $validated['page_type'] == "page130") {
                $warehousing->where('w_type', '=', 'IW')->where('w_category_name', '=', '수입풀필먼트');
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
            if (isset($validated['co_no'])) {
                // $import_schedule->whereHas('company', function ($q) use ($validated) {
                //     return $q->where(DB::raw('lower(aaa.co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                // });
                $warehousing->where('co_no', '=', $validated['co_no']);
            }
            if (isset($validated['w_schedule_number'])) {
                $warehousing->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
            }
            if (isset($validated['w_schedule_number_iw'])) {
                $warehousing->whereHas('w_import_parent', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_iw'] . '%', 'and', 'w_type', '=', 'IW');
                });
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
            if (isset($validated['status'])) {
                $warehousing->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                    $query->where('rgd_status1', '=', $validated['status']);
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
            if (isset($validated['page_type'])) {
                if ($validated['page_type'] == 'page130') {
                    $warehousing = $warehousing->orWhereIn('w_no', $w_no_in)->orderBy('w_completed_day', 'DESC');
                }
            } else {
                $warehousing = $warehousing->orWhereIn('w_no', $w_no_in)->orderBy('w_no', 'DESC');
            }
            $members = Member::where('mb_no', '!=', 0)->get();

            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) {

                    $item->total_item = WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_spasys')->sum('wi_number');
                    if ($item['warehousing_item'][0]['item']) {
                        $first_name_item = $item['warehousing_item'][0]['item']['item_name'];
                        $total_item = $item['warehousing_item']->count();
                        $final_total = (($total_item / 2)  - 1);
                        if ($final_total <= 0) {
                            $item->first_item_name_total = $first_name_item;
                        } else {
                            $item->first_item_name_total = $first_name_item . '외' . ' ' . $final_total . '건';
                        }
                    } else {
                        $item->first_item_name_total = '';
                    }

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
    public function getWarehousingApiPOPUP(WarehousingSearchRequest $request)

    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if(isset($validated['status']) && $validated['status'] == '입고'){
            if ($user->mb_type == 'shop') {
                $warehousing2 = Warehousing::join(
                    DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                    'm.w_no',
                    '=',
                    'warehousing.w_no'
                )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });
                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                    ->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->whereHas('receving_goods_delivery', function ($q) use ($user) {
                        $q->where('rgd_status1', '!=', '입고 취소');
                    });
            } else if ($user->mb_type == 'shipper') {
                $warehousing2 = Warehousing::join(
                    DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                    'm.w_no',
                    '=',
                    'warehousing.w_no'
                )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });
                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                    ->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->whereHas('receving_goods_delivery', function ($q) use ($user) {
                        $q->where('rgd_status1', '!=', '입고 취소');
                    });
            } else if ($user->mb_type == 'spasys') {

                $warehousing2 = Warehousing::join(
                    DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                    'm.w_no',
                    '=',
                    'warehousing.w_no'
                )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });

                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child', 'rate_data_general'])->where('w_category_name', '=', '수입풀필먼트')->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')
                    ->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->whereHas('receving_goods_delivery', function ($q) use ($user) {
                        $q->where('rgd_status1', '!=', '입고 취소');
                    });
            }
            $warehousing->whereDoesntHave('rate_data_general');
       
            if (isset($validated['connection_number_type'])) {
                $warehousing->whereNull('connection_number');
            }

            if (isset($validated['page_type']) && $validated['page_type'] == "page130") {
                $warehousing->where('w_type', '=', 'IW')->where('w_category_name', '=', '수입풀필먼트');
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
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_iw'] . '%', 'and', 'w_type', '=', 'IW');
                });
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
            if (isset($validated['status'])) {
                $warehousing->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                    $query->where('rgd_status1', '=', $validated['status']);
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
            $warehousing = $warehousing->orWhereIn('w_no', $w_no_in)->orderBy('w_no', 'DESC');
            $members = Member::where('mb_no', '!=', 0)->get();

            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) {

                    $item->total_item = WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_spasys')->sum('wi_number');
                    if ($item['warehousing_item'][0]['item']) {
                        $first_name_item = $item['warehousing_item'][0]['item']['item_name'];
                        $total_item = $item['warehousing_item']->count();
                        $final_total = (($total_item / 2)  - 1);
                        if ($final_total <= 0) {
                            $item->first_item_name_total = $first_name_item . '외';
                        } else {
                            $item->first_item_name_total = $first_name_item . '외' . ' ' . $final_total . '건';
                        }
                    } else {
                        $item->first_item_name_total = '';
                    }

                    return $item;
                })
            );
            return response()->json($warehousing);
            } else {
                if (isset($validated['status']) && $validated['status'] == '출고예정') {
                    if ($user->mb_type == 'shop') {
                        $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status','출고예정')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        })->orderBy('ss_no', 'DESC');
                    } else if ($user->mb_type == 'shipper') {
                        $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status','출고예정')->whereHas('ContractWms.company', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        })->orderBy('ss_no', 'DESC');
                    } else if ($user->mb_type == 'spasys') {
                        $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status','출고예정')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        })->orderBy('ss_no', 'DESC');
                    }
                } else if(isset($validated['status']) && $validated['status'] == '출고') {
                    if ($user->mb_type == 'shop') {
                        $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status','출고')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        })
                        // ->where(function ($q) {
                        //     $q->whereHas('receving_goods_delivery', function ($q1) {
                        //         $q1->where('rgd_status3', '=',"배송완료");
                        //     });
                        // })
                        ->orderBy('ss_no', 'DESC');
                    } else if ($user->mb_type == 'shipper') {
                        $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status','출고')->whereHas('ContractWms.company', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        })
                        // ->where(function ($q) {
                        //     $q->whereHas('receving_goods_delivery', function ($q1) {
                        //         $q1->where('rgd_status3', '=',"배송완료");
                        //     });
                        // })
                        ->orderBy('ss_no', 'DESC');
                    } else if ($user->mb_type == 'spasys') {
                        $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status','출고')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        })
                        // ->where(function ($q) {
                        //     $q->whereHas('receving_goods_delivery', function ($q1) {
                        //         $q1->where('rgd_status3', '=',"배송완료");
                        //     });
                        // })
                        ->orderBy('ss_no', 'DESC');
                    }
                }
                //return DB::getQueryLog();
    
                if (isset($validated['from_date'])) {
                    $schedule_shipment->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                }
    
                if (isset($validated['to_date'])) {
                    $schedule_shipment->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                }
    
                if (isset($validated['co_parent_name'])) {
                    $schedule_shipment->whereHas('ContractWms.company.co_parent', function ($query) use ($validated) {
                        $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    });
                }
    
                if (isset($validated['co_name'])) {
                    $schedule_shipment->whereHas('ContractWms.company', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    });
                }
    
                if (isset($validated['item_brand'])) {
                    $schedule_shipment->whereHas('schedule_shipment_info.item', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                    });
                }
    
                if (isset($validated['item_channel_name'])) {
                    $schedule_shipment->whereHas('schedule_shipment_info.item.item_channels', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(item_channel_name)'), 'like', '%' . strtolower($validated['item_channel_name']) . '%');
                    });
                }
    
                if (isset($validated['item_name'])) {
                    $schedule_shipment->whereHas('schedule_shipment_info.item', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(item_name)'), 'like', '%' . strtolower($validated['item_name']) . '%');
                    });
                }
    
                if (isset($validated['status'])) {
                    if ($validated['status'] == '배송준비') {
                        $schedule_shipment->whereDoesntHave('receving_goods_delivery', function ($q) use ($validated) {
                            $q->where(DB::raw('lower(rgd_status3)'), 'like', '%' . strtolower($validated['status']) . '%');
                            $q->orWhere('rgd_status3', '!=', '배송준비');
                        });
                    } elseif ($validated['status'] == '배송중') {
                        $schedule_shipment->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                            $q->where(DB::raw('lower(rgd_status3)'), 'like', '%' . strtolower($validated['status']) . '%');
                        });
                    } elseif ($validated['status'] == '배송완료') {
                        $schedule_shipment->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                            $q->where(DB::raw('lower(rgd_status3)'), 'like', '%' . strtolower($validated['status']) . '%');
                        });
                    }
                }
    
                if (isset($validated['order_id'])) {
    
                    $schedule_shipment->where(DB::raw('lower(order_id)'), 'like', '%' . strtolower($validated['order_id']) . '%');
                }
                if (isset($validated['recv_name'])) {
                    $schedule_shipment->where(DB::raw('lower(recv_name)'), 'like', '%' . strtolower($validated['recv_name']) . '%');
                }
                if (isset($validated['name'])) {
                    $schedule_shipment->whereHas('schedule_shipment_info', function ($q) use ($validated) {
                        $q->where('name', 'like', '%' . $validated['name'] . '%');
                    });
                }
                if (isset($validated['qty'])) {
                    $schedule_shipment->whereHas('schedule_shipment_info', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(qty)'), 'like', '%' . strtolower($validated['qty']) . '%');
                    });
                }
                if (isset($validated['trans_corp'])) {
                    $schedule_shipment->where(DB::raw('lower(trans_corp)'), 'like', '%' . strtolower($validated['trans_corp']) . '%');
                }
    
                $schedule_shipment = $schedule_shipment->paginate($per_page, ['*'], 'page', $page);
                $schedule_shipment->setCollection(
                    $schedule_shipment->getCollection()->map(function ($q) {
                        $schedule_shipment_item = DB::table('schedule_shipment_info')->where('schedule_shipment_info.ss_no', $q->ss_no)->get();
                        $count_item = 0;
                        foreach ($schedule_shipment_item as $item) {
                            $q->total_amount += $item->qty;
                            $count_item++;
                        }
                        $q->count_item = $count_item;
    
                        $scheduleshipment_info_ = ScheduleShipmentInfo::with(['item'])->where('ss_no', $q->ss_no)->first();
                        $item_schedule_shipment = Item::where('product_id', $scheduleshipment_info_->product_id)->first();
                        if (isset($item_schedule_shipment)) {
                            $item_first_name = $item_schedule_shipment['item_name'];
                            $total_item = $scheduleshipment_info_['item']->count() - 1;
                            if ($total_item <= 0) {
                                $q->first_item_name_total = $item_first_name . '외';
                            } else {
                                $q->first_item_name_total = $item_first_name . '외' . ' ' . $total_item . '건';
                            }
                        } else {
                            $q->first_item_name_total = '';
                        }
    
    
                        return  $q;
                    })
                );
                return response()->json($schedule_shipment);
            }
  
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

            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function warehousingImport(Request $request)
    {
        try {
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

            $amount_total = array_sum(array_column($warehousing_item_data, 'D'));

            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $results[$sheet->getTitle()] = [];
            $errors[$sheet->getTitle()] = [];
            $key_schedule = Warehousing::latest()->orderBy('w_no', 'DESC')->first();
            $check_key = 1;

            $rows_warehousing_add = 0;
            $rows_number_item_add = 0;
            $check_error = false;
            foreach ($warehousing_data as $key => $warehouse) {
                if ($key <= 2) {
                    continue;
                }
                $validator = Validator::make($warehouse, WarehousingDataValidate::rules());
                if ($validator->fails()) {
                    $errors[$sheet->getTitle()][] = $validator->errors();
                    $check_error = true;
                } else {
                    $w_schedule_day = date('Y-m-d', strtotime($warehouse['B']));
                    $schedule_number = 'SPA_' . date('Ymd') . ((int) $key_schedule->w_no + $check_key) . '_IW';
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
                        'w_cancel_yn' => 'n',
                    ]);
                    if ($warehousing_id) {
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
                        foreach ($warehousing_item_data as $key => $warehouse_item) {
                            if ($key <= 2) {
                                continue;
                            }

                            $validator_item = Validator::make($warehouse_item, WarehousingItemValidate::rules());
                            if ($validator_item->fails()) {
                                $errors[$sheet->getTitle()][] = $validator_item->errors();
                                $check_error = true;
                            } else {
                                if ($warehouse['A'] === $warehouse_item['A']) {
                                    $rows_number_item_add = $rows_number_item_add + 1;
                                    $item_no = WarehousingItem::insert([
                                        'item_no' => $warehouse_item['B'],
                                        'w_no' => $warehousing_id,
                                        'wi_number' => $warehouse_item['C'],
                                        'wi_type' => '입고_shipper',
                                    ]);
                                }
                            }
                        }
                    }
                }
                $check_key++;
            }
            if ($check_error == true) {
                DB::rollback();
                return response()->json([
                    'message' => Messages::MSG_0007,
                    'status' => 2,
                    'errors' => $errors,
                    'rows_warehousing_add' => $rows_warehousing_add,
                    'rows_number_item_add' => $rows_number_item_add,
                ], 201);
            } else {
                DB::commit();
                return response()->json([
                    'message' => Messages::MSG_0007,
                    'errors' => $errors,
                    'status' => 1,
                    'rows_warehousing_add' => $rows_warehousing_add,
                    'rows_number_item_add' => $rows_number_item_add,
                ], 201);
            }
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);

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
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                        $q->where('rgd_status1', '!=', '입고')->orWhereNull('rgd_status1');
                    })->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                        $q->where('rgd_status1', '!=', '입고')->orWhereNull('rgd_status1');
                    })->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                        $q->where('rgd_status1', '!=', '입고')->orWhereNull('rgd_status1');
                    })->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
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

            if (isset($validated['connection_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(connection_number)'), 'like', '%' . strtolower($validated['connection_number']) . '%');
                });
            }

            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }
            if (isset($validated['rgd_status1'])) {
                if ($validated['rgd_status1'] != "전체") {
                    $warehousing->where('rgd_status1', '=', $validated['rgd_status1']);
                }
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
            $warehousing->orderBy('created_at', 'DESC');
            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) {

                    $item->total_item = WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_spasys')->sum('wi_number');
                    if (!empty($item['warehousing']['warehousing_item'][0]) && isset($item['warehousing']['warehousing_item'][0]['item'])) {
                        $first_name_item = $item['warehousing']['warehousing_item'][0]['item']['item_name'];
                        $total_item = $item['warehousing']['warehousing_item']->count();
                        $final_total = ($total_item   - 1);
                        if ($final_total <= 0) {
                            $item->first_item_name_total = $first_name_item ;
                        } else {
                            $item->first_item_name_total = $first_name_item . '외' . ' ' . $final_total . '건';
                        }
                    } else {
                        $item->first_item_name_total = '';
                    }

                    return $item;
                })
            );
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
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('receiving_goods_delivery.created_at', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('receiving_goods_delivery.created_at', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('receiving_goods_delivery.created_at', 'DESC');
            }

            $warehousing->whereNull('rgd_parent_no');

            if (isset($validated['from_date'])) {
                $warehousing->where('warehousing.w_completed_day', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }
            if (isset($validated['status'])) {
                $warehousing->where('rgd_status1', '=', $validated['status']);
            }
            if (isset($validated['to_date'])) {
                $warehousing->where('warehousing.w_completed_day', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
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
                    return $q->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number_iw'] . '%');
                });
            }
            if (isset($validated['connection_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('connection_number', 'like', '%' . $validated['connection_number'] . '%');
                });
            }
            if (isset($validated['w_schedule_number_ew'])) {
                $warehousing->whereHas('w_no.w_import_parent', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_ew'] . '%');
                });
            }
            $warehousing->orderBy('w_completed_day', 'DESC');
            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) {
                    // if(!empty($item->w_no)){
                    //     $item->w_amount_left = $item->w_no->w_amount - $item->w_no->w_schedule_amount;
                    // }
                    $warehousing = Warehousing::where('w_no', $item->w_no)->first();
                    $item->w_amount_left = $warehousing->w_amount - $warehousing->w_schedule_amount;

                    $item->total_item = WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_shipper')->sum('wi_number');
                    if (!empty($item['warehousing']['warehousing_item'][0]) && isset($item['warehousing']['warehousing_item'][0]['item'])) {
                        $first_name_item = $item['warehousing']['warehousing_item'][0]['item']['item_name'];
                        
                        $total_item = 0;
                        foreach($item['warehousing']['warehousing_item'] as $a){
                            if($a['wi_type'] == "입고_spasys"){
                                $total_item++;
                            }
                        } 
                        $final_total = ($total_item - 1);
                        if ($final_total <= 0) {
                            $item->first_item_name_total = $first_name_item;
                        } else {
                            $item->first_item_name_total = $first_name_item . '외' . ' ' . $final_total . '건';
                        }
                    } else {
                        $item->first_item_name_total = '';
                    }
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
    public function getWarehousingImportStatus1POPUP(WarehousingSearchRequest $request) //page 134 show IW,rgd_status1 = complete

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('rgd_status1', '!=', '입고 취소')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('rgd_status1', '!=', '입고 취소')->where('w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('rgd_status1', '!=', '입고 취소')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            }

            $warehousing->whereNull('rgd_parent_no');
            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }
            if (isset($validated['status'])) {
                $warehousing->where('rgd_status1', '=', $validated['status']);
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

                    $item->total_item = WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_shipper')->sum('wi_number');
                    if (!empty($item['warehousing']['warehousing_item'][0]) && isset($item['warehousing']['warehousing_item'][0]['item'])) {
                        $first_name_item = $item['warehousing']['warehousing_item'][0]['item']['item_name'];
                        $total_item = $item['warehousing']['warehousing_item']->count();
                        $final_total = ($total_item   - 1);
                        if ($final_total <= 0) {
                            $item->first_item_name_total = $first_name_item . '외';
                        } else {
                            $item->first_item_name_total = $first_name_item . '외' . ' ' . $final_total . '건';
                        }
                    } else {
                        $item->first_item_name_total = '';
                    }

                    return $item;
                })
            );
            return response()->json($warehousing);
        } catch (\Exception $e) {
            Log::error($e);

            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function getWarehousingImport7103(WarehousingSearchRequest $request) //page 7103 popup

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($validated) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->where('co_no', $validated['co_no']);
                    // ->whereHas('co_no.co_parent', function ($q) use ($validated) {
                    //     $q->where('co_no', $validated['co_no']);
                    // });
                })->orderBy('receiving_goods_delivery.created_at', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($validated) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->where('co_no', $validated['co_no']);
                    // ->whereHas('co_no', function ($q) use ($validated) {
                    //     $q->where('co_no', $validated['co_no']);
                    // });
                })->orderBy('receiving_goods_delivery.created_at', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($validated) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->where('co_no', $validated['co_no']);
                    // ->whereHas('co_no.co_parent.co_parent', function ($q) use ($validated) {
                    //     $q->where('co_no', $validated['co_no']);
                    // });
                })->orderBy('receiving_goods_delivery.created_at', 'DESC');
            }

            $warehousing->whereNull('rgd_parent_no');

            if (isset($validated['from_date'])) {
                $warehousing->where('warehousing.w_completed_day', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }
            if (isset($validated['status'])) {
                $warehousing->where('rgd_status1', '=', $validated['status']);
            }
            if (isset($validated['to_date'])) {
                $warehousing->where('warehousing.w_completed_day', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
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
                    return $q->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number_iw'] . '%');
                });
            }
            if (isset($validated['connection_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('connection_number', 'like', '%' . $validated['connection_number'] . '%');
                });
            }
            if (isset($validated['w_schedule_number_ew'])) {
                $warehousing->whereHas('w_no.w_import_parent', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_ew'] . '%');
                });
            }
            $warehousing->orderBy('w_completed_day', 'DESC');
            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) {
                    // if(!empty($item->w_no)){
                    //     $item->w_amount_left = $item->w_no->w_amount - $item->w_no->w_schedule_amount;
                    // }
                    $warehousing = Warehousing::where('w_no', $item->w_no)->first();
                    $item->w_amount_left = $warehousing->w_amount - $warehousing->w_schedule_amount;

                    $item->total_item = WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_shipper')->sum('wi_number');
                    if (!empty($item['warehousing']['warehousing_item'][0]) && isset($item['warehousing']['warehousing_item'][0]['item'])) {
                        $first_name_item = $item['warehousing']['warehousing_item'][0]['item']['item_name'];
                        $total_item = $item['warehousing']['warehousing_item']->count();
                        $final_total = ($total_item   - 1);
                        if ($final_total <= 0) {
                            $item->first_item_name_total = $first_name_item . '외';
                        } else {
                            $item->first_item_name_total = $first_name_item . '외' . ' ' . $final_total . '건';
                        }
                    } else {
                        $item->first_item_name_total = '';
                    }

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
    public function getWarehousingDelivery(WarehousingSearchRequest $request) //page715 show delivery

    {
        try {
            DB::enableQueryLog();

            $validated = $request->validated();
            if ($validated['service'] == "유통가공") {
                // If per_page is null set default data = 15
                $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
                // If page is null set default data = 1
                $page = isset($validated['page']) ? $validated['page'] : 1;
                $user = Auth::user();
                if ($user->mb_type == 'shop') {
                    $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                        $query->where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                            $q->where('rgd_status1', '=', '출고')->orWhereNull('rgd_status1');
                        })->whereHas('co_no.co_parent', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        });
                    })->orderBy('rgd_no', 'DESC');
                } else if ($user->mb_type == 'shipper') {
                    $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                        $query->where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                            $q->where('rgd_status1', '=', '출고')->orWhereNull('rgd_status1');
                        })->whereHas('co_no', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        });
                    })->orderBy('rgd_no', 'DESC');
                } else if ($user->mb_type == 'spasys') {
                    $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                        $query->where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                            $q->where('rgd_status1', '=', '출고')->orWhereNull('rgd_status1');
                        })->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        });
                    })->orderBy('rgd_no', 'DESC');
                }

                if (isset($validated['from_date'])) {
                    // $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                    $warehousing->whereHas('w_no', function ($q) use ($validated) {
                        return $q->where('w_completed_day',  '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                    });
                }

                if (isset($validated['to_date'])) {
                    //$warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                    $warehousing->whereHas('w_no', function ($q) use ($validated) {
                        return $q->where('w_completed_day',  '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
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
                        return $q->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number'] . '%');
                    });
                }
                if (isset($validated['order_id'])) {
                    $warehousing->whereHas('w_no', function ($q) use ($validated) {
                        return $q->where('logistic_manage_number', 'like', '%' . $validated['order_id'] . '%');
                    });
                }

                if (isset($validated['status'])) {
                    $warehousing->where(function ($query) use ($validated) {
                        $query->where('rgd_status3', '=', $validated['status']);
                    });
                    //$warehousing->where('rgd_status3', '=', $validated['status']);

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
                if (isset($validated['carrier'])) {
                    $warehousing->where(DB::raw('lower(rgd_delivery_company)'), 'like', '%' . $validated['carrier'] . '%');
                }
                $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
                return response()->json($warehousing);
            } else if ($validated['service'] == "수입풀필먼트") {
                $user = Auth::user();
                // If per_page is null set default data = 15
                $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
                // If page is null set default data = 1
                $page = isset($validated['page']) ? $validated['page'] : 1;

                // if ($user->mb_type == 'shop') {
                //     $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->whereNotNull('trans_no')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                //         $q->where('co_no', $user->co_no);
                //     })->where(function ($q) {
                //         $q->whereHas('receving_goods_delivery', function ($q1) {
                //             $q1->where('rgd_status3', '!=', "배송완료");
                //             $q1->orwhereNull('rgd_status3');
                //         })->orwheredoesnthave('receving_goods_delivery');
                //     })->orderBy('ss_no', 'DESC');
                // } else if ($user->mb_type == 'shipper') {
                //     $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->whereNotNull('trans_no')->whereHas('ContractWms.company', function ($q) use ($user) {
                //         $q->where('co_no', $user->co_no);
                //     })->where(function ($q) {
                //         $q->whereHas('receving_goods_delivery', function ($q1) {
                //             $q1->where('rgd_status3', '!=', "배송완료");
                //             $q1->orwhereNull('rgd_status3');
                //         })->orwheredoesnthave('receving_goods_delivery');
                //     })->orderBy('ss_no', 'DESC');
                // } else if ($user->mb_type == 'spasys') {
                //     $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->whereNotNull('trans_no')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                //         $q->where('co_no', $user->co_no);
                //     })->where(function ($q) {
                //         $q->whereHas('receving_goods_delivery', function ($q1) {
                //             $q1->where('rgd_status3', '!=', "배송완료");
                //             $q1->orwhereNull('rgd_status3');
                //         })->orwheredoesnthave('receving_goods_delivery');
                //     })->orderBy('ss_no', 'DESC');
                // }
                if ($user->mb_type == 'shop') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })
                        ->orderBy('ss_no', 'DESC');
                } else if ($user->mb_type == 'shipper') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })
                        ->orderBy('ss_no', 'DESC');
                } else if ($user->mb_type == 'spasys') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })
                        ->orderBy('ss_no', 'DESC');
                }

                if (isset($validated['from_date'])) {
                    //$schedule_shipment->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                    // $schedule_shipment->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                    //     $q->where('rgd_delivery_schedule_day', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                    // });
                }

                if (isset($validated['to_date'])) {
                    //$schedule_shipment->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                    // $schedule_shipment->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                    //     $q->where('rgd_delivery_schedule_day', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                    // });
                }

                if (isset($validated['co_parent_name'])) {
                    $schedule_shipment->whereHas('ContractWms.company.co_parent', function ($query) use ($validated) {
                        $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    });
                }

                if (isset($validated['co_name'])) {
                    $schedule_shipment->whereHas('ContractWms.company', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    });
                }

                if (isset($validated['item_brand'])) {
                    $schedule_shipment->whereHas('schedule_shipment_info.item', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                    });
                }

                if (isset($validated['item_channel_name'])) {
                    $schedule_shipment->whereHas('schedule_shipment_info.item.item_channels', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(item_channel_name)'), 'like', '%' . strtolower($validated['item_channel_name']) . '%');
                    });
                }

                if (isset($validated['item_name'])) {
                    $schedule_shipment->whereHas('schedule_shipment_info.item', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(item_name)'), 'like', '%' . strtolower($validated['item_name']) . '%');
                    });
                }
                if (isset($validated['w_schedule_number'])) {
                    $schedule_shipment->where('ss_no', 'like', '%' . $validated['w_schedule_number'] . '%');
                }
                if (isset($validated['status'])) {
                    // if ($validated['status'] == "배송준비") {
                    //     $schedule_shipment->where(function ($query) {
                    //         $query->where('trans_no', '=', '출고')->whereHas('receving_goods_delivery', function ($q) {
                    //             $q->where('rgd_status3', '=', null)->where('rgd_status3', '!=', '배송완료')->where('rgd_status3', '!=', '배송중');
                    //         });
                    //     })->orWhere(function ($query) {
                    //         $query->where('trans_no', '=', '출고')->doesntHave('receving_goods_delivery');
                    //     })->orWhereHas('receving_goods_delivery', function ($query) {
                    //         $query->where('rgd_status3', '=', '배송준비');
                    //     });
                    // } else if ($validated['status'] == "배송중") {
                    //     $schedule_shipment->whereHas('receving_goods_delivery', function ($query) {
                    //         $query->where('rgd_status3', '=', '배송중');
                    //     });
                    // } else if ($validated['status'] == "배송완료") {
                    //     $schedule_shipment->whereHas('receving_goods_delivery', function ($query) {
                    //         $query->where('rgd_status3', '=', '배송완료');
                    //     });
                    // }
                    if ($validated['status'] == '배송준비') {
                        $schedule_shipment->whereDoesntHave('receving_goods_delivery', function ($q) use ($validated) {
                            $q->where(DB::raw('lower(rgd_status3)'), 'like', '%' . strtolower($validated['status']) . '%');
                            $q->orWhere('rgd_status3', '!=', '배송준비');
                        });
                    } elseif ($validated['status'] == '배송중') {
                        $schedule_shipment->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                            $q->where('rgd_status3', '=', '배송중');
                        });
                    } elseif ($validated['status'] == '배송완료') {
                        $schedule_shipment->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                            $q->where('rgd_status3', '=', '배송완료');
                        });
                    }
                }

                if (isset($validated['order_id'])) {

                    //$schedule_shipment->where(DB::raw('lower(order_id)'), 'like', '%' . strtolower($validated['order_id']) . '%');
                }
                if (isset($validated['carrier'])) {
                    // if($validated['carrier'] == "배송중"){
                    //     $schedule_shipment->whereHas('receving_goods_delivery', function ($q){
                    //         $q->where('rgd_status3', '=', '배송중');
                    //     });
                    // }else if($validated['carrier'] == "배송완료"){
                    //     $schedule_shipment->whereHas('receving_goods_delivery', function ($q){
                    //         $q->where('rgd_status3', '=', '배송완료');
                    //     });
                    // }else if($validated['carrier'] == "배송준비"){
                    //     $schedule_shipment->whereHas('receving_goods_delivery', function ($q){
                    //         $q->where('rgd_status3', '=', '배송완료');
                    //     });
                    // }
                    if ($validated['carrier'] == '택배') {
                        $schedule_shipment->where(function ($q) use ($validated) {
                            $q->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                                //$q->where('rgd_status3', '=', $validated['carrier']);
                                return $q->where(DB::raw('lower(rgd_delivery_company)'), 'like', '%' . $validated['carrier'] . '%');
                            })->orwheredoesnthave('receving_goods_delivery');
                        });
                    } else {
                        $schedule_shipment->where(function ($q) use ($validated) {
                            $q->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                                //$q->where('rgd_status3', '=', $validated['carrier']);
                                return $q->where(DB::raw('lower(rgd_delivery_company)'), 'like', '%' . $validated['carrier'] . '%');
                            });
                        });
                    }
                }
                $schedule_shipment = $schedule_shipment->paginate($per_page, ['*'], 'page', $page);

                return response()->json($schedule_shipment);
            } else if ($validated['service'] == "보세화물") {
                // If per_page is null set default data = 15
                $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
                // If page is null set default data = 1
                $page = isset($validated['page']) ? $validated['page'] : 1;

                DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
                $user = Auth::user();
                if ($user->mb_type == 'shop') {

                    $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                        ->leftjoin('company', function ($join) {
                            $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                        })->leftjoin('company as parent_shop', function ($join) {
                            $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                        })->leftjoin('company as parent_spasys', function ($join) {
                            $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                        })->where('parent_shop.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                        ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                        ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                    $sub_2 = Import::select('ti_h_bl', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                        ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                    $sub_4 = Export::select('te_h_bl', 'connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                        ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
                    $sub_5 = ReceivingGoodsDelivery::select('*')->groupBy('is_no');

                    $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                        $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                    })

                        ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                            $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                        })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {
                            $leftjoin->on('ddd.te_carry_out_number', '=', 'nnn.is_no')->where('ddd.te_carry_out_number', '!=', null);
                            $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number');
                            $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                        })->orderBy('te_e_date', 'DESC');
                } else if ($user->mb_type == 'shipper') {

                    $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                        ->leftjoin('company', function ($join) {
                            $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                        })->leftjoin('company as parent_shop', function ($join) {
                            $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                        })->leftjoin('company as parent_spasys', function ($join) {
                            $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                        })->where('company.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                        ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                        ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                    $sub_2 = Import::select('ti_h_bl', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                        ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                    // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                    //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                    $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                        // ->leftjoin('receiving_goods_delivery', function ($join) {
                        //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                        // })
                        ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
                    $sub_5 = ReceivingGoodsDelivery::select('*')->groupBy('is_no');

                    $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                        $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                    })
                        // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                        //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                        // })
                        ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                            //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                            $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                        })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {
                            $leftjoin->on('ddd.te_carry_out_number', '=', 'nnn.is_no')->where('ddd.te_carry_out_number', '!=', null);
                            $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number');
                            $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                        })->orderBy('te_e_date', 'DESC');
                } else if ($user->mb_type == 'spasys') {

                    $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                        ->leftjoin('company as parent_spasys', function ($join) {
                            $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                        })
                        ->leftjoin('company', function ($join) {
                            $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                        })->leftjoin('company as parent_shop', function ($join) {
                            $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                        })


                        ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                        ->where('tie_is_date', '>=', '2022-01-04')
                        ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                        ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                    $sub_2 = Import::select('ti_h_bl', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')

                        ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                    // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                    //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                    $sub_4 = Export::select('te_h_bl', 'connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                        // ->leftjoin('receiving_goods_delivery', function ($join) {
                        //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                        // })
                        ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
                    $sub_5 = ReceivingGoodsDelivery::select('*')->groupBy('is_no');

                    $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                        $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                    })
                        // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                        //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                        // })
                        ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                            //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                            $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                        })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {
                            $leftjoin->on('ddd.te_carry_out_number', '=', 'nnn.is_no')->where('ddd.te_carry_out_number', '!=', null);
                            $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number');
                            $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                        })->orderBy('te_e_date', 'DESC');
                }

                if (isset($validated['from_date'])) {
                    $import_schedule->where('te_e_date', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                }

                if (isset($validated['to_date'])) {
                    $import_schedule->where('te_e_date', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                }

                if (isset($validated['co_parent_name'])) {

                    $import_schedule->where(DB::raw('lower(aaa.co_name_shop)'), 'like', '%' . $validated['co_parent_name'] . '%');
                }

                if (isset($validated['co_name'])) {

                    $import_schedule->where(DB::raw('lower(aaa.co_name)'), 'like', '%' . $validated['co_name'] . '%');
                }

                if (isset($validated['m_bl'])) {
                    $import_schedule->where(DB::raw('aaa.tie_m_bl'), 'like', '%' . strtolower($validated['m_bl']) . '%');
                }

                if (isset($validated['h_bl'])) {
                    $import_schedule->where(function ($query) use ($validated) {
                        $query->where(DB::raw('ddd.te_h_bl'), 'like', '%' . $validated['h_bl'] . '%')
                            ->orWhere(DB::raw('aaa.tie_h_bl'), 'like', '%' . $validated['h_bl'] . '%');
                    });
                }

                if (isset($validated['logistic_manage_number'])) {
                    $import_schedule->where('aaa.tie_logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
                }
                if (isset($validated['w_schedule_number'])) {
                    $import_schedule->where(DB::raw('te_carry_out_number'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%');
                }

                if (1 == 1) {

                    $tie_logistic_manage_number = $this->SQL($validated);

                    $import_schedule->whereNotIn('tie_logistic_manage_number', $tie_logistic_manage_number);
                    //$import_schedule->whereNotNull('ddd.te_logistic_manage_number');
                    //return DB::getQueryLog();
                }
                if (isset($validated['tie_status'])) {
                    if ($validated['tie_status'] == '반출') {

                        $tie_logistic_manage_number = $this->SQL($validated);
                        $import_schedule->whereNotIn('tie_logistic_manage_number', $tie_logistic_manage_number);
                        //$import_schedule->whereNotNull('ddd.te_logistic_manage_number');
                        //return DB::getQueryLog();
                    } else if ($validated['tie_status'] == '반입') {
                        $import_schedule->whereNotNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number');
                    } else if ($validated['tie_status'] == '반입예정') {
                        $import_schedule->whereNotNull('aaa.tie_logistic_manage_number')->whereNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number');
                    }
                }
                if (isset($validated['tie_status_2'])) {
                    // if ($validated['tie_status'] == '반출') {
                    //     $import_schedule->where('ddd.te_status_2', '=', $validated['tie_status_2']);
                    // } else if ($validated['tie_status'] == '반출승인') {
                    //     $import_schedule->where('ddd.tec_status_2', '=', $validated['tie_status_2']);
                    // } else if ($validated['tie_status'] == '반입') {
                    //     $import_schedule->where('bbb.ti_status_2', '=', $validated['tie_status_2']);
                    // } else if ($validated['tie_status'] == '반입예정') {
                    //     $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                    // }
                    $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                }

                if (isset($validated['order_id'])) {
                    $import_schedule->where('te_logistic_manage_number', 'like', '%' . $validated['order_id'] . '%');
                }

                // $import_schedule = $import_schedule->leftjoin('receiving_goods_delivery', function ($join) {
                //     $join->on('te_carry_out_number', '=', 'receiving_goods_delivery.is_no')->where('te_carry_out_number', '!=', null);
                //     $join->orOn('ti_carry_in_number', '=', 'receiving_goods_delivery.is_no')->whereNull('te_carry_out_number');
                //     $join->orOn('tie_logistic_manage_number', '=', 'receiving_goods_delivery.is_no')->whereNull('te_carry_out_number')->whereNull('ti_carry_in_number');
                // });
                if (isset($validated['status'])) {


                    // if($validated['status'] == "배송준비"){
                    // $import_schedule->whereHas('export.receiving_goods_delivery', function ($q) use ($validated) {
                    //     return $q->where('rgd_status3', '=', $validated['status']);
                    // });
                    //}
                    if ($validated['status'] == "배송준비") {
                        $import_schedule->where(function ($query) {
                            $query->whereNull('rgd_status3')->orWhere('rgd_status3', '=', '배송준비');
                            $query->where('rgd_status1', '!=', '반입');
                        });
                        //$import_schedule->whereNull('rgd_status3')->orWhere('rgd_status3','=','배송준비');
                        // $import_schedule->orwhereNull('rgd_status3');
                    } else {
                        $import_schedule->where(function ($query) use ($validated) {
                            $query->where('rgd_status3', '=', $validated['status']);
                            $query->where(function ($q) {
                                $q->where('rgd_status1', '!=', '반입')->orWhereNull('rgd_status1');
                            });
                        });
                    }
                }

                if (isset($validated['carrier'])) {
                    $import_schedule->whereHas('export.receiving_goods_delivery', function ($q) use ($validated) {
                        //return $q->where('rgd_delivery_company', '=', $validated['carrier']);
                        return $q->where(DB::raw('lower(rgd_delivery_company)'), 'like', '%' . $validated['carrier'] . '%');
                    });
                }
                $import_schedule = $import_schedule->paginate($per_page, ['*'], 'page', $page);
                // $import_schedule->setCollection(
                //     $import_schedule->getCollection()->map(function ($item) use ($validated) {
                //         if(isset($item->te_carry_out_number)){
                //             $is_no = $item->te_carry_out_number;
                //         }else if(isset($item->ti_carry_in_number)){
                //             $is_no = $item->ti_carry_in_number;
                //         }else {
                //             $is_no = $item->tie_logistic_manage_number;
                //         }

                //         //$is_no = isset($item->te_carry_out_number) ? $item->te_carry_out_number : isset($item->ti_carry_in_number) ? $item->ti_carry_in_number : $item->tie_logistic_manage_number;
                //         $rgd = ReceivingGoodsDelivery::where('is_no', $is_no)->first();

                //         $item->is_no = isset($is_no) ? $is_no : null;
                //         $item->rgd_status3 =  isset($rgd->rgd_status3) ? $rgd->rgd_status3 : null;
                //         $item->rgd_no =  isset($rgd->rgd_no) ? $rgd->rgd_no : null;


                //         return $item;
                //     })
                // );

                $status = DB::table('t_import_expected')
                    ->select('tie_status_2')
                    ->groupBy('tie_status_2')
                    ->get();

                $custom = collect(['status_filter' => $status]);

                $import_schedule = $custom->merge($import_schedule);

                DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
                //return DB::getQueryLog();
                return response()->json($import_schedule);
            } else if ($validated['service'] == "전체") {
                // If per_page is null set default data = 15
                $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
                // If page is null set default data = 1
                $page = isset($validated['page']) ? $validated['page'] : 1;
                $user = Auth::user();
                if ($user->mb_type == 'shop') {
                    $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                        $query->where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                            $q->where('rgd_status1', '=', '출고')->orWhereNull('rgd_status1');
                        })->whereHas('co_no.co_parent', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        });
                    })->orderBy('rgd_no', 'DESC');
                } else if ($user->mb_type == 'shipper') {
                    $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                        $query->where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                            $q->where('rgd_status1', '=', '출고')->orWhereNull('rgd_status1');
                        })->whereHas('co_no', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        });
                    })->orderBy('rgd_no', 'DESC');
                } else if ($user->mb_type == 'spasys') {
                    $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                        $query->where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                            $q->where('rgd_status1', '=', '출고')->orWhereNull('rgd_status1');
                        })->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        });
                    })->orderBy('rgd_no', 'DESC');
                }

                // if (isset($validated['from_date'])) {
                //     $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                // }

                // if (isset($validated['to_date'])) {
                //     $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                // }
                if (isset($validated['from_date'])) {
                    // $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                    $warehousing->whereHas('w_no', function ($q) use ($validated) {
                        return $q->where('w_completed_day',  '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                    });
                }

                if (isset($validated['to_date'])) {
                    //$warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                    $warehousing->whereHas('w_no', function ($q) use ($validated) {
                        return $q->where('w_completed_day',  '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
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

                if (isset($validated['order_id'])) {
                    $warehousing->whereHas('w_no', function ($q) use ($validated) {
                        return $q->where('logistic_manage_number', 'like', '%' . $validated['order_id'] . '%');
                    });
                }

                if (isset($validated['w_schedule_number'])) {
                    $warehousing->whereHas('w_no', function ($q) use ($validated) {
                        return $q->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number'] . '%');
                    });
                }

                if (isset($validated['status'])) {
                    $warehousing->where(function ($query) use ($validated) {
                        $query->where('rgd_status3', '=', $validated['status']);
                    });
                    //$warehousing->where('rgd_status3', '=', $validated['status']);
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
                if (isset($validated['carrier'])) {
                    $warehousing->where(DB::raw('lower(rgd_delivery_company)'), 'like', '%' . $validated['carrier'] . '%');
                }

                // if ($user->mb_type == 'shop') {
                //     $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->whereNotNull('trans_no')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                //         $q->where('co_no', $user->co_no);
                //     })->where(function ($q) {
                //         $q->whereHas('receving_goods_delivery', function ($q1) {
                //             $q1->where('rgd_status3', '!=', "배송완료");
                //             $q1->orwhereNull('rgd_status3');
                //         })->orwheredoesnthave('receving_goods_delivery');
                //     })->orderBy('ss_no', 'DESC');
                // } else if ($user->mb_type == 'shipper') {
                //     $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->whereNotNull('trans_no')->whereHas('ContractWms.company', function ($q) use ($user) {
                //         $q->where('co_no', $user->co_no);
                //     })->where(function ($q) {
                //         $q->whereHas('receving_goods_delivery', function ($q1) {
                //             $q1->where('rgd_status3', '!=', "배송완료");
                //             $q1->orwhereNull('rgd_status3');
                //         })->orwheredoesnthave('receving_goods_delivery');
                //     })->orderBy('ss_no', 'DESC');
                // } else if ($user->mb_type == 'spasys') {
                //     $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->whereNotNull('trans_no')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                //         $q->where('co_no', $user->co_no);
                //     })->where(function ($q) {
                //         $q->whereHas('receving_goods_delivery', function ($q1) {
                //             $q1->where('rgd_status3', '!=', "배송완료");
                //             $q1->orwhereNull('rgd_status3');
                //         })->orwheredoesnthave('receving_goods_delivery');
                //     })->orderBy('ss_no', 'DESC');
                // }
                if ($user->mb_type == 'shop') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })
                        ->orderBy('ss_no', 'DESC');
                } else if ($user->mb_type == 'shipper') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })
                        ->orderBy('ss_no', 'DESC');
                } else if ($user->mb_type == 'spasys') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })
                        ->orderBy('ss_no', 'DESC');
                }

                if (isset($validated['from_date'])) {
                    //$schedule_shipment->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                    // $schedule_shipment->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                    //     $q->where('rgd_delivery_schedule_day', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                    // });
                }

                if (isset($validated['to_date'])) {
                    //$schedule_shipment->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                    // $schedule_shipment->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                    //     $q->where('rgd_delivery_schedule_day', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                    // });
                }

                if (isset($validated['co_parent_name'])) {
                    $schedule_shipment->whereHas('ContractWms.company.co_parent', function ($query) use ($validated) {
                        $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    });
                }

                if (isset($validated['co_name'])) {
                    $schedule_shipment->whereHas('ContractWms.company', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    });
                }

                if (isset($validated['item_brand'])) {
                    $schedule_shipment->whereHas('schedule_shipment_info.item', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                    });
                }

                if (isset($validated['item_channel_name'])) {
                    $schedule_shipment->whereHas('schedule_shipment_info.item.item_channels', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(item_channel_name)'), 'like', '%' . strtolower($validated['item_channel_name']) . '%');
                    });
                }

                if (isset($validated['item_name'])) {
                    $schedule_shipment->whereHas('schedule_shipment_info.item', function ($q) use ($validated) {
                        return $q->where(DB::raw('lower(item_name)'), 'like', '%' . strtolower($validated['item_name']) . '%');
                    });
                }
                if (isset($validated['w_schedule_number'])) {
                    $schedule_shipment->where('ss_no', 'like', '%' . $validated['w_schedule_number'] . '%');
                }
                if (isset($validated['status'])) {
                    // if ($validated['status'] == "배송준비") {
                    //     $schedule_shipment->where(function ($query) {
                    //         $query->where('trans_no', '=', '출고')->whereHas('receving_goods_delivery', function ($q) {
                    //             $q->where('rgd_status3', '=', null)->where('rgd_status3', '!=', '배송완료')->where('rgd_status3', '!=', '배송중');
                    //         });
                    //     })->orWhere(function ($query) {
                    //         $query->where('trans_no', '=', '출고')->doesntHave('receving_goods_delivery');
                    //     })->orWhereHas('receving_goods_delivery', function ($query) {
                    //         $query->where('rgd_status3', '=', '배송준비');
                    //     });
                    // } else if ($validated['status'] == "배송중") {
                    //     $schedule_shipment->whereHas('receving_goods_delivery', function ($query) {
                    //         $query->where('rgd_status3', '=', '배송중');
                    //     });
                    // } else if ($validated['status'] == "배송완료") {
                    //     $schedule_shipment->whereHas('receving_goods_delivery', function ($query) {
                    //         $query->where('rgd_status3', '=', '배송완료');
                    //     });
                    // }
                    if ($validated['status'] == '배송준비') {
                        $schedule_shipment->whereDoesntHave('receving_goods_delivery', function ($q) use ($validated) {
                            $q->where(DB::raw('lower(rgd_status3)'), 'like', '%' . strtolower($validated['status']) . '%');
                            $q->orWhere('rgd_status3', '!=', '배송준비');
                        });
                    } elseif ($validated['status'] == '배송중') {
                        $schedule_shipment->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                            $q->where('rgd_status3', '=', '배송중');
                        });
                    } elseif ($validated['status'] == '배송완료') {
                        $schedule_shipment->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                            $q->where('rgd_status3', '=', '배송완료');
                        });
                    }
                }

                if (isset($validated['order_id'])) {

                    //$schedule_shipment->where(DB::raw('lower(order_id)'), 'like', '%' . strtolower($validated['order_id']) . '%');
                }
                if (isset($validated['carrier'])) {
                    // if($validated['carrier'] == "배송중"){
                    //     $schedule_shipment->whereHas('receving_goods_delivery', function ($q){
                    //         $q->where('rgd_status3', '=', '배송중');
                    //     });
                    // }else if($validated['carrier'] == "배송완료"){
                    //     $schedule_shipment->whereHas('receving_goods_delivery', function ($q){
                    //         $q->where('rgd_status3', '=', '배송완료');
                    //     });
                    // }else if($validated['carrier'] == "배송준비"){
                    //     $schedule_shipment->whereHas('receving_goods_delivery', function ($q){
                    //         $q->where('rgd_status3', '=', '배송완료');
                    //     });
                    // }
                    if ($validated['carrier'] == '택배') {
                        $schedule_shipment->where(function ($q) use ($validated) {
                            $q->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                                //$q->where('rgd_status3', '=', $validated['carrier']);
                                return $q->where(DB::raw('lower(rgd_delivery_company)'), 'like', '%' . $validated['carrier'] . '%');
                            })->orwheredoesnthave('receving_goods_delivery');
                        });
                    } else {
                        $schedule_shipment->where(function ($q) use ($validated) {
                            $q->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                                //$q->where('rgd_status3', '=', $validated['carrier']);
                                return $q->where(DB::raw('lower(rgd_delivery_company)'), 'like', '%' . $validated['carrier'] . '%');
                            });
                        });
                    }
                }

                DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
                $user = Auth::user();
                if ($user->mb_type == 'shop') {
                    $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                        ->leftjoin('company', function ($join) {
                            $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                        })->leftjoin('company as parent_shop', function ($join) {
                            $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                        })->leftjoin('company as parent_spasys', function ($join) {
                            $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                        })->where('parent_shop.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                        ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                        ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                    $sub_2 = Import::select('ti_h_bl', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                        ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                    // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                    //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                    $sub_4 = Export::select('te_h_bl', 'connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                        // ->leftjoin('receiving_goods_delivery', function ($join) {
                        //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                        // })
                        ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
                    $sub_5 = ReceivingGoodsDelivery::select('*')->groupBy('is_no');
                } else if ($user->mb_type == 'shipper') {
                    $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                        ->leftjoin('company', function ($join) {
                            $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                        })->leftjoin('company as parent_shop', function ($join) {
                            $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                        })->leftjoin('company as parent_spasys', function ($join) {
                            $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                        })->where('company.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                        ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                        ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                    $sub_2 = Import::select('ti_h_bl', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')

                        ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                    // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                    //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                    $sub_4 = Export::select('te_h_bl', 'connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                        // ->leftjoin('receiving_goods_delivery', function ($join) {
                        //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                        // })
                        ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
                    $sub_5 = ReceivingGoodsDelivery::select('*')->groupBy('is_no');
                } else if ($user->mb_type == 'spasys') {
                    //FIX NOT WORK 'with'
                    $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                        ->leftjoin('company as parent_spasys', function ($join) {
                            $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                        })
                        ->leftjoin('company', function ($join) {
                            $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                        })->leftjoin('company as parent_shop', function ($join) {
                            $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                        })
                        // ->leftjoin('company', function ($join) {
                        //     $join->on('company.co_license', '=', 't_import_expected.tie_co_license');

                        // })->leftjoin('company as parent_shop', function ($join) {
                        //     $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                        // })->leftjoin('company as parent_spasys', function ($join) {
                        //     $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                        //     $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                        // })
                        //->where('parent_spasys.co_no', $user->co_no)

                        ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                        ->where('tie_is_date', '>=', '2022-01-04')
                        ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                        ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                    $sub_2 = Import::select('ti_h_bl', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                        // ->leftjoin('receiving_goods_delivery', function ($join) {
                        //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                        // })
                        ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                    // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                    //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                    $sub_4 = Export::select('te_h_bl', 'connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                        // ->leftjoin('receiving_goods_delivery', function ($join) {
                        //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                        // })
                        ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
                    $sub_5 = ReceivingGoodsDelivery::select('*')->groupBy('is_no');


                    //return DB::getQueryLog();
                    //END FIX NOT WORK 'with'
                }

                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
                    // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                    //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                    // })
                    ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                        //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                        $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                    })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {
                        $leftjoin->on('ddd.te_carry_out_number', '=', 'nnn.is_no')->where('ddd.te_carry_out_number', '!=', null);
                        $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number');
                        $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                    })->orderBy('te_e_date', 'DESC');

                //return DB::getQueryLog();

                //$sql2 = DB::table('t_export')->select('te_logistic_manage_number','te_carry_out_number')->groupBy('te_logistic_manage_number','te_carry_out_number')->get();

                //$import_schedule = ImportExpected::with(['import','company'])->orderBy('tie_no', 'DESC');

                if (isset($validated['from_date'])) {
                    $import_schedule->where('te_e_date', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                }

                if (isset($validated['to_date'])) {
                    $import_schedule->where('te_e_date', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                }

                if (isset($validated['co_parent_name'])) {
                    // $import_schedule->whereHas('company.co_parent', function ($query) use ($validated) {
                    //     $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    // });
                    $import_schedule->where(DB::raw('lower(aaa.co_name_shop)'), 'like', '%' . $validated['co_parent_name'] . '%');
                }

                if (isset($validated['co_name'])) {
                    // $import_schedule->whereHas('company', function ($q) use ($validated) {
                    //     return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                    // });
                    $import_schedule->where(DB::raw('lower(aaa.co_name)'), 'like', '%' . $validated['co_name'] . '%');
                }

                if (isset($validated['m_bl'])) {
                    $import_schedule->where(DB::raw('aaa.tie_m_bl'), 'like', '%' . strtolower($validated['m_bl']) . '%');
                }

                if (isset($validated['h_bl'])) {
                    $import_schedule->where(function ($query) use ($validated) {
                        $query->where(DB::raw('ddd.te_h_bl'), 'like', '%' . $validated['h_bl'] . '%')
                            ->orWhere(DB::raw('aaa.tie_h_bl'), 'like', '%' . $validated['h_bl'] . '%');
                    });
                }
                if (isset($validated['w_schedule_number'])) {
                    $import_schedule->where(DB::raw('te_carry_out_number'), 'like', '%' . strtolower($validated['w_schedule_number']) . '%');
                }
                if (isset($validated['logistic_manage_number'])) {
                    $import_schedule->where('aaa.tie_logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
                }

                if (1 == 1) {

                    $tie_logistic_manage_number = $this->SQL($validated);

                    $import_schedule->whereNotIn('tie_logistic_manage_number', $tie_logistic_manage_number);
                }

                if (isset($validated['tie_status'])) {
                    if ($validated['tie_status'] == '반출') {

                        $tie_logistic_manage_number = $this->SQL($validated);
                        $import_schedule->whereNotIn('tie_logistic_manage_number', $tie_logistic_manage_number);
                        //$import_schedule->whereNotNull('ddd.te_logistic_manage_number');
                        //return DB::getQueryLog();
                    } else if ($validated['tie_status'] == '반입') {
                        $import_schedule->whereNotNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number');
                    } else if ($validated['tie_status'] == '반입예정') {
                        $import_schedule->whereNotNull('aaa.tie_logistic_manage_number')->whereNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number');
                    }
                }
                if (isset($validated['tie_status_2'])) {
                    // if ($validated['tie_status'] == '반출') {
                    //     $import_schedule->where('ddd.te_status_2', '=', $validated['tie_status_2']);
                    // } else if ($validated['tie_status'] == '반출승인') {
                    //     $import_schedule->where('ddd.tec_status_2', '=', $validated['tie_status_2']);
                    // } else if ($validated['tie_status'] == '반입') {
                    //     $import_schedule->where('bbb.ti_status_2', '=', $validated['tie_status_2']);
                    // } else if ($validated['tie_status'] == '반입예정') {
                    //     $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                    // }
                    $import_schedule->where('aaa.tie_status_2', '=', $validated['tie_status_2']);
                }

                if (isset($validated['order_id'])) {
                    $import_schedule->where('te_logistic_manage_number', 'like', '%' . $validated['order_id'] . '%');
                }

                $import_schedule = $import_schedule->leftjoin('receiving_goods_delivery', function ($join) {
                    // $join->on('te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // $join->orOn('ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    // $join->orOn('tie_logistic_manage_number', '=', 'receiving_goods_delivery.is_no');
                    $join->on('te_carry_out_number', '=', 'receiving_goods_delivery.is_no')->where('te_carry_out_number', '!=', null);
                    $join->orOn('ti_carry_in_number', '=', 'receiving_goods_delivery.is_no')->whereNull('te_carry_out_number');
                    $join->orOn('tie_logistic_manage_number', '=', 'receiving_goods_delivery.is_no')->whereNull('te_carry_out_number')->whereNull('ti_carry_in_number');
                });

                if (isset($validated['status'])) {



                    // if ($validated['status'] == "배송준비") {
                    //     $import_schedule->where('rgd_status3', '=', null);
                    //     //$import_schedule->orwhereNull('rgd_status3');
                    // } else {
                    //     $import_schedule->where('rgd_status3', '=', $validated['status']);
                    // }
                    if ($validated['status'] == "배송준비") {
                        $import_schedule->where(function ($query) {
                            $query->whereNull('rgd_status3')->orWhere('rgd_status3', '=', '배송준비');
                            $query->where('rgd_status1', '!=', '반입');
                        });
                        // $import_schedule->orwhereNull('rgd_status3');
                    } else {
                        $import_schedule->where(function ($query) use ($validated) {
                            $query->where('rgd_status3', '=', $validated['status']);
                            $query->where('rgd_status1', '!=', '반입');
                        });
                    }
                }

                if (isset($validated['carrier'])) {
                    $import_schedule->whereHas('export.receiving_goods_delivery', function ($q) use ($validated) {
                        // return $q->where('rgd_delivery_company', '=', $validated['carrier']);
                        return $q->where(DB::raw('lower(rgd_delivery_company)'), 'like', '%' . $validated['carrier'] . '%');
                    });
                }



                $warehousing = $warehousing->get();

                $schedule_shipment = $schedule_shipment->get();

                $import_schedule = $import_schedule->get();

                //$final =  $warehousing->merge($schedule_shipment)->merge($import_schedule);
                $final = collect($warehousing)->map(function ($q) {

                    return $q;
                });
                $final2 = collect($schedule_shipment)->map(function ($q) {

                    return $q;
                });
                $final3 = collect($import_schedule)->map(function ($q) {

                    return $q;
                });
                $final4 = $final->merge($final2)->merge($final3);

                $data = $this->paginate($final4, $validated['per_page'], $validated['page']);
                //$final = $final->paginate($per_page, ['*'], 'page', $page);

                DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

                return $data;
            }
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getWarehousingDelivery3(WarehousingSearchRequest $request) //page715 show delivery

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                        $q->where('rgd_status1', '=', '출고')->where('rgd_status3', '!=', '배송완료')->orWhereNull('rgd_status1');
                    })->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                        $q->where('rgd_status1', '=', '출고')->where('rgd_status3', '!=', '배송완료')->orWhereNull('rgd_status1');
                    })->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                        $q->where('rgd_status1', '=', '출고')->where('rgd_status3', '!=', '배송완료')->orWhereNull('rgd_status1');
                    })->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
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

            if (isset($validated['order_id'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['order_id'] . '%');
                });
            }

            if (isset($validated['status'])) {
                $warehousing->where('rgd_status3', '=', $validated['status']);
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

            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getWarehousingDelivery2(WarehousingSearchRequest $request) //page715 show delivery

    {
        try {

            $validated = $request->validated();
            $user = Auth::user();
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            if ($user->mb_type == 'shop') {
                $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms'])->whereNotNull('trans_no')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('ss_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms'])->whereNotNull('trans_no')->where('status', '!=', '출고예정')->whereHas('ContractWms.company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('ss_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms'])->whereNotNull('trans_no')->where('status', '!=', '출고예정')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('ss_no', 'DESC');
            }

            if (isset($validated['from_date'])) {
                $schedule_shipment->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $schedule_shipment->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $schedule_shipment->whereHas('ContractWms.company.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }

            if (isset($validated['co_name'])) {
                $schedule_shipment->whereHas('ContractWms.company', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['item_brand'])) {
                $schedule_shipment->whereHas('schedule_shipment_info.item', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }

            if (isset($validated['item_channel_name'])) {
                $schedule_shipment->whereHas('schedule_shipment_info.item.item_channels', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_channel_name)'), 'like', '%' . strtolower($validated['item_channel_name']) . '%');
                });
            }

            if (isset($validated['item_name'])) {
                $schedule_shipment->whereHas('schedule_shipment_info.item', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_name)'), 'like', '%' . strtolower($validated['item_name']) . '%');
                });
            }

            if (isset($validated['status'])) {
                if ($validated['status'] == "배송준비") {
                    $schedule_shipment->where('status', '=', 1);
                } else if ($validated['status'] == "배송중") {
                    $schedule_shipment->where('status', '=', 7);
                } else {
                    $schedule_shipment->where('status', '=', 8);
                }
            }

            if (isset($validated['order_id'])) {

                $schedule_shipment->where(DB::raw('lower(order_id)'), 'like', '%' . strtolower($validated['order_id']) . '%');
            }

            $schedule_shipment = $schedule_shipment->paginate($per_page, ['*'], 'page', $page);

            return response()->json($schedule_shipment);
        } catch (\Exception $e) {
            Log::error($e);

            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getWarehousingDelivery1(WarehousingSearchRequest $request) //page715 show delivery

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $import_schedule = ImportExpected::with(['import', 'company', 'receiving_goods_delivery'])->whereHas('company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->groupBy('t_import_expected.tie_logistic_manage_number')->leftjoin('t_export', 't_import_expected.tie_logistic_manage_number', '=', 't_export.te_logistic_manage_number')
                    ->select(['t_import_expected.*', 't_export.te_logistic_manage_number', 't_export.te_carry_out_number'])
                    ->where('tie_is_date', '>=', '2022-01-04')->where('tie_is_date', '<=', '2022-10-04')
                    ->groupBy('t_export.te_logistic_manage_number', 't_export.te_carry_out_number')->orderBy('t_export.te_carry_out_number', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $import_schedule = ImportExpected::with(['import', 'company', 'receiving_goods_delivery'])->whereHas('company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->groupBy('t_import_expected.tie_logistic_manage_number')->leftjoin('t_export', 't_import_expected.tie_logistic_manage_number', '=', 't_export.te_logistic_manage_number')
                    ->select(['t_import_expected.*', 't_export.te_logistic_manage_number', 't_export.te_carry_out_number'])
                    ->where('tie_is_date', '>=', '2022-01-04')->where('tie_is_date', '<=', '2022-10-04')
                    ->groupBy('t_export.te_logistic_manage_number', 't_export.te_carry_out_number')->orderBy('t_export.te_carry_out_number', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $import_schedule = ImportExpected::with(['import', 'company', 'receiving_goods_delivery'])->whereHas('company.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->groupBy('t_import_expected.tie_logistic_manage_number')->leftjoin('t_export', 't_import_expected.tie_logistic_manage_number', '=', 't_export.te_logistic_manage_number')
                    ->select(['t_import_expected.*', 't_export.te_logistic_manage_number', 't_export.te_carry_out_number'])
                    ->where('tie_is_date', '>=', '2022-01-04')->where('tie_is_date', '<=', '2022-10-04')
                    ->groupBy('t_export.te_logistic_manage_number', 't_export.te_carry_out_number')->orderBy('t_export.te_carry_out_number', 'DESC');
            }

            //return DB::getQueryLog();

            //$sql2 = DB::table('t_export')->select('te_logistic_manage_number','te_carry_out_number')->groupBy('te_logistic_manage_number','te_carry_out_number')->get();

            //$import_schedule = ImportExpected::with(['import','company'])->orderBy('tie_no', 'DESC');

            if (isset($validated['from_date'])) {
                $import_schedule->where('t_import_expected.created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $import_schedule->where('t_import_expected.created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $import_schedule->whereHas('company.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }

            if (isset($validated['co_name'])) {
                $import_schedule->whereHas('company', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['m_bl'])) {
                $import_schedule->where(DB::raw('tie_m_bl'), 'like', '%' . strtolower($validated['m_bl']) . '%');
            }

            if (isset($validated['h_bl'])) {
                $import_schedule->where(DB::raw('tie_h_bl'), 'like', '%' . strtolower($validated['h_bl']) . '%');
            }

            if (isset($validated['logistic_manage_number'])) {
                $import_schedule->where('logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
            }
            if (isset($validated['tie_status'])) {
                $import_schedule->where('tie_status', '=', $validated['tie_status']);
            }
            if (isset($validated['tie_status_2'])) {
                $import_schedule->where('tie_status_2', '=', $validated['tie_status_2']);
            }

            if (isset($validated['order_id'])) {
                $import_schedule->where('t_export.te_carry_out_number', 'like', '%' . $validated['order_id'] . '%');
            }

            if (isset($validated['status'])) {

                // $import_schedule->leftJoin('t_export', function ($query) use ($validated) {
                //     $query->on('tie_logistic_manage_number', '=', 't_export.te_logistic_manage_number')->whereHas('receiving_goods_delivery',function($query) use ($validated) {
                //         return $query->where(DB::raw('lower(receiving_goods_delivery.rgd_status3)'), '=', $validated['status']);
                //      });
                // });
                // $import_schedule->leftJoin('receiving_goods_delivery', function($q) use ($validated) {

                //     return $q->on('t_export.te_carry_out_number','=','receiving_goods_delivery.is_no')->where(DB::raw('lower(receiving_goods_delivery.rgd_status3)'), '=', $validated['status']);
                // });
                // $import_schedule->leftJoin('receiving_goods_delivery', function($q) use ($validated) {

                //     return $q->on('te_carry_out_number','=','receiving_goods_delivery.is_no')->where(DB::raw('lower(receiving_goods_delivery.rgd_status3)'), '=', $validated['status']);
                // });

            }

            // if (isset($validated['import_schedule_status1']) || isset($validated['import_schedule_status2'])) {
            //     $import_schedule->where(function($query) use ($validated) {
            //         $query->orwhere('import_schedule_status', '=', $validated['import_schedule_status1']);
            //         $query->orWhere('import_schedule_status', '=', $validated['import_schedule_status2']);
            //     });
            // }

            //$members = Member::where('mb_no', '!=', 0)->get();

            $import_schedule = $import_schedule->paginate($per_page, ['*'], 'page', $page);

            $status = DB::table('t_import_expected')
                ->select('tie_status_2')
                ->groupBy('tie_status_2')
                ->get();

            $custom = collect(['status_filter' => $status]);

            $import_schedule = $custom->merge($import_schedule);

            DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            //return DB::getQueryLog();
            return response()->json($import_schedule);
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
            if ($user->mb_type == 'shop') {
                $warehousing2 = Warehousing::where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->whereNull('w_children_yn')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->whereNotIn('w_no', $w_import_no)->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->where('rgd_status2', '=', '작업완료')->whereNull('w_children_yn')->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orwhere('rgd_status1', '=', '출고예정')->where('w_category_name', '=', '유통가공')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orwhere('rgd_status1', '=', '출고예정 취소')->where('w_category_name', '=', '유통가공')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('created_at', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $warehousing2 = Warehousing::where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->whereNull('w_children_yn')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->whereNotIn('w_no', $w_import_no)->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->where('w_category_name', '=', '유통가공')->where('rgd_status2', '=', '작업완료')->whereNull('w_children_yn')->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orwhere('rgd_status1', '=', '출고예정')->where('w_category_name', '=', '유통가공')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orwhere('rgd_status1', '=', '출고예정 취소')->where('w_category_name', '=', '유통가공')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('created_at', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $warehousing2 = Warehousing::where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->whereNull('w_children_yn')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });

                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->whereNotIn('w_no', $w_import_no)->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->where('rgd_status2', '=', '작업완료')->whereNull('w_children_yn')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orwhere('rgd_status1', '=', '출고예정')->where('w_category_name', '=', '유통가공')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orwhere('rgd_status1', '=', '출고예정 취소')->where('w_category_name', '=', '유통가공')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('created_at', 'DESC');
            }

            $warehousing->whereNull('rgd_parent_no');

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
            if (isset($validated['connection_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    $q->where('connection_number', 'like', '%' . $validated['connection_number'] . '%');
                });
            }

            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%', 'and', 'w_type', '!=', 'IW');
                });
            }

            if (isset($validated['w_schedule_number2'])) {

                $warehousing->where(function ($q) use ($validated) {
                    $q->whereHas('w_no', function ($q1) use ($validated) {
                        $q1->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number2'] . '%', 'and', 'w_type', '=', 'IW');
                    })->orWhereHas('w_no.w_import_parent', function ($q2) use ($validated) {
                        $q2->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number2'] . '%');
                    });
                });
            }
            // $warehousing->get();
            // return DB::getQueryLog();
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

            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) {
                    $item->total_wi_number = WarehousingItem::where('w_no', $item->w_no)->sum('wi_number');

                    $item->total_item = WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_shipper')->sum('wi_number');
                    if (!empty($item['warehousing']['warehousing_item'][0]) && isset($item['warehousing']['warehousing_item'][0]['item'])) {
                        $first_name_item = $item['warehousing']['warehousing_item'][0]['item']['item_name'];
                        $total_item = $item['warehousing']['warehousing_item']->count();
                        $final_total = ($total_item   - 1);
                        if ($final_total <= 0) {
                            $item->first_item_name_total = $first_name_item ;
                        } else {
                            $item->first_item_name_total = $first_name_item . '외' . ' ' . $final_total . '건';
                        }
                    } else {
                        $item->first_item_name_total = '';
                    }

                    return $item;
                })
            );
            return response()->json($warehousing);
        } catch (\Exception $e) {
            Log::error($e);
            // return $e;
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
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')
                        ->where('rgd_status1', '=', '출고')
                        ->where('rgd_status2', '=', '작업완료')
                        ->where('w_category_name', '=', '유통가공')
                        ->where(function ($q) {
                            $q->where(function ($query) {
                                $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                            })
                                ->orWhereNull('rgd_status4');
                        })->whereHas('co_no.co_parent', function ($q2) use ($user) {
                            $q2->where('co_no', $user->co_no);
                        });
                });
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')
                        ->where('rgd_status1', '=', '출고')
                        ->where('rgd_status2', '=', '작업완료')
                        ->where('w_category_name', '=', '유통가공')
                        ->where(function ($q) {
                            $q->where(function ($query) {
                                $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                            })
                                ->orWhereNull('rgd_status4');
                        })->whereHas('co_no', function ($q2) use ($user) {
                            $q2->where('co_no', $user->co_no);
                        });
                });
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')
                        ->where('rgd_status1', '=', '출고')
                        ->where('rgd_status2', '=', '작업완료')
                        ->where('w_category_name', '=', '유통가공')
                        ->where(function ($q) {
                            $q->where(function ($query) {
                                $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                            })
                                ->orWhereNull('rgd_status4');
                        })->whereHas('co_no.co_parent.co_parent', function ($q2) use ($user) {
                            $q2->where('co_no', $user->co_no);
                        });
                });
            }
            $warehousing->whereNull('rgd_parent_no');

            if (isset($validated['from_date'])) {
                $warehousing->where('warehousing.w_completed_day', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('warehousing.w_completed_day', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
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
                    return $q->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number'] . '%');
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
            if (isset($validated['w_schedule_number2'])) {

                $warehousing->where(function ($q) use ($validated) {
                    $q->whereHas('w_no', function ($q1) use ($validated) {
                        $q1->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number2'] . '%', 'and', 'w_type', '=', 'IW');
                    })->orWhereHas('w_no.w_import_parent', function ($q2) use ($validated) {
                        $q2->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number2'] . '%');
                    });
                });
            }
            if (isset($validated['connection_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    $q->where('connection_number', 'like', '%' . $validated['connection_number'] . '%');
                });
            }
            if (isset($validated['rgd_receiver'])) {
                $warehousing->where('rgd_receiver', 'like', '%' . $validated['rgd_receiver'] . '%');
            }
            if (isset($validated['rgd_contents'])) {
                $warehousing->where('rgd_contents', '=', $validated['rgd_contents']);
            }
            $warehousing->orderBy('w_completed_day', 'DESC');
            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);

            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) {

                    $item->total_item = WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_spasys')->sum('wi_number');
                    if (!empty($item['warehousing']['warehousing_item'][0]) && isset($item['warehousing']['warehousing_item'][0]['item'])) {
                        $first_name_item = $item['warehousing']['warehousing_item'][0]['item']['item_name'];
                        $total_item = $item['warehousing']['warehousing_item']->count();
                        $final_total = ($total_item / 2   - 1);
                        if ($final_total <= 0) {
                            $item->first_item_name_total = $first_name_item;
                        } else {
                            $item->first_item_name_total = $first_name_item . '외' . ' ' . $final_total . '건';
                        }
                    } else {
                        $item->first_item_name_total = '';
                    }

                    return $item;
                })
            );

            //return DB::getQueryLog();

            return response()->json($warehousing);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getWarehousingByRgd($rgd_no, $type, Request $request)
    {
        try {
            $check_cofirm = 0;
            $check_paid = 0;
            $user = Auth::user();
            $pathname = $request->header('Pathname');
            $is_check_page = str_contains($pathname, '_check');
            
            if ($type == 'monthly') {
                $rgd = ReceivingGoodsDelivery::with(['rgd_child', 'warehousing', 'rgd_parent_payment', 'rate_data_general'])->where('rgd_no', $rgd_no)->first();
                $contract = Contract::where('co_no',  $user->co_no)->first();
                if (isset($contract->c_calculate_deadline_yn)) {
                    $rgd['c_calculate_deadline_yn'] = $contract->c_calculate_deadline_yn;
                } else {
                    $rgd['c_calculate_deadline_yn'] = 'n';
                }
                $w_no = $rgd->w_no;
                $co_no = $rgd->warehousing->co_no;
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();
                $created_at = Carbon::createFromFormat('Y.m.d H:i:s', $rgd->created_at->format('Y.m.d H:i:s'));

                $start_date = $created_at->startOfMonth()->toDateString();
                $end_date = $created_at->endOfMonth()->toDateString();

                $rgds = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general', 'rgd_child', 'rate_meta_data', 'rate_meta_data_parent'])
                    ->whereHas('w_no', function ($q) use ($co_no) {
                        $q
                            ->where('w_category_name', '유통가공');
                    })->whereHas('mb_no', function ($q) {
                        if (Auth::user()->mb_type == 'spasys') {
                            $q->where('mb_type', 'spasys');
                        } else if (Auth::user()->mb_type == 'shop') {
                            $q->where('mb_type', 'shop');
                        }
                    })
                    // ->doesntHave('rgd_child')
                    ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($start_date)))
                    ->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($end_date)))
                    ->where('rgd_status1', '=', '입고')

                    ->where('rgd_bill_type', $user->mb_type == 'spasys' ? 'expectation_monthly_spasys' : 'expectation_monthly_shop')
                    ->where(function ($q) {
                        $q->where('rgd_status5', '!=', 'cancel')
                            ->orWhereNull('rgd_status5');
                    })
                    ->where(function ($q) {
                        $q->where('rgd_status5', '!=', 'issued')
                            ->orWhereNull('rgd_status5');
                    })
                    ->whereDoesntHave('rgd_child')
                    ->get();

                foreach ($rgds as $key => $rgd2) {
                    $rmd = RateMetaData::where('rgd_no', $rgd2['rgd_parent_no'])->where('set_type', 'work_monthly')->first();
                    if (isset($rmd->rmd_no)) {
                        $work_sum = RateData::where('rmd_no', $rmd->rmd_no)->sum('rd_data4');
                        $rgd2['work_sum'] = $work_sum;
                    } else {
                        $rgd2['work_sum'] = 0;
                    }
                }

                $warehousing = Warehousing::with(['w_ew_many' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);
                }, 'w_ew' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);
                }, 'co_no', 'warehousing_request', 'w_import_parent', 'warehousing_child'])->withSum('warehousing_item_IW_spasys_confirm', 'wi_number')->find($w_no);
                $adjustment_group = AdjustmentGroup::where('co_no', '=', $user->mb_type == 'spasys' || ($is_check_page == true && $user->mb_type == 'shop') ? $warehousing->company->co_parent->co_no : $warehousing->co_no)->first();
                $adjustment_group2 = AdjustmentGroup::select(['ag_name'])->where('co_no', '=', $user->mb_type == 'spasys' || ($is_check_page == true && $user->mb_type == 'shop') ? $warehousing->company->co_parent->co_no : $warehousing->co_no)->get();

                $time = str_replace('-', '.', $start_date) . ' ~ ' . str_replace('-', '.', $end_date);
            }
            if ($type == 'monthly_edit') {
                $rgd = ReceivingGoodsDelivery::with(['rgd_child', 'warehousing' , 'rgd_parent_payment', 'rate_data_general'])->where('rgd_no', $rgd_no)->first();
                $contract = Contract::where('co_no',  $user->co_no)->first();
                if (isset($contract->c_calculate_deadline_yn)) {
                    $rgd['c_calculate_deadline_yn'] = $contract->c_calculate_deadline_yn;
                } else {
                    $rgd['c_calculate_deadline_yn'] = 'n';
                }
                $w_no = $rgd->w_no;
                $co_no = $rgd->warehousing->co_no;
                $created_at = Carbon::createFromFormat('Y.m.d H:i:s', $rgd->created_at->format('Y.m.d H:i:s'));

                $start_date = $created_at->startOfMonth()->toDateString();
                $end_date = $created_at->endOfMonth()->toDateString();
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();

                $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general'])
                    ->whereHas('w_no', function ($q) use ($co_no) {
                        $q
                            ->where('w_category_name', '유통가공');
                    })
                    ->where('rgd_settlement_number', $rgd->rgd_settlement_number)

                    ->get();

                foreach ($rgds as $key => $rgd2) {
                    $rmd = RateMetaData::where('rgd_no', $rgd2['rgd_parent_no'])->where('set_type', 'work_monthly')->first();
                    if (isset($rmd->rmd_no)) {
                        $work_sum = RateData::where('rmd_no', $rmd->rmd_no)->sum('rd_data4');
                        $rgd2['work_sum'] = $work_sum;
                    } else {
                        $rgd2['work_sum'] = 0;
                    }
                }
                $warehousing = Warehousing::with(['w_ew_many' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);
                }, 'w_ew' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);
                }, 'co_no', 'warehousing_request', 'w_import_parent', 'warehousing_child'])->withSum('warehousing_item_IW_spasys_confirm', 'wi_number')->find($w_no);
                $adjustment_group = AdjustmentGroup::where('co_no', '=', $user->mb_type == 'spasys' || ($is_check_page == true && $user->mb_type == 'shop') ? $warehousing->company->co_parent->co_no : $warehousing->co_no)->first();
                $adjustment_group2 = AdjustmentGroup::select(['ag_name'])->where('co_no', '=', $user->mb_type == 'spasys' || ($is_check_page == true && $user->mb_type == 'shop') ? $warehousing->company->co_parent->co_no : $warehousing->co_no)->get();

                $time = str_replace('-', '.', $start_date) . ' ~ ' . str_replace('-', '.', $end_date);
            } else if ($type == 'additional_monthly') {
                $rgd = ReceivingGoodsDelivery::with(['rgd_child', 'warehousing'])->where('rgd_no', $rgd_no)->first();
                $rgd = ReceivingGoodsDelivery::with(['rgd_child', 'warehousing'])->where('rgd_no', $rgd->rgd_parent_no)->first();

                $w_no = $rgd->w_no;
                $co_no = $rgd->warehousing->co_no;
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();
                $created_at = Carbon::createFromFormat('Y.m.d H:i:s', $rgd->created_at->format('Y.m.d H:i:s'));

                $start_date = $created_at->startOfMonth()->toDateString();
                $end_date = $created_at->endOfMonth()->toDateString();

                $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general'])
                    ->whereHas('w_no', function ($q) use ($co_no) {
                        $q
                            ->where('w_category_name', '유통가공');
                    })
                    ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($start_date)))
                    ->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($end_date)))
                    ->where('rgd_status1', '=', '입고')
                    ->where('rgd_settlement_number', $rgd->rgd_settlement_number)
                    ->get();

                foreach ($rgds as $key => $rgd2) {
                    $rmd = RateMetaData::where('rgd_no', $rgd2['rgd_parent_no'])->where('set_type', 'work_monthly')->first();
                    if (isset($rmd->rmd_no)) {
                        $work_sum = RateData::where('rmd_no', $rmd->rmd_no)->sum('rd_data4');
                        $rgd2['work_sum'] = $work_sum;
                    } else {
                        $rgd2['work_sum'] = 0;
                    }
                }

                $warehousing = Warehousing::with(['w_ew_many' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);
                }, 'w_ew' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);
                }, 'co_no', 'warehousing_request', 'w_import_parent', 'warehousing_child'])->withSum('warehousing_item_IW_spasys_confirm', 'wi_number')->find($w_no);
                $adjustment_group = AdjustmentGroup::where('co_no', '=', $user->mb_type == 'spasys' || ($is_check_page == true && $user->mb_type == 'shop') ? $warehousing->company->co_parent->co_no : $warehousing->co_no)->first();
                $adjustment_group2 = AdjustmentGroup::select(['ag_name'])->where('co_no', '=', $user->mb_type == 'spasys' || ($is_check_page == true && $user->mb_type == 'shop') ? $warehousing->company->co_parent->co_no : $warehousing->co_no)->get();

                $time = str_replace('-', '.', $start_date) . ' ~ ' . str_replace('-', '.', $end_date);
                $rgd = ReceivingGoodsDelivery::with(['rgd_child', 'warehousing'])->where('rgd_no', $rgd_no)->first();
                $contract = Contract::where('co_no',  $user->co_no)->first();
                if (isset($contract->c_calculate_deadline_yn)) {
                    $rgd['c_calculate_deadline_yn'] = $contract->c_calculate_deadline_yn;
                } else {
                    $rgd['c_calculate_deadline_yn'] = 'n';
                }
            } else {
                $rgd = ReceivingGoodsDelivery::with(['rgd_child', 'warehousing', 'rgd_parent_payment', 'rate_data_general'])->where('rgd_no', $rgd_no)->first();

                $contract = Contract::where('co_no',  $user->co_no)->first();
                if (isset($contract->c_calculate_deadline_yn)) {
                    $rgd['c_calculate_deadline_yn'] = $contract->c_calculate_deadline_yn;
                } else {
                    $rgd['c_calculate_deadline_yn'] = 'n';
                }

                $w_no = $rgd->w_no;
                $check_cofirm = ReceivingGoodsDelivery::where('rgd_status5', 'confirmed')->where('rgd_bill_type', 'final')->where('w_no', $w_no)->get()->count();
                $check_paid = ReceivingGoodsDelivery::where('rgd_status5', 'paid')->where('rgd_bill_type', 'additional')->where('w_no', $w_no)->get()->count();

                $warehousing = Warehousing::with(['w_ew_many' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);
                }, 'w_ew' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);
                }, 'co_no', 'warehousing_request', 'w_import_parent', 'warehousing_child'])->withSum('warehousing_item_IW_spasys_confirm', 'wi_number')->find($w_no);

                $adjustment_group2 = AdjustmentGroup::select(['ag_name'])->where('co_no', '=', $user->mb_type == 'spasys' || ($is_check_page == true && $user->mb_type == 'shop') ? $warehousing->company->co_parent->co_no : $warehousing->co_no)->get();
                $adjustment_group = AdjustmentGroup::where('co_no', '=', $user->mb_type == 'spasys' || ($is_check_page == true && $user->mb_type == 'shop') ? $warehousing->company->co_parent->co_no : $warehousing->co_no)->first();
            }
            $adjustment_group_choose = '';
            $rdg = RateDataGeneral::with(['rgd_no_final'])->where('rgd_no', $rgd_no)->first();
            if ($rdg) {
                $adjustment_group_choose = AdjustmentGroup::where('co_no', '=', $user->mb_type == 'spasys' || ($is_check_page == true && $user->mb_type == 'shop') ? $warehousing->company->co_parent->co_no : $warehousing->co_no)->where('ag_name', '=', $rdg->rdg_set_type)->first();
            }
            return response()->json(
                [
                    'message' => Messages::MSG_0007,
                    'adjustment_group2' => $adjustment_group2,
                    'rgds' => isset($rgds) ? $rgds : null,
                    'adjustment_group_choose' => $adjustment_group_choose,
                    'adjustment_group' => $adjustment_group,
                    'warehousing' => isset($warehousing) ? $warehousing : null,
                    'rgd' => $rgd,
                    'check_cofirm' => $check_cofirm,
                    'rdg' => $rdg,
                    'time' => isset($time) ? $time : '',
                    'check_paid' => $check_paid,
                ],
                200
            );
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getWarehousingByRgdFulfillment($rgd_no, $type)
    {
        try {
            $check_cofirm = 0;
            $check_paid = 0;
            if ($type == 'monthly') {
                $rgd = ReceivingGoodsDelivery::with(['rgd_child', 'warehousing', 'rate_data_general'])->where('rgd_no', $rgd_no)->first();
                $w_no = $rgd->w_no;
                $co_no = $rgd->warehousing->co_no;
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();
                $updated_at = Carbon::createFromFormat('Y.m.d H:i:s', $rgd->updated_at->format('Y.m.d H:i:s'));

                $start_date = $updated_at->startOfMonth()->toDateString();
                $end_date = $updated_at->endOfMonth()->toDateString();

                $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general'])
                    ->whereHas('w_no', function ($q) use ($co_no) {
                        $q->where('co_no', $co_no)
                            ->where('w_category_name', '수입풀필먼트');
                    })
                    ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($start_date)))
                    ->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($end_date)))
                    ->where('rgd_status1', '=', '입고')
                    ->whereNull('rgd_bill_type')
                    ->where(function ($q) {
                        $q->whereDoesntHave('rgd_child')
                            ->orWhere('rgd_status5', '!=', 'issued')
                            ->orWhereNull('rgd_status5');
                    })
                    ->get();
                if ($rgds->count() == 0) {
                    $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general', 'rgd_child', 'rate_meta_data', 'rate_meta_data_parent'])
                        ->whereHas('w_no', function ($q) use ($co_no) {
                            $q->where('co_no', $co_no)
                                ->where('w_category_name', '수입풀필먼트');
                        })
                        // ->doesntHave('rgd_child')
                        ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($start_date)))
                        ->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($end_date)))
                        ->where('rgd_status1', '=', '입고')
                        ->where('rgd_bill_type', 'final_monthly')
                        ->where('rgd_settlement_number', $rgd->rgd_settlement_number)
                        ->where(function ($q) {
                            $q->whereDoesntHave('rgd_child')
                                ->orWhere('rgd_status5', '!=', 'issued')
                                ->orWhereNull('rgd_status5');
                        })
                        ->get();

                    if ($rgds->count() == 0) {
                        $rgd = ReceivingGoodsDelivery::with(['rgd_child', 'warehousing'])->where('rgd_no', $rgd->rgd_parent_no)->first();
                        $w_no = $rgd->w_no;
                        $co_no = $rgd->warehousing->co_no;
                        $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();
                        $updated_at = Carbon::createFromFormat('Y.m.d H:i:s', $rgd->updated_at->format('Y.m.d H:i:s'));

                        $start_date = $updated_at->startOfMonth()->toDateString();
                        $end_date = $updated_at->endOfMonth()->toDateString();
                        $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general', 'rgd_child', 'rate_meta_data', 'rate_meta_data_parent'])
                            ->whereHas('w_no', function ($q) use ($co_no) {
                                $q->where('co_no', $co_no)
                                    ->where('w_category_name', '유통가공');
                            })
                            // ->doesntHave('rgd_child')
                            ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($start_date)))
                            ->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($end_date)))
                            ->where('rgd_status1', '=', '입고')
                            ->where('rgd_bill_type', 'final_monthly')
                            ->where('rgd_settlement_number', $rgd->rgd_settlement_number)
                            ->where(function ($q) {
                                $q->whereDoesntHave('rgd_child')
                                    ->orWhere('rgd_status5', '!=', 'issued')
                                    ->orWhereNull('rgd_status5');
                            })
                            ->get();
                    }
                }
                $warehousing = Warehousing::with(['w_ew_many' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);
                }, 'w_ew' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);
                }, 'co_no', 'warehousing_request', 'w_import_parent', 'warehousing_child'])->withSum('warehousing_item_IW_spasys_confirm', 'wi_number')->find($w_no);
                $adjustment_group = AdjustmentGroup::where('co_no', '=', $warehousing->co_no)->first();
                $adjustment_group2 = AdjustmentGroup::select(['ag_name', 'ag_no'])->where('co_no', '=', $warehousing->co_no)->get();

                $time = str_replace('-', '.', $start_date) . ' ~ ' . str_replace('-', '.', $end_date);
            } else {
                $rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd_no)->first();
                $w_no = $rgd->w_no;
                $check_cofirm = ReceivingGoodsDelivery::where('rgd_status5', 'confirmed')->where('rgd_bill_type', 'final')->where('w_no', $w_no)->get()->count();
                $check_paid = ReceivingGoodsDelivery::where('rgd_status5', 'paid')->where('rgd_bill_type', 'additional')->where('w_no', $w_no)->get()->count();

                $warehousing = Warehousing::with(['w_ew_many' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);
                }, 'w_ew' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);
                }, 'co_no', 'warehousing_request', 'w_import_parent', 'warehousing_child'])->withSum('warehousing_item_IW_spasys_confirm', 'wi_number')->find($w_no);

                $adjustment_group2 = AdjustmentGroup::select(['ag_name', 'ag_no'])->where('co_no', '=', $warehousing->co_no)->get();
                $adjustment_group = AdjustmentGroup::where('co_no', '=', $warehousing->co_no)->first();
            }
            $adjustment_group_choose = '';
            $rdg = RateDataGeneral::with(['rgd_no_final'])->where('rgd_no', $rgd_no)->first();
            if ($rdg) {
                $adjustment_group_choose = AdjustmentGroup::where('co_no', '=', $warehousing->co_no)->where('ag_name', '=', $rdg->rdg_set_type)->first();
            }
            return response()->json(
                [
                    'message' => Messages::MSG_0007,
                    'adjustment_group2' => $adjustment_group2,
                    'rgds' => isset($rgds) ? $rgds : null,
                    'adjustment_group_choose' => $adjustment_group_choose,
                    'adjustment_group' => $adjustment_group,
                    'warehousing' => isset($warehousing) ? $warehousing : null,
                    'rgd' => $rgd,
                    'check_cofirm' => $check_cofirm,
                    'rdg' => $rdg,
                    'time' => isset($time) ? $time : '',
                    'check_paid' => $check_paid,
                ],
                200
            );
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getWarehousingImportStatusComplete(WarehousingSearchRequest $request) //page 260

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general', 'rate_meta_data'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereNull('rgd_status4');
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general', 'rate_meta_data'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general', 'rate_meta_data' => function ($q) {

                    $q->withCount([
                        'rate_data as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(rd_data7)'));
                        },
                    ]);
                    $q->withCount([
                        'rate_data as bonusQuantity2' => function ($query) {

                            $query->select(DB::raw('SUM(rd_data6)'));
                        },
                    ]);
                }])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereNull('rgd_status5');
            }
            $warehousing->where('rgd_status1', '=', '입고')
                ->whereNull('rgd_parent_no')
                ->whereHas('w_no', function ($query) {
                    $query->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')
                                ->where('rgd_status4', '!=', '확정청구서')->where('rgd_status4', '!=', '추가청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })
                        ->where('w_category_name', '=', '유통가공')->where('w_type', '=', 'IW');
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
            if (isset($validated['w_schedule_number2'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number2'] . '%');
                });
            }
            if (isset($validated['settlement_cycle'])) {
                $warehousing->whereHas('w_no.co_no.company_distribution_cycle', function ($q) use ($validated) {
                    return $q->where('cs_payment_cycle', $validated['settlement_cycle']);
                });
            }

            $warehousing->orderBy('created_at', 'DESC');
            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) use ($user) {
                    $service_name = $item->service_korean_name;
                    $w_no = $item->w_no;

                    if ($user->mb_type == 'spasys') {
                        $co_no = $item->warehousing->company->co_parent->co_no;
                    } else if ($user->mb_type == 'shop') {
                        $co_no = $item->warehousing->company->co_no;
                    } else {
                        $co_no = $user->co_no;
                    }
                    $service = Service::where('service_name', $service_name)->first();

                    if(isset($service->service_no)){
                        $company_settlement = CompanySettlement::where([
                            'co_no' => $co_no,
                            'service_no' => $service->service_no,
                        ])->first();
                        $item->settlement_cycle = $company_settlement ? $company_settlement->cs_payment_cycle : "";
                    }

                  

                    //CHECK SHIPPER COMPANY IS SENT RATE DATA YET

                    $rate_data = RateData::where('rd_cate_meta1', '유통가공');



                    $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
                    $rate_data = $rate_data->where('rd_co_no', $co_no);
                    if (isset($rmd->rmd_no)) {
                        $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
                    } else {
                        $rate_data = [];
                    }


                    $item->rate_data = count($rate_data) == 0 ? 0 : 1;
                    $i = 0;
                    $k = 0;
                    $completed_date = null;
                    foreach ($item->warehousing->warehousing_child as $child) {
                        $i++;
                        if ($child['w_completed_day'] != null) {
                            $completed_date = $child['w_completed_day'];
                            $k++;
                        }
                    }

                    if ($i == $k || $i == 1) {
                        $item->is_completed = true;
                        $item->completed_date = $completed_date != null ? Carbon::parse($completed_date)->format('Y.m.d') : null;
                    } else {
                        $item->is_completed = false;
                        $item->completed_date = null;
                    }

                    return $item;
                })
            );

            return response()->json($warehousing);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function getWarehousingImportStatus4(WarehousingSearchRequest $request) //page 144 show EW,rgd_status1 and rgd_status2 = complete

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();

            // ====================DISTRIBUTION======================

            $warehousing = ReceivingGoodsDelivery::with(['rate_meta_data', 'rate_meta_data_parent', 'rate_data_general', 'payment', 't_import' ,'cancel_bill_history', 'rgd_child']);
            if ($user->mb_type == 'shop' && $request->type == 'check_list') {
                $warehousing->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereHas('mb_no', function ($q) {
                    $q->where('mb_type', 'spasys');
                });
            } else if ($user->mb_type == 'shop' && $request->type == 'view_list') {
                $warehousing->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereHas('mb_no', function ($q) {
                    $q->where('mb_type', 'shop');
                })->orderBy('created_at', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $warehousing->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereHas('mb_no', function ($q) {
                    $q->where('mb_type', 'shop');
                })->orderBy('created_at', 'DESC');
            } else if ($user->mb_type == 'spasys' && $request->type == 'check_list') {
            } else if ($user->mb_type == 'spasys' && $request->type == 'view_list') {
                $warehousing->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereHas('mb_no', function ($q) {
                    $q->where('mb_type', 'spasys');
                });
            }
            $warehousing->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '유통가공');
                })
                ->where('rgd_is_show', 'y')->orderBy('created_at', 'DESC');

            // ====================FULFILLMENT======================

            $warehousing_fulfillment = ReceivingGoodsDelivery::with(['rate_meta_data', 'rate_meta_data_parent', 'rate_data_general', 'cancel_bill_history', 'rgd_child']);
            if ($user->mb_type == 'shop' && $request->type == 'view_list') {
                $warehousing_fulfillment->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereHas('mb_no', function ($q) {
                    $q->where('mb_type', 'shop');
                });
            } else if ($user->mb_type == 'shop' && $request->type == 'check_list') {
                $warehousing_fulfillment->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereHas('mb_no', function ($q) {
                    $q->where('mb_type', 'spasys');
                });
            } else if ($user->mb_type == 'shipper') {
                $warehousing_fulfillment->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereHas('mb_no', function ($q) {
                    $q->where('mb_type', 'shop');
                });
            } else if ($user->mb_type == 'spasys' && $request->type == 'view_list') {
                $warehousing_fulfillment->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            }
            $warehousing_fulfillment->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '수입풀필먼트');
                })
                ->where('rgd_is_show', 'y');

            // ====================BONDED======================

            $warehousing_bonded = ReceivingGoodsDelivery::with(['rate_meta_data', 'rate_data_general', 't_export', 'cancel_bill_history', 'rgd_child']);
            if ($user->mb_type == 'shop' && $request->type == 'view_list') {
                $warehousing_bonded->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereHas('mb_no', function ($q) use ($user) {
                    $q->where('mb_type', 'shop');
                });
            } else if ($user->mb_type == 'shop' && $request->type == 'check_list') {
                $warehousing_bonded->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereHas('mb_no', function ($q) use ($user) {
                    $q->where('mb_type', 'spasys');
                });
            } else if ($user->mb_type == 'shipper') {
                $warehousing_bonded->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereHas('mb_no', function ($q) {
                    $q->where('mb_type', 'shop');
                });
            } else if ($user->mb_type == 'spasys' && $request->type == 'view_list') {
                $warehousing_bonded->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereHas('mb_no', function ($q) use ($user) {
                    $q->where('mb_type', 'spasys');
                });
            }
            $warehousing_bonded->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '보세화물');
                })
                ->where('rgd_is_show', 'y');


            // ====================SEARCH PART======================

            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                $warehousing_bonded->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                $warehousing_fulfillment->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                $warehousing_bonded->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                $warehousing_fulfillment->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('warehousing.co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
                $warehousing_bonded->whereHas('warehousing.co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
                $warehousing_fulfillment->whereHas('warehousing.co_no', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('warehousing.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
                $warehousing_bonded->whereHas('warehousing.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
                $warehousing_fulfillment->whereHas('warehousing.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['rgd_status4']) && $validated['rgd_status4'] != '전체') {
                $warehousing->where('rgd_status4', '=', $validated['rgd_status4']);
                $warehousing_bonded->where('rgd_status4', '=', $validated['rgd_status4']);
                $warehousing_fulfillment->where('rgd_status4', '=', $validated['rgd_status4']);
            }
            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('warehousing', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
                $warehousing_bonded->whereHas('warehousing', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
                $warehousing_fulfillment->whereHas('warehousing', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }
            if (isset($validated['w_schedule_number2'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->whereHas('warehousing', function ($q) use ($validated) {
                        $q->where('w_category_name', '=', '수입풀필먼트')->where('w_schedule_number2', 'like', '%' .  $validated['w_schedule_number2'] . '%');
                    })->orwhereHas('warehousing', function ($q) use ($validated) {
                        $q->where('w_category_name', '=', '유통가공')->whereHas('company', function ($q1) use ($validated) {
                            $q1->where('w_schedule_number2', 'like', '%' .  $validated['w_schedule_number2'] . '%');
                        });
                    })->orwhereHas('t_import', function ($q) use ($validated) {
                        $q->where('ti_h_bl', 'like', '%' .  $validated['w_schedule_number2'] . '%');
                    });
                });
                $warehousing_bonded->where(function ($q) use ($validated) {
                    $q->whereHas('warehousing', function ($q) use ($validated) {
                        $q->where('w_category_name', '=', '수입풀필먼트')->where('w_schedule_number2', 'like', '%' .  $validated['w_schedule_number2'] . '%');
                    })->orwhereHas('warehousing', function ($q) use ($validated) {
                        $q->where('w_category_name', '=', '유통가공')->whereHas('company', function ($q1) use ($validated) {
                            $q1->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number2'] . '%');
                        });
                    })->orwhereHas('t_import', function ($q) use ($validated) {
                        $q->where('ti_h_bl', 'like', '%' .  $validated['w_schedule_number2'] . '%');
                    });
                });
                $warehousing_fulfillment->where(function ($q) use ($validated) {
                    $q->whereHas('warehousing', function ($q) use ($validated) {
                        $q->where('w_category_name', '=', '수입풀필먼트')->where('w_schedule_number2', 'like', '%' .  $validated['w_schedule_number2'] . '%');
                    })->orwhereHas('warehousing', function ($q) use ($validated) {
                        $q->where('w_category_name', '=', '유통가공')->whereHas('company', function ($q1) use ($validated) {
                            $q1->where('w_schedule_number2', 'like', '%' .  $validated['w_schedule_number2'] . '%');
                        });
                    })->orwhereHas('t_import', function ($q) use ($validated) {
                        $q->where('ti_h_bl', 'like', '%' .  $validated['w_schedule_number2'] . '%');
                    });
                });
            }
            if (isset($validated['rgd_settlement_number'])) {
                $warehousing->where('rgd_settlement_number', 'like', '%' .  $validated['rgd_settlement_number'] . '%');
                $warehousing_bonded->where('rgd_settlement_number', 'like', '%' . $validated['rgd_settlement_number'] . '%');
                $warehousing_fulfillment->where('rgd_settlement_number', 'like', '%' .  $validated['rgd_settlement_number'] . '%');
            }
            if (isset($validated['rgd_bill_type'])) {
                if ($validated['rgd_bill_type'] == '월별') {
                    $warehousing->where(function ($q) {
                        $q->where('service_korean_name', '수입풀필먼트')->orWhere('rgd_bill_type', 'like', '%' . 'monthly' . '%');
                    });
                } else if ($validated['rgd_bill_type'] == '건별') {
                    $warehousing->where(function ($q) {
                        $q->where('rgd_bill_type', 'not like', '%' . 'monthly' . '%');
                    });
                }
                if ($validated['rgd_bill_type'] == '월별') {
                    $warehousing_bonded->where(function ($q) {
                        $q->where('service_korean_name', '수입풀필먼트')->orWhere('rgd_bill_type', 'like', '%' . 'monthly' . '%');
                    });
                } else if ($validated['rgd_bill_type'] == '건별') {
                    $warehousing_bonded->where(function ($q) {
                        $q->where('rgd_bill_type', 'not like', '%' . 'monthly' . '%');
                    });
                }
                if ($validated['rgd_bill_type'] == '월별') {
                    $warehousing_fulfillment->where(function ($q) {
                        $q->where('service_korean_name', '수입풀필먼트')->orWhere('rgd_bill_type', 'like', '%' . 'monthly' . '%');
                    });
                } else if ($validated['rgd_bill_type'] == '건별') {
                    $warehousing_fulfillment->where(function ($q) {
                        $q->where('rgd_bill_type', 'not like', '%' . 'monthly' . '%');
                    });
                }
            }
            if (isset($validated['rgd_status5'])) {
                if ($validated['rgd_status5'] == '청구서 취소') {
                    $warehousing->where('rgd_status5', '=', 'cancel');
                } else if ($validated['rgd_status5'] == '발행') {
                    $warehousing->where(function ($q) {
                        $q->where('rgd_status5', '!=', 'cancel')->orWhereNull('rgd_status5');
                    });
                }
                if ($validated['rgd_status5'] == '청구서 취소') {
                    $warehousing_bonded->where('rgd_status5', '=', 'cancel');
                } else if ($validated['rgd_status5'] == '발행') {
                    $warehousing_bonded->where(function ($q) {
                        $q->where('rgd_status5', '!=', 'cancel')->orWhereNull('rgd_status5');
                    });
                }
                if ($validated['rgd_status5'] == '청구서 취소') {
                    $warehousing_fulfillment->where('rgd_status5', '=', 'cancel');
                } else if ($validated['rgd_status5'] == '발행') {
                    $warehousing_fulfillment->where(function ($q) {
                        $q->where('rgd_status5', '!=', 'cancel')->orWhereNull('rgd_status5');
                    });
                }
            }
            if (isset($validated['rgd_status6'])) {
                if ($validated['rgd_status6'] == 'cancel') {
                    $warehousing->where('rgd_status6', '=', 'cancel')->where('rgd_calculate_deadline_yn', 'y');
                } else if ($validated['rgd_status6'] == 'paid') {
                    $warehousing->where('rgd_status6', '=', 'paid')->where('rgd_calculate_deadline_yn', 'y');
                } else if ($validated['rgd_status6'] == '진행중') {
                    $warehousing->where(function ($q) {
                        $q->where('rgd_calculate_deadline_yn', 'y')->where(function ($q2) {
                            $q2->where('rgd_status4', '예상경비청구서')
                                ->where('service_korean_name', '보세화물')
                                ->where('rgd_bill_type', 'not like', '%' . 'month' . '%')
                                ->where(function ($q4) {
                                    $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                                })
                                ->whereNull('rgd_status6');
                        })->orWhere(function ($q3) {
                            $q3->where('rgd_calculate_deadline_yn', 'y')->where(function ($q4) {
                                $q4->Where('rgd_status5', 'confirmed');
                            })
                                ->where(function ($q4) {
                                    $q4->whereNull('rgd_status6');
                                })
                                ->where(function ($q4) {
                                    $q4->where('rgd_status4', '추가청구서')->orWhere('rgd_status4', '확정청구서');
                                });
                        });
                    });
                }

                if ($validated['rgd_status6'] == 'cancel') {
                    $warehousing_bonded->where('rgd_status6', '=', 'cancel')->where('rgd_calculate_deadline_yn', 'y');
                } else if ($validated['rgd_status6'] == 'paid') {
                    $warehousing_bonded->where('rgd_status6', '=', 'paid')->where('rgd_calculate_deadline_yn', 'y');
                } else if ($validated['rgd_status6'] == '진행중') {
                    $warehousing_bonded->where(function ($q) {
                        $q->where('rgd_calculate_deadline_yn', 'y')->where(function ($q2) {
                            $q2->where('rgd_status4', '예상경비청구서')
                                ->where('service_korean_name', '보세화물')
                                ->where('rgd_bill_type', 'not like', '%' . 'month' . '%')
                                ->where(function ($q4) {
                                    $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                                })
                                ->whereNull('rgd_status6');
                        })->orWhere(function ($q3) {
                            $q3->where('rgd_calculate_deadline_yn', 'y')->where(function ($q4) {
                                $q4->Where('rgd_status5', 'confirmed');
                            })->where(function ($q4) {
                                $q4->whereNull('rgd_status6');
                            })
                                ->where(function ($q4) {
                                    $q4->where('rgd_status4', '추가청구서')->orWhere('rgd_status4', '확정청구서');
                                });
                        });
                    });
                }
                if ($validated['rgd_status6'] == 'cancel') {
                    $warehousing_fulfillment->where('rgd_status6', '=', 'cancel')->where('rgd_calculate_deadline_yn', 'y');
                } else if ($validated['rgd_status6'] == 'paid') {
                    $warehousing_fulfillment->where('rgd_status6', '=', 'paid')->where('rgd_calculate_deadline_yn', 'y');
                } else if ($validated['rgd_status6'] == '진행중') {
                    $warehousing_fulfillment->where(function ($q) {
                        $q->where('rgd_calculate_deadline_yn', 'y')->where(function ($q2) {
                            $q2->where('rgd_status4', '예상경비청구서')
                                ->where('service_korean_name', '보세화물')
                                ->where('rgd_bill_type', 'not like', '%' . 'month' . '%')
                                ->where(function ($q4) {
                                    $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                                })
                                ->whereNull('rgd_status6');
                        })->orWhere(function ($q3) {
                            $q3->where('rgd_calculate_deadline_yn', 'y')->where(function ($q4) {
                                $q4->Where('rgd_status5', 'confirmed');
                            })
                                ->where(function ($q4) {
                                    $q4->whereNull('rgd_status6');
                                })
                                ->where(function ($q4) {
                                    $q4->where('rgd_status4', '추가청구서')->orWhere('rgd_status4', '확정청구서');
                                });
                        });
                    });
                }
            }

            if (isset($validated['rgd_status5_1'])) {
                if ($validated['rgd_status5_1'] == '요청중') {
                    $warehousing->where(function ($q) {
                        $q->where('rgd_bill_type', 'like', '%' . 'final' . '%')->whereNull('rgd_status5');
                    });
                } else if ($validated['rgd_status5_1'] == '승인완료') {
                    $warehousing->where(function ($q) {
                        $q->where('rgd_bill_type', 'like', '%' . 'final' . '%')->Where(function ($q2) {
                            $q2->where('rgd_status5', 'confirmed')->orWhere('rgd_status5', 'issued');
                        });
                    });
                }

                if ($validated['rgd_status5_1'] == '요청중') {
                    $warehousing_bonded->where(function ($q) {
                        $q->where('rgd_bill_type', 'like', '%' . 'final' . '%')->whereNull('rgd_status5');
                    });
                } else if ($validated['rgd_status5_1'] == '승인완료') {
                    $warehousing_bonded->where(function ($q) {
                        $q->where('rgd_bill_type', 'like', '%' . 'final' . '%')->Where(function ($q2) {
                            $q2->where('rgd_status5', 'confirmed')->orWhere('rgd_status5', 'issued');
                        });
                    });
                }
                if ($validated['rgd_status5_1'] == '요청중') {
                    $warehousing_fulfillment->where(function ($q) {
                        $q->where('rgd_bill_type', 'like', '%' . 'final' . '%')->whereNull('rgd_status5');
                    });
                } else if ($validated['rgd_status5_1'] == '승인완료') {
                    $warehousing_fulfillment->where(function ($q) {
                        $q->where('rgd_bill_type', 'like', '%' . 'final' . '%')->Where(function ($q2) {
                            $q2->where('rgd_status5', 'confirmed')->orWhere('rgd_status5', 'issued');
                        });
                    });
                }
            }
            if (isset($validated['rgd_status67'])) {
                if ($validated['rgd_status67'] == '정산완료') {
                    $warehousing->where(function ($q) use ($validated) {
                        $q->where(function ($q) use ($validated) {
                            $q->where('rgd_status7', '=', 'taxed')->where('rgd_status6', '=', 'paid');
                        })->orwhere('rgd_status8', 'completed');
                    });
                } else if ($validated['rgd_status67'] == '진행중') {
                    $warehousing->where(function ($q) use ($validated) {
                        $q->where('rgd_status8', '!=', 'completed')->orwhereNull('rgd_status8');
                    })->where(function ($q) use ($validated) {
                        $q->where(function ($q) use ($validated) {
                            $q->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status5')->orwhere('rgd_status5', '!=', 'cancel');
                            })->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status6')->orwhere('rgd_status6', '!=', 'paid');
                            });
                        })->orwhere(function ($q) use ($validated) {
                            $q->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status5')->orwhere('rgd_status5', '!=', 'cancel');
                            })->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status7')->orwhere('rgd_status7', '!=', 'taxed');
                            });
                        });
                    });
                }
                if ($validated['rgd_status67'] == '정산완료') {
                    $warehousing_bonded->where(function ($q) use ($validated) {
                        $q->where(function ($q) use ($validated) {
                            $q->where('rgd_status7', '=', 'taxed')->where('rgd_status6', '=', 'paid');
                        })->orwhere('rgd_status8', 'completed');
                    });
                } else if ($validated['rgd_status67'] == '진행중') {
                    $warehousing_bonded->where(function ($q) use ($validated) {
                        $q->where('rgd_status8', '!=', 'completed')->orwhereNull('rgd_status8');
                    })->where(function ($q) use ($validated) {
                        $q->where(function ($q) use ($validated) {
                            $q->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status5')->orwhere('rgd_status5', '!=', 'cancel');
                            })->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status6')->orwhere('rgd_status6', '!=', 'paid');
                            });
                        })->orwhere(function ($q) use ($validated) {
                            $q->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status5')->orwhere('rgd_status5', '!=', 'cancel');
                            })->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status7')->orwhere('rgd_status7', '!=', 'taxed');
                            });
                        });
                    });
                }
                if ($validated['rgd_status67'] == '정산완료') {
                    $warehousing_fulfillment->where(function ($q) use ($validated) {
                        $q->where(function ($q) use ($validated) {
                            $q->where('rgd_status7', '=', 'taxed')->where('rgd_status6', '=', 'paid');
                        })->orwhere('rgd_status8', 'completed');
                    });
                } else if ($validated['rgd_status67'] == '진행중') {
                    $warehousing_fulfillment->where(function ($q) use ($validated) {
                        $q->where('rgd_status8', '!=', 'completed')->orwhereNull('rgd_status8');
                    })->where(function ($q) use ($validated) {
                        $q->where(function ($q) use ($validated) {
                            $q->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status5')->orwhere('rgd_status5', '!=', 'cancel');
                            })->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status6')->orwhere('rgd_status6', '!=', 'paid');
                            });
                        })->orwhere(function ($q) use ($validated) {
                            $q->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status5')->orwhere('rgd_status5', '!=', 'cancel');
                            })->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status7')->orwhere('rgd_status7', '!=', 'taxed');
                            });
                        });
                    });
                }
            }
            if (isset($validated['rgd_status7']) && $validated['rgd_status7'] != '전체') {

                $warehousing->where('rgd_calculate_deadline_yn', 'y')->where('rgd_status4', '확정청구서');
                $warehousing_bonded->where('rgd_calculate_deadline_yn', 'y')->where('rgd_status4', '확정청구서');
                $warehousing_fulfillment->where('rgd_calculate_deadline_yn', 'y')->where('rgd_status4', '확정청구서');

                if ($validated['rgd_status7'] == 'waiting') {
                    $warehousing->whereNull('rgd_status7')->where('rgd_status5', 'confirmed');
                } else {
                    $warehousing->where('rgd_status7', '=', $validated['rgd_status7']);
                }

                if ($validated['rgd_status7'] == 'waiting') {
                    $warehousing_bonded->whereNull('rgd_status7')->where('rgd_status5', 'confirmed');
                } else {
                    $warehousing_bonded->where('rgd_status7', '=', $validated['rgd_status7']);
                }

                if ($validated['rgd_status7'] == 'waiting') {
                    $warehousing_fulfillment->whereNull('rgd_status7')->where('rgd_status5', 'confirmed');
                } else {
                    $warehousing_fulfillment->where('rgd_status7', '=', $validated['rgd_status7']);
                }
            }

            if (isset($validated['rgd_status1']) && $validated['rgd_status1'] != '전체') {
                if ($validated['rgd_status1'] == 'waiting') {
                    $warehousing->where(function ($q) {
                        $q->where('rgd_status4', '확정청구서')->orwhere(function ($q) {
                            $q->where('service_korean_name', '==', '보세화물')->where('rgd_status4', '예상경비청구서')->where('rgd_bill_type', 'not like', '%' . 'monthly' . '%');
                        });
                    })
                        ->where(function ($q) {
                            $q->where('rgd_status5', '!=', 'cancel')->orwhereNull('rgd_status5');
                        })
                        ->whereNull('rgd_status7');
                } else {
                    $warehousing->where(function ($q) {
                        $q->where('rgd_status5', '!=', 'cancel')->orwhereNull('rgd_status5');
                    })->where('rgd_status7', '=', $validated['rgd_status1']);
                }

                if ($validated['rgd_status1'] == 'waiting') {
                    $warehousing_bonded->where(function ($q) {
                        $q->where('rgd_status4', '확정청구서')->orwhere(function ($q) {
                            $q->where('service_korean_name', '==', '보세화물')->where('rgd_status4', '예상경비청구서')->where('rgd_bill_type', 'not like', '%' . 'monthly' . '%');
                        });
                    })
                        ->where(function ($q) {
                            $q->where('rgd_status5', '!=', 'cancel')->orwhereNull('rgd_status5');
                        })
                        ->whereNull('rgd_status7');
                } else
                    $warehousing_bonded->where(function ($q) {
                        $q->where('rgd_status5', '!=', 'cancel')->orwhereNull('rgd_status5');
                    })->where('rgd_status7', '=', $validated['rgd_status1']);

                if ($validated['rgd_status1'] == 'waiting') {
                    $warehousing_fulfillment->where(function ($q) {
                        $q->where('rgd_status4', '확정청구서')->orwhere(function ($q) {
                            $q->where('service_korean_name', '==', '보세화물')->where('rgd_status4', '예상경비청구서')->where('rgd_bill_type', 'not like', '%' . 'monthly' . '%');
                        });
                    })
                        ->where(function ($q) {
                            $q->where('rgd_status5', '!=', 'cancel')->orwhereNull('rgd_status5');
                        })
                        ->whereNull('rgd_status7');
                } else {
                    $warehousing_fulfillment->where(function ($q) {
                        $q->where('rgd_status5', '!=', 'cancel')->orwhereNull('rgd_status5');
                    })->where('rgd_status7', '=', $validated['rgd_status1']);
                }
            }



            if (isset($validated['service']) && $validated['service'] != '전체') {
                $warehousing->where('service_korean_name', '=', $validated['service']);
                $warehousing_bonded->where('service_korean_name', '=', $validated['service']);
                $warehousing_fulfillment->where('service_korean_name', '=', $validated['service']);
            }

            $warehousing->union($warehousing_fulfillment)->union($warehousing_bonded)->orderBy('updated_at', 'DESC')
                ->orderBy('rgd_no', 'DESC');



            $contract = Contract::where('co_no',  $user->co_no)->first();

            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) use ($contract) {
                    if (isset($contract->c_calculate_deadline_yn))
                        $item->c_calculate_deadline_yn = $contract->c_calculate_deadline_yn;
                    else
                        $item->c_calculate_deadline_yn = 'n';


                    $service_name = $item->service_korean_name;

                    $co_no = Warehousing::where('w_no', $item->w_no)->first()->co_no;
                    $service_no = Service::where('service_name', $service_name)->first()->service_no;

                    $company_settlement = CompanySettlement::where([
                        'co_no' => $co_no,
                        'service_no' => $service_no,
                    ])->first();

                    if (isset($item->payment->p_method_fee)) {
                        $item->p_method_fee = $item->payment->p_method_fee + isset($item->rate_data_general) ? $item->rate_data_general->rdg_sum4 : 0;
                    } else {
                        $item->p_method_fee = isset($item->rate_data_general) ? $item->rate_data_general->rdg_sum4 : 0;
                    }

                    $item->settlement_cycle = $company_settlement ? $company_settlement->cs_payment_cycle : "";

                    $i = 0;
                    $k = 0;
                    $completed_date = null;
                    foreach ($item->warehousing->warehousing_child as $child) {
                        $i++;
                        if ($child['w_completed_day'] != null) {
                            $completed_date = $child['w_completed_day'];
                            $k++;
                        }
                    }
                    if ($item->service_korean_name == '보세화물') {
                        if ($item->rgd_bill_type == 'final_monthly') {
                            $item->discount = '';
                            $total_discount = 0;
                            foreach ($item->rgd_settlement as $row) {
                                if (isset($row->rate_meta_data_parent[0])) {
                                    $rate_data = RateData::where('rmd_no', $row->rate_meta_data_parent[0]->rate_data[0]->rmd_no)->where('rd_cate2', '할인금액')->first();
                                    $total_discount += isset($rate_data->rd_data4) ? (int)$rate_data->rd_data4 : 0;
                                } else {
                                    $total_discount += 0;
                                }
                            }
                            $item->discount = $total_discount;
                            $item->sum_price_total2 = $item->rate_data_general->rdg_sum7 + $total_discount;
                        } else if (count($item->rate_meta_data) > 0) {
                            $total_discount = 0;

                            $rate_data = RateData::where('rmd_no', $item->rate_meta_data[0]->rate_data[0]->rmd_no)->where('rd_cate2', '할인금액')->first();
                            $total_discount += isset($rate_data->rd_data4) ? (int)$rate_data->rd_data4 : 0;

                            $item->sum_price_total2 = $item->rate_data_general->rdg_sum7 + $total_discount;
                            $item->discount = $total_discount;
                        } else if (count($item->rate_meta_data_parent) > 0) {
                            $total_discount = 0;

                            $rate_data = RateData::where('rmd_no', $item->rate_meta_data_parent[0]->rate_data[0]->rmd_no)->where('rd_cate2', '할인금액')->first();
                            $total_discount += isset($rate_data->rd_data4) ? (int)$rate_data->rd_data4 : 0;

                            $item->sum_price_total2 = $item->rate_data_general->rdg_sum7 + $total_discount;
                            $item->discount = $total_discount;
                        }

                        $item->sum_price_total = isset($item->rate_data_general) ? $item->rate_data_general->rdg_sum7 : 0;
                    } else if ($item->service_korean_name == '수입풀필먼트') {
                        $item->discount = "";
                        $item->sum_price_total2 =  isset($item->rate_data_general) ? $item->rate_data_general->rdg_sum6 : 0;
                        $item->sum_price_total = isset($item->rate_data_general) ? $item->rate_data_general->rdg_sum6 : 0;
                    } else {
                        $item->discount = "";
                        $item->sum_price_total2 = isset($item->rate_data_general) ? $item->rate_data_general->rdg_sum4 : 0;
                        $item->sum_price_total = isset($item->rate_data_general) ? $item->rate_data_general->rdg_sum4 : 0;
                    }

                    if(isset($item->rate_data_general)){
                        if ($item->rate_data_general->rdg_sum7) {
                            $item->sum_price_total3 = $item->rate_data_general->rdg_sum7;
                        } else if ($item->rate_data_general->rdg_sum6) {
                            $item->sum_price_total3 = $item->rate_data_general->rdg_sum6;
                        } else if ($item->rate_data_general->rdg_sum4) {
                            $item->sum_price_total3 = $item->rate_data_general->rdg_sum4;
                        }   else {
                            $item->sum_price_total3 = 0;
                        }
                    } else {
                        $item->sum_price_total3 = 0;
                    }


                    if ($i == $k) {
                        $item->is_completed = true;
                        $item->completed_date = Carbon::parse($completed_date)->format('Y.m.d');
                    } else {
                        $item->is_completed = false;
                        $item->completed_date = null;
                    }

                    return $item;
                })
            );
            //return DB::getQueryLog();
            // return DB::getQueryLog();
            return response()->json($warehousing);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getFulfillmentExportStatusComplete(WarehousingSearchRequest $request) //page 263

    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general', 'rate_meta_data' => function ($q) {

                    $q->withCount([
                        'rate_data as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(rd_data7)'));
                        },
                    ]);
                    $q->withCount([
                        'rate_data as bonusQuantity2' => function ($query) {

                            $query->select(DB::raw('SUM(rd_data6)'));
                        },
                    ]);
                }])
                    ->whereHas('w_no', function ($query) use ($user) {
                        $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        });
                    });
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general', 'rate_meta_data' => function ($q) {

                    $q->withCount([
                        'rate_data as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(rd_data7)'));
                        },
                    ]);
                    $q->withCount([
                        'rate_data as bonusQuantity2' => function ($query) {

                            $query->select(DB::raw('SUM(rd_data6)'));
                        },
                    ]);
                }])
                    ->whereHas('w_no', function ($query) use ($user) {
                        $query->whereHas('co_no', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        });
                    });
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general', 'rate_meta_data' => function ($q) {

                    $q->withCount([
                        'rate_data as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(rd_data7)'));
                        },
                    ]);
                    $q->withCount([
                        'rate_data as bonusQuantity2' => function ($query) {

                            $query->select(DB::raw('SUM(rd_data6)'));
                        },
                    ]);
                }])
                    ->whereHas('w_no', function ($query) use ($user) {
                        $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no);
                        });
                    });
            }
            $warehousing->where('rgd_status1', '=', '입고')
                ->whereNull('rgd_parent_no')
                ->whereHas('w_no', function ($query) {
                    $query->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')
                                ->where('rgd_status4', '!=', '확정청구서')->where('rgd_status4', '!=', '추가청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })
                        ->where('w_category_name', '=', '수입풀필먼트')->where('w_type', '=', 'IW');
                });

            $warehousing->whereNull('rgd_status4')->whereNull('rgd_status5')->whereHas('rate_data_general');

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
                if ($user->mb_type == 'shop') {
                    $warehousing->whereHas('w_no.co_no.co_parent', function ($query) use ($validated) {
                        $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    });
                } else {
                    $warehousing->whereHas('w_no.co_no', function ($query) use ($validated) {
                        $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    });
                }
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('w_no.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
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



            $members = Member::where('mb_no', '!=', 0)->get();

            $warehousing = $warehousing->orderBy('created_at', 'DESC')->paginate($per_page, ['*'], 'page', $page);

            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) use ($user) {

                    $updated_at = Carbon::createFromFormat('Y.m.d H:i:s', $item->updated_at->format('Y.m.d H:i:s'));

                    $start_date = $updated_at->startOfMonth()->toDateString();
                    $end_date = $updated_at->endOfMonth()->toDateString();
                    $item->time = str_replace('-', '.', $start_date) . ' ~ ' . str_replace('-', '.', $end_date);

                    //CHECK SHIPPER COMPANY IS SENT RATE DATA YET

                    $rate_data = RateData::where('rd_cate_meta1', '수입풀필먼트');

                    if ($user->mb_type == 'spasys') {
                        $co_no = $item->warehousing->co_no;
                    } else if ($user->mb_type == 'shop') {
                        $co_no = $item->warehousing->co_no;
                    } else {
                        $co_no = $user->co_no;
                    }

                    $company = Company::where('co_no', $co_no)->first();
                    if ($company->co_type == 'shipper') {
                        $sh = StockHistory::whereHas('member', function ($q) use ($co_no) {
                            $q->where('co_no', $co_no);
                        })->where('sh_date', $item->rgd_monthbill_start->format('Y-m-d'))->first();
                        $item->start_stock = isset($sh->sh_left_stock) ? $sh->sh_left_stock : 0;

                        $sh = StockHistory::whereHas('member', function ($q) use ($co_no) {
                            $q->where('co_no', $co_no);
                        })->where('sh_date', $item->rgd_monthbill_end->format('Y-m-d'))->first();
                        $item->end_stock = isset($sh->sh_left_stock) ? $sh->sh_left_stock : 0;
                    } else if ($company->co_type == 'shop') {
                        $sh = StockHistory::whereHas('member.company.co_parent', function ($q) use ($co_no) {
                            $q->where('co_no', $co_no);
                        })->where('sh_date', $item->rgd_monthbill_start->format('Y-m-d'));
                        $item->start_stock = !empty($sh) ? $sh->sum('sh_left_stock') : 0;

                        $sh = StockHistory::whereHas('member.company.co_parent', function ($q) use ($co_no) {
                            $q->where('co_no', $co_no);
                        })->where('sh_date', $item->rgd_monthbill_end->format('Y-m-d'));
                        $item->end_stock = !empty($sh) ? $sh->sum('sh_left_stock') : 0;
                    }

                    $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
                    $rate_data = $rate_data->where('rd_co_no', $co_no);
                    if (isset($rmd->rmd_no)) {
                        $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
                    } else {
                        $rate_data = [];
                    }


                    $item->rate_data = count($rate_data) == 0 ? 0 : 1;

                    $rmd = RateMetaData::where(
                        [
                            'rgd_no' => $item->rgd_no,
                            'set_type' => 'fulfill1_final',
                        ]
                    )->first();

                    if (!empty($rmd)) {
                        $rate_data = RateData::where('rmd_no', $rmd->rmd_no)->where(function ($q) {
                            $q->orWhere('rd_cate_meta1', '수입풀필먼트');
                        })->get();

                        $pcs = 0;
                        $box = 0;
                        $caton = 0;
                        $returns = 0;

                        foreach ($rate_data as $rate) {
                            if ($rate['rd_data1'] == 'PCS' && $rate['rd_cate1'] == '출고') {
                                $pcs += intval($rate['rd_data4']);
                            } else if ($rate['rd_data1'] == 'BOX' && $rate['rd_cate1'] == '출고') {
                                $box += intval($rate['rd_data4']);
                            } else if ($rate['rd_data1'] == 'CATON' && $rate['rd_cate1'] == '출고') {
                                $caton += intval($rate['rd_data4']);
                            }
                            if ($rate['rd_cate1'] == '입고' && $rate['rd_cate2'] == '반품입고') {
                                $returns = intval($rate['rd_data4']);
                            }
                        }

                        $item->pcs = $pcs;
                        $item->box = $box;
                        $item->caton = $caton;
                        $item->returns = $returns;
                    } else {
                        $item->pcs = 0;
                        $item->box = 0;
                        $item->caton = 0;
                    }

                    return $item;
                })
            );

            return response()->json($warehousing);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getBondedExportStatusComplete(WarehousingSearchRequest $request) //page 213

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with(['rate_data_general', 't_import_expected', 't_import', 't_export', 'rate_meta_data' => function ($q) {

                    $q->withCount([
                        'rate_data as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(rd_data4)'))->where('rd_cate2', '소계');
                        },
                    ]);
                }])->join('t_import', 't_import.ti_carry_in_number', '=', 'receiving_goods_delivery.rgd_ti_carry_in_number')->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereNull('rgd_status4');
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['rate_data_general', 't_import_expected', 't_import', 't_export', 'rate_meta_data' => function ($q) {

                    $q->withCount([
                        'rate_data as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(rd_data4)'))->where('rd_cate2', '소계');
                        },
                    ]);
                }])->join('t_import', 't_import.ti_carry_in_number', '=', 'receiving_goods_delivery.rgd_ti_carry_in_number')->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['rate_data_general', 't_import_expected', 't_import', 't_export', 'rate_meta_data' => function ($q) {

                    $q->withCount([
                        'rate_data as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(rd_data4)'))->where('rd_cate2', '소계');
                        },
                    ]);
                }])->join('t_import', 't_import.ti_carry_in_number', '=', 'receiving_goods_delivery.rgd_ti_carry_in_number')->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereNull('rgd_status5');
            }
            $warehousing->where('rgd_status1', '=', '입고')
                ->where(function ($q) {
                    $q->where(function ($query) {
                        $query->where('rgd_status4', '!=', '예상경비청구서')
                            ->where('rgd_status4', '!=', '확정청구서');
                    })
                        ->orWhereNull('rgd_status4');
                })
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_type', '=', 'SET')
                        ->where('w_category_name', '=', '보세화물');
                });
            // ->whereHas('mb_no', function ($q) {
            //     $q->whereHas('company', function ($q) {
            //         $q->where('co_type', 'spasys');
            //     });
            // });

            if (isset($validated['from_date'])) {
                $warehousing->whereHas('t_import', function ($q) use ($validated) {
                    $q->where('ti_i_date', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                });
            }

            if (isset($validated['to_date'])) {
                $warehousing->whereHas('t_import', function ($q) use ($validated) {
                    $q->where('ti_i_date', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                });
            }

            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('warehousing.co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['h_bl'])) {
                $warehousing->whereHas('t_import_expected', function ($q) use ($validated) {
                    $q->where(DB::raw('lower(tie_h_bl)'), 'like', '%' . strtolower($validated['h_bl']) . '%');
                });
            }

            if (isset($validated['co_name'])) {
                $warehousing->whereHas('warehousing.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['settlement_cycle']) && $validated['settlement_cycle'] != '전체') {
                $warehousing->whereHas('warehousing.co_no.company_bonded_cycle', function ($q) use ($validated) {
                    return $q->where('cs_payment_cycle', $validated['settlement_cycle']);
                });
            }
            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('warehousing', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }


            $warehousing->orderBy('ti_i_date', 'DESC')->orderBy('rgd_tracking_code', 'DESC');
            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();

            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) use ($user) {
                    $service_name = $item->service_korean_name;

                    if ($user->mb_type == 'spasys') {
                        $co_no = $item->warehousing->company->co_parent->co_no;
                    } else if ($user->mb_type == 'shop') {
                        $co_no = $item->warehousing->company->co_no;
                    } else {
                        $co_no = $user->co_no;
                    }

                    $service_no = Service::where('service_name', $service_name)->first()->service_no;

                    $company_settlement = CompanySettlement::where([
                        'co_no' => $co_no,
                        'service_no' => $service_no,
                    ])->first();
                    $item->settlement_cycle = $company_settlement ? $company_settlement->cs_payment_cycle : "";

                    //CHECK SHIPPER COMPANY IS SENT RATE DATA YET

                    $rate_data = RateData::where('rd_cate_meta1', '보세화물');

                    $rmd = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->latest('created_at')->first();
                    $rate_data = $rate_data->where('rd_co_no', $co_no);
                    if (isset($rmd->rmd_no)) {
                        $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no)->get();
                    } else {
                        $rate_data = [];
                    }


                    $item->rate_data = count($rate_data) == 0 ? 0 : 1;

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
            if ($user->mb_type == 'shop' && $request->type == 'view_list') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'shop' && $request->type == 'check_list') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'spasys' && $request->type == 'view_list') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            }
            $warehousing->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서')
                    ->orWhere('rgd_status4', '=', '추가청구서');
            })
                ->whereHas('w_no', function ($query) {
                    $query->where('w_category_name', '=', '수입풀필먼트');
                })
                ->where('rgd_is_show', 'y')
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
            //
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getBondedExportStatus4(WarehousingSearchRequest $request)
    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop' && $request->type == 'view_list') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general', 't_export'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereHas('mb_no', function ($q) use ($user) {
                    $q->where('mb_type', 'shop');
                });
            } else if ($user->mb_type == 'shop' && $request->type == 'check_list') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general', 't_export']);
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general', 't_export'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'spasys' && $request->type == 'view_list') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general', 't_export'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->whereHas('mb_no', function ($q) use ($user) {
                    $q->where('mb_type', 'spasys');
                });
            }
            $warehousing->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서')
                    ->orWhere('rgd_status4', '=', '추가청구서');
            })->whereHas('w_no', function ($query) {
                $query->where('w_category_name', '=', '보세화물');
            })
                ->where('rgd_is_show', 'y')
                ->orderBy('updated_at', 'DESC')
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

            if (isset($validated['settlement_cycle'])) {
                $warehousing->whereHas('w_no.co_no.company_bonded_cycle', function ($q) use ($validated) {
                    return $q->where('cs_payment_cycle', $validated['settlement_cycle']);
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


            $contract = Contract::where('co_no',  $user->co_no)->first();

            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) use ($contract) {
                    if (isset($contract->c_calculate_deadline_yn))
                        $item->c_calculate_deadline_yn = $contract->c_calculate_deadline_yn;
                    else
                        $item->c_calculate_deadline_yn = 'n';

                    $rmd = RateMetaData::where('rgd_no', $item->rgd_no)->where('set_type', 'bonded1')->first();
                    if (isset($rmd->rmd_no)) {
                        $rate_data = RateData::where('rmd_no', $rmd->rmd_no)->where('rd_cate2', '할인금액')->first();
                        $item->discount_money = isset($rate_data->rd_data4) ? ($rate_data->rd_data4 != "" ? $rate_data->rd_data4 : 0) : 0;
                    } else {
                        $item->discount_money = 0;
                    }


                    return $item;
                })
            );


            return response()->json($warehousing);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getFulfillmentExportStatus4ById($rgd_no)
    {

        $warehousing = ReceivingGoodsDelivery::find($rgd_no);
        $rate_data_general = RateDataGeneral::where('rgd_no', $rgd_no)->first();
        $w_no = Warehousing::with(['co_no'])->where('w_no', $warehousing->w_no)->first();

        $get_co_no = $w_no->co_no;
        $get_service_no = Service::where('service_name', $warehousing->service_korean_name)->first()->service_no;
        $settlement_cycle = CompanySettlement::where('service_no', $get_service_no)->where('co_no', $get_co_no)->first()->cs_payment_cycle;
        if (!empty($warehousing)) {
            return response()->json(
                [
                    'message' => Messages::MSG_0007,
                    'data' => $warehousing,
                    'rate_data_general' => $rate_data_general,
                    'w_no' => $w_no,
                    'get_co_no' => $get_co_no,
                    'get_service_no' => $get_service_no,
                    'settlement_cycle' => $settlement_cycle,
                ],
                200
            );
        } else {
            return response()->json([
                'message' => CommonFunc::renderMessage(Messages::MSG_0016, ['Warehousing']),

            ]);
        }
    }

    public function get_tax_invoice_list(WarehousingSearchRequest $request) //page277

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with(['member', 'warehousing', 'rate_data_general', 't_export', 't_import'])->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('company.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->whereHas('company.contract', function ($q) use ($user) {
                        $q->where('c_calculate_deadline_yn', 'y');
                    });
                });
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['member', 'warehousing', 'rate_data_general', 't_export', 't_import'])->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('company', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->whereHas('company.contract', function ($q) use ($user) {
                        $q->where('c_calculate_deadline_yn', 'y');
                    });
                });
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['member', 'warehousing', 'rate_data_general', 't_export', 't_import'])->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('company.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orWhereHas('company.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })
                    ->whereHas('warehousing', function ($query) use ($user) {
                        $query->whereHas('company.co_parent.contract', function ($q) use ($user) {
                            $q->where('c_calculate_deadline_yn', 'y');
                        })->orWhereHas('company.contract', function ($q) use ($user) {
                            $q->where('c_calculate_deadline_yn', 'y');
                        });
                    });
            }
            $warehousing->where(function ($q) {
                $q->where('rgd_status4', '확정청구서');
            })
                ->where('rgd_status5', 'confirmed')
                ->where('rgd_is_show', 'y')
                ->where('rgd_calculate_deadline_yn', 'y')
                ->whereHas('member', function ($q) use ($user) {
                    $q->where('mb_type', $user->mb_type);
                })
                ->where(function ($q4) {
                    $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                })
                // ->orderBy('rgd_tax_invoice_date', 'DESC')
                ->orderBy('rgd_no', 'DESC');

            if (isset($validated['status'])) {
                if ($validated['status'] == 'waiting')
                    $warehousing->whereNull('rgd_status7');
                else if ($validated['status'] == 'taxed')
                    $warehousing->where('rgd_status7', '=', 'taxed');
                else
                    $warehousing->where('rgd_status7', '=', 'cancel');
            }

            if (isset($validated['service']) && $validated['service'] != '전체') {
                $warehousing->where(DB::raw('lower(service_korean_name)'), 'like', '%' . strtolower($validated['service']) . '%');
            }

            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('warehousing.company.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('warehousing.company', function ($q) use ($validated) {
                    $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['rgd_settlement_number'])) {
                $warehousing->where('rgd_settlement_number', 'like', '%' . $validated['rgd_settlement_number'] . '%');
            }
            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('warehousing', function ($q) use ($validated) {
                    $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }
            if (isset($validated['w_schedule_number2'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->whereHas('warehousing', function ($q) use ($validated) {
                        $q->where('w_category_name', '=', '수입풀필먼트')->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number2'] . '%');
                    })->orwhereHas('warehousing', function ($q) use ($validated) {
                        $q->where('w_category_name', '=', '유통가공')->whereHas('company', function ($q1) use ($validated) {
                            $q1->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number2'] . '%');
                        });
                    })->orwhereHas('t_import', function ($q) use ($validated) {
                        $q->where('ti_h_bl', 'like', '%' . $validated['w_schedule_number2'] . '%');
                    });
                });
            }
            if (isset($validated['rgd_status1']) && $validated['rgd_status1'] != '전체') {
                if ($validated['rgd_status1'] == 'waiting') {
                    $warehousing->whereNull('rgd_status7');
                } else
                    $warehousing->where('rgd_status7', '=', $validated['rgd_status1']);
            }
            if (isset($validated['rgd_status6'])) {
                if ($validated['rgd_status6'] == 'cancel') {
                    $warehousing->where('rgd_status6', '=', 'cancel');
                } else if ($validated['rgd_status6'] == 'paid') {
                    $warehousing->where('rgd_status6', '=', 'paid');
                } else if ($validated['rgd_status6'] == '진행중') {
                    $warehousing->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('rgd_status4', '예상경비청구서')
                                ->where('service_korean_name', '보세화물')
                                ->where('rgd_bill_type', 'not like', '%' . 'month' . '%')
                                ->where(function ($q4) {
                                    $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                                })
                                ->whereNull('rgd_status6');
                        })->orWhere(function ($q3) {
                            $q3->where(function ($q4) {
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                                ->where(function ($q4) {
                                    $q4->whereNull('rgd_status6');
                                })
                                ->where(function ($q4) {
                                    $q4->where('rgd_status4', '추가청구서')->orWhere('rgd_status4', '확정청구서');
                                });
                        });
                    });
                }
            }

            if (isset($validated['rgd_status67'])) {
                if ($validated['rgd_status67'] == '정산완료') {
                    $warehousing->where(function ($q) use ($validated) {
                        $q->where(function ($q) use ($validated) {
                            $q->where('rgd_status7', '=', 'taxed')->where('rgd_status6', '=', 'paid');
                        })->orwhere('rgd_status8', 'completed');
                    });
                } else if ($validated['rgd_status67'] == '진행중') {
                    $warehousing->where(function ($q) use ($validated) {
                        $q->where('rgd_status8', '!=', 'completed')->orwhereNull('rgd_status8');
                    })->where(function ($q) use ($validated) {
                        $q->where(function ($q) use ($validated) {
                            $q->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status5')->orwhere('rgd_status5', '!=', 'cancel');
                            })->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status6')->orwhere('rgd_status6', '!=', 'paid');
                            });
                        })->orwhere(function ($q) use ($validated) {
                            $q->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status5')->orwhere('rgd_status5', '!=', 'cancel');
                            })->where(function ($q) use ($validated) {
                                $q->whereNull('rgd_status7')->orwhere('rgd_status7', '!=', 'taxed');
                            });
                        });
                    });
                }
            }


            if (isset($validated['service_korean_name'])) {
                $warehousing->where('service_korean_name', 'like', '%' . $validated['service_korean_name'] . '%');
            }

            if (isset($validated['co_close_yn'])) {
                if ($user->mb_type == 'spasys') {
                    $warehousing->where(function ($q) use ($validated) {
                        $q->where(function ($q1) use ($validated) {
                            $q1->where('service_korean_name', '수입풀필먼트')->whereHas('warehousing.company', function ($query) use ($validated) {
                                $query->where('co_close_yn', $validated['co_close_yn']);
                            })->orwhere('service_korean_name', '!=', '수입풀필먼트')->whereHas('warehousing.company.co_parent', function ($query) use ($validated) {
                                $query->where('co_close_yn', $validated['co_close_yn']);
                            });
                        });
                    });
                } else if ($user->mb_type == 'shipper') {
                    $warehousing->where(function ($q) use ($validated) {
                        $q->whereHas('warehousing.company', function ($query) use ($validated) {
                            $query->where('co_close_yn', $validated['co_close_yn']);
                        });
                    });
                }
            }

            $warehousing_data = $warehousing->get();

            $arr_data = collect($warehousing_data)->map(function ($item) {

                return [
                    'sum_supply_price' => $item['service_korean_name'] == '유통가공' ? $item['rate_data_general']['rdg_supply_price4'] : ($item['service_korean_name'] == '수입풀필먼트' ? $item['rate_data_general']['rdg_supply_price6'] : $item['rate_data_general']['rdg_supply_price7']),

                    'sum_vat' => $item['service_korean_name'] == '유통가공' ? $item['rate_data_general']['rdg_vat4'] : ($item['service_korean_name'] == '수입풀필먼트' ? $item['rate_data_general']['rdg_vat6'] : $item['rate_data_general']['rdg_vat7']),

                    'sum_sum' => $item['service_korean_name'] == '유통가공' ? $item['rate_data_general']['rdg_sum4'] : ($item['service_korean_name'] == '수입풀필먼트' ? $item['rate_data_general']['rdg_sum6'] : $item['rate_data_general']['rdg_sum7']),

                ];
            });

            $sum_supply_price = $arr_data->sum('sum_supply_price');

            $sum_vat = $arr_data->sum('sum_vat');

            $sum_sum = $arr_data->sum('sum_sum');


            $custom = collect([
                'sum_supply_price' => $sum_supply_price,
                'sum_vat' => $sum_vat,
                'sum_sum' => $sum_sum,
            ]);


            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) {
                    if ($item->service_korean_name === "유통가공") {
                        $item->supply_price_total = $item->rate_data_general->rdg_supply_price4 ? $item->rate_data_general->rdg_supply_price4 : 0;
                        $item->vat_price_total = $item->rate_data_general->rdg_vat4 ? $item->rate_data_general->rdg_vat4 : 0;
                        $item->sum_price_total = $item->rate_data_general->rdg_sum4 ? $item->rate_data_general->rdg_sum4 : 0;
                    } else if ($item->service_korean_name === "수입풀필먼트") {
                        $item->supply_price_total = $item->rate_data_general->rdg_supply_price6 ? $item->rate_data_general->rdg_supply_price6 : 0;
                        $item->vat_price_total = $item->rate_data_general->rdg_vat6 ? $item->rate_data_general->rdg_vat6 : 0;
                        $item->sum_price_total = $item->rate_data_general->rdg_sum6 ? $item->rate_data_general->rdg_sum6 : 0;
                    } else {
                        $item->supply_price_total = $item->rate_data_general->rdg_supply_price7 ? $item->rate_data_general->rdg_supply_price7 : 0;
                        $item->vat_price_total = $item->rate_data_general->rdg_vat7 ? $item->rate_data_general->rdg_vat7 : 0;
                        $item->sum_price_total = $item->rate_data_general->rdg_sum7 ? $item->rate_data_general->rdg_sum7 : 0;
                    }

                    return $item;
                })
            );
            $data = $custom->merge($warehousing);

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function get_tax_history(Request $request) //page277

    {
        try {
            $per_page = isset($$request['per_page']) ? $$request['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($$request['page']) ? $$request['page'] : 1;
            $th = CancelBillHistory::with('member')->where('rgd_no', $request->rgd_no)->where('cbh_status_after', '!=', 'taxed')->where('cbh_status_after', '!=', 'cancel')->where('cbh_status_after', '!=', 'edited')->where('cbh_type', 'tax')->orderby('cbh_no', 'DESC')->paginate($per_page, ['*'], 'page', $page);

            return response()->json($th);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function get_tax_invoice_completed_list(WarehousingSearchRequest $request) //page277

    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'warehousing', 'rate_data_general', 't_export', 't_import'])->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('company.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->whereHas('company.contract', function ($q) use ($user) {
                        $q->where('c_calculate_deadline_yn', 'y');
                    });
                })->orderBy('rgd_tax_invoice_date', 'DESC')->orderBy('rgd_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'warehousing', 'rate_data_general', 't_export', 't_import'])->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('company', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->whereHas('company.contract', function ($q) use ($user) {
                        $q->where('c_calculate_deadline_yn', 'y');
                    });
                })->orderBy('rgd_tax_invoice_date', 'DESC')->orderBy('rgd_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'warehousing', 'rate_data_general', 't_export', 't_import'])->whereHas('warehousing', function ($query) use ($user) {
                    $query->whereHas('company.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orWhereHas('company.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })
                    ->whereHas('warehousing', function ($query) use ($user) {
                        $query->whereHas('company.co_parent.contract', function ($q) use ($user) {
                            $q->where('c_calculate_deadline_yn', 'y');
                        })->orWhereHas('company.contract', function ($q) use ($user) {
                            $q->where('c_calculate_deadline_yn', 'y');
                        });
                    });
            }
            $warehousing->where(function ($q) {
                $q->where('rgd_status4', '확정청구서');
            })
                ->where('rgd_status5', 'confirmed')
                ->where('rgd_calculate_deadline_yn', 'y')
                ->whereNull('rgd_status6')
                ->where('rgd_is_show', 'y')
                ->where(function ($q4) {
                    $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                })
                ->whereHas('mb_no', function ($q) use ($user) {
                    $q->where('mb_type', $user->mb_type);
                })
                ->orderBy('rgd_no', 'DESC');

            if (isset($validated['service']) && $validated['service'] != '전체') {
                $warehousing->where(DB::raw('lower(service_korean_name)'), 'like', '%' . strtolower($validated['service']) . '%');
            }

            if (isset($validated['settlement_cycle'])) {
                if ($validated['settlement_cycle'] == '건별')
                    $warehousing->where(DB::raw('lower(rgd_bill_type)'), 'not like', '%' . 'monthly' . '%');
                else if ($validated['settlement_cycle'] == '월별')
                    $warehousing->where(DB::raw('lower(rgd_bill_type)'), 'like', '%' . 'monthly' . '%');
            }

            if (isset($validated['w_schedule_number2'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->whereHas('warehousing', function ($q) use ($validated) {
                        $q->where('w_category_name', '=', '수입풀필먼트')->where('w_schedule_number2', 'like', '%' .  $validated['w_schedule_number2'] . '%');
                    })->orwhereHas('warehousing', function ($q) use ($validated) {
                        $q->where('w_category_name', '=', '유통가공')->whereHas('company', function ($q1) use ($validated) {
                            $q1->where('w_schedule_number2', 'like', '%' .  $validated['w_schedule_number2'] . '%');
                        });
                    })->orwhereHas('t_import', function ($q) use ($validated) {
                        $q->where('ti_h_bl', 'like', '%' .  $validated['w_schedule_number2'] . '%');
                    });
                });
            }

            if (isset($validated['from_date'])) {
                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('warehousing.company.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('warehousing.company', function ($q) use ($validated) {
                    $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['rgd_status4'])) {
                if ($validated['rgd_status4'] == '전체') {
                } else
                    $warehousing->where('rgd_status4', '=', $validated['rgd_status4']);
            }


            if (isset($validated['service_korean_name'])) {
                $warehousing->where('service_korean_name', 'like', '%' . $validated['service_korean_name'] . '%');
            }

            if (isset($validated['rgd_settlement_number'])) {
                $warehousing->where('rgd_settlement_number', 'like', '%' .  $validated['rgd_settlement_number'] . '%');
            }
            if (isset($validated['rgd_status6'])) {
                if ($validated['rgd_status6'] == 'cancel') {
                    $warehousing->where('rgd_status6', '=', 'cancel');
                } else if ($validated['rgd_status6'] == 'paid') {
                    $warehousing->where('rgd_status6', '=', 'paid');
                } else if ($validated['rgd_status6'] == '진행중') {
                    $warehousing->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('rgd_status4', '예상경비청구서')
                                ->where('service_korean_name', '보세화물')
                                ->where('rgd_bill_type', 'not like', '%' . 'month' . '%')
                                ->where(function ($q4) {
                                    $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                                })
                                ->whereNull('rgd_status6');
                        })->orWhere(function ($q3) {
                            $q3->where(function ($q4) {
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                                ->where(function ($q4) {
                                    $q4->whereNull('rgd_status6');
                                })
                                ->where(function ($q4) {
                                    $q4->where('rgd_status4', '추가청구서')->orWhere('rgd_status4', '확정청구서');
                                });
                        });
                    });
                }
            }

            $warehousing_data = $warehousing->get();

            $arr_data = collect($warehousing_data)->map(function ($item) {

                return [
                    'sum_supply_price' => $item['service_korean_name'] == '유통가공' ? $item['rate_data_general']['rdg_supply_price4'] : ($item['service_korean_name'] == '수입풀필먼트' ? $item['rate_data_general']['rdg_supply_price6'] : $item['rate_data_general']['rdg_supply_price7']),

                    'sum_vat' => $item['service_korean_name'] == '유통가공' ? $item['rate_data_general']['rdg_vat4'] : ($item['service_korean_name'] == '수입풀필먼트' ? $item['rate_data_general']['rdg_vat6'] : $item['rate_data_general']['rdg_vat7']),

                    'sum_sum' => $item['service_korean_name'] == '유통가공' ? $item['rate_data_general']['rdg_sum4'] : ($item['service_korean_name'] == '수입풀필먼트' ? $item['rate_data_general']['rdg_sum6'] : $item['rate_data_general']['rdg_sum7']),

                ];
            });

            $sum_supply_price = $arr_data->sum('sum_supply_price');

            $sum_vat = $arr_data->sum('sum_vat');

            $sum_sum = $arr_data->sum('sum_sum');


            $custom = collect([
                'sum_supply_price' => $sum_supply_price,
                'sum_vat' => $sum_vat,
                'sum_sum' => $sum_sum,
            ]);


            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);

            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) {
                    if ($item->service_korean_name === "유통가공") {
                        $item->supply_price_total = $item->rate_data_general->rdg_supply_price4 ? $item->rate_data_general->rdg_supply_price4 : 0;
                        $item->vat_price_total = $item->rate_data_general->rdg_vat4 ? $item->rate_data_general->rdg_vat4 : 0;
                        $item->sum_price_total = $item->rate_data_general->rdg_sum4 ? $item->rate_data_general->rdg_sum4 : 0;
                    } else if ($item->service_korean_name === "수입풀필먼트") {
                        $item->supply_price_total = $item->rate_data_general->rdg_supply_price6 ? $item->rate_data_general->rdg_supply_price6 : 0;
                        $item->vat_price_total = $item->rate_data_general->rdg_vat6 ? $item->rate_data_general->rdg_vat6 : 0;
                        $item->sum_price_total = $item->rate_data_general->rdg_sum6 ? $item->rate_data_general->rdg_sum6 : 0;
                    } else {
                        $item->supply_price_total = $item->rate_data_general->rdg_supply_price7 ? $item->rate_data_general->rdg_supply_price7 : 0;
                        $item->vat_price_total = $item->rate_data_general->rdg_vat7 ? $item->rate_data_general->rdg_vat7 : 0;
                        $item->sum_price_total = $item->rate_data_general->rdg_sum7 ? $item->rate_data_general->rdg_sum7 : 0;
                    }

                    return $item;
                })
            );
            //return DB::getQueryLog();

            $data = $custom->merge($warehousing);

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function get_tid_list(Request $request) //page277
    {
        try {
            DB::enableQueryLog();
            $user = Auth::user();
            $tid = TaxInvoiceDivide::where('rgd_no', $request->rgd_no)->get();


            $rgd = ReceivingGoodsDelivery::with('warehousing')->where('rgd_no', $request->rgd_no)->first();

            if ($tid->count() == 0) {
                $tid = TaxInvoiceDivide::where('tid_no', $rgd->tid_no)->get();
            }

            if ($user->mb_type == 'spasys' && $rgd->service_korean_name == '수입풀필먼트') {
                $company = Company::with(['co_parent', 'adjustment_group'])->where('co_no', $rgd['warehousing']['co_no'])->first();
            } else {
                $company = Company::with(['co_parent', 'adjustment_group'])->where('co_no', $rgd['warehousing']['company']['co_parent']['co_no'])->first();
            }

            return response()->json([
                'tid' => $tid,
                'company' => $company
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function get_cr_list(Request $request) //page277
    {
        try {
            DB::enableQueryLog();
            $user = Auth::user();
            $tid = CashReceipt::where('rgd_no', $request->rgd_no)->get();

            $rgd = ReceivingGoodsDelivery::with('warehousing')->where('rgd_no', $request->rgd_no)->first();

            if ($user->mb_type == 'spasys' && $rgd->service_korean_name == '수입풀필먼트') {
                $company = Company::with(['co_parent', 'adjustment_group'])->where('co_no', $rgd['warehousing']['co_no'])->first();
            } else {
                $company = Company::with(['co_parent', 'adjustment_group'])->where('co_no', $rgd['warehousing']['company']['co_parent']['co_no'])->first();
            }

            return response()->json([
                'tid' => $tid,
                'company' => $company
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function create_tid(Request $request) //page277
    {
        try {
            DB::beginTransaction();
            if ($request->type == 'option') {
                $user = Auth::user();

                $tids = TaxInvoiceDivide::where('rgd_no', $request->rgd_no)->get();
                $tax_number = CommonFunc::generate_tax_number($request->rgd_no);

                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                    'rgd_tax_invoice_date' => Carbon::now()->toDateTimeString(),
                    'rgd_tax_invoice_number' => $tax_number ? $tax_number : null,
                    'rgd_status7' => 'taxed',
                ]);

                $rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $request->rgd_no)->first();

                CommonFunc::insert_alarm('[공통] 계산서발행 안내', $rgd, $user, null, 'settle_payment', null);

                $i = 0;
                foreach ($request->tid_list as $tid) {
                    if (isset($tid['tid_no'])) {
                        // if(false){
                        $tid_ = TaxInvoiceDivide::where('tid_no', $tid['tid_no']);
                        $tid_->update([
                            'tid_supply_price' => $tid['tid_supply_price'],
                            'tid_vat' => $tid['tid_vat'],
                            'tid_sum' => $tid['tid_sum'],
                            'rgd_no' => isset($tid['rgd_no']) ? $tid['rgd_no'] : $request->rgd_no,
                            'tid_number' => isset($tid['tid_number']) ? $tid['tid_number'] : null,
                            'co_license' => $request['company']['co_license'],
                            'co_owner' => $request['company']['co_owner'],
                            'co_name' => $request['company']['co_name'],
                            'co_major' => $request['company']['co_major'],
                            'co_address' => $request['company']['co_address'],
                            'co_email' => $request['ag']['ag_email'] ? $request['ag']['ag_email'] : null,
                            'co_email2' => $request['ag']['ag_email2'] ? $request['ag']['ag_email2'] : null,
                            'rgd_number' => $tax_number ? $tax_number : null,
                            'mb_no' => $user->mb_no,
                        ]);
                        $id = $tid_->first()->tid_no;

                        $cbh = CancelBillHistory::insertGetId([
                            'rgd_no' => $request->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_before' => 'taxed',
                            'cbh_status_after' => 'edited'
                        ]);
                    } else {
                        $id = TaxInvoiceDivide::insertGetId([
                            'tid_supply_price' => $tid['tid_supply_price'],
                            'tid_vat' => $tid['tid_vat'],
                            'tid_sum' => $tid['tid_sum'],
                            'rgd_no' => isset($tid['rgd_no']) ? $tid['rgd_no'] : $request->rgd_no,
                            'tid_number' => isset($tid['tid_number']) ? $tid['tid_number'] : null,
                            'co_license' => $request['company']['co_license'],
                            'co_owner' => $request['company']['co_owner'],
                            'co_name' => $request['company']['co_name'],
                            'co_major' => $request['company']['co_major'],
                            'co_address' => $request['company']['co_address'],
                            'co_email' => $request['ag']['ag_email'] ? $request['ag']['ag_email'] : null,
                            'co_email2' => $request['ag']['ag_email2'] ? $request['ag']['ag_email2'] : null,
                            'rgd_number' => $tax_number ? $tax_number : null,
                            'mb_no' => $user->mb_no,
                        ]);

                        $cbh = CancelBillHistory::insertGetId([
                            'rgd_no' => $request->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_after' => 'taxed'
                        ]);

                        if ($rgd['rgd_status6'] == 'paid' && $i == 0) {
                            CancelBillHistory::insertGetId([
                                'rgd_no' => $rgd['rgd_no'],
                                'mb_no' => $user->mb_no,
                                'cbh_type' => 'tax',
                                'cbh_status_before' => $rgd['rgd_status8'],
                                'cbh_status_after' => 'completed'
                            ]);

                            ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                                'rgd_status8' => 'completed',
                            ]);

                            //UPDATE EST BILL
                            $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                            if($est_rgd->rgd_status8 != 'completed'){
                                ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                                    'rgd_status8' => 'completed',
                                ]);
                                CancelBillHistory::insertGetId([
                                    'rgd_no' => $est_rgd->rgd_no,
                                    'mb_no' => $user->mb_no,
                                    'cbh_type' => 'tax',
                                    'cbh_status_before' => $est_rgd->rgd_status8,
                                    'cbh_status_after' => 'completed'
                                ]);
                            }
                        }
                        $i++;
                    }
                    $ids[] = $id;
                }
                TaxInvoiceDivide::where('rgd_no', $request->rgd_no)
                    ->whereNotIn('tid_no', $ids)->delete();

                DB::commit();
                return response()->json([
                    'message' => Messages::MSG_0007,
                    'tid_list' => $tids,
                ]);
            } else if ($request->type == 'receipt') {
                $user = Auth::user();

                $tids = CashReceipt::where('rgd_no', $request->rgd_no)->get();
                $tax_number = CommonFunc::generate_tax_number($request->rgd_no);

                $rgd = ReceivingGoodsDelivery::where('rgd_no', $request->rgd_no)->update([
                    'rgd_tax_invoice_date' => Carbon::now()->toDateTimeString(),
                    'rgd_tax_invoice_number' => $tax_number ? $tax_number : null,
                    'rgd_status7' => 'receipted',
                ]);

                foreach ($request->tid_list as $tid) {
                    if (isset($tid['cr_no'])) {
                        // if(false){
                        $tid_ = CashReceipt::where('cr_no', $tid['cr_no']);
                        $tid_->update([
                            'cr_supply_price' => $tid['tid_supply_price'],
                            'cr_vat' => $tid['tid_vat'],
                            'cr_sum' => $tid['tid_sum'],
                            'rgd_no' => isset($tid['rgd_no']) ? $tid['rgd_no'] : $request->rgd_no,
                            'cr_number' => isset($tid['tid_number']) ? $tid['tid_number'] : null,
                            'co_license' => $request['company']['co_license'],
                            'co_owner' => $request['company']['co_owner'],
                            'co_name' => $request['company']['co_name'],
                            'co_major' => $request['company']['co_major'],
                            'co_address' => $request['company']['co_address'],
                            'rgd_number' => $tax_number ? $tax_number : null,
                            'mb_no' => $user->mb_no,
                        ]);
                        $id = $tid_->first()->tid_no;

                        $cbh = CancelBillHistory::insertGetId([
                            'rgd_no' => $request->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_after' => 'edited'
                        ]);
                    } else {
                        $id = CashReceipt::insertGetId([
                            'cr_supply_price' => $tid['tid_supply_price'],
                            'cr_vat' => $tid['tid_vat'],
                            'cr_sum' => $tid['tid_sum'],
                            'rgd_no' => isset($tid['rgd_no']) ? $tid['rgd_no'] : $request->rgd_no,
                            'cr_number' => isset($tid['tid_number']) ? $tid['tid_number'] : null,
                            'co_license' => $request['company']['co_license'],
                            'co_owner' => $request['company']['co_owner'],
                            'co_name' => $request['company']['co_name'],
                            'co_major' => $request['company']['co_major'],
                            'co_address' => $request['company']['co_address'],
                            'rgd_number' => $tax_number ? $tax_number : null,
                            'mb_no' => $user->mb_no,
                        ]);

                        $cbh = CancelBillHistory::insertGetId([
                            'rgd_no' => $request->rgd_no,
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_after' => 'taxed'
                        ]);

                        if ($rgd['rgd_status6'] == 'paid') {
                            CancelBillHistory::insertGetId([
                                'rgd_no' => $rgd['rgd_no'],
                                'mb_no' => $user->mb_no,
                                'cbh_type' => 'tax',
                                'cbh_status_before' => $rgd['rgd_status8'],
                                'cbh_status_after' => 'completed'
                            ]);

                            ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                                'rgd_status8' => 'completed',
                            ]);

                            //UPDATE EST BILL
                            $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                            if($est_rgd->rgd_status8 != 'completed'){
                                ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                                    'rgd_status8' => 'completed',
                                ]);
                                CancelBillHistory::insertGetId([
                                    'rgd_no' => $est_rgd->rgd_no,
                                    'mb_no' => $user->mb_no,
                                    'cbh_type' => 'tax',
                                    'cbh_status_before' => $est_rgd->rgd_status8,
                                    'cbh_status_after' => 'completed'
                                ]);
                            }
                        }
                    }
                    $ids[] = $id;
                }
                CashReceipt::where('rgd_no', $request->rgd_no)
                    ->whereNotIn('cr_no', $ids)->delete();

                DB::commit();
                return response()->json([
                    'message' => Messages::MSG_0007,
                    'cr_list' => $tids,
                ]);
            } else if ($request->type == 'add_all') {
                $user = Auth::user();

                $id = TaxInvoiceDivide::insertGetId([
                    'tid_supply_price' => $request->supply_price,
                    'tid_vat' => $request->vat,
                    'tid_sum' => $request->sum,
                    'tid_number' => $request->tid_number,
                    'co_license' => $request['company']['co_license'],
                    'co_owner' => $request['company']['co_owner'],
                    'co_name' => $request['company']['co_name'],
                    'co_major' => $request['company']['co_major'],
                    'co_address' => $request['company']['co_address'],
                    'co_email' => $request['ag']['ag_email'] ? $request['ag']['ag_email'] : null,
                    'co_email2' => $request['ag']['ag_email2'] ? $request['ag']['ag_email2'] : null,
                    'mb_no' => $user->mb_no,
                ]);

                foreach ($request->rgds as $rgd) {
                    $tax_number = CommonFunc::generate_tax_number($rgd['rgd_no']);

                    $rgd_ = ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                        'rgd_tax_invoice_date' => Carbon::now()->toDateTimeString(),
                        'rgd_tax_invoice_number' => $tax_number ? $tax_number : null,
                        'rgd_status7' => 'taxed',
                        'tid_no' => $id
                    ]);

                    $rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $rgd['rgd_no'])->first();

                    CommonFunc::insert_alarm('[공통] 계산서발행 안내', $rgd, $user, null, 'settle_payment', null);

                    $cbh = CancelBillHistory::insertGetId([
                        'rgd_no' => $rgd['rgd_no'],
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_after' => 'taxed'
                    ]);

                    if ($rgd['rgd_status6'] == 'paid') {
                        CancelBillHistory::insertGetId([
                            'rgd_no' => $rgd['rgd_no'],
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_before' => $rgd['rgd_status8'],
                            'cbh_status_after' => 'completed'
                        ]);

                        ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                            'rgd_status8' => 'completed',
                        ]);

                        //UPDATE EST BILL
                        $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                        if($est_rgd->rgd_status8 != 'completed'){
                            ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                                'rgd_status8' => 'completed',
                            ]);
                            CancelBillHistory::insertGetId([
                                'rgd_no' => $est_rgd->rgd_no,
                                'mb_no' => $user->mb_no,
                                'cbh_type' => 'tax',
                                'cbh_status_before' => $est_rgd->rgd_status8,
                                'cbh_status_after' => 'completed'
                            ]);
                        }
                    }
                }
                DB::commit();
                return response()->json([
                    'message' => Messages::MSG_0007,
                ]);
            } else if ($request->type == 'separate') {
                $user = Auth::user();

                foreach ($request->rgds as $rgd) {

                    $tax_number = CommonFunc::generate_tax_number($rgd['rgd_no']);

                    ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                        'rgd_tax_invoice_date' => Carbon::now()->toDateTimeString(),
                        'rgd_status7' => 'taxed',
                        'rgd_tax_invoice_number' => $tax_number ? $tax_number : null,
                    ]);

                    $rgd = ReceivingGoodsDelivery::with(['warehousing'])->where('rgd_no', $rgd['rgd_no'])->first();

                    CommonFunc::insert_alarm('[공통] 계산서발행 안내', $rgd, $user, null, 'settle_payment', null);

                    $rgd = ReceivingGoodsDelivery::with(['warehousing', 'rate_data_general'])->where('rgd_no', $rgd['rgd_no'])->first();

                    if ($user->mb_type == 'spasys' && $rgd->service_korean_name == '수입풀필먼트') {
                        $company = Company::with(['co_parent', 'adjustment_group'])->where('co_no', $rgd['warehousing']['co_no'])->first();
                    } else {
                        $company = Company::with(['co_parent', 'adjustment_group'])->where('co_no', $rgd['warehousing']['company']['co_parent']['co_no'])->first();
                    }

                    $ag = AdjustmentGroup::where('ag_no', $rgd->rate_data_general->ag_no)->first();

                    $id = TaxInvoiceDivide::insertGetId([
                        'tid_supply_price' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_supply_price7'] : ($rgd['service_korean_name']  == '수입풀필먼트' ? $rgd['rate_data_general']['rdg_supply_price6'] : $rgd['rate_data_general']['rdg_supply_price4']),
                        'tid_vat' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_vat7'] : ($rgd['service_korean_name']  == '수입풀필먼트' ? $rgd['rate_data_general']['rdg_vat6'] : $rgd['rate_data_general']['rdg_vat4']),
                        'tid_sum' => $rgd['service_korean_name']  == '보세화물' ? $rgd['rate_data_general']['rdg_sum7'] : ($rgd['service_korean_name']  == '수입풀필먼트' ? $rgd['rate_data_general']['rdg_sum6'] : $rgd['rate_data_general']['rdg_sum4']),
                        'rgd_no' => $rgd['rgd_no'],
                        'tid_number' => $rgd['rgd_settlement_number'],
                        'co_license' => $company['co_license'],
                        'co_owner' => $company['co_owner'],
                        'co_name' => $company['co_name'],
                        'co_major' => $company['co_major'],
                        'co_address' => $company['co_address'],
                        'co_email' => $ag['ag_email'] ? $ag['ag_email'] : null,
                        'co_email2' => $ag['ag_email2'] ? $ag['ag_email2'] : null,
                        'rgd_number' => $tax_number ? $tax_number : null,
                        'mb_no' => $user->mb_no,
                    ]);

                    $cbh = CancelBillHistory::insertGetId([
                        'rgd_no' => $rgd['rgd_no'],
                        'mb_no' => $user->mb_no,
                        'cbh_type' => 'tax',
                        'cbh_status_after' => 'taxed'
                    ]);

                    if ($rgd['rgd_status6'] == 'paid') {
                        CancelBillHistory::insertGetId([
                            'rgd_no' => $rgd['rgd_no'],
                            'mb_no' => $user->mb_no,
                            'cbh_type' => 'tax',
                            'cbh_status_before' => $rgd['rgd_status8'],
                            'cbh_status_after' => 'completed'
                        ]);

                        ReceivingGoodsDelivery::where('rgd_no', $rgd['rgd_no'])->update([
                            'rgd_status8' => 'completed',
                        ]);

                       //UPDATE EST BILL
                       $est_rgd = ReceivingGoodsDelivery::where('rgd_no', $rgd->rgd_parent_no)->first();
                       if($est_rgd->rgd_status8 != 'completed'){
                            ReceivingGoodsDelivery::where('rgd_no', $est_rgd->rgd_no)->update([
                                'rgd_status8' => 'completed',
                            ]);
                            CancelBillHistory::insertGetId([
                                'rgd_no' => $est_rgd->rgd_no,
                                'mb_no' => $user->mb_no,
                                'cbh_type' => 'tax',
                                'cbh_status_before' => $est_rgd->rgd_status8,
                                'cbh_status_after' => 'completed'
                            ]);
                        }
                    }
                }
                DB::commit();
                return response()->json([
                    'message' => Messages::MSG_0007,
                ]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function importExcelDistribution(WarehousingSearchRequest $request)
    {
        DB::beginTransaction();
        $f = Storage::disk('public')->put('files/tmp', $request['file']);

        $path = storage_path('app/public') . '/' . $f;
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        $co_no = Auth::user()->co_no ? Auth::user()->co_no : null;
        $member = Member::where('mb_id', Auth::user()->mb_id)->first();

        $sheet = $spreadsheet->getSheet(0);
        $warehousing_data = $sheet->toArray(null, true, true, true);
        $sheet2 = $spreadsheet->getSheet(1);
        $warehousing_item_data = $sheet2->toArray(null, true, true, true);

        $check_update = 0;
        foreach ($warehousing_data as $key => $warehouse) {
            if ($key <= 2) {
                continue;
            }
            if (!empty($warehouse['A'])) {
                $receivingGoodsDelivery = ReceivingGoodsDelivery::where('w_no', '=', $warehouse['A'])->first();
                $receivingGoodsDelivery->update(array(
                    'co_name' => isset($warehouse['B']) ? $warehouse['B'] : '',
                    'rgd_contents' => isset($warehouse['C']) ? $warehouse['C'] : '',
                    'rgd_address' => isset($warehouse['D']) ? $warehouse['D'] : '',
                    'rgd_address_detail' => isset($warehouse['E']) ? $warehouse['E'] : '',
                    'rgd_receiver' => isset($warehouse['F']) ? $warehouse['F'] : '',
                    'rgd_hp' => isset($warehouse['G']) ? $warehouse['G'] : '',
                    'rgd_memo' => isset($warehouse['H']) ? $warehouse['H'] : '',
                ));
                $total_wi_number = array();
                foreach ($warehousing_item_data as $key2 => $warehousing_item) {
                    if ($key2 <= 2) {
                        continue;
                    }
                    if ($warehouse['A'] == $warehousing_item['A']) {
                        $warehousingItem = WarehousingItem::where('item_no', '=', $warehousing_item['B'])->where('w_no', '=', $warehousing_item['A'])->first();
                        if (!empty($warehousingItem) && !empty($warehousing_item['C'])) {
                            $total_wi_number[$warehousing_item['A']][] = $warehousing_item['C'];
                            $warehousingItem->update(array(
                                'w_no' => $warehousing_item['A'],
                                'item_no' => $warehousing_item['B'],
                                'wi_number' => $warehousing_item['C'],
                            ));
                        }
                    }
                }
                $check_update = 1;
            }
        }
        if ($check_update == 1) {
            DB::commit();
            return response()->json([
                'message' => '데이터 업로드 성공',
                'status' => 1,
            ], 201);
        } else {
            DB::rollback();
            return response()->json([
                'message' => '데이터 가져오기 실패',
                'status' => 0,
            ], 201);
        }
    }

    public function scheduleListImport(Request $request)
    {
        return response()->json([
            'message' => '데이터 업로드 성공',
            'status' => 1,
        ], 201);
    }
    public function downloadBondedSettlement(Request $request)
    {
        try {
            $user = Auth::user();
            $per_page = isset($request['per_page']) ? $request['per_page'] : 100000;
            $page = isset($request['page']) ? $request['page'] : 1;
            // $import_schedule = ImportSchedule::with('co_no')->with('files')->orderBy('is_no', 'DESC');

            // if (isset($request['from_date'])) {
            //     $import_schedule->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($request['from_date'])));
            // }

            // if (isset($request['to_date'])) {
            //     $import_schedule->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($request['to_date'])));
            // }

            // if (isset($request['co_name'])) {
            //     $import_schedule->whereHas('co_no', function($q) use($request) {
            //         return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($request['co_name']) . '%');
            //     });
            // }

            // if (isset($request['m_bl'])) {
            //     $import_schedule->where('m_bl', 'like', '%' . $request['m_bl'] . '%');
            // }

            // if (isset($request['h_bl'])) {
            //     $import_schedule->where('h_bl', 'like', '%' . $request['h_bl'] . '%');
            // }

            // if (isset($request['logistic_manage_number'])) {
            //     $import_schedule->where('logistic_manage_number', 'like', '%' . $request['logistic_manage_number'] . '%');
            // }

            // $import_schedule = $import_schedule->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'No');
            $sheet->setCellValue('B1', '가맹점');
            $sheet->setCellValue('C1', '화주');
            $sheet->setCellValue('D1', '서비스');
            $sheet->setCellValue('E1', '정산주기');
            $sheet->setCellValue('F1', '화물번호');
            $sheet->setCellValue('G1', '입고번호');
            $sheet->setCellValue('H1', '청구번호');
            $sheet->setCellValue('I1', '유형');

            $num_row = 2;
            // $data_schedules =  json_decode($import_schedule);
            if (!empty($data_schedules)) {
                foreach ($data_schedules as $data) {
                    $sheet->setCellValue('A' . $num_row, '');
                    $sheet->setCellValue('B' . $num_row, '');
                    $sheet->setCellValue('C' . $num_row, '');
                    $sheet->setCellValue('D' . $num_row, '');
                    $sheet->setCellValue('E' . $num_row, '');
                    $sheet->setCellValue('F' . $num_row, '');
                    $sheet->setCellValue('G' . $num_row, '');
                    $sheet->setCellValue('H' . $num_row, '');
                    $sheet->setCellValue('I' . $num_row, '');
                    $num_row++;
                }
            }

            $Excel_writer = new Xlsx($spreadsheet);
            if (isset($user->mb_no)) {
                $path = '../storage/download/' . $user->mb_no . '/';
            } else {
                $path = '../storage/download/no-name/';
            }
            if (!is_dir($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }
            $mask = $path . 'DownloadBondedSettlement-*.*';
            array_map('unlink', glob($mask));
            $file_name_download = $path . 'DownloadBondedSettlement-' . date('YmdHis') . '.Xlsx';
            $Excel_writer->save($file_name_download);
            return response()->json([
                'status' => 1,
                'link_download' => $file_name_download,
                'message' => 'Download File',
            ], 500);
            ob_end_clean();
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    public function UpdateStatusDelivery(Request $request)
    {
        //return $request;
        try {
            DB::beginTransaction();

            if ($request->service == "유통가공") {
                foreach ($request->datachkbox as $value) {
                    $rgd = ReceivingGoodsDelivery::where('rgd_no', $value['rgd_no'])
                        ->update([
                            'rgd_status3' => "배송완료",
                            'rgd_arrive_day' => Carbon::now()->toDateTimeString(),
                        ]);
                }
            } elseif ($request->service == "수입풀필먼트") {
                foreach ($request->datachkbox as $value) {
                    // $rgd = ReceivingGoodsDelivery::where('ss_no', $value['ss_no'])
                    //     ->update([
                    //         'rgd_status3' => "배송완료",
                    //     ]);
                    $rgd = ReceivingGoodsDelivery::updateOrCreate(
                        [
                            'ss_no' =>  $value['ss_no']
                        ],
                        [
                            'mb_no' => Auth::user()->mb_no,
                            'service_korean_name' => '수입풀필먼트',
                            'rgd_status1' => "출고",
                            'rgd_status3' => "배송완료",
                        ]
                    );
                }
            } else if ($request->service == "보세화물") {
                foreach ($request->datachkbox as $value) {
                    // foreach ($value as $receiving_goods_delivery) {
                    //     $rgd = ReceivingGoodsDelivery::where('is_no', $receiving_goods_delivery['is_no'])
                    //         ->update(
                    //             [
                    //             'rgd_status3' => "배송완료",
                    //             ]
                    //         );
                    // }
                    //return $value['is_no'];
                    if (isset($value['is_no'])) {
                        $check_is_no = ReceivingGoodsDelivery::where('is_no', $value['is_no'])->first();
                    }
                    if (isset($value['te_carry_out_number'])) {
                        $is_no = $value['te_carry_out_number'];
                    } else if (isset($value['ti_carry_in_number'])) {
                        $is_no = $value['ti_carry_in_number'];
                    } else {
                        $is_no = $value['tie_logistic_manage_number'];
                    }
                    if (isset($check_is_no)) {
                        $rgd = ReceivingGoodsDelivery::updateOrCreate(
                            [
                                'is_no' =>  $value['is_no']
                            ],
                            [
                                'mb_no' => Auth::user()->mb_no,
                                'service_korean_name' => '보세화물',
                                'rgd_status3' => "배송완료",
                                'rgd_confirmed_date' => Carbon::now()->toDateTimeString(),
                            ]
                        );
                    } else {
                        $rgd = ReceivingGoodsDelivery::insertGetId([
                            'mb_no' => Auth::user()->mb_no,
                            'service_korean_name' => '보세화물',
                            'rgd_status3' => "배송완료",
                            'is_no' => $is_no,
                            'rgd_confirmed_date' => Carbon::now()->toDateTimeString(),

                        ]);
                    }
                }
            } else {
                foreach ($request->datachkbox as $value) {
                    if (isset($value['rgd_no'])) {
                        $rgd = ReceivingGoodsDelivery::where('rgd_no', $value['rgd_no'])
                            ->update([
                                'rgd_status3' => "배송완료",
                            ]);
                    } else if (isset($value['ss_no'])) {
                        // $rgd = ReceivingGoodsDelivery::where('ss_no', $value['ss_no'])
                        // ->update([
                        //     'rgd_status3' => "배송완료",
                        // ]);
                        $rgd = ReceivingGoodsDelivery::updateOrCreate(
                            [
                                'ss_no' =>  $value['ss_no']
                            ],
                            [
                                'mb_no' => Auth::user()->mb_no,
                                'service_korean_name' => '수입풀필먼트',
                                'rgd_status1' => "출고",
                                'rgd_status3' => "배송완료",
                            ]
                        );
                    } else {
                        foreach ($value['receiving_goods_delivery'] as $receiving_goods_delivery) {
                            $rgd = ReceivingGoodsDelivery::where('rgd_no', $receiving_goods_delivery['rgd_no'])
                                ->update([
                                    'rgd_status3' => "배송완료",
                                ]);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rgd' => isset($rgd) ? $rgd : '',
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }


    public function fulfillment_billing()
    {
        try {
            $user = Auth::user();
            $co_no = $user->co_no;

            $companies = Company::with(['co_parent', 'adjustment_group'])->where(function ($q) use ($co_no, $user) {

                $q->WhereHas('co_parent', function ($q) use ($co_no) {
                    $q->where('co_no', $co_no);
                });


                // $q->whereHas('co_parent', function($q) use($co_no){
                //     $q->where('co_no', $co_no);
                // })->orWhereHas('co_parent.co_parent', function($q) use($co_no){
                //     $q->where('co_no', $co_no);
                // });
            })->where('co_service', 'like', '%' . '수입풀필먼트' . '%')->get();

            // $rate_data = RateData::where('rd_cate_meta1', '수입풀필먼트');

            // if ($user->mb_type == 'spasys') {
            //     $rate_data = $rate_data->where('co_no', $co_no);
            // } else if ($user->mb_type == 'shop' || $user->mb_type == 'shipper') {
            //     $rmd = RateMetaData::where('co_no', $co_no)->latest('created_at')->first();
            //     $rate_data = $rate_data->where('rd_co_no', $co_no);
            //     if (isset($rmd->rmd_no)) {
            //         $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
            //     }
            // } else {
            //     $rate_data = $rate_data->where('co_no', $co_no);
            // }

            // $rate_data = $rate_data->get();

            return response()->json([
                'message' => Messages::MSG_0007,
                'companies' => $companies,
                // 'rate_data' => $rate_data,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function fulfillment_create_billing(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $warehousing2 = Warehousing::join(
                    DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                    'm.w_no',
                    '=',
                    'warehousing.w_no'
                )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });
                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_status'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                    ->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
            } else if ($user->mb_type == 'shipper') {
                $warehousing2 = Warehousing::join(
                    DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                    'm.w_no',
                    '=',
                    'warehousing.w_no'
                )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });
                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_status'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                    ->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
            } else if ($user->mb_type == 'spasys') {

                $warehousing2 = Warehousing::join(
                    DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                    'm.w_no',
                    '=',
                    'warehousing.w_no'
                )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });

                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child', 'rate_data_general', 'warehousing_status'])->where('w_category_name', '=', '수입풀필먼트')->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')
                    ->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
            }

            $warehousing->where(function ($q) {
                $q->whereHas('warehousing_status', function ($q) {
                    $q->where('status', '!=', '입고 취소');
                })->orWhereDoesntHave('warehousing_status');
            })->whereDoesntHave('rate_data_general');

            if (isset($request->from_date)) {

                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($request->from_date)));
            }

            if (isset($request->to_date)) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($request->to_date)));
            }
            // if (isset($validated['co_parent_name'])) {
            //     $warehousing->whereHas('co_no.co_parent', function ($query) use ($validated) {
            //         $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
            //     });
            // }
            // if (isset($validated['co_name'])) {
            //     $warehousing->whereHas('co_no', function ($q) use ($validated) {
            //         return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
            //     });
            // }

            $warehousing->whereHas('co_no', function ($q) use ($request) {
                return $q->where('co_no', $request->co_no)
                    ->orWhereHas('co_parent', function ($q) use ($request) {
                        $q->where('co_no', $request->co_no);
                    });
            });
            $warehousing_list = $warehousing->orderBy('w_no', 'DESC')->get();


            // if(count($warehousing_list) == 0){
            //     return response()->json([
            //         'message' => '입고된 화물내역이 없습니다.',
            //         'error' => 'y'
            //     ]);
            // }

            $amount = $warehousing->orWhereIn('w_no', $w_no_in)->orderBy('w_no', 'DESC')->sum('w_amount');

            if ($user->mb_type == 'shop') {
                $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms'])->whereNotNull('trans_no')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('ss_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms'])->whereNotNull('trans_no')->whereHas('ContractWms.company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('ss_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms'])->whereNotNull('trans_no')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('ss_no', 'DESC');
            }

            if (isset($request->from_date)) {

                $schedule_shipment->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($request->from_date)));
            }

            if (isset($request->to_date)) {
                $schedule_shipment->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($request->to_date)));
            }

            $amount_export = $schedule_shipment->sum('qty');
            // return $warehousing_list[0]['w_schedule_number'];
            if (count($warehousing->get()) > 0) {
                $first_name_item = $warehousing->get()[0]['warehousing_item'][0]['item'] ? $warehousing->get()[0]['warehousing_item'][0]['item']['item_name'] : null;
                $total_item = $warehousing->get()[0]['warehousing_item']->count();
                $final_total = (($total_item / 2)  - 1);
            }

            $w_no_data = Warehousing::insertGetId([
                'mb_no' => $user->mb_no,
                'w_schedule_amount' => $amount,
                'w_amount' => $amount,
                'w_type' => 'IW',
                'w_category_name' => '수입풀필먼트',
                'w_no_cargo' => count($warehousing->get()) > 0 ? $warehousing_list[0]['w_no'] : null,
                'w_amount_export' => $amount_export,
                'w_schedule_number_settle' => count($warehousing->get()) > 0 ? $warehousing_list[0]['w_schedule_number'] : null,
                'w_schedule_number_settle2' => count($warehousing->get()) > 0 ? $warehousing_list[0]['w_schedule_number2'] : null,
                'co_no' => $request->co_no,
            ]);

            // $schedule_number = (new CommonFunc)->generate_w_schedule_number($w_no_data, 'IW');
            // Warehousing::where('w_no', $w_no_data)->update([
            //     'w_schedule_number' => null
            // ]);
            //THUONG EDIT TO MAKE SETTLEMENT
            $rgd_no = ReceivingGoodsDelivery::insertGetId([
                'mb_no' => $user->mb_no,
                'w_no' => $w_no_data,
                'service_korean_name' => '수입풀필먼트',
                'rgd_status1' => '입고',
                'rgd_status2' => '작업완료',
                'rgd_monthbill_start' => Carbon::createFromFormat('Y-m-d', $request->from_date),
                'rgd_monthbill_end' => Carbon::createFromFormat('Y-m-d', $request->to_date),
                'rgd_item_first_name' => isset($first_name_item) && isset($final_total) ? ($first_name_item . '외' . ' ' . $final_total . '건') : '',
            ]);

            RateDataGeneral::insertGetId([
                'mb_no' => $user->mb_no,
                'w_no' => $w_no_data,
                'rgd_no' => $rgd_no,
                'rgd_no_expectation' => $rgd_no,
                'rdg_set_type' => $request->adjustment_group,
                'ag_no' => $request->ag_no,
                'rdg_bill_type' =>  $user->mb_type == 'spasys' ? 'final_spasys' : 'final_shop',
            ]);


            foreach ($warehousing_list as $wl) {
                WarehousingSettlement::insertGetId([
                    'mb_no' => $user->mb_no,
                    'rgd_no' => $rgd_no,
                    'w_no' => $wl['w_no'],
                    'w_no_settlement' => $w_no_data,
                    'w_amount' => $wl['w_amount'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => Messages::MSG_0007,
                'rgd_no' => $rgd_no,
                '$warehousing' => $warehousing->get()
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function load_table_top_right($rgd_no)
    {
        $rgd = ReceivingGoodsDelivery::with(['cancel_bill_history', 'rgd_child', 'rate_data_general', 'payment'])->find($rgd_no);

        $tax_history = CancelBillHistory::where('rgd_no', $rgd_no)->where('cbh_type', 'tax')->where('cbh_status_after', 'in_process')->first();

        $approval_history = CancelBillHistory::where('rgd_no', $rgd_no)->whereIn('cbh_type', ['approval'])->first();

        if($rgd->rgd_status4 == '예상경비청구서' && $rgd->service_korean_name != '수입풀필먼트' && !str_contains($rgd->rgd_bill_type, 'month')){
            $payment_history = CancelBillHistory::where('rgd_no', $rgd_no)->where('cbh_type', 'payment')->where('cbh_status_after', 'request_bill')->first();

            if (empty($payment_history->cbh_no) && $rgd->rgd_calculate_deadline_yn == 'y') {
    
                CancelBillHistory::insertGetId([
                    'rgd_no' => $rgd_no,
                    'mb_no' => $rgd->mb_no,
                    'cbh_type' => 'payment',
                    'cbh_status_after' => 'request_bill',
                    'created_at' => $rgd->created_at,
                    'updated_at' => $rgd->updated_at,
                ]);
            }
        }

        if (empty($tax_history->cbh_no)) {
            ReceivingGoodsDelivery::where('rgd_settlement_number', $rgd->rgd_settlement_number)->update([
                'rgd_status8' => 'in_process',
            ]);

            CancelBillHistory::insertGetId([
                'rgd_no' => $rgd_no,
                'mb_no' => $rgd->mb_no,
                'cbh_type' => 'tax',
                'cbh_status_after' => 'in_process',
                'created_at' => $rgd->created_at,
                'updated_at' => $rgd->updated_at,
            ]);
        }

        if (empty($approval_history->cbh_no)) {
            CancelBillHistory::insertGetId([
                'rgd_no' => $rgd_no,
                'mb_no' => $rgd->mb_no,
                'cbh_type' => 'approval',
                'cbh_status_after' => 'request_approval',
                'created_at' => $rgd->created_at,
                'updated_at' => $rgd->updated_at,
            ]);
        }

        if (!empty($rgd)) {
            return response()->json(
                [
                    'message' => Messages::MSG_0007,
                    'data' => $rgd
                ],
                200
            );
        } else {
            return response()->json(['message' => CommonFunc::renderMessage(Messages::MSG_0016, ['ReceivingGoodsDelivery'])], 400);
        }
    }

    public function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    public function importExcelFulfillmentProcessing(Request $request)
    {
        // try {
        DB::beginTransaction();
        $member = Member::where('mb_id', Auth::user()->mb_id)->first();
        $co_no = Auth::user()->co_no ? Auth::user()->co_no : null;
        $user = Auth::user();

        if ($user->mb_type == "spasys") {
            $companies_shop = Company::where('co_parent_no', $user->co_no)->where('co_type', 'shop')->orderBy('co_no', 'DESC')->get();

            $companies_shipper = Company::with(['contract', 'co_parent'])->with('warehousing')->where('co_type', 'shipper')->whereIn('co_parent_no', function ($query) use ($user) {
                $query->select('co_no')
                    ->from(with(new Company)->getTable())
                    ->where('co_type', 'shop')
                    ->where('co_parent_no', $user->co_no);
            })->orderBy('co_no', 'DESC')->get();

            $co_no_shop = [];
            foreach ($companies_shop as $value) {
                $co_no_shop[] = $value->co_no;
            }

            $co_no_shipper = [];
            foreach ($companies_shipper as $value) {
                $co_no_shipper[] = $value->co_no;
            }
            $co_no_in = array_merge($co_no_shop, $co_no_shipper);
            array_push($co_no_in, $co_no);
        }
        //return $co_no_in;
        $f = Storage::disk('public')->put('files/tmp', $request['file']);

        $path = storage_path('app/public') . '/' . $f;
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        $sheet = $spreadsheet->getSheet(0);
        $datas = $sheet->toArray(null, true, true, true);

        // $sheet2 = $spreadsheet->getSheet(1);
        // $data_channels = $sheet2->toArray(null, true, true, true);

        //return $datas;

        $results[$sheet->getTitle()] = [];
        $errors[$sheet->getTitle()] = [];

        $data_item_count = 0;
        $data_channel_count = 0;

        $check_error = false;
        $out = [];
        $test = [];
        $test_i = [];
        $test_date = '';
        foreach ($datas as $key => $d) {
            if ($key <= 1) {
                continue;
            }

            $validator = Validator::make($d, ExcelRequest::rules());
            if ($validator->fails()) {
                $data_item_count =  $data_item_count - 1;
                $errors[$sheet->getTitle()][] = $validator->errors();
                $check_error = true;
            } else {
                $contains = Str::contains($d['D'], 'S');

                if ($contains) {
                    $item = Item::with(['company', 'ContractWms'])->where('item_service_name', '수입풀필먼트')->where('option_id', $d['D'])->where('product_id', $d['C']);
                    $item->whereHas('ContractWms.company', function ($q) use ($co_no_in) {
                        $q->whereIn('co_no', $co_no_in);
                    })->first();
                } else {
                    $item = Item::with(['company', 'ContractWms'])->where('item_service_name', '수입풀필먼트')->where('product_id', $d['D']);
                    $item->whereHas('ContractWms.company', function ($q) use ($co_no_in) {
                        $q->whereIn('co_no', $co_no_in);
                    })->first();
                }

                $item = $item->first();

                //$test[] = $item;
                if (!isset($item)) {
                    $data_item_count =  $data_item_count - 1;
                    $errors[$sheet->getTitle()][] = $validator->errors();
                    $check_error = true;
                } else {
                    $data_item_count =  $data_item_count + 1;

                    $custom = collect(['wi_number' => $d['G']]);

                    $item = $custom->merge($item);

                    $index = $d['A'] . ',' . $d['H'];
                    // if (array_key_exists($index, $out)){
                    //     $out[$index] = $d['A'];
                    // }
                    $test[$index] = $d['A'];

                    if (isset($test_i[$index])) {
                        $tmp = $test_i[$index];
                        $tmp[] = $item;
                        $test_i[$index] = $tmp;
                    } else {
                        $tmp = [];
                        $tmp[] = $item;
                        $test_i[$index] = $tmp;
                    }
                }
            }
        }

        foreach ($test_i as $key => $value) {
            $strArray = explode(',', $key);
            $w_no_data = Warehousing::insertGetId([
                'mb_no' => $member->mb_no,
                'w_type' => 'IW',
                'w_category_name' => "수입풀필먼트",
                'co_no' => isset($validated['co_no']) ? $validated['co_no'] : $value[0]['contract_wms']['co_no'],
                'w_schedule_day' => $strArray[1],
                'w_completed_day' => Carbon::now()->toDateTimeString()
            ]);

            ReceivingGoodsDelivery::insertGetId([
                'mb_no' => $member->mb_no,
                'w_no' => $w_no_data,
                'service_korean_name' => "수입풀필먼트",
                'rgd_status1' => '입고',
                'rgd_status2' => '작업완료',
                //'rgd_delivery_schedule_day' => $strArray[1]
            ]);

            $w_no = isset($validated['w_no']) ? $validated['w_no'] : $w_no_data;

            if (isset($w_no)) {
                $w_schedule_number = (new CommonFunc)->generate_w_schedule_number($w_no, 'IW');
            }

            Warehousing::where('w_no', $w_no)->update([
                'w_schedule_number' =>  $w_schedule_number
            ]);

            $w_amount = 0;
            foreach ($value as $warehousing_item) {
                WarehousingItem::insert([
                    'item_no' => $warehousing_item['item_no'],
                    'w_no' => $w_no,
                    'wi_number' => null,
                    'wi_type' => '입고_shipper'
                ]);

                WarehousingItem::insert([
                    'item_no' => $warehousing_item['item_no'],
                    'w_no' => $w_no,
                    'wi_number' => isset($warehousing_item['wi_number']) ? $warehousing_item['wi_number'] : null,
                    'wi_type' => '입고_spasys'
                ]);
                $w_amount += $warehousing_item['wi_number'];
            }

            Warehousing::Where('w_no', $w_no_data)->update([
                'w_amount' =>  $w_amount,

            ]);
        }


        Storage::disk('public')->delete($f);
        //return $test_i;
        if ($check_error == true) {
            DB::rollback();
            return response()->json([
                'message' => Messages::MSG_0007,
                'status' => 2,
                'errors' => $errors,
                'data_item_count' => $data_item_count,
            ], 201);
        } else {
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'errors' => $errors,
                'status' => 1,
                'data_item_count' => $data_item_count,
            ], 201);
        }

        // } catch (\Exception $e) {

        //     Log::error($e);
        //     return response()->json(['message' => Messages::MSG_0004], 500);
        // }
    }

    public function get_warehousing_3_status(WarehousingSearchRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;


            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            $user = Auth::user();
            DB::enableQueryLog();
            if ($user->mb_type == 'shop') {

                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('parent_shop.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);

                $sub_5 = ReceivingGoodsDelivery::select('is_no', 'rgd_status3', 'rgd_status1', 'rgd_address', 'rgd_no')->groupBy('is_no');

                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })->orderBy('ti_i_date', 'DESC');
            } else if ($user->mb_type == 'shipper') {

                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('company.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
                $sub_5 = ReceivingGoodsDelivery::select('is_no', 'rgd_status3', 'rgd_status1', 'rgd_address', 'rgd_no')->groupBy('is_no');

                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {

                    $leftjoin->On('bbb.ti_carry_in_number', '=', 'nnn.is_no');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('bbb.ti_carry_in_number');
                })->orderBy('ti_i_date', 'DESC');
            } else if ($user->mb_type == 'spasys') {

                //FIX NOT WORK 'with'
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                    })
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })
                    // ->leftjoin('company', function ($join) {
                    //     $join->on('company.co_license', '=', 't_import_expected.tie_co_license');

                    // })->leftjoin('company as parent_shop', function ($join) {
                    //     $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    // })->leftjoin('company as parent_spasys', function ($join) {
                    //     $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    //     $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                    // })
                    //->where('parent_spasys.co_no', $user->co_no)

                    ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                    ->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
                $sub_5 = ReceivingGoodsDelivery::select('is_no', 'rgd_status3', 'rgd_status1', 'rgd_address', 'rgd_no')->groupBy('is_no');

                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {

                    $leftjoin->On('bbb.ti_carry_in_number', '=', 'nnn.is_no');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('bbb.ti_carry_in_number');
                })->orderBy('ti_i_date', 'DESC');
                // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                // })
                // ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                //     //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                //     $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                // })



                //return DB::getQueryLog();
                //END FIX NOT WORK 'with'
            }

            if (isset($validated['status'])) {


                $import_schedule->where('aaa.rgd_status1', '=', $validated['status']);
            }
            if (isset($validated['from_date'])) {
                $import_schedule->where('aaa.tie_is_date', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $import_schedule->where('aaa.tie_is_date', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            //get_warehousing_api

            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $warehousing2 = Warehousing::join(
                    DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                    'm.w_no',
                    '=',
                    'warehousing.w_no'
                )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });
                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                    ->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
            } else if ($user->mb_type == 'shipper') {
                $warehousing2 = Warehousing::join(
                    DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                    'm.w_no',
                    '=',
                    'warehousing.w_no'
                )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });
                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                    ->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
            } else if ($user->mb_type == 'spasys') {

                $warehousing2 = Warehousing::join(
                    DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                    'm.w_no',
                    '=',
                    'warehousing.w_no'
                )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });

                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child', 'rate_data_general'])->where('w_category_name', '=', '수입풀필먼트')->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')
                    ->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
            }
            $warehousing->whereDoesntHave('rate_data_general');

            if (isset($validated['connection_number_type'])) {
                $warehousing->whereNull('connection_number');
            }

            if (isset($validated['page_type']) && $validated['page_type'] == "page130") {
                $warehousing->where('w_type', '=', 'IW')->where('w_category_name', '=', '수입풀필먼트');
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
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_iw'] . '%', 'and', 'w_type', '=', 'IW');
                });
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
            if (isset($validated['status'])) {
                $warehousing->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                    $query->where('rgd_status1', '=', $validated['status']);
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


            $warehousing = $warehousing->orWhereIn('w_no', $w_no_in)->orderBy('w_completed_day', 'DESC');
            $members = Member::where('mb_no', '!=', 0)->get();


            //get_warehousing2

            $user = Auth::user();
            if ($user->mb_type == 'shop') {

                $warehousing2 = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->where('w_type', 'IW')->whereNotNull('w_schedule_number2')->where('w_schedule_number2', '!=', '')
                    ->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('w_completed_day', 'DESC');
            } else if ($user->mb_type == 'shipper') {

                $warehousing2 = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->where('w_type', 'IW')->whereNotNull('w_schedule_number2')->where('w_schedule_number2', '!=', '')
                    ->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('w_completed_day', 'DESC');
            } else if ($user->mb_type == 'spasys') {

                $warehousing2 = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child'])->where('w_type', 'IW')->whereNotNull('w_schedule_number2')->where('w_schedule_number2', '!=', '')
                    ->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('w_completed_day', 'DESC');
            }

            if (isset($validated['page_type']) && $validated['page_type'] == "page130") {
                $warehousing2->where('w_type', '=', 'IW');
            }

            if (isset($validated['from_date'])) {
                $warehousing2->where('w_completed_day', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing2->where('w_completed_day', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['mb_name'])) {
                $warehousing2->whereHas('mb_no', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(mb_name)'), 'like', '%' . strtolower($validated['mb_name']) . '%');
                });
            }
            if (isset($validated['co_parent_name'])) {
                $warehousing2->whereHas('co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing2->whereHas('co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['w_schedule_number'])) {
                $warehousing2->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
            }
            if (isset($validated['w_schedule_number_iw'])) {
                $warehousing2->whereHas('w_import_parent', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_iw'] . '%', 'and', 'w_type', '=', 'IW');
                });
            }
            if (isset($validated['w_schedule_number_ew'])) {
                $warehousing2->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number_ew'] . '%', 'and', 'w_type', '=', 'EW');
            }
            if (isset($validated['logistic_manage_number'])) {
                $warehousing2->where('logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
            }
            if (isset($validated['m_bl'])) {
                $warehousing2->where('m_bl', 'like', '%' . $validated['m_bl'] . '%');
            }
            if (isset($validated['h_bl'])) {
                $warehousing2->where('h_bl', 'like', '%' . $validated['h_bl'] . '%');
            }
            if (isset($validated['rgd_status1'])) {
                $warehousing2->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                    $query->where('rgd_status1', '=', $validated['rgd_status1']);
                });
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing2->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                    $query->where('rgd_status2', '=', $validated['rgd_status2']);
                });
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing2->whereHas('receving_goods_delivery', function ($query) use ($validated) {
                    $query->where('rgd_status3', '=', $validated['rgd_status3']);
                });
            }

            $members = Member::where('mb_no', '!=', 0)->get();

            // $warehousing2 = $warehousing2->paginate($per_page, ['*'], 'page', $page);

            //    $warehousing2->setCollection(
            //     $warehousing2->getCollection()->map(function ($item) {

            //         $item->total_item = WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_spasys')->sum('wi_number');
            //         if ($item['warehousing_item'][0]['item']) {
            //             $first_name_item = $item['warehousing_item'][0]['item']['item_name'];
            //             $total_item = $item['warehousing_item']->count();
            //             $final_total = (($total_item / 2)  - 1);
            //             if ($final_total <= 0) {
            //                 $item->first_item_name_total = $first_name_item . '외';
            //             } else {
            //                 $item->first_item_name_total = $first_name_item . '외' . ' ' . $final_total . '건';
            //             }
            //         } else {
            //             $item->first_item_name_total = '';
            //         }

            //         return $item;
            //     })
            // );


            $warehousing = $warehousing->get();
            if (isset($warehousing)) {
                foreach ($warehousing as $item) {
                    $item->total_item = WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_spasys')->sum('wi_number');
                    if ($item['warehousing_item'][0]['item']) {
                        $first_name_item = $item['warehousing_item'][0]['item']['item_name'];
                        $total_item = $item['warehousing_item']->count();
                        $final_total = (($total_item / 2)  - 1);
                        if ($final_total <= 0) {
                            $item->first_item_name_total = $first_name_item . '외';
                        } else {
                            $item->first_item_name_total = $first_name_item . '외' . ' ' . $final_total . '건';
                        }
                    } else {
                        $item->first_item_name_total = '';
                    }
                }
            }

            $import_schedule = $import_schedule->get();

            foreach ($import_schedule as $item) {
                if (isset($item->te_e_number)) {
                    $item->number = $item->te_e_number;
                } else if (isset($item->ti_i_number)) {
                    $item->number =  $item->ti_i_number;
                } else if (isset($item->tie_is_number)) {
                    $item->number =  $item->tie_is_number;
                }
            }

            $warehousing2 = $warehousing2->get();
            if (isset($warehousing2)) {
                foreach ($warehousing2 as $item) {
                    $item->total_item = WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_spasys')->sum('wi_number');
                    if ($item['warehousing_item'][0]['item']) {
                        $first_name_item = $item['warehousing_item'][0]['item']['item_name'];
                        $total_item = $item['warehousing_item']->count();
                        $final_total = (($total_item / 2)  - 1);
                        if ($final_total <= 0) {
                            $item->first_item_name_total = $first_name_item . '외';
                        } else {
                            $item->first_item_name_total = $first_name_item . '외' . ' ' . $final_total . '건';
                        }
                    } else {
                        $item->first_item_name_total = '';
                    }
                }
            }

            $final2 = collect($warehousing)->map(function ($q) {

                return $q;
            });

            $final = collect($import_schedule)->map(function ($q) {

                return $q;
            });

            $final3 = collect($warehousing2)->map(function ($q) {

                return $q;
            });

            $final4 = $final2->merge($final)->merge($final3);

            $data = $this->paginate($final4, isset($validated['per_page']) ? $validated['per_page'] : 200, isset($validated['page']) ? $validated['page'] : 1);

            // $status = DB::table('t_import_expected')
            //     ->select('tie_status_2')
            //     ->groupBy('tie_status_2')
            //     ->get();
            // $custom = collect(['status_filter' => $status]);
            // $import_schedule = $custom->merge($import_schedule);

            DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

            return $data;
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
        }
    }
    public function SQL($validated)
    {

        // If per_page is null set default data = 15
        $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
        // If page is null set default data = 1
        $page = isset($validated['page']) ? $validated['page'] : 1;

        //DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        $user = Auth::user();
        if ($user->mb_type == 'shop') {

            $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                ->leftjoin('company', function ($join) {
                    $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as parent_shop', function ($join) {
                    $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                })->leftjoin('company as parent_spasys', function ($join) {
                    $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                })->where('parent_shop.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                ->leftjoin('receiving_goods_delivery', function ($join) {
                    $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                })
                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

            $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')

                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })

                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('te_carry_out_number', 'DESC');
        } else if ($user->mb_type == 'shipper') {

            $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                ->leftjoin('company', function ($join) {
                    $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as parent_shop', function ($join) {
                    $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                })->leftjoin('company as parent_spasys', function ($join) {
                    $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                })->where('company.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                ->leftjoin('receiving_goods_delivery', function ($join) {
                    $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                })
                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);



            $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')

                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })

                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {


                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('te_carry_out_number', 'DESC');
        } else if ($user->mb_type == 'spasys') {

            //FIX NOT WORK 'with'
            $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                ->leftjoin('company as parent_spasys', function ($join) {
                    $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                })
                ->leftjoin('company', function ($join) {
                    $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as parent_shop', function ($join) {
                    $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                })

                ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                ->where('tie_is_date', '>=', '2022-01-04')
                ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                ->leftjoin('receiving_goods_delivery', function ($join) {
                    $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                })
                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

            $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')

                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })

                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('ti_logistic_manage_number', 'DESC')->orderBy('te_logistic_manage_number', 'DESC');
        }

        // if (isset($validated['status'])) {
        //     $import_schedule->where('aaa.rgd_status1', '=', $validated['status']);
        // }
        if (isset($validated['from_date'])) {
            $import_schedule->where('aaa.tie_is_date', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
        }

        if (isset($validated['to_date'])) {
            $import_schedule->where('aaa.tie_is_date', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
        }

        if (isset($validated['co_parent_name'])) {
            $import_schedule->where(DB::raw('lower(aaa.co_name_shop)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
        }

        if (isset($validated['co_name'])) {
            $import_schedule->where(DB::raw('lower(aaa.co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
        }

        if (isset($validated['m_bl'])) {
            $import_schedule->where(DB::raw('aaa.tie_m_bl'), 'like', '%' . strtolower($validated['m_bl']) . '%');
        }

        if (isset($validated['h_bl'])) {
            $import_schedule->where(DB::raw('aaa.tie_h_bl'), 'like', '%' . strtolower($validated['h_bl']) . '%');
        }

        if (isset($validated['logistic_manage_number'])) {
            $import_schedule->where('aaa.tie_logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
        }

        $import_schedule = $import_schedule->whereNull('ddd.te_logistic_manage_number')->get();


        //DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        $id = [];
        foreach ($import_schedule as $te) {
            $id[] = $te->tie_logistic_manage_number;
        }
        return $id;
    }
}

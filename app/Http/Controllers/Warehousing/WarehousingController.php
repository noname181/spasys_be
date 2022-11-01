<?php

namespace App\Http\Controllers\Warehousing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehousing\WarehousingDataValidate;
use App\Http\Requests\Warehousing\WarehousingItemValidate;
use App\Http\Requests\Warehousing\WarehousingRequest;
use App\Http\Requests\Warehousing\WarehousingSearchRequest;
use App\Models\AdjustmentGroup;
use App\Models\CompanySettlement;
use App\Models\Company;
use App\Models\RateData;
use App\Models\ImportExpected;
use App\Models\Member;
use App\Models\RateDataGeneral;
use App\Models\RateMetaData;
use App\Models\ReceivingGoodsDelivery;
use App\Models\ScheduleShipment;
use App\Models\Service;
use App\Models\Warehousing;
use App\Models\WarehousingItem;
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
                    })->orderBy('w_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {

                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->where('w_type', 'IW')->whereNotNull('w_schedule_number2')->where('w_schedule_number2', '!=', '')
                    ->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('w_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {

                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child'])->where('w_type', 'IW')->whereNotNull('w_schedule_number2')->where('w_schedule_number2', '!=', '')
                    ->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('w_no', 'DESC');
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

            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);

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
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '유통가공')
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
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '유통가공')
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
                $warehousing2 = Warehousing::where('warehousing.w_type', '=', 'EW')->where('warehousing.w_category_name', '=', '유통가공')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();

                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $w_no_in = collect($warehousing2)->map(function ($q) {

                    return $q->w_no;
                });

                $warehousing = Warehousing::with('mb_no')
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child'])->withCount([
                    'warehousing_item as bonusQuantity' => function ($query) {

                        $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                    },
                ])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '유통가공')
                    ->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orWhereIn('w_no', $w_no_in)->orderBy('w_no', 'DESC');
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
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child'])->where('w_category_name', '=', '수입풀필먼트')->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')
                    ->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })

                    ;
            }
            $warehousing->whereDoesntHave('receving_goods_delivery');

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

            return response()->json($warehousing);
        } catch (\Exception $e) {
            Log::error($e);

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
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                        $q->where('rgd_status1', '!=', '입고')->orWhereNull('rgd_status1');
                    })->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                        $q->where('rgd_status1', '!=', '입고')->orWhereNull('rgd_status1');
                    })->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                        $q->where('rgd_status1', '!=', '입고')->orWhereNull('rgd_status1');
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
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
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
                return response()->json($warehousing);
            } else if ($validated['service'] == "수입풀필먼트") {
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
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms'])->whereNotNull('trans_no')->where('status', '!=', '8')->whereHas('ContractWms.company', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('ss_no', 'DESC');
                } else if ($user->mb_type == 'spasys') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms'])->whereNotNull('trans_no')->where('status', '!=', '8')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
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
            } else {
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
                        ->where('tie_is_date', '>=', '2022-01-04')->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                        ->groupBy('t_export.te_logistic_manage_number', 't_export.te_carry_out_number')->orderBy('t_export.te_carry_out_number', 'DESC');
                } else if ($user->mb_type == 'shipper') {
                    $import_schedule = ImportExpected::with(['import', 'company', 'receiving_goods_delivery'])->whereHas('company', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->groupBy('t_import_expected.tie_logistic_manage_number')->leftjoin('t_export', 't_import_expected.tie_logistic_manage_number', '=', 't_export.te_logistic_manage_number')
                        ->select(['t_import_expected.*', 't_export.te_logistic_manage_number', 't_export.te_carry_out_number'])
                        ->where('tie_is_date', '>=', '2022-01-04')->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                        ->groupBy('t_export.te_logistic_manage_number', 't_export.te_carry_out_number')->orderBy('t_export.te_carry_out_number', 'DESC');
                } else if ($user->mb_type == 'spasys') {
                    $import_schedule = ImportExpected::with(['import', 'company'])->whereHas('company.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->with(['receiving_goods_delivery' => function ($q) {
                        $q->where('rgd_status3', '=', "배송준비");
                    }])->groupBy('t_import_expected.tie_logistic_manage_number')->leftjoin('t_export', 't_import_expected.tie_logistic_manage_number', '=', 't_export.te_logistic_manage_number')
                        ->select(['t_import_expected.*', 't_export.te_logistic_manage_number', 't_export.te_carry_out_number'])
                        ->where('tie_is_date', '>=', '2022-01-04')->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
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
                $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms'])->whereNotNull('trans_no')->where('status', '!=', '8')->whereHas('ContractWms.company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('ss_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms'])->whereNotNull('trans_no')->where('status', '!=', '8')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
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
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereNotIn('w_no', $w_import_no)->whereHas('w_no', function ($query) use ($user) {
                    $query->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->where('rgd_status2', '=', '작업완료')->whereNull('w_children_yn')->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orwhere('rgd_status1', '=', '출고예정')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $warehousing2 = Warehousing::where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->whereNull('w_children_yn')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereNotIn('w_no', $w_import_no)->whereHas('w_no', function ($query) use ($user) {
                    $query->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->where('rgd_status2', '=', '작업완료')->whereNull('w_children_yn')->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orwhere('rgd_status1', '=', '출고예정')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $warehousing2 = Warehousing::where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->whereNull('w_children_yn')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $w_import_no = collect($warehousing2)->map(function ($q) {

                    return $q->w_import_no;
                });

                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereNotIn('w_no', $w_import_no)->whereHas('w_no', function ($query) use ($user) {
                    $query->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->where('rgd_status2', '=', '작업완료')->whereNull('w_children_yn')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orwhere('rgd_status1', '=', '출고예정')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
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
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) {
                    $item->total_wi_number = WarehousingItem::where('w_no', $item->w_no)->sum('wi_number');
                    return $item;
                })
            );
            return response()->json($warehousing);
        } catch (\Exception $e) {
            Log::error($e);

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
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')
                        ->where('rgd_status1', '=', '출고')
                        ->where('rgd_status2', '=', '작업완료')
                        ->where(function ($q) {
                            $q->where(function ($query) {
                                $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                            })
                                ->orWhereNull('rgd_status4');
                        })->whereHas('co_no.co_parent', function ($q2) use ($user) {
                        $q2->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')
                        ->where('rgd_status1', '=', '출고')
                        ->where('rgd_status2', '=', '작업완료')
                        ->where(function ($q) {
                            $q->where(function ($query) {
                                $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                            })
                                ->orWhereNull('rgd_status4');
                        })->whereHas('co_no', function ($q2) use ($user) {
                        $q2->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with('w_no')->with(['mb_no'])->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')
                        ->where('rgd_status1', '=', '출고')
                        ->where('rgd_status2', '=', '작업완료')
                        ->where(function ($q) {
                            $q->where(function ($query) {
                                $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                            })
                                ->orWhereNull('rgd_status4');
                        })->whereHas('co_no.co_parent.co_parent', function ($q2) use ($user) {
                        $q2->where('co_no', $user->co_no);
                    });
                })->orderBy('rgd_no', 'DESC');
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

            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getWarehousingByRgd($rgd_no, $type)
    {
        try {
            $check_cofirm = 0;
            $check_paid = 0;
            if ($type == 'monthly') {
                $rgd = ReceivingGoodsDelivery::with(['rgd_child', 'warehousing'])->where('rgd_no', $rgd_no)->first();
                $w_no = $rgd->w_no;
                $co_no = $rgd->warehousing->co_no;
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();
                $updated_at = Carbon::createFromFormat('Y.m.d H:i:s', $rgd->updated_at->format('Y.m.d H:i:s'));

                $start_date = $updated_at->startOfMonth()->toDateString();
                $end_date = $updated_at->endOfMonth()->toDateString();

                $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general'])
                    ->whereHas('w_no', function ($q) use ($co_no) {
                        $q->where('co_no', $co_no)
                            ->where('w_category_name', '유통가공');
                    })
                    ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($start_date)))
                    ->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($end_date)))
                    ->where('rgd_status1', '=', '입고')
                    ->where('rgd_bill_type', 'expectation_monthly')
                    ->where(function ($q) {
                        $q->whereDoesntHave('rgd_child')
                            ->orWhere('rgd_status5', '!=', 'issued')
                            ->orWhereNull('rgd_status5');
                    })
                    ->where(function ($q) {
                        $q->where('rgd_status4', '=', '예상경비청구서')->orWhereNull('rgd_status4');
                    })
                    ->get();
                if($rgds->count() == 0){
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
                    ->get();

                    if($rgds->count() == 0 ){
                        
                        $rgd = ReceivingGoodsDelivery::with(['rgd_child', 'warehousing'])->where('rgd_no', $rgd->rgd_parent_no)->first();
                        if(!empty($rgd)){
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
                            ->get();
                        }else {
                            $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general'])
                            ->whereHas('w_no', function ($q) use ($co_no) {
                                $q->where('co_no', $co_no)
                                    ->where('w_category_name', '유통가공');
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
                            ->where(function ($q) {
                                $q->where('rgd_status4', '=', '예상경비청구서')->orWhereNull('rgd_status4');
                            })
                            ->get();

                            
                        }
                       
                    }
                }
                $warehousing = Warehousing::with(['w_ew_many' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);

                },'w_ew' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);

                }, 'co_no', 'warehousing_request', 'w_import_parent', 'warehousing_child'])->withSum('warehousing_item_IW_spasys_confirm', 'wi_number')->find($w_no);
                $adjustment_group = AdjustmentGroup::where('co_no', '=', $warehousing->co_no)->first();
                $adjustment_group2 = AdjustmentGroup::select(['ag_name'])->where('co_no', '=', $warehousing->co_no)->get();

                $time = str_replace('-', '.', $start_date) . ' ~ ' . str_replace('-', '.', $end_date);
            } else if ($type == 'additional_monthly') {
                $rgd = ReceivingGoodsDelivery::with(['rgd_child', 'warehousing'])->where('rgd_no', $rgd_no)->first();
                $w_no = $rgd->w_no;
                $co_no = $rgd->warehousing->co_no;
                $rdg = RateDataGeneral::where('rgd_no', $rgd_no)->first();
                $updated_at = Carbon::createFromFormat('Y.m.d H:i:s', $rgd->updated_at->format('Y.m.d H:i:s'));

                $start_date = $updated_at->startOfMonth()->toDateString();
                $end_date = $updated_at->endOfMonth()->toDateString();

                $rgds = ReceivingGoodsDelivery::with(['w_no', 'rate_data_general'])
                    ->whereHas('w_no', function ($q) use ($co_no) {
                        $q->where('co_no', $co_no)
                            ->where('w_category_name', '유통가공');
                    })
                    ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($start_date)))
                    ->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($end_date)))
                    ->where('rgd_status1', '=', '입고')
                    ->where('rgd_settlement_number', $rgd->rgd_settlement_number)
                    ->get();
             
                $warehousing = Warehousing::with(['w_ew_many' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);

                },'w_ew' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);

                }, 'co_no', 'warehousing_request', 'w_import_parent', 'warehousing_child'])->withSum('warehousing_item_IW_spasys_confirm', 'wi_number')->find($w_no);
                $adjustment_group = AdjustmentGroup::where('co_no', '=', $warehousing->co_no)->first();
                $adjustment_group2 = AdjustmentGroup::select(['ag_name'])->where('co_no', '=', $warehousing->co_no)->get();

                $time = str_replace('-', '.', $start_date) . ' ~ ' . str_replace('-', '.', $end_date);
            }  else {
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

                },'w_ew' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);

                }, 'co_no', 'warehousing_request', 'w_import_parent', 'warehousing_child'])->withSum('warehousing_item_IW_spasys_confirm', 'wi_number')->find($w_no);

                $adjustment_group2 = AdjustmentGroup::select(['ag_name'])->where('co_no', '=', $warehousing->co_no)->get();
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

    public function getWarehousingByRgdFulfillment($rgd_no, $type)
    {
        try {
            $check_cofirm = 0;
            $check_paid = 0;
            if ($type == 'monthly') {
                $rgd = ReceivingGoodsDelivery::with(['rgd_child', 'warehousing'])->where('rgd_no', $rgd_no)->first();
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
                if($rgds->count() == 0){
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

                    if($rgds->count() == 0){
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

                },'w_ew' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);

                }, 'co_no', 'warehousing_request', 'w_import_parent', 'warehousing_child'])->withSum('warehousing_item_IW_spasys_confirm', 'wi_number')->find($w_no);
                $adjustment_group = AdjustmentGroup::where('co_no', '=', $warehousing->co_no)->first();
                $adjustment_group2 = AdjustmentGroup::select(['ag_name'])->where('co_no', '=', $warehousing->co_no)->get();

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

                },'w_ew' => function ($q) {

                    $q->withCount([
                        'warehousing_item as bonusQuantity' => function ($query) {

                            $query->select(DB::raw('SUM(wi_number)'))->where('wi_type', '출고_spasys');
                        },
                    ]);

                }, 'co_no', 'warehousing_request', 'w_import_parent', 'warehousing_child'])->withSum('warehousing_item_IW_spasys_confirm', 'wi_number')->find($w_no);

                $adjustment_group2 = AdjustmentGroup::select(['ag_name'])->where('co_no', '=', $warehousing->co_no)->get();
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
                });
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

            $warehousing->orderBy('rgd_no', 'DESC');
            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) use($user) {
                    $service_name = $item->service_korean_name;
                    $w_no = $item->w_no;
                    $co_no = Warehousing::where('w_no', $w_no)->first()->co_no;
                    $service_no = Service::where('service_name', $service_name)->first()->service_no;

                    $company_settlement = CompanySettlement::where([
                        'co_no' => $co_no,
                        'service_no' => $service_no,
                    ])->first();
                    $item->settlement_cycle = $company_settlement ? $company_settlement->cs_payment_cycle : "";

                    //CHECK SHIPPER COMPANY IS SENT RATE DATA YET

                    $rate_data = RateData::where('rd_cate_meta1', '유통가공');

                    if ($user->mb_type == 'spasys') {
                        $co_no = $item->warehousing->company->co_parent->co_no;
                    } else if ($user->mb_type == 'shop') {
                        $co_no = $item->warehousing->company->co_no;
                    } else {
                        $co_no = $user->co_no;
                    }

                    $rmd = RateMetaData::where('co_no', $co_no)->latest('created_at')->first();
                    $rate_data = $rate_data->where('rd_co_no', $co_no);
                    if (isset($rmd->rmd_no)) {
                        $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
                    }else {
                        $rate_data = [];
                    }


                    $item->rate_data = empty($rate_data) ? 0 : 1;
                    $i = 0;
                    $k = 0;
                    foreach($item->warehousing->warehousing_child as $child){
                        $i++;
                        if($child['w_completed_day'] != null){
                            $k++;
                        }
                    }

                    if($i == $k){
                        $item->is_completed = true;
                    }else {
                        $item->is_completed = false;
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
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general','settlement_number'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            }
            $warehousing->whereHas('w_no', function ($query) {
                $query->where('rgd_status1', '=', '입고')
                    ->where(function ($q) {
                        $q->where('rgd_status5', '!=', 'cancel')
                            ->orWhereNull('rgd_status5');
                    })
                    ->where(function ($q) {
                        $q->where('rgd_status4', '=', '예상경비청구서')
                            ->orWhere('rgd_status4', '=', '확정청구서')
                            ->orWhere('rgd_status4', '=', '추가청구서');
                    })
                    ->where('w_category_name', '=', '유통가공');
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

            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }

            $warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) {
                    $service_name = $item->service_korean_name;
                    $w_no = $item->w_no;
                    $co_no = Warehousing::where('w_no', $w_no)->first()->co_no;
                    $service_no = Service::where('service_name', $service_name)->first()->service_no;

                    $company_settlement = CompanySettlement::where([
                        'co_no' => $co_no,
                        'service_no' => $service_no,
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
            //
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

            $warehousing = $warehousing->orderBy('rgd_no', 'DESC')->paginate($per_page, ['*'], 'page', $page);

            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) use($user) {

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

                    $rmd = RateMetaData::where('co_no', $co_no)->latest('created_at')->first();
                    $rate_data = $rate_data->where('rd_co_no', $co_no);
                    if (isset($rmd->rmd_no)) {
                        $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
                    }else {
                        $rate_data = [];
                    }


                    $item->rate_data = empty($rate_data) ? 0 : 1;

                    $rmd = RateMetaData::where(
                        [
                            'rgd_no' => $item->rgd_no,
                            'set_type' => 'fulfill1_final',
                        ]
                    )->first();
                    
                    if(!empty($rmd)){
                        $rate_data = RateData::where('rmd_no', $rmd->rmd_no)->where(function ($q) {
                            $q->orWhere('rd_cate_meta1', '수입풀필먼트');
                        })->get();

                        $pcs = 0;
                        $box = 0;
                        $caton = 0;

                        foreach($rate_data as $rate){
                            if($rate['rd_data1'] == 'PCS'){
                                $pcs += intval($rate['rd_data4']);
                            }else if($rate['rd_data1'] == 'BOX'){
                                $box += intval($rate['rd_data4']);
                            }else if($rate['rd_data1'] == 'CATON'){
                                $caton += intval($rate['rd_data4']);
                            }
                        }

                        $item->pcs = $pcs;
                        $item->box = $box;
                        $item->caton = $caton;
                    }else {
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
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general', 't_export'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            }
            $warehousing->where('rgd_status1', '=', '입고')
                ->where(function ($q) {
                    $q->where(function ($query) {
                        $query->where('rgd_status4', '!=', '예상경비청구서')
                            ->where('rgd_status4', '!=', '확정청구서');
                    })
                        ->orWhereNull('rgd_status4');
                })
                ->whereHas('w_no', function ($query) {
                    $query->where('w_type', '=', 'IW')
                        ->where('w_category_name', '=', '보세화물');
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

            $warehousing->setCollection(
                $warehousing->getCollection()->map(function ($item) use($user) {
                    $service_name = $item->service_korean_name;
                    $w_no = $item->w_no;
                    $co_no = Warehousing::where('w_no', $w_no)->first()->co_no;
                    $service_no = Service::where('service_name', $service_name)->first()->service_no;

                    $company_settlement = CompanySettlement::where([
                        'co_no' => $co_no,
                        'service_no' => $service_no,
                    ])->first();
                    $item->settlement_cycle = $company_settlement ? $company_settlement->cs_payment_cycle : "";

                    //CHECK SHIPPER COMPANY IS SENT RATE DATA YET

                    $rate_data = RateData::where('rd_cate_meta1', '유통가공');

                    if ($user->mb_type == 'spasys') {
                        $co_no = $item->warehousing->company->co_parent->co_no;
                    } else if ($user->mb_type == 'shop') {
                        $co_no = $item->warehousing->company->co_no;
                    } else {
                        $co_no = $user->co_no;
                    }

                    $rmd = RateMetaData::where('co_no', $co_no)->latest('created_at')->first();
                    $rate_data = $rate_data->where('rd_co_no', $co_no);
                    if (isset($rmd->rmd_no)) {
                        $rate_data = $rate_data->where('rmd_no', $rmd->rmd_no);
                    }else {
                        $rate_data = [];
                    }


                    $item->rate_data = empty($rate_data) ? 0 : 1;

                    return $item;
                })
            );

            return response()->json($warehousing);
        } catch (\Exception $e) {
            Log::error($e);

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
            if ($user->mb_type == 'shop') {
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
            } else if ($user->mb_type == 'spasys') {
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
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
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
            //
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
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['mb_no', 'w_no', 'rate_data_general'])->whereHas('w_no', function ($query) use ($user) {
                    $query->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    });
                });
            }
            $warehousing->where('rgd_status1', '=', '입고')
                ->where(function ($q) {
                    $q->where('rgd_status4', '=', '확정청구서')
                        ->orWhere('rgd_status4', '=', '추가청구서');
                })
                ->where('rgd_status5', '=', 'confirmed')
                ->whereHas('w_no', function ($query) {
                    $query->where('w_category_name', '=', '유통가공');
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
            //
            return response()->json(['message' => Messages::MSG_0018], 500);
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
        try {
            DB::beginTransaction();

            if ($request->service == "유통가공") {
                foreach ($request->datachkbox as $value) {
                    $rgd = ReceivingGoodsDelivery::where('rgd_no', $value['rgd_no'])
                        ->update([
                            'rgd_status3' => "배송완료",
                        ]);
                }
            } elseif ($request->service == "수입풀필먼트") {
                foreach ($request->datachkbox as $value) {
                    $rgd = ScheduleShipment::where('ss_no', $value['ss_no'])
                        ->update([
                            'status' => 8,
                        ]);
                }
            } else {
                foreach ($request->datachkbox as $value) {
                    foreach ($value['receiving_goods_delivery'] as $receiving_goods_delivery) {
                        $rgd = ReceivingGoodsDelivery::where('rgd_no', $receiving_goods_delivery['rgd_no'])
                            ->update([
                                'rgd_status3' => "배송완료",
                            ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'rgd' => $rgd,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }


    public function fulfillment_billing(){
        try {
            $user = Auth::user();
            $co_no = $user->co_no;

            $companies = Company::with(['co_parent', 'adjustment_group'])->where(function($q) use($co_no, $user){

            $q->WhereHas('co_parent', function($q) use($co_no){
                $q->where('co_no', $co_no);
            });


                // $q->whereHas('co_parent', function($q) use($co_no){
                //     $q->where('co_no', $co_no);
                // })->orWhereHas('co_parent.co_parent', function($q) use($co_no){
                //     $q->where('co_no', $co_no);
                // });
            })->get();

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
        }catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }



    }

    public function fulfillment_create_billing(Request $request){
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
                    ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child'])->where('w_category_name', '=', '수입풀필먼트')->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')
                    ->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })

                    ;
            }

            $warehousing->whereDoesntHave('receving_goods_delivery');

            if (isset($request->from_date)) {

                $warehousing->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($request->from_date)));
            }

            if (isset($validated->to_date)) {
                $warehousing->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated->to_date)));
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
                        ->orWhereHas('co_parent', function($q) use($request) {
                            $q->where('co_no', $request->co_no);
                        });
            });

            $amount = $warehousing->orWhereIn('w_no', $w_no_in)->orderBy('w_no', 'DESC')->sum('w_amount');


            $w_no_data = Warehousing::insertGetId([
                'mb_no' => $user->mb_no,
                'w_schedule_amount' => $amount,
                'w_amount' => $amount,
                'w_type' => 'IW',
                'w_category_name' => '수입풀필먼트',
                'co_no' => $request->co_no,
            ]);

            $schedule_number = (new CommonFunc)->generate_w_schedule_number($w_no_data, 'IW');
            Warehousing::where('w_no', $w_no_data)->update([
                'w_schedule_number' => $schedule_number
            ]);

            //THUONG EDIT TO MAKE SETTLEMENT
            $rgd_no = ReceivingGoodsDelivery::insertGetId([
                'mb_no' => $user->mb_no,
                'w_no' => $w_no_data,
                'service_korean_name' => '수입풀필먼트',
                'rgd_status1' => '입고',
                'rgd_status2' => '작업완료',
            ]);

            $rdg_no = RateDataGeneral::insertGetId([
                'mb_no' => $user->mb_no,
                'w_no' => $w_no_data,
                'rgd_no' => $rgd_no,
                'rgd_no_expectation' => $rgd_no,
                'rdg_set_type' => $request->adjustment_group,
                'rdg_bill_type' => 'final',
            ]);

            DB::commit();

            return response()->json([
                'message' => Messages::MSG_0007,
                'rgd_no' => $rgd_no
            ]);
        }catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }



    }
    public function load_table_top_right($rgd_no)
    {
        $data = ReceivingGoodsDelivery::find($rgd_no);
        if (!empty($data)) {
            return response()->json(
                ['message' => Messages::MSG_0007,
                 'data' => $data
                ], 200);
        } else {
            return response()->json(['message' => CommonFunc::renderMessage(Messages::MSG_0016, ['ReceivingGoodsDelivery'])], 400);
        }
    }
}

<?php

namespace App\Http\Controllers\WarehousingStatus;

use DateTime;
use App\Models\Member;
use App\Models\WarehousingStatus;
use App\Models\Warehousing;
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
use App\Http\Requests\WarehousingStatus\WarehousingStatusRequest;

use App\Models\Import;
use App\Models\ImportExpected;
use App\Models\Export;
use \Carbon\Carbon;

class WarehousingStatusController extends Controller
{
    /**
     * Fetch data
     * @param  \App\Http\Requests\WarehousingStatus\WarehousingStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(WarehousingStatusRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $warehousing_status = WarehousingStatus::with('mb_no')->orderBy('ws_no', 'DESC');

            $members = Member::where('mb_no', '!=', 0)->get();

            $warehousing_status = $warehousing_status->paginate($per_page, ['*'], 'page', $page);

            return response()->json($warehousing_status);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Get WarehousingStatus
     * @param  WarehousingStatusRequest $request
     */
    public function getWarehousingStatus(WarehousingStatusRequest $request)
    {
        try {
            $validated = $request->validated();
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $warehousing = Warehousing::where('w_no', '=', $validated['w_no'])->first();

            $warehousing_status = WarehousingStatus::with(['mb_no', 'warehousing'])->orderBy('ws_no', 'DESC');

            if (isset($validated['page_type']) && $validated['page_type'] == "Page146") {
                //$warehousing_status->where('w_no', '=', $validated['w_no'])->orwhere('w_no', '=', $warehousing->w_import_no);
                $warehousing_status->where('w_no', '=', $validated['w_no'])->where('status', '!=', '출고예정 취소');
            } elseif (isset($validated['page_type']) && $validated['page_type'] == "bonded_cargo_list_details") {
               
                $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number','tie_is_date')
                    //->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                    ->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('ti_logistic_manage_number', 'ti_carry_in_number','ti_i_date','ti_i_time')

                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                $sub_4 = Export::select('te_logistic_manage_number', 'te_carry_in_number', 'te_carry_out_number','te_e_date','te_e_time')
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


                $warehousing_status = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                });

                $warehousing_status->where(function ($query) use ($validated) {
                    $query->where(
                        $validated['type_page'],'=',$validated['w_no']
                    );
                });
                
                
                
            } else {
                $warehousing_status->where('w_no', '=', $validated['w_no'])->where('status', '!=', '출고예정 취소');
            }


            $members = Member::where('mb_no', '!=', 0)->get();

            $warehousing_status = $warehousing_status->paginate($per_page, ['*'], 'page', $page);
            
            DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            return response()->json($warehousing_status);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getWarehousingStatusMobile(WarehousingStatusRequest $request)
    {
        try {
            $validated = $request->validated();

            $warehousing = Warehousing::where('w_no', '=', $validated['w_no'])->first();

            $warehousing_status = WarehousingStatus::with(['mb_no', 'warehousing'])->orderBy('ws_no', 'DESC');
            if ($warehousing) {
                $warehousing_status = $warehousing_status->where('w_no', '=', $validated['w_no'])->orwhere('w_no', '=', $warehousing->w_import_no);
            } else {
                $warehousing_status = $warehousing_status->where('w_no', '=', $validated['w_no']);
            }

            $members = Member::where('mb_no', '!=', 0)->get();

            $warehousing_status = $warehousing_status->get();

            return response()->json($warehousing_status);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function paginateWarehousingStatus(WarehousingStatusRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $warehousing_request = WarehousingStatus::with('mb_no')->orderBy('wr_no', 'DESC');

            $warehousing_request = $warehousing_request->where('w_no', '=', $validated['w_no']);

            $members = Member::where('mb_no', '!=', 0)->get();

            $warehousing_request = $warehousing_request->paginate($per_page, ['*'], 'page', $page);

            return response()->json($warehousing_request);
        } catch (\Exception $e) {
            Log::error($e);

            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Register qna
     * @param  WarehousingStatusRegisterRequest $request
     * @return \Illuminate\Http\Response
     */
    public function register(WarehousingStatusRequest $request)
    {
        $validated = $request->validated();
        try {
            //DB::beginTransaction();
            // FIXME hard set mb_no = 1
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $wr_no = WarehousingStatus::insertGetId([
                'mb_no' => $member->mb_no,
                'wr_type' => $validated['wr_type'],
                'wr_contents' => $validated['wr_contents'],
            ]);

            DB::commit();
            return response()->json(['message' => Messages::MSG_0007, 'wr_no' => $wr_no], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
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
     * @param  \App\Models\WarehousingStatus  $WarehousingStatus
     * @return \Illuminate\Http\Response
     */
    public function show(WarehousingStatus $WarehousingStatus)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WarehousingStatus  $WarehousingStatus
     * @return \Illuminate\Http\Response
     */
    public function edit(WarehousingStatus $WarehousingStatus)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WarehousingStatus  $WarehousingStatus
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, WarehousingStatus $WarehousingStatus)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WarehousingStatus  $WarehousingStatus
     * @return \Illuminate\Http\Response
     */
    public function destroy(WarehousingStatus $WarehousingStatus)
    {
        //
    }
}

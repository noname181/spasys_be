<?php

namespace App\Http\Controllers\WarehousingRequest;

use DateTime;
use App\Models\Member;
use App\Models\WarehousingRequest;
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
use App\Http\Requests\WarehousingRequest\WarehousingRequestRequest;

class WarehousingRequestController extends Controller
{
    /**
     * Fetch data
     * @param  \App\Http\Requests\WarehousingRequest\WarehousingRequestRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(WarehousingRequestRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $warehousing_request = WarehousingRequest::with('mb_no')->orderBy('wr_no', 'DESC');

            $members = Member::where('mb_no', '!=', 0)->get();

            $warehousing_request = $warehousing_request->paginate($per_page, ['*'], 'page', $page);

            return response()->json($warehousing_request);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Get WarehousingRequest
     * @param  WarehousingRequestRequest $request
     */
    public function getWarehousingRequest(WarehousingRequestRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $warehousing = Warehousing::where('w_no', '=', $validated['w_no'])->first();

            $warehousing_request = WarehousingRequest::with(['mb_no','warehousing'])->where('wr_type','<>','List')->orderBy('wr_no', 'DESC');
            if($warehousing){
                $warehousing_request = $warehousing_request->where('w_no', '=', $validated['w_no']);
                //->orwhere('w_no', '=', $warehousing->w_import_no);
            }else{
                $warehousing_request = $warehousing_request->where('w_no', '=', $validated['w_no']);
            }

            $warehousing_request = $warehousing_request->paginate($per_page, ['*'], 'page', $page);

            return response()->json($warehousing_request);
        } catch (\Exception $e) {
            Log::error($e);
            //return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getWarehousingRequestList(WarehousingRequestRequest $request)
    {
        try {
            $validated = $request->validated();
            $warehousing = Warehousing::where('w_no', '=', $validated['w_no'])->first();

            $warehousing_request = WarehousingRequest::with(['mb_no','warehousing'])->where('wr_type','=','List')->orderBy('wr_no', 'DESC');
            if($warehousing){
                $warehousing_request = $warehousing_request->where('w_no', '=', $validated['w_no'])->orwhere('w_no', '=', $warehousing->w_import_no);
            }else{
                $warehousing_request = $warehousing_request->where('w_no', '=', $validated['w_no']);
            }

            $warehousing_request = $warehousing_request->first();

            return response()->json($warehousing_request);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function paginateWarehousingRequest(WarehousingRequestRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $warehousing_request = WarehousingRequest::with('mb_no')->orderBy('wr_no', 'DESC');

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
     * @param  WarehousingRequestRegisterRequest $request
     * @return \Illuminate\Http\Response
     */
    public function register(WarehousingRequestRegisterRequest $request)
    {
        $validated = $request->validated();
        try {
            //DB::beginTransaction();
            // FIXME hard set mb_no = 1
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $wr_no = WarehousingRequest::insertGetId([
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
     * @param  \App\Models\WarehousingRequest  $warehousingRequest
     * @return \Illuminate\Http\Response
     */
    public function show(WarehousingRequest $warehousingRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WarehousingRequest  $warehousingRequest
     * @return \Illuminate\Http\Response
     */
    public function edit(WarehousingRequest $warehousingRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WarehousingRequest  $warehousingRequest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, WarehousingRequest $warehousingRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WarehousingRequest  $warehousingRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(WarehousingRequest $warehousingRequest)
    {
        //
    }
}

<?php

namespace App\Http\Controllers\WarehousingRequest;

use DateTime;
use App\Models\Member;
use App\Models\WarehousingRequest;
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
            $warehousing_request = WarehousingRequest::with('mb_no')->orderBy('wr_no', 'DESC');

            $members = Member::where('mb_no', '!=', 0)->get();

            $warehousing_request = $warehousing_request->paginate($per_page, ['*'], 'page', $page);

            return response()->json($warehousing_request);
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

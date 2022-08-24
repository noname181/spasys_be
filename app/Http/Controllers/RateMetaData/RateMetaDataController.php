<?php

namespace App\Http\Controllers\RateMetaData;

use App\Utils\Messages;
use App\Models\RateData;
use App\Models\RateMetaData;
use App\Models\RateMeta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\RateMetaData\RateMetaDataSearchRequest;

class RateMetaDataController extends Controller
{
    /**
     * Register RateDataCreate
     * @param  App\Http\Requests\RateDataRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll(RateMetaDataSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $rmd = RateMetaData::with(['rate_meta', 'member:mb_no,co_no']);
            if(isset($validated['from_date'])) {
                $rm->where('created_at', '>=' , date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }
            if(isset($validated['to_date'])) {
                $rm->where('created_at', '<=' , date('Y-m-d 23:59:59', strtotime($validated['to_date'])));
            }
            // if(isset($validated['rm_biz_name'])) {
            //     $rm->where('rm_biz_name', 'like', '%'.$validated['rm_biz_name'].'%');
            // }
            // if(isset($validated['rm_biz_number'])) {
            //     $rm->where('rm_biz_number','like', '%'.$validated['rm_biz_number'].'%');
            // }
            // if(isset($validated['rm_owner_name'])) {
            //     $rm->where('rm_owner_name','like', '%'.$validated['rm_owner_name'].'%');
            // }
            $rmd = $rmd->paginate($per_page, ['*'], 'page', $page);
            return response()->json($rmd);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
}

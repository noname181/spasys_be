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
use App\Models\Company;
class RateMetaDataController extends Controller
{
    /**
     * Register RateDataCreate
     * @param  App\Http\Requests\RateDataRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllRM(RateMetaDataSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            $user = Auth::user();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $rmd = RateMetaData::with(['rate_meta', 'member:mb_no,mb_type,co_no' , 'rate_data_one'])
            ->whereNotNull('rm_no')
            ->whereNull('rmd_parent_no')
            ->whereHas('member', function($q) use($user){
                $q->where('co_no', $user->co_no);
            })
            ->orderBy('rmd_no', 'DESC');

            if(isset($validated['from_date'])) {
                $rmd->where('created_at', '>=' , date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }
            if(isset($validated['to_date'])) {
                $rmd->where('created_at', '<=' , date('Y-m-d 23:59:59', strtotime($validated['to_date'])));
            }
            if(isset($validated['rm_biz_name'])) {
                $rmd->whereHas('rate_meta', function($rm) use ($validated){
                    $rm->where('rm_biz_name', 'like', '%'.$validated['rm_biz_name'].'%');
                });
            }
            if(isset($validated['rm_biz_number'])) {
                $rmd->whereHas('rate_meta', function($rm) use ($validated){
                    $rm->where('rm_biz_number', 'like', '%'.$validated['rm_biz_number'].'%');
                });
            }
            if(isset($validated['rm_owner_name'])) {
                $rmd->whereHas('rate_meta', function($rm) use ($validated){
                    $rm->where('rm_owner_name', 'like', '%'.$validated['rm_owner_name'].'%');
                });
            }
            if(isset($validated['rd_cate_meta1'])) {
                $rmd->whereHas('rate_data_one', function($rm) use ($validated){
                    $rm->where('rd_cate_meta1', '=', $validated['rd_cate_meta1']);
                });
            }
            $rmd = $rmd->paginate($per_page, ['*'], 'page', $page);

            $rmd->setCollection(
                $rmd->getCollection()->map(function ($item) {
                    $rmd = RateMetaData::where('rmd_no', $item['rmd_no'])->first();
                    if(isset($rmd->rmd_parent_no)){
                        $lastest = RateMetaData::where('rmd_no', $rmd->rmd_parent_no)->orWhere('rmd_parent_no', $rmd->rmd_parent_no)->orderBy('rmd_no', 'DESC')->first();
                    }else {
                        $lastest = RateMetaData::where('rmd_no', '!=', $item['rmd_no'])->where('rmd_parent_no', $item['rmd_no'])->orderBy('rmd_no', 'DESC')->first();
                    }

                    $item->lastest = $lastest;
                    return $item;
                })
            );

            return response()->json($rmd);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getAllCO(RateMetaDataSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            $user = Auth::user();
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $rmd = RateMetaData::with(['rate_meta', 'member:mb_no,co_no,mb_name', 'company'])
            ->whereNotNull('co_no')
            ->whereNull('rmd_parent_no')
            ->whereHas('member', function($q) use($user){
                $q->where('co_no', $user->co_no);
            })
            ->whereHas('company', function($q) use($user){
                $q->where('co_type', '!=', 'spasys');
            })
            ->whereNull('set_type')
            ->orderBy('rmd_no', 'DESC');
            if(isset($validated['from_date'])) {
                $rmd->where('created_at', '>=' , date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }
            if(isset($validated['to_date'])) {
                $rmd->where('created_at', '<=' , date('Y-m-d 23:59:59', strtotime($validated['to_date'])));
            }
            if(isset($validated['service'])) {
      
                $rmd->whereHas('company', function($rm) use ($validated){
                 
                    $rm->where(DB::raw('lower(co_service)'), 'like','%'. strtolower($validated['service']) .'%');
                });
            }
            if(isset($validated['co_name'])) {
                $rmd->whereHas('company', function($rm) use ($validated){
                    $rm->where('co_name', 'like', '%'.$validated['co_name'].'%');
                });
            }
            if(isset($validated['co_parent_name'])) {
                $rmd->whereHas('company', function($rm) use ($validated){
                    $rm->where('rm_owner_name', 'like', '%'.$validated['rm_owner_name'].'%');
                });
            }

            $rmd = $rmd->paginate($per_page, ['*'], 'page', $page);

            $rmd->setCollection(
                $rmd->getCollection()->map(function ($item) {
                    $rmd = RateMetaData::where('rmd_no', $item['rmd_no'])->first();
                    if(isset($rmd->rmd_parent_no)){
                        $lastest = RateMetaData::where('rmd_no', $rmd->rmd_parent_no)->orWhere('rmd_parent_no', $rmd->rmd_parent_no)->orderBy('rmd_no', 'DESC')->first();
                    }else {
                        $lastest = RateMetaData::where('rmd_no', '!=', $item['rmd_no'])->where('rmd_parent_no', $item['rmd_no'])->orderBy('rmd_no', 'DESC')->first();
                    }

                    $item->lastest = $lastest;
                    return $item;
                })
            );

            return response()->json($rmd);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function checkCO(Request $request)
    {

        try {
            $user = Auth::user();
            // If per_page is null set default data = 15

            // If page is null set default data = 1
            DB::enableQueryLog();
            $rmd = RateMetaData::with(['rate_meta', 'member:mb_no,co_no,mb_name', 'company'])
            ->whereNotNull('co_no')
            ->whereNull('rmd_parent_no')
            ->whereNull('set_type')
            ->where('co_no',$request->co_no)
            ->orderBy('rmd_no', 'DESC');
            if($request->co_service){
                $company_check = Company::where('co_no',$request->co_no)->first();
            }
            $rmd = $rmd->get();
            if($request->co_service){
            if(count($rmd) == 0 && ($company_check->co_service == '' || $company_check->co_service == null)){
                $rmd = ['no_services'=>'123'];
            }
            }



            return response()->json($rmd);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function getAllCOPrecalculateDetails(RateMetaDataSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            $user = Auth::user();
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $rmd = RateMetaData::with(['rate_meta', 'member:mb_no,co_no,mb_name', 'company','rate_data_general'])
            ->whereNotNull('co_no')->where(function($q){
                $q->where('set_type','=','estimated_costs')
                ->orWhere('set_type', 'precalculate');
            })
            ->whereHas('member', function($q) use($user){
                $q->where('co_no', $user->co_no);
            })
            ->orderBy('rmd_no', 'DESC');
            if(isset($validated['from_date'])) {
                $rmd->where('created_at', '>=' , date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }
            if(isset($validated['to_date'])) {
                $rmd->where('created_at', '<=' , date('Y-m-d 23:59:59', strtotime($validated['to_date'])));
            }
            if(isset($validated['service'])) {
                $rmd->whereHas('company', function($rm) use ($validated){
                    $rm->where('co_service', 'like', '%'.$validated['service'].'%');
                });
            }
            if(isset($validated['co_name'])) {
                $rmd->whereHas('company', function($rm) use ($validated){
                    $rm->where('co_name', 'like', '%'.$validated['co_name'].'%');
                });
            }
            if(isset($validated['co_parent_name'])) {
                $rmd->whereHas('company', function($rm) use ($validated){
                    $rm->where('rm_owner_name', 'like', '%'.$validated['rm_owner_name'].'%');
                });
            }

            $rmd = $rmd->paginate($per_page, ['*'], 'page', $page);
            return response()->json($rmd);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function get_RMD_data($rmd_no)
    {

        try {
            $rmd = RateMetaData::with(['member'])->where('rmd_no', $rmd_no)->first();

            return response()->json($rmd);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

}

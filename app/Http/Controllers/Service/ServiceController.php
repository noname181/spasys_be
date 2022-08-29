<?php

namespace App\Http\Controllers\Service;

use App\Http\Requests\Service\ServiceRequest;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Menu;
use App\Models\Company;
use App\Models\Member;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * Register Service
     * @param  App\Http\Requests\ServiceRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(ServiceRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            $ids = [];
            $i = 0;
            foreach ($validated['services'] as $val) {
                Log::error($val);
                $service = Service::updateOrCreate(
                    [
                        'service_no' => isset($val['service_no']) ? $val['service_no'] : null,
                    ],
                    [
                        'mb_no' => Auth::user()->mb_no,
                        'service_name' => $val['service_name'],
                        'service_name' => $val['service_name'],
                        'service_eng' => $val['service_eng'],
                        'service_use_yn' => $val['service_use_yn'],
                    ],
                );
                $ids[] = $service->service_no;
                $i++;
            }
            Service::whereNotIn('service_no', $ids)->where('mb_no', Auth::user()->mb_no)->delete();

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function getServices()
    {
        try {
            $services = Service::all();
            $menu_main = Menu::select(['menu_no', 'menu_name', 'service_no_array'])->where('menu_depth', '상위')->get();

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'services' => $services,
                'menu_main' => $menu_main
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getServiceByCoNo($co_no)
    {
        try {
            $company = Company::where('co_no', $co_no)->first();
            if($company->co_type == 'spasys'){
                $services = Service::where('service_use_yn', 'y')->get();
            }else {
                $co_service_array = explode(" ", $company->co_service);
                $services = Service::whereIN("service_name", $co_service_array)->get();
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'services' => $services,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getServiceByMember()
    {
        try {
            $member = Member::with('company')->where('mb_no', Auth::user()->mb_no)->first();

            if($member->mb_type == 'admin'){
                $services = Service::where('service_use_yn', 'y')->where('service_no', '!=', 1)->get();
            }
            else if($member->company->co_type == 'spasys'){
                $services = Service::where('service_use_yn', 'y')->where('service_no', '!=', 1)->get();
            }else {
                $co_service_array = explode(" ",  $member->company->co_service);
                $services = Service::whereIN("service_name", $co_service_array)->get();
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'services' => $services,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function getAllServices()
    {
        try {
            $services = Service::where('service_no', '!=', 1)->get();
            return response()->json([
                'message' => Messages::MSG_0007,
                'services' => $services,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }


    public function getActiveServices()
    {
        try {
            $services = Service::where('service_use_yn', 'y')->get();

            return response()->json([
                'message' => Messages::MSG_0007,
                'services' => $services,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function deleteService(Service $service)
    {
        try {
            $service->delete();
            return response()->json([
                'message' => Messages::MSG_0007
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0006], 500);
        }
    }
}

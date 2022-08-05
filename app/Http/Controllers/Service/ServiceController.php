<?php

namespace App\Http\Controllers\Service;

use App\Http\Requests\Service\ServiceRequest;
use App\Http\Controllers\Controller;
use App\Models\Service;
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

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'services' => $services
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
}

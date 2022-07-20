<?php

namespace App\Http\Controllers\Company;

use App\Http\Requests\Company\CompanyRegisterController\InvokeRequest;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\COAddress;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    /**
     * Register company
     * @param  \App\Http\Requests\Company\CompanyRegisterController\InvokeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(InvokeRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            $co_no = Company::insertGetId([
                'mb_no' => Auth::user()->mb_no,
                'co_name' => $validated['co_name'],
                'co_country' => $validated['co_country'],
                'co_service' => $validated['co_service'],
                'co_owner' => $validated['co_owner'],
                'co_license' => $validated['co_license'],
                'co_homepage' => $validated['co_homepage'],
                'co_email' => $validated['co_email'],
                'co_etc' => $validated['co_etc']
            ]);

            $validated['co_no'] = $co_no;

            $ca_no = COAddress::insertGetId([
                'co_no' => $validated['co_no'],
                'mb_no' => Auth::user()->mb_no,
                'ca_address' => $validated['co_address'],
                'ca_address_detail' => $validated['co_address_detail']
            ]);

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'co_no' => $co_no,
                'ca_no' => $ca_no,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function getCompany()
    {
        try {
            $company = Company::select([
                'company.co_no',
                'company.mb_no',
                'company.co_name',
                'company.co_country',
                'company.co_service',
                'company.co_license',
                'company.co_owner',
                'company.co_homepage',
                'company.co_email',
                'company.co_etc',
                'co_address.ca_address as co_address',
                'co_address.ca_address_detail as co_address_detail',
            ])->join('co_address', 'co_address.co_no', 'company.co_no')
                ->where('company.mb_no', Auth::user()->mb_no)
                ->where('co_address.mb_no', Auth::user()->mb_no)
                ->first();

            return response()->json([
                'message' => Messages::MSG_0007,
                'company' => $company
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    /**
     * Register company
     * @param  \App\Http\Requests\Company\CompanyRegisterController\InvokeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCompany(InvokeRequest $request, Company $company)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();

            $comp = Company::where('co_no', $company->co_no)
                ->where('mb_no', Auth::user()->mb_no)
                ->update([
                    'co_name' => $validated['co_name'],
                    'co_country' => $validated['co_country'],
                    'co_service' => $validated['co_service'],
                    'co_owner' => $validated['co_owner'],
                    'co_license' => $validated['co_license'],
                    'co_homepage' => $validated['co_homepage'],
                    'co_email' => $validated['co_email'],
                    'co_etc' => $validated['co_etc'],
                ]);


            COAddress::where('co_no', $company->co_no)
                ->where('mb_no', Auth::user()->mb_no)
                ->update([
                    'ca_address' => $validated['co_address'],
                    'ca_address_detail' => $validated['co_address_detail']
                ]);

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'company' => $comp,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }
}

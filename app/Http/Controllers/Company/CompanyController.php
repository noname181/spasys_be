<?php

namespace App\Http\Controllers\Company;

use App\Http\Requests\Company\CompanyRegisterController\InvokeRequest;
use App\Http\Requests\Company\CompaniesRequest;
use App\Http\Requests\Company\CompanySearchRequest;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Manager;
use App\Models\AdjustmentGroup;
use App\Models\CoAddress;
use App\Models\ForwarderInfo;
use App\Models\CustomsInfo;
use App\Models\RateData;
use App\Models\RateMetaData;
use App\Models\Contract;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Service;
use App\Models\CompanySettlement;
use App\Models\Export;
use App\Models\Import;
use App\Models\ImportExpected;
use App\Models\CompanyPayment;
use App\Models\ExportConfirm;
use Illuminate\Http\Request;
use App\Utils\CommonFunc;
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

            if (Auth::user()->mb_type == 'spasys') {
                $co_type = 'shop';
            } else if (Auth::user()->mb_type == 'shop') {
                $co_type = 'shipper';
            } else {
                $co_type = 'spasys';
            }
            $co_no = Company::insertGetId([
                'mb_no' => Auth::user()->mb_no,
                'co_parent_no' => Auth::user()->co_no,
                'co_name' => $validated['co_name'],
                'co_address' => isset($validated['co_address']) ? $validated['co_address']: '',
                'co_zipcode' => isset($validated['co_zipcode']) ? $validated['co_zipcode'] : '',
                'co_address_detail' => isset($validated['co_address_detail']) ? $validated['co_address_detail']: '',
                // 'co_country' => $validated['co_country'],
                'co_major' => isset($validated['co_major']) ? $validated['co_major']: '',
                'co_owner' => isset($validated['co_owner']) ? $validated['co_owner']: '',
                'co_license' => isset($validated['co_license']) ? $validated['co_license']: '',
                'co_homepage' => isset($validated['co_homepage']) ? $validated['co_homepage']: '',
                'co_email' => isset($validated['co_email']) ? $validated['co_email']: '',
                'co_etc' => isset($validated['co_etc']) ? $validated['co_etc']: '',
                'co_type' => isset($co_type) ? $co_type : '',
                'co_close_yn' => isset($validated['co_close_yn']) ? $validated['co_close_yn']: '',
                'co_tel' => isset($validated['co_tel']) ? $validated['co_tel']: '',
            ]);

            $company = Company::where('co_no', $co_no)->first();

            // $ca_no = COAddress::insertGetId([
            //     'co_no' => $validated['co_no'],
            //     'mb_no' => Auth::user()->mb_no,
            //     'ca_address' => $validated['co_address'],
            //     'ca_address_detail' => $validated['co_address_detail']
            // ]);

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'company' => $company,
                // 'ca_no' => $ca_no,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function getCompanies(CompanySearchRequest $request)
    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();
            $user = Auth::user();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $companies = Company::with(['contract', 'co_parent', 'company_settlement', 'company_payment', 'mb_no'])
                ->where(function ($q) use ($user) {
                    $q->where('co_no', $user->co_no)
                        ->orWhereHas('co_parent', function ($q) use ($user) {
                            $q->where('co_no', $user->co_no)
                                ->orWhereHas('co_parent', function ($q) use ($user) {
                                    $q->where('co_no', $user->co_no);
                                });
                        });
                })

                ->where('co_type', '!=', 'spasys')
                ->orderBy('co_no', 'DESC');

            // $user = Auth::user();
            // if($user->mb_type == 'shop'){
            //     $companies = Company::with(['contract', 'co_parent'])->where('co_type', '!=', 'spasys')->whereHas('co_parent',function($q) use ($user){
            //         $q->where('co_no', $user->co_no);
            //     })->orderBy('co_no', 'DESC');
            // }else if($user->mb_type == 'shipper'){
            //     $companies = Company::with(['contract', 'co_parent'])->where('co_type', '!=', 'spasys')->where('co_no',$user->co_no)->orderBy('co_no', 'DESC');
            // }else if($user->mb_type == 'spasys'){
            //     $companies = Company::with(['contract', 'co_parent'])->where('co_type', '!=', 'spasys')->whereHas('co_parent.co_parent',function($q) use ($user){
            //         $q->where('co_no', $user->co_no);
            //     })->orderBy('co_no', 'DESC');
            // }

            if (isset($validated['from_date'])) {
                $companies->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $companies->where('updated_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $companies->whereHas('co_parent', function ($query) use ($validated) {
                    $query->where('co_name', 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                    $query->orWhere('company.co_name', 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }

            if (isset($validated['co_name'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['co_service'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_service)'), 'like', '%' . ($validated['co_service']) . '%');
                });
            }
            if (isset($validated['c_payment_cycle'])) {
                $companies->whereHas('contract', function ($query) use ($validated) {
                    $query->where('c_payment_cycle', '=', $validated['c_payment_cycle']);
                });
            }
            if (isset($validated['c_calculate_method1']) || isset($validated['c_calculate_method2']) || isset($validated['c_calculate_method3']) || isset($validated['c_calculate_method4'])) {
                $companies->whereHas('contract', function ($query) use ($validated) {
                    $query->where('c_calculate_method', '=', $validated['c_calculate_method1']);
                    $query->orwhere('c_calculate_method', '=', $validated['c_calculate_method2']);
                    $query->orwhere('c_calculate_method', '=', $validated['c_calculate_method3']);
                    $query->orwhere('c_calculate_method', '=', $validated['c_calculate_method4']);
                });
            }
            if (isset($validated['c_transaction_yn'])) {
                $companies->whereHas('contract', function ($query) use ($validated) {
                    $query->where('c_transaction_yn', '=', $validated['c_transaction_yn']);
                });
            }
            if (isset($validated['co_close_yn'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where('co_close_yn', '=', $validated['co_close_yn']);
                });
            }
            if (isset($validated['c_calculate_deadline_yn'])) {
                $companies->whereHas('contract', function ($query) use ($validated) {
                    $query->where('c_calculate_deadline_yn', '=', $validated['c_calculate_deadline_yn']);
                });
            }

            if (isset($validated['c_integrated_calculate_yn'])) {
                $companies->whereHas('contract', function ($query) use ($validated) {
                    $query->where('c_integrated_calculate_yn', '=', $validated['c_integrated_calculate_yn']);
                });
            }

            $companies = $companies->paginate($per_page, ['*'], 'page', $page);

           


            return response()->json($companies);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getCompany($co_no)
    {
        try {
            $contract = Contract::where('co_no', $co_no)->first();
            if (isset($contract->c_no)) {
                $company = Company::select([
                    'company.co_no',
                    'company.co_type',
                    'company.co_parent_no',
                    'company.mb_no',
                    'company.co_name',
                    'company.co_address',
                    'company.co_zipcode',
                    'company.co_address_detail',
                    'company.co_country',
                    'company.co_service',
                    'company.co_major',
                    'company.co_license',
                    'company.co_close_yn',
                    'company.co_owner',
                    'company.co_homepage',
                    'company.co_email',
                    'company.co_etc',
                    'contract.c_integrated_calculate_yn as c_integrated_calculate_yn',
                    'contract.c_calculate_deadline_yn as c_calculate_deadline_yn',
                    'company.co_tel',
                    // 'co_address.ca_address_detail as co_address_detail',
                    // ])->join('co_address', 'co_address.co_no', 'company.co_no')
                ])->join('contract', 'contract.co_no', 'company.co_no')->where('company.co_no', $co_no)
                    // ->where('co_address.co_no', $co_no)
                    ->first();
            } else {
                $company = Company::select([
                    'company.co_no',
                    'company.co_type',
                    'company.co_parent_no',
                    'company.mb_no',
                    'company.co_name',
                    'company.co_address',
                    'company.co_zipcode',
                    'company.co_address_detail',
                    'company.co_country',
                    'company.co_service',
                    'company.co_major',
                    'company.co_license',
                    'company.co_close_yn',
                    'company.co_owner',
                    'company.co_homepage',
                    'company.co_email',
                    'company.co_etc',
                    'company.co_tel',

                ])->where('company.co_no', $co_no)
                    // ->where('co_address.co_no', $co_no)
                    ->first();
                if ($company) {
                    $company->c_integrated_calculate_yn = null;
                    $company->c_calculate_deadline_yn = null;
                }
            }


            $adjustment_groups = AdjustmentGroup::select(['ag_no', 'co_no', 'ag_name', 'ag_manager', 'ag_hp', 'ag_email', 'ag_email2', 'ag_auto_issue'])->where('co_no', $co_no)->get();
            $co_address = CoAddress::select(['ca_is_default', 'ca_no', 'co_no', 'ca_name', 'ca_manager', 'ca_hp', 'ca_address', 'ca_address_detail'])->where('co_no', $co_no)->get();
            $forwarder_info = ForwarderInfo::select(['fi_no', 'co_no', 'fi_name', 'fi_manager', 'fi_hp', 'fi_address', 'fi_address_detail'])->where('co_no', $co_no)->get();
            $customs_info = CustomsInfo::select(['ci_no', 'co_no', 'ci_name', 'ci_manager', 'ci_hp', 'ci_address', 'ci_address_detail'])->where('co_no', $co_no)->get();
            $manager = Manager::where('co_no', $co_no)->first();
            $services = Service::where('service_use_yn', 'y')->where('service_no', '!=', 1)->get();
            $services_use = explode(" ",$company->co_service);
            $company_payment = CompanyPayment::where('co_no', $company->co_parent_no)->first();
            return response()->json([
                'message' => Messages::MSG_0007,
                'company' => $company,
                'adjustment_groups' => $adjustment_groups,
                'co_address' => $co_address,
                'forwarder_info' => $forwarder_info,
                'customs_info' => $customs_info,
                'manager' => $manager,
                'services' => $services,
                'services_use'=>$services_use,
                'company_payment' => $company_payment
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
    public function getCoAddressList(CompanySearchRequest $request)
    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            $user = Auth::user();
            $co_address = CoAddress::with(['company'])->where('co_no', $request->co_no);


            $co_address = $co_address->paginate($per_page, ['*'], 'page', $page);

            return response()->json($co_address);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function getCoAddressDefault(CompanySearchRequest $request)
    {
        try {
            DB::enableQueryLog();

            $co_address_default = CoAddress::with(['company'])->where('ca_is_default', 'y')->where('co_no', $request->co_no)->first();

            return response()->json(['data' => $co_address_default]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
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
        $user = Auth::user();
        try {
            DB::beginTransaction();

            //INSERT ALARM
            $company = Company::with(['contract'])->where('co_no', $company->co_no)->first();
            if($company->co_close_yn == 'n' && $validated['co_close_yn'] == 'y'){
               CommonFunc::insert_alarm('휴폐업 안내', $company, $user, null, 'update_company', null);
            }

            //END INSERT ALARM

            $comp = Company::where('co_no', $company->co_no)
                // ->where('mb_no', Auth::user()->mb_no)
                ->update([
                    'co_name' => $validated['co_name'],
                    'co_address' => $validated['co_address'],
                    'co_zipcode' => $validated['co_zipcode'],
                    'co_address_detail' => $validated['co_address_detail'],
                    // 'co_country' => $validated['co_country'],
                    'co_major' => $validated['co_major'],
                    'co_owner' => $validated['co_owner'],
                    'co_license' => $validated['co_license'],
                    'co_homepage' => $validated['co_homepage'],
                    'co_email' => $validated['co_email'],
                    'co_etc' => $validated['co_etc'],
                    'co_close_yn' => $validated['co_close_yn'],
                    'co_tel' => $validated['co_tel'],
                ]);


            // COAddress::where('co_no', $company->co_no)
            //     ->where('mb_no', Auth::user()->mb_no)
            //     ->update([
            //         'ca_address' => $validated['co_address'],
            //         'ca_address_detail' => $validated['co_address_detail']
            //     ]);

            

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'company' => $comp,
                'sql' => $company->co_no,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }
    public function updateCompanyColicense(Request $request, Company $company)
    {
        try {
            DB::beginTransaction();

            // $comp = Company::where('co_no', $company->co_no)
            //     ->update([
            //         'co_license' => $request->co_license,
            //     ]);
            $update = Company::updateOrCreate(
                [
                    'co_no' =>  $company->co_no
                ],
                [
                    'co_license' => $request->co_license,
                ]
            );
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'company' => $update,
                'sql' => $company->co_no,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }

    public function  getShopCompanies(CompanySearchRequest $request)
    {
        try {
            $validated = $request->validated();
            //DB::enableQueryLog();
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $co_no = Auth::user()->co_no ? Auth::user()->co_no : '';
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $companies = Company::with(['contract', 'co_parent', 'adjustment_group'])->where('co_type', 'shop')->orderBy('co_no', 'DESC');


            $companies->whereHas('co_parent', function ($query) use ($co_no) {
                $query->where('co_no', '=',  $co_no);
            });


            if (isset($validated['from_date'])) {
                $companies->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $companies->where('updated_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_name'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }


            if (isset($validated['co_service'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_service)'), 'like', '%' . strtolower($validated['co_service']) . '%');
                });
            }

            $companies = $companies->paginate($per_page, ['*'], 'page', $page);

            $companies->setCollection(
                $companies->getCollection()->map(function ($item) {
                    $service_names = explode(" ", $item->co_service);
                    $co_no = $item->co_no;

                    $settlement_cycle = [];

                    foreach ($service_names as $service_name) {
                        $service = Service::where('service_name', $service_name)->first();
                        if (isset($service->service_no)) {
                            $company_settlement = CompanySettlement::where([
                                'co_no' => $co_no,
                                'service_no' => $service->service_no
                            ])->first();
                            if ($company_settlement) {
                                $settlement_cycle[] = $company_settlement->cs_payment_cycle;
                            }
                        }
                    }
                    $settlement_cycle = implode("/", $settlement_cycle);
                         
                    $rmd = RateMetaData::with(['rate_meta', 'member:mb_no,co_no,mb_name', 'company'])
                    ->whereNotNull('co_no')
                    ->whereNull('rmd_parent_no')
                    ->whereNull('set_type')
                    ->where('co_no',$co_no)
                    ->orderBy('rmd_no', 'DESC')->get();
                    $item->settlement_cycle = $settlement_cycle;
                    $item->check_rate = $rmd;
                    
                    $check_block = 'n';
                    foreach(explode(" ", $item->co_service) as $row){
                     
                       $rate_data = RateData::where('rd_cate_meta1', $row);
                       $rmd_2 = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->orderBy('rmd_no', 'DESC')->first();
                       $rate_data = $rate_data->where('rd_co_no', $co_no);
                       
                       if (isset($rmd_2->rmd_no)) {
                        $rate_data = $rate_data->where('rmd_no', $rmd_2->rmd_no)->get();
                        if(count($rate_data) > 0){
                            
                        } else {
                            $check_block = 'y';
                        }
                       }else {
                          $check_block = 'y';
                       }
                    
                    }
                    $item->check_block = $check_block;
                    return $item;
                })
            );
            //return DB::getQueryLog();
            return response()->json($companies);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function getCompanyFromtcon(Request $request)
    {
        if($request->type_page == "te_carry_out_number"){
            $export = Export::with(['import', 'import_expected', 't_export_confirm'])->where('te_carry_out_number', $request->is_no)->first();
            $company = Company::with(['co_parent'])->where('co_license', $export->import_expected->tie_co_license)->first();
            if (isset($company->co_no)) {
                $adjustment_group = AdjustmentGroup::where('co_no', '=', $company->co_no)->first();
            } else {
                $adjustment_group = "";
            }
        }else if($request->type_page == "ti_carry_in_number") {
            $export = [];
            $import = Import::with(['import_expected', 'export_confirm'])->where('ti_carry_in_number', $request->is_no)->first();
            $export['import'] = $import;
            $export['import_expected'] = $import->import_expected;
            $company = Company::with(['co_parent'])->where('co_license', $import->import_expect->tie_co_license)->first();
            if (isset($company->co_no)) {
                $adjustment_group = AdjustmentGroup::where('co_no', '=', $company->co_no)->first();
            } else {
                $adjustment_group = "";
            }
        }else{
            $import_expected = ImportExpected::where('tie_logistic_manage_number', $request->is_no)->first();
            $export['import_expected'] = $import_expected;
            $company = Company::with(['co_parent'])->where('co_license', $import_expected->tie_co_license)->first();
            if (isset($company->co_no)) {
                $adjustment_group = AdjustmentGroup::where('co_no', '=', $company->co_no)->first();
            } else {
                $adjustment_group = "";
            }
        }

        return response()->json(['export' => $export, 'company' => $company, 'adjustment_group' => $adjustment_group]);
    }
    public function  getShopCompaniesMobile(CompanySearchRequest $request)
    {
        try {
            $validated = $request->validated();
            //DB::enableQueryLog();

            $co_no = Auth::user()->co_no ? Auth::user()->co_no : '';

            $companies = Company::with(['contract', 'co_parent', 'adjustment_group'])->where('co_type', 'shop')->orderBy('co_no', 'DESC');


            $companies->whereHas('co_parent', function ($query) use ($co_no) {
                $query->where('co_no', '=',  $co_no);
            });


            if (isset($validated['from_date'])) {
                $companies->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $companies->where('updated_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_name'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }


            if (isset($validated['co_service'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_service)'), 'like', '%' . strtolower($validated['co_service']) . '%');
                });
            }

            $companies = $companies->get();
            forEach($companies as $item){
                $service_names = explode(" ", $item->co_service);
                $co_no = $item->co_no;

                $settlement_cycle = [];

                foreach ($service_names as $service_name) {
                    $service = Service::where('service_name', $service_name)->first();
                    if (isset($service->service_no)) {
                        $company_settlement = CompanySettlement::where([
                            'co_no' => $co_no,
                            'service_no' => $service->service_no
                        ])->first();
                        if ($company_settlement) {
                            $settlement_cycle[] = $company_settlement->cs_payment_cycle;
                        }
                    }
                }
                $settlement_cycle = implode("/", $settlement_cycle);
                     
                $rmd = RateMetaData::with(['rate_meta', 'member:mb_no,co_no,mb_name', 'company'])
                ->whereNotNull('co_no')
                ->whereNull('rmd_parent_no')
                ->whereNull('set_type')
                ->where('co_no',$co_no)
                ->orderBy('rmd_no', 'DESC')->get();
                $item->settlement_cycle = $settlement_cycle;
                $item->check_rate = $rmd;

                $check_block = 'n';
                foreach(explode(" ", $item->co_service) as $row){
                 
                   $rate_data = RateData::where('rd_cate_meta1', $row);
                   $rmd_2 = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->orderBy('rmd_no', 'DESC')->first();
                   $rate_data = $rate_data->where('rd_co_no', $co_no);
                   
                   if (isset($rmd_2->rmd_no)) {
                    $rate_data = $rate_data->where('rmd_no', $rmd_2->rmd_no)->get();
                    if(count($rate_data) > 0){
                        
                    } else {
                        $check_block = 'y';
                    }
                   }else {
                      $check_block = 'y';
                   }
                
                }
                $item->check_block = $check_block;
               // return $item;
            }

            //return DB::getQueryLog();
            return response()->json(['data' => $companies]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function  getShipperCompanies(CompanySearchRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $co_no = Auth::user()->co_no ? Auth::user()->co_no : '';
            $user = Auth::user();
            if ($user->mb_type == "shop") {
                $companies = Company::with(['contract', 'co_parent', 'warehousing'])->where('co_type', 'shipper')->orderBy('co_no', 'DESC');


                $companies->whereHas('co_parent', function ($query) use ($user) {
                    $query->where('co_no', '=',  $user->co_no);
                });
            } else {
                $companies = Company::with(['contract', 'co_parent'])->with('warehousing')->where('co_type', 'shipper')->whereIn('co_parent_no', function ($query) use ($user) {
                    $query->select('co_no')
                        ->from(with(new Company)->getTable())
                        ->where('co_type', 'shop')
                        ->where('co_parent_no', $user->co_no);
                })->orderBy('co_no', 'DESC');
            }
            //$companies = Company::with('contract')->with('warehousing')->where('co_type', 'shipper')->where('co_parent_no', $user->co_no)->orderBy('co_no', 'DESC');


            // if (isset($validated['w_no'])) {
            //     $companies->whereHas('warehousing', function ($query) use ($validated) {
            //         $query->where('w_no', '=',  $validated['w_no']);
            //     });
            // }
            
            if (isset($validated['co_service'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_service)'), 'like', '%' . strtolower($validated['co_service']) . '%');
                });
            }

            if (isset($validated['from_date'])) {
                $companies->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $companies->where('updated_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_name_shop'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name_shop']) . '%');
                });
            }

            if (isset($validated['co_service'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_service)'), 'like', '%' . strtolower($validated['co_service']) . '%');
                });
            }

            $companies = $companies->paginate($per_page, ['*'], 'page', $page);
            $companies->setCollection(
                $companies->getCollection()->map(function ($item) {
                    $service_names = explode(" ", $item->co_service);
                    $co_no = $item->co_no;
                 
                    $settlement_cycle = [];

                    foreach ($service_names as $service_name) {
                        $service = Service::where('service_name', $service_name)->first();
                        if (isset($service->service_no)) {
                            $company_settlement = CompanySettlement::where([
                                'co_no' => $co_no,
                                'service_no' => $service->service_no
                            ])->first();
                            if ($company_settlement) {
                                $settlement_cycle[] = $company_settlement->cs_payment_cycle;
                            }
                        }
                    }
                    $settlement_cycle = implode("/", $settlement_cycle);
                        
                    $rmd = RateMetaData::with(['rate_meta', 'member:mb_no,co_no,mb_name', 'company'])
                    ->whereNotNull('co_no')
                    ->whereNull('rmd_parent_no')
                    ->whereNull('set_type')
                    ->where('co_no',$co_no)
                    ->orderBy('rmd_no', 'DESC')->get();
                    $item->settlement_cycle = $settlement_cycle;
                    $item->check_rate = $rmd;

                    $check_block = 'n';
                    foreach(explode(" ", $item->co_service) as $row){
                     
                       $rate_data = RateData::where('rd_cate_meta1', $row);
                       $rmd_2 = RateMetaData::where('co_no', $co_no)->whereNull('set_type')->orderBy('rmd_no', 'DESC')->first();
                       $rate_data = $rate_data->where('rd_co_no', $co_no);
                       
                       if (isset($rmd_2->rmd_no)) {
                        $rate_data = $rate_data->where('rmd_no', $rmd_2->rmd_no)->get();
                        if(count($rate_data) > 0){
                            
                        } else {
                            $check_block = 'y';
                        }
                       }else {
                          $check_block = 'y';
                       }
                    
                    }
                    $item->check_block = $check_block;

                    return $item;
                })
            );
            return response()->json($companies);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getShopAndShipperCompanies(CompanySearchRequest $request)
    {
        try {
            $validated = $request->validated();
            //DB::enableQueryLog();
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $co_no = Auth::user()->co_no ? Auth::user()->co_no : '';
            $user = Auth::user();
            $page = isset($validated['page']) ? $validated['page'] : 1;

            if ($validated['type'] == "shop") {
                $companies = Company::with(['contract', 'co_parent'])->where('co_type', 'shop')->orderBy('co_no', 'DESC');


                $companies->whereHas('co_parent', function ($query) use ($co_no) {
                    $query->where('co_no', '=',  $co_no);
                });
            } else {
                // $companies_shop_id = Company::with('contract')->with('warehousing')->where('co_type', 'shop')
                // ->where('co_parent_no', $user->co_no)->orderBy('co_no', 'DESC')->pluck('co_no')->toArray();

                $companies = Company::with(['contract', 'co_parent'])->with('warehousing')->where('co_type', 'shipper')->whereIn('co_parent_no', function ($query) use ($user) {
                    $query->select('co_no')
                        ->from(with(new Company)->getTable())
                        ->where('co_type', 'shop')
                        ->where('co_parent_no', $user->co_no);
                })->orderBy('co_no', 'DESC');
            }

            if (isset($validated['from_date'])) {
                $companies->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $companies->where('updated_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_name'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }


            if (isset($validated['co_service'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_service)'), 'like', '%' . strtolower($validated['co_service']) . '%');
                });
            }

            $companies = $companies->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();
            return response()->json($companies);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function  getShipperCompaniesMobile(CompanySearchRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15

            // If page is null set default data = 1

            $user = Auth::user();
            //$companies = Company::with('contract')->with('warehousing')->where('co_type', 'shipper')->where('co_parent_no', $user->co_no)->orderBy('co_no', 'DESC');

            if ($user->mb_type == "shop") {
                $companies = Company::with(['contract', 'co_parent', 'warehousing'])->where('co_type', 'shipper')->orderBy('co_no', 'DESC');


                $companies->whereHas('co_parent', function ($query) use ($user) {
                    $query->where('co_no', '=',  $user->co_no);
                });
            } else {
                $companies = Company::with(['contract', 'co_parent'])->with('warehousing')->where('co_type', 'shipper')->whereIn('co_parent_no', function ($query) use ($user) {
                    $query->select('co_no')
                        ->from(with(new Company)->getTable())
                        ->where('co_type', 'shop')
                        ->where('co_parent_no', $user->co_no);
                })->orderBy('co_no', 'DESC');
            }

            // if (isset($validated['w_no'])) {
            //     $companies->whereHas('warehousing', function ($query) use ($validated) {
            //         $query->where('w_no', '=',  $validated['w_no']);
            //     });
            // }

            if (isset($validated['from_date'])) {
                $companies->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $companies->where('updated_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_name_shop'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name_shop']) . '%');
                });
            }

            if (isset($validated['co_service'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_service)'), 'like', '%' . strtolower($validated['co_service']) . '%');
                });
            }

            $companies = $companies->get();
            forEach($companies as $item){
                $service_names = explode(" ", $item->co_service);
                $co_no = $item->co_no;

                $settlement_cycle = [];

                foreach ($service_names as $service_name) {
                    $service = Service::where('service_name', $service_name)->first();
                    if (isset($service->service_no)) {
                        $company_settlement = CompanySettlement::where([
                            'co_no' => $co_no,
                            'service_no' => $service->service_no
                        ])->first();
                        if ($company_settlement) {
                            $settlement_cycle[] = $company_settlement->cs_payment_cycle;
                        }
                    }
                }
                $settlement_cycle = implode("/", $settlement_cycle);
                     
                $rmd = RateMetaData::with(['rate_meta', 'member:mb_no,co_no,mb_name', 'company'])
                ->whereNotNull('co_no')
                ->whereNull('rmd_parent_no')
                ->whereNull('set_type')
                ->where('co_no',$co_no)
                ->orderBy('rmd_no', 'DESC')->get();
                $item->settlement_cycle = $settlement_cycle;
                $item->check_rate = $rmd;
               // return $item;
            }
            return response()->json(['data' =>$companies]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function  getShipperCompanies2(CompanySearchRequest $request)
    {
        try {
            $validated = $request->validated();


            $user = Auth::user();
            $companies2 = "";

            if ($validated['type'] == 'shop') {
                $companies = Company::with('contract')->with('warehousing')->where('co_type', 'shipper')->where('co_parent_no', $user->co_no)->orderBy('co_no', 'DESC');

                if (isset($validated['w_no'])) {
                    $companies2 = Company::with('contract')->with('warehousing')->where('co_type', 'shipper')->where('co_parent_no', $user->co_no)->orderBy('co_no', 'DESC');
                    $companies2->whereHas('warehousing', function ($query) use ($validated) {
                        $query->where('w_no', '=',  $validated['w_no']);
                    });
                    $companies2 = $companies2->first();
                }
            } else {
                $companies = Company::with('contract')->with('warehousing')->where('co_type', 'shipper')->orderBy('co_no', 'DESC');

                if (isset($validated['w_no'])) {
                    $companies2 = Company::with('contract')->with('warehousing')->where('co_type', 'shipper')->orderBy('co_no', 'DESC');
                    $companies2->whereHas('warehousing', function ($query) use ($validated) {
                        $query->where('w_no', '=',  $validated['w_no']);
                    });
                    $companies2 = $companies2->first();
                }
            }



            $companies = $companies->get();

            return response()->json(['data' => $companies, 'selected' => $companies2]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function  getItemCompanies(CompanySearchRequest $request)
    {
        try {
            //return $request;
            $validated = $request->validated();
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop')
                $companies = Company::with('contract')->where('co_type', 'shipper')->where('co_parent_no', $user->co_no)->orderBy('co_no', 'DESC');
            else if ($user->mb_type == 'spasys')
                $companies = Company::with('contract')->where('co_type', 'shipper')->whereHas('co_parent', function ($q) use ($user) {
                    $q->where('co_parent_no', $user->co_no);
                })->orderBy('co_no', 'DESC');

            if (isset($validated['from_date'])) {
                $companies->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $companies->where('updated_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_name_shop'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name_shop']) . '%');
                });
            }

            if (isset($validated['co_service'])) {
                $companies->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_service)'), 'like', '%' . strtolower($validated['co_service']) . '%');
                });
            }

            $companies = $companies->paginate($per_page, ['*'], 'page', $page);

            return response()->json($companies);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function getCustomerCenterInformation()
    {
        try {
            $co_no = Auth::user()->co_no;
            $company = Company::where('co_no', $co_no)->first();
            $company->co_parent;
            $co_type = Auth::user()->mb_type;
            if ($co_type == 'shipper') {
                $infomation = $company->co_parent->co_parent;
            }
            if ($co_type == 'shop') {
                $infomation = $company->co_parent;
            }
            if ($co_type == 'spasys') {
                $infomation = $company;
            }
            return response()->json($infomation);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function getCompanyPolicy($co_no)
    {
        try {
            $companypolicy = Company::select([
                'company.co_policy',
            ])->where('company.co_no', $co_no)->first();

            return response()->json([
                'message' => Messages::MSG_0007,
                'companypolicy' => $companypolicy,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
}

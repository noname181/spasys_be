<?php

namespace App\Http\Controllers\Contract;

use DateTime;
use App\Http\Requests\Contract\ContractRegisterController\ContractRegisterRequest;
use App\Http\Requests\Contract\ContractUpdateController\ContractUpdateRequest;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ReceivingGoodsDelivery;
use App\Models\Payment;
use App\Models\Company;
use App\Models\CompanySettlement;
use App\Models\CompanyPayment;
use App\Models\Member;
use App\Models\Service;
use App\Utils\Messages;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    /**
     * Register Contract
     * @param  App\Http\Requests\Contract\ContractRegisterController\ContractRegisterRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(ContractRegisterRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            $co_no = $validated['co_no'];

            $c_file_insulance = join('/', ['files', 'company', $co_no, 'insulance']);
            $c_file_license = join('/', ['files', 'company',  $co_no, 'license']);
            $c_file_contract = join('/', ['files', 'company',  $co_no, 'contract']);
            $c_file_bank_account = join('/', ['files', 'company',  $co_no, 'bank_account']);

            $c_file_insulance_ = isset($validated['c_file_insulance']) ? $validated['c_file_insulance'] : '';
            $c_file_license_ = isset($validated['c_file_license']) ? $validated['c_file_license'] : '';
            $c_file_contract_ = isset($validated['c_file_contract']) ? $validated['c_file_contract'] : '';
            $c_file_bank_account_ = isset($validated['c_file_bank_account']) ? $validated['c_file_bank_account'] : '';


            if(isset($validated['c_file_insulance'])) $inl = Storage::disk('public')->put($c_file_insulance, $c_file_insulance_) ;
            if(isset($validated['c_file_license'])) $lic = Storage::disk('public')->put($c_file_license,  $c_file_license_);
            if(isset($validated['c_file_contract'])) $con = Storage::disk('public')->put($c_file_contract,   $c_file_contract_);
            if(isset($validated['c_file_bank_account'])) $bc = Storage::disk('public')->put($c_file_bank_account,  $c_file_bank_account_);

            $c_no = Contract::insertGetId([
                'co_no' => $validated['co_no'],
                'mb_no' => Auth::user()->mb_no,
                'c_start_date' => DateTime::createFromFormat('Y-m-d', $validated['c_start_date']),
                'c_end_date' => DateTime::createFromFormat('Y-m-d', $validated['c_end_date']),
                'c_transaction_yn' => $validated['c_transaction_yn'],
                'c_calculate_deadline_yn' => $validated['c_calculate_deadline_yn'],
                'c_integrated_calculate_yn' => $validated['c_integrated_calculate_yn'],
                // 'c_card_number' => $validated['c_card_number'],
                'c_deposit_day' => ($validated['c_deposit_day'] && $validated['c_deposit_day'] !='null') ? $validated['c_deposit_day']  : null,
                // 'c_account_number' => $validated['c_account_number'],
                'c_deposit_price' => ($validated['c_deposit_price'] && $validated['c_deposit_price'] !='null') ? $validated['c_deposit_price']  : null,
                'c_deposit_date' => $validated['c_deposit_date'] ? DateTime::createFromFormat('Y-m-d', $validated['c_deposit_date']) : null,
                'c_file_insulance' => isset($inl) ? $inl : null,
                'c_file_license' => isset($lic) ? $lic : null,
                'c_file_contract' => isset($con) ? $con : null,
                'c_file_bank_account' => isset($bc) ? $bc : null,
                'c_deposit_return_price' => ($validated['c_deposit_return_price'] && $validated['c_deposit_return_price'] !='null') ? $validated['c_deposit_return_price']  : null,
                'c_deposit_return_date' => $validated['c_deposit_return_date'] ? DateTime::createFromFormat('Y-m-d', $validated['c_deposit_return_date']) : null,
                'c_deposit_return_reg_date' => $validated['c_deposit_return_reg_date'] ? DateTime::createFromFormat('Y-m-d', $validated['c_deposit_return_reg_date']) : null,
                'c_deposit_return_expiry_date' =>  $validated['c_deposit_return_expiry_date'] ? DateTime::createFromFormat('Y-m-d', $validated['c_deposit_return_expiry_date']) : null,
            ]);
            $company = Company::where('co_no', $co_no)->update([
                'co_service' => $validated['co_service'],
            ]);

            $i = 0;
            foreach($validated['service_no'] as $co_settlement){

                CompanySettlement::updateOrCreate(
                    [
                        'co_no' => $co_no,
                        'service_no' => $validated['service_no'][$i],
                    ],
                    [
                        'cs_payment_cycle' => $validated['c_payment_cycle'][$i],
                        'cs_money_type' => $validated['c_money_type'][$i],
                        'cs_payment_group' => $validated['c_payment_group'][$i],
                        'cs_system' => $validated['c_system'][$i],
                    ]
                );
                $i++;
            }

            CompanyPayment::updateOrCreate(
                [
                    'co_no' => $co_no
                ],
                [
                    'cp_method' => isset($validated['cp_method']) ? $validated['cp_method'] : null,
                    'cp_virtual_account' => isset($validated['cp_virtual_account']) ? $validated['cp_virtual_account'] : null,
                    'cp_bank' => isset($validated['cp_bank']) ? $validated['cp_bank'] : null,
                    'cp_bank_number' => isset($validated['cp_bank_number']) ? $validated['cp_bank_number'] : null,
                    'cp_bank_name' => isset($validated['cp_bank_name']) ? $validated['cp_bank_name'] : null,
                    'cp_card_name' => isset($validated['cp_card_name']) ? $validated['cp_card_name'] : null,
                    'cp_card_number' => isset($validated['cp_card_number']) ? $validated['cp_card_number'] : null,
                    'cp_card_cvc' => isset($validated['cp_card_cvc']) ? $validated['cp_card_cvc'] : null,
                    'cp_valid_period' => isset($validated['cp_valid_period']) ? $validated['cp_valid_period'] : null,
                    'cp_cvc' => isset($validated['cp_cvc']) ? $validated['cp_cvc'] : null,
                ]
            );

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'c_no' => $c_no,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function getContract($co_no)
    {
        try {
            $company = Company::where('co_no', $co_no)->first();
            $co_service = $company->co_service;
            $contract = Contract::where(['co_no' => $co_no])->first();
            $co_payment = CompanyPayment::where(['co_no' => $co_no])->first();
           

            $services = Service::where('service_use_yn', 'y')->where('service_no', '!=', 1)->get();

            foreach($services as $service){
                $cs = CompanySettleMent::where('service_no', $service['service_no'])->where('co_no', $co_no)->first();

                if(empty($cs)){
                    CompanySettlement::updateOrCreate(
                        [
                            'co_no' => $co_no,
                            'service_no' => $service['service_no'],
                        ],
                        [
                            'cs_payment_cycle' => '건별',
                            'cs_money_type' => 'KRW',
                            'cs_payment_group' => 'y',
                            'cs_system' => 'BLP시스템',
                        ]
                    );
                }
            }

            $company_settlement = CompanySettleMent::where(['co_no' => $co_no])
            ->leftJoin('service', 'service.service_no', '=', 'company_settlement.service_no')
            ->get();
            
            return response()->json([
                'message' => Messages::MSG_0007,
                'contract' => $contract,
                'services' => $services,
                'co_service' => $co_service,
                'co_payment' => $co_payment,
                'company_settlement' => $company_settlement,
                'company' => $company
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return  $e;
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    /**
     * Update Contract
     * @param  App\Http\Requests\Contract\ContractUpdateController\ContractUpdateRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateContract(ContractUpdateRequest $request, Contract $contract)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            $co_no = $validated['co_no'];

            $files = [];
            if (isset($validated['c_file_insulance'])) {
                $c_file_insulance = join('/', ['files', 'company', $co_no, 'insulance']);
                Storage::disk('public')->delete($contract->c_file_insulance);
                $files['c_file_insulance'] = Storage::disk('public')->put($c_file_insulance, $validated['c_file_insulance']);
            }
            if (isset($validated['c_file_license'])) {
                $c_file_license = join('/', ['files', 'company',  $co_no, 'license']);
                Storage::disk('public')->delete($contract->c_file_license);
                $files['c_file_license'] = Storage::disk('public')->put($c_file_license, $validated['c_file_license']);
            }
            if (isset($validated['c_file_contract'])) {
                $c_file_contract = join('/', ['files', 'company',  $co_no, 'contract']);
                Storage::disk('public')->delete($contract->c_file_contract);
                $files['c_file_contract'] = Storage::disk('public')->put($c_file_contract, $validated['c_file_contract']);
            }
            if (isset($validated['c_file_bank_account'])) {
                $c_file_bank_account = join('/', ['files', 'company',  $co_no, 'bank_account']);
                Storage::disk('public')->delete($contract->c_file_bank_account);
                $files['c_file_bank_account'] = Storage::disk('public')->put($c_file_bank_account, $validated['c_file_bank_account']);
            }

            $update = [
                'c_start_date' => DateTime::createFromFormat('Y-m-d', $validated['c_start_date']),
                'c_end_date' => DateTime::createFromFormat('Y-m-d', $validated['c_end_date']),
                'c_transaction_yn' => $validated['c_transaction_yn'],

                'c_calculate_deadline_yn' => $validated['c_calculate_deadline_yn'],
                'c_integrated_calculate_yn' => $validated['c_integrated_calculate_yn'],
                // 'c_card_number' => $validated['c_card_number'],
                'c_deposit_day' => $validated['c_deposit_day'] ? $validated['c_deposit_day'] : "",
                'c_account_number' => $validated['c_account_number'],
                'c_deposit_price' => ($validated['c_deposit_price'] && $validated['c_deposit_price'] !='null') ? $validated['c_deposit_price']  : null,
                'c_deposit_date' =>  isset($validated['c_deposit_date']) && $validated['c_deposit_date'] != 'null'  ? DateTime::createFromFormat('Y-m-d', $validated['c_deposit_date']) : null,
                'c_deposit_return_price' => ($validated['c_deposit_return_price'] && $validated['c_deposit_return_price'] !='null') ? $validated['c_deposit_return_price']  : null,
                'c_deposit_return_date' => isset($validated['c_deposit_return_date']) && $validated['c_deposit_return_date'] != 'null' ? DateTime::createFromFormat('Y-m-d', $validated['c_deposit_return_date']) : null,
                'c_deposit_return_reg_date' =>  isset($validated['c_deposit_return_reg_date'])  && $validated['c_deposit_return_reg_date'] != 'null' ? DateTime::createFromFormat('Y-m-d', $validated['c_deposit_return_reg_date']) : null,
                'c_deposit_return_expiry_date' => isset($validated['c_deposit_return_expiry_date'])  && $validated['c_deposit_return_expiry_date'] != 'null' ? DateTime::createFromFormat('Y-m-d', $validated['c_deposit_return_expiry_date']) : null,
            ];

            $update = array_merge($update, $files);

            $contract = Contract::where(['co_no' => $co_no, 'c_no' => $contract->c_no])
                ->update($update);

            $company = Company::where('co_no', $co_no)->update([
                'co_service' => $validated['co_service'],
            ]);

            //UPDATE SERVICE FOR MEMBER
            $members = Member::where('co_no', $co_no)->get();
            

            
            foreach($members as $member){
                
                $member_service = $member['mb_service_no_array'];
                $service_arr = explode(" ", $member_service);

                foreach($service_arr as $service_){
                    if(!str_contains($validated['co_service'], $service_)) {
                        $member_service = str_replace($service_, "", $member_service);
                    }
                }

                Member::where('mb_no', $member->mb_no)->update(
                    [
                        'mb_service_no_array' => trim($member_service),
                    ]
                );
                
            }

            $i = 0;
           
            // $member = Member::where('co_no', $co_no)->get();
            
            
            // $array_services_co = explode(" ",$validated['co_service']);
            // foreach($member as $row){
            //     $services_member = '';
            //     foreach(explode(" ", $row['mb_service_no_array']) as $row2){
            //         if($row2 != '공통'){
            //             if(in_array($row2, $array_services_co)){
            //                 $services_member .= $row2.' ';
            //             }
            //         }
            //     }
            //     $services_member = rtrim($services_member, " ");
            //     if($services_member == '보세화물 수입풀필먼트 유통가공' && $validated['co_service'] == '보세화물 수입풀필먼트 유통가공'){
            //         $services_member = '공통 보세화물 수입풀필먼트 유통가공';
            //     }


            //     Member::where('co_no', $co_no)->where('mb_no',$row['mb_no'])->update([
            //         'mb_service_no_array' => $services_member,
            //     ]);
            // }

           
            foreach($validated['service_no'] as $service_no){

                CompanySettlement::updateOrCreate(
                    [
                        'co_no' => $co_no,
                        'service_no' => $validated['service_no'][$i],
                    ],
                    [
                        'cs_payment_cycle' => $validated['c_payment_cycle'][$i],
                        'cs_money_type' => $validated['c_money_type'][$i],
                        'cs_payment_group' => $validated['c_payment_group'][$i],
                        'cs_system' => $validated['c_system'][$i],
                    ]
                );
                $i++;
            }

            CompanyPayment::updateOrCreate(
                [
                    'co_no' => $co_no
                ],
                [
                    'cp_method' => isset($validated['cp_method']) ? $validated['cp_method'] : null,
                    'cp_virtual_account' => isset($validated['cp_virtual_account']) ? $validated['cp_virtual_account'] : null,
                    'cp_bank' => isset($validated['cp_bank']) ? $validated['cp_bank'] : null,
                    'cp_bank_number' => isset($validated['cp_bank_number']) ? $validated['cp_bank_number'] : null,
                    'cp_bank_name' => isset($validated['cp_bank_name']) ? $validated['cp_bank_name'] : null,
                    'cp_card_name' => isset($validated['cp_card_name']) ? $validated['cp_card_name'] : null,
                    'cp_card_number' => isset($validated['cp_card_number']) ? $validated['cp_card_number'] : null,
                    'cp_card_cvc' => isset($validated['cp_card_cvc']) ? $validated['cp_card_cvc'] : null,
                    'cp_valid_period' => isset($validated['cp_valid_period']) ? $validated['cp_valid_period'] : null,
                    'cp_cvc' => isset($validated['cp_cvc']) ? $validated['cp_cvc'] : null,
                ]
            );

            $rgds = ReceivingGoodsDelivery::with(['payment'])->whereHas('member', function($q) use ($co_no){
                $q->where('co_no', $co_no);
            })->whereHas('payment', function($q) {
                $q->where('p_method', 'deposit_without_bankbook');
            })->where('rgd_status6', 'deposit_without_bankbook')
            ->get();

            foreach($rgds as $index => $rgd){
                Payment::where('p_no', $rgd['payment']['p_no'])->update([
                    'p_method_number' => isset($validated['cp_bank_number']) ? $validated['cp_bank_number'] : null,
                    'p_card_name' => isset($validated['cp_card_name']) ? $validated['cp_card_name'] : null,
                    'p_method_name' => isset($validated['cp_bank_name']) ? $validated['cp_bank_name'] : null,
                ]);
            }

            // return $rgds;

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'contract' => $contract,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }
}

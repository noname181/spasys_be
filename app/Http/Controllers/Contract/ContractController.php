<?php

namespace App\Http\Controllers\Contract;

use DateTime;
use App\Http\Requests\Contract\ContractRegisterController\ContractRegisterRequest;
use App\Http\Requests\Contract\ContractUpdateController\ContractUpdateRequest;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Company;
use App\Models\CompanySettlement;
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

            $inl = Storage::disk('public')->put($c_file_insulance, $validated['c_file_insulance']);
            $lic = Storage::disk('public')->put($c_file_license, $validated['c_file_license']);
            $con = Storage::disk('public')->put($c_file_contract, $validated['c_file_contract']);
            $bc = Storage::disk('public')->put($c_file_bank_account, $validated['c_file_bank_account']);

            $c_no = Contract::insertGetId([
                'co_no' => $validated['co_no'],
                'mb_no' => Auth::user()->mb_no,
                'c_start_date' => DateTime::createFromFormat('Y-m-d', $validated['c_start_date']),
                'c_end_date' => DateTime::createFromFormat('Y-m-d', $validated['c_end_date']),
                'c_transaction_yn' => $validated['c_transaction_yn'],
                'c_calculate_deadline_yn' => $validated['c_calculate_deadline_yn'],
                'c_integrated_calculate_yn' => $validated['c_integrated_calculate_yn'],
                'c_calculate_method' => $validated['c_calculate_method'],
                // 'c_card_number' => $validated['c_card_number'],
                'c_deposit_day' => $validated['c_deposit_day'],
                // 'c_account_number' => $validated['c_account_number'],
                'c_deposit_price' => $validated['c_deposit_price'],
                'c_deposit_date' => DateTime::createFromFormat('Y-m-d', $validated['c_deposit_date']),
                'c_file_insulance' => $inl,
                'c_file_license' => $lic,
                'c_file_contract' => $con,
                'c_file_bank_account' => $bc,
                'c_deposit_return_price' => $validated['c_deposit_return_price'],
                'c_deposit_return_date' => DateTime::createFromFormat('Y-m-d', $validated['c_deposit_return_date']),
                'c_deposit_return_reg_date' => DateTime::createFromFormat('Y-m-d', $validated['c_deposit_return_reg_date']),
                'c_deposit_return_expiry_date' => DateTime::createFromFormat('Y-m-d', $validated['c_deposit_return_expiry_date']),
            ]);
            $company = Company::where('co_no', $co_no)->update(['co_service' => $validated['co_service']]);

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
            $co_service = Company::where('co_no', $co_no)->first()->co_service;
            $contract = Contract::where(['co_no' => $co_no])->first();
            $company_settlement = CompanySettleMent::where(['co_no' => $co_no])
            ->leftJoin('service', 'service.service_no', '=', 'company_settlement.service_no')
            ->get();

            $services = Service::where('service_use_yn', 'y')->where('service_no', '!=', 1)->get();
            return response()->json([
                'message' => Messages::MSG_0007,
                'contract' => $contract,
                'services' => $services,
                'co_service' => $co_service,
                'company_settlement' => $company_settlement
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
                'c_calculate_method' => $validated['c_calculate_method'],
                'c_card_number' => $validated['c_card_number'],
                'c_deposit_day' => $validated['c_deposit_day'] ? $validated['c_deposit_day'] : "",
                'c_account_number' => $validated['c_account_number'],
                'c_deposit_price' => $validated['c_deposit_price'],
                'c_deposit_date' =>  DateTime::createFromFormat('Y-m-d', $validated['c_deposit_date']),
                'c_deposit_return_price' => $validated['c_deposit_return_price'],
                'c_deposit_return_date' => DateTime::createFromFormat('Y-m-d', $validated['c_deposit_return_date']),
                'c_deposit_return_reg_date' =>  DateTime::createFromFormat('Y-m-d', $validated['c_deposit_return_reg_date']),
                'c_deposit_return_expiry_date' => DateTime::createFromFormat('Y-m-d', $validated['c_deposit_return_expiry_date']),
            ];

            $update = array_merge($update, $files);

            $contract = Contract::where(['co_no' => $co_no, 'c_no' => $contract->c_no])
                ->update($update);

            $company = Company::where('co_no', $co_no)->update([
                'co_service' => $validated['co_service'],
                'co_settlement_cycle' => $validated['c_payment_cycle'],
            ]);
            $i = 0;
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

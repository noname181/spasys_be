<?php

namespace App\Http\Controllers\Company;

use App\Http\Requests\Company\CompanyRegisterController\InvokeRequest;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contract;
use App\Utils\Messages;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyRegisterController extends Controller
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
                'co_name' => $validated['co_name'],
                'co_country' => $validated['co_country'],
                'co_service' => $validated['co_service'],
                'co_owner' => $validated['co_owner'],
                'co_license' => $validated['co_license'],
                'co_close_yn' => $validated['co_close_yn'],
                'co_homepage' => $validated['co_homepage'],
                'co_email' => $validated['co_email'],
                'co_etc' => $validated['co_etc']
            ]);

            $validated['co_no'] = $co_no;

            $c_file_insulance = join('/', ['files', 'contract', $co_no, 'insulance']);
            $c_file_license = join('/', ['files', 'contract',  $co_no, 'license']);
            $c_file_contract = join('/', ['files', 'contract',  $co_no, 'contract']);
            $c_file_bank_account = join('/', ['files', 'contract',  $co_no, 'bank_account']);

            $inl = Storage::disk('public')->put($c_file_insulance, $validated['c_file_insulance']);
            $lic = Storage::disk('public')->put($c_file_license, $validated['c_file_license']);
            $con = Storage::disk('public')->put($c_file_contract, $validated['c_file_contract']);
            $bc = Storage::disk('public')->put($c_file_bank_account, $validated['c_file_bank_account']);

            $c_no = Contract::insertGetId([
                'co_no' => $validated['co_no'],
                'c_start_date' => $validated['c_start_date'],
                'c_end_date' => $validated['c_end_date'],
                'c_transaction_yn' => $validated['c_transaction_yn'],
                'c_payment_cycle' => $validated['c_payment_cycle'],
                'c_money_type' => $validated['c_money_type'],
                'c_payment_group' => $validated['c_payment_group'],
                'c_calculate_deadline_yn' => $validated['c_calculate_deadline_yn'],
                'c_integrated_calculate_yn' => $validated['c_integrated_calculate_yn'],
                'c_calculate_method' => $validated['c_calculate_method'],
                'c_card_number' => $validated['c_card_number'],
                'c_deposit_day' => $validated['c_deposit_day'],
                'c_account_number' => $validated['c_account_number'],
                'c_deposit_price' => $validated['c_deposit_price'],
                'c_deposit_date' => $validated['c_deposit_date'],
                'c_file_insulance' => $inl,
                'c_file_license' => $lic,
                'c_file_contract' => $con,
                'c_file_bank_account' => $bc,
                'c_deposit_return_price' => $validated['c_deposit_return_price'],
                'c_deposit_return_date' => $validated['c_deposit_return_date'],
                'c_deposit_return_reg_date' => $validated['c_deposit_return_reg_date'],
                'c_deposit_return_expiry_date' => $validated['c_deposit_return_expiry_date']
            ]);

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'co_no' => $co_no,
                'c_no' => $c_no,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }
}

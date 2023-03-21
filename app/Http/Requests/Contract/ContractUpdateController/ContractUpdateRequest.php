<?php

namespace App\Http\Requests\Contract\ContractUpdateController;

use App\Http\Requests\BaseFormRequest;

class ContractUpdateRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'co_no' => [
                'required',
                'int',
            ],
            'co_service' => [
                'required',
                'string',
            ],
            'service_no' => [
                'required',
                'array',
            ],
            'c_payment_cycle' => [
                'required',
                'array',
            ],
            'c_money_type' => [
                'required',
                'array',
            ],
            'c_system' => [
                'required',
                'array',
            ],
            'c_payment_group' => [
                'required',
                'array',
            ],
            'c_start_date' => [
                'required',
                'date_format:Y-m-d'
            ],
            'c_end_date' => [
                'required',
                'date_format:Y-m-d'
            ],
            'c_transaction_yn' => [
                'required',
                'string',
                'max:1'
            ],
            'c_calculate_deadline_yn' => [
                'required',
                'string',
                'max:1'
            ],
            'c_integrated_calculate_yn' => [
                // 'nullable',
                // 'string',
                // 'max:1'
            ],
            'cp_method' => [
                // 'nullable',
                // 'string',
                // 'max:255'
            ],
            'c_deposit_day' => [
            ],
            'cp_virtual_account' => [
            ],
            'cp_bank' => [
            ],
            'cp_bank_number' => [
            ],
            'cp_bank_name' => [
            ],
            'cp_card_name' => [
            ],
            'cp_card_number' => [
            ],
            'cp_card_cvc' => [
            ],
            'cp_valid_period' => [
            ],
            'c_account_number' => [
                'max:255'
            ],
            'c_deposit_price' => [
            ],
            'c_deposit_date' => [
                '',
            ],
            'c_file_insulance' => [
                ''
                // 'max:2048',
                // 'mimes:pdf,png,jpg,jpeg'
            ],
            'c_file_license' => [
                ''
                // 'max:2048',
                // 'mimes:pdf,png,jpg,jpeg'
            ],
            'c_file_contract' => [
                ''
                // 'max:2048',
                // 'mimes:pdf,png,jpg,jpeg'
            ],
            'c_file_bank_account' => [
                ''
                // 'max:2048',
                // 'mimes:pdf,png,jpg,jpeg'
            ],
            'c_deposit_return_price' => [
                ''
            ],
            'c_deposit_return_date' => [
                ''
            ],
            'c_deposit_return_reg_date' => [
                ''
            ],
            'c_deposit_return_expiry_date' => [
                ''
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }
}

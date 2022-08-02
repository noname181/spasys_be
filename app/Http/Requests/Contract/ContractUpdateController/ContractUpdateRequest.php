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
    // public function authorize()
    // {
    //     return true;
    // }

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
            'c_start_date' => [
                'required',
                'date_format:n/j/Y' 
            ],
            'c_end_date' => [
                'required',
                'date_format:n/j/Y' 
            ],
            'c_transaction_yn' => [
                'required',
                'string',
                'max:1'
            ],
            'c_payment_cycle' => [
                'required',
                'string',
                'max:255'
            ],
            'c_money_type' => [
                'required',
                'string',
                'max:255'
            ],
            'c_payment_group' => [
                'required',
                'string',
                'max:255'
            ],
            'c_calculate_deadline_yn' => [
                'required',
                'string',
                'max:1'
            ],
            'c_integrated_calculate_yn' => [
                'required',
                'string',
                'max:1'
            ],
            'c_calculate_method' => [
                'required',
                'string',
                'max:255'
            ],
            'c_card_number' => [
                'required',
                'string',
                'max:255'
            ],
            'c_deposit_day' => [
                'required',
                'integer'
            ],
            'c_account_number' => [
                'required',
                'string',
                'max:255'
            ],
            'c_deposit_price' => [
                'required',
                'integer'
            ],
            'c_deposit_date' => [
                'required',
                'date'
            ],
            'c_file_insulance' => [
                'max:2048',
                'mimes:pdf,png,jpg,jpeg'
            ],
            'c_file_license' => [
                'max:2048',
                'mimes:pdf,png,jpg,jpeg'
            ],
            'c_file_contract' => [
                'max:2048',
                'mimes:pdf,png,jpg,jpeg'
            ],
            'c_file_bank_account' => [
                'max:2048',
                'mimes:pdf,png,jpg,jpeg'
            ],
            'c_deposit_return_price' => [
                'required',
                'integer'
            ],
            'c_deposit_return_date' => [
                'required',
                'date_format:n/j/Y' 
            ],
            'c_deposit_return_reg_date' => [
                'required',
                'date_format:n/j/Y' 
            ],
            'c_deposit_return_expiry_date' => [
                'required',
                'date_format:n/j/Y' 
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

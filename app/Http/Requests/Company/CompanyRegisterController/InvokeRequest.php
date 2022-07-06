<?php

namespace App\Http\Requests\Company\CompanyRegisterController;

use App\Http\Requests\BaseFormRequest;

class InvokeRequest extends BaseFormRequest
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
            'co_name' => [
                'required',
                'string',
                'max:255'
            ],
            'co_country' => [
                'required',
                'string',
                'max:255'
            ],
            'co_service' => [
                'required',
                'string',
                'max:255'
            ],
            'co_license' => [
                'required',
                'string',
                'max:255',
                'unique:company,co_license'
            ],
            'co_owner' => [
                'required',
                'string',
                'max:255'
            ],
            'co_close_yn' => [
                'required',
                'string',
                'max:1'
            ],
            'co_homepage' => [
                'required',
                'string',
                'max:255'
            ],
            'co_email' => [
                'required',
                'string',
                'max:255',
                'email',
                'unique:company,co_email'
            ],
            'co_etc' => [
                'required',
                'string',
            ],


            'c_start_date' => [
                'required',
                'date',
            ],
            'c_end_date' => [
                'required',
                'date',
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
                'required',
                'max:5000',
                'mimes:pdf,png,jpg,jpeg'
            ],
            'c_file_license' => [
                'required',
                'max:5000',
                'mimes:pdf,png,jpg,jpeg'
            ],
            'c_file_contract' => [
                'required',
                'max:5000',
                'mimes:pdf,png,jpg,jpeg'
            ],
            'c_file_bank_account' => [
                'required',
                'max:5000',
                'mimes:pdf,png,jpg,jpeg'
            ],
            'c_deposit_return_price' => [
                'required',
                'integer'
            ],
            'c_deposit_return_date' => [
                'required',
                'date'
            ],
            'c_deposit_return_reg_date' => [
                'required',
                'date'
            ],
            'c_deposit_return_expiry_date' => [
                'required',
                'date'
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

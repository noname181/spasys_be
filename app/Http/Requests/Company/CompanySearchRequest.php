<?php

namespace App\Http\Requests\Company;

use App\Http\Requests\BaseFormRequest;

class CompanySearchRequest extends BaseFormRequest
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
            'w_no' => [
                ''
            ],
            'from_date' => [
                'string',
                'date_format:Y-m-d'
            ],
            'to_date' => [
                'string',
                'date_format:Y-m-d'
            ],
            'co_no' => [
                '',
            ],
            'co_name' => [
                'string',
                'nullable',
            ],
            'co_name_shop' => [
                'string',
                'nullable',
            ],
            'co_name_shipper' => [
                'string',
                'nullable',
            ],
            'co_service' => [
                'string',
                'nullable',
            ],
            'per_page' => [
                'nullable',
                'int',
            ],
            'page' => [
                'nullable',
                'int',
            ],
            'c_payment_cycle' => [
                '',
            ],
            'c_calculate_method1' => [
                '',
            ],
            'c_calculate_method2' => [
                '',
            ],
            'c_calculate_method3' => [
                '',
            ],
            'c_calculate_method4' => [
                '',
            ],
            'c_transaction_yn' => [
                '',
            ],
            'co_close_yn' => [
                '',
            ],
            'c_calculate_deadline_yn' => [
                '',
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

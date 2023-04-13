<?php

namespace App\Http\Requests\Report;

use App\Http\Requests\BaseFormRequest;

class ReportSearchRequest extends BaseFormRequest
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
            'from_date' => [
                'string',
                'date_format:Y-m-d'
            ],
            'to_date' => [
                'string',
                'date_format:Y-m-d'
            ],
            'shop_name' => [
                'string',
                'nullable',
            ],
            'shipper_name' => [
                'nullable',
                'string',
            ],
            'service_name' => [
                'nullable',
                'string',
            ],
            'service_name1' => [
                'nullable',
                'string',
            ],
            'service_name2' => [
                'nullable',
                'string',
            ],
            'service_name3' => [
                'nullable',
                'string',
            ],
            'rp_cate' => [
                'nullable',
                'string',
            ],
            'rp_cate1' => [
                'nullable',
                'string',
            ],
            'rp_cate2' => [
                'nullable',
                'string',
            ],
            'rp_cate3' => [
                'nullable',
                'string',
            ],
            'rp_cate4' => [
                'nullable',
                'string',
            ],
            'page' => [
                'nullable',
                'int',
            ],
            'per_page' => [
                'nullable',
                'int',
            ],
            'per_page' => [
                'nullable',
                'int',
            ],
            'co_name' => [
                'nullable',
                'string',
            ],
            'co_parent_name' => [
                'nullable',
                'string',
            ],
            'w_schedule_number' => [
                'nullable',
                'string',
            ],
            'logistic_manage_number' => [
                'nullable',
                'string',
            ],
            'm_bl' => [
                'nullable',
                'string',
            ],
            'h_bl' => [
                'nullable',
                'string',
            ],
            'rgd_status1_1' => [
                '',
            ],
            'rgd_status1_2' => [
                '',
            ],
            'rgd_status1_3' => [
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

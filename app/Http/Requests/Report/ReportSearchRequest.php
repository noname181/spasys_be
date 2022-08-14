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
            'rp_cate' => [
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

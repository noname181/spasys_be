<?php

namespace App\Http\Requests\RateData;

use App\Http\Requests\BaseFormRequest;

class RateDataRequest extends BaseFormRequest
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
            'rate_data' => [
                'array',
            ],
            'rm_no' => [
                'int',
                'nullable'
            ],
            'storage_days' => [
                'int',
                'nullable'
            ],
            'rmd_no' => [
                'int',
                'nullable'
            ],
            'w_no' => [
                'int',
                'nullable'
            ],
            'rgd_no' => [
                'int',
                'nullable'
            ],
            'set_type' => [
                'string',
                'nullable'
            ],
            'type' => [
                'string',
                'nullable'
            ],
            'rd_co_no' => [
                'int',
                'nullable'
            ],
            'newRmd_no' => [
                'int',
                'nullable',
            ],
            'co_no' => [
                'int',
                'nullable'
            ],
            'rate_data.*.rd_no' => [
                'int',
                'nullable'
            ],
            'rate_data.*.rm_no' => [
                'int',
                'nullable'
            ],
            'rate_data.*.co_no' => [
                'int',
                'nullable'
            ],
            'rate_data.*.rd_cate_meta1' => [
                'required',
                'string',
                'max:255',
            ],
            'rate_data.*.rd_cate_meta2' => [
                'string',
                'max:255',
                'nullable'
            ],
            'rate_data.*.rd_cate1' => [
                'nullable',
                'string',
                'max:255',
            ],
            'rate_data.*.rd_cate2' => [
                'nullable',
                'string',
                'max:255',
            ],
            'rate_data.*.rd_cate3' => [
                'nullable',
                'string',
                'max:255',
            ],
            'rate_data.*.rd_data1' => [
                '',
            ],
            'rate_data.*.rd_data2' => [
                '',
            ],
            'rate_data.*.rd_data3' => [
                'nullable'
            ],
            'rate_data.*.rd_data8' => [
                'nullable'
            ],
            'rate_data.*.rd_data4' => [
                'nullable'
            ],
            'rate_data.*.rd_data5' => [
                'nullable'
            ],
            'rate_data.*.rd_data6' => [
                'nullable'
            ],
            'rate_data.*.rd_data7' => [
                'nullable'
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
        return [
        ];
    }
}

<?php

namespace App\Http\Requests\RateData;

use App\Http\Requests\BaseFormRequest;

class RateDataImportFulfillmentRequest extends BaseFormRequest
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
            'newRmd_no' => [
                'int',
                'nullable',
            ],
            'rm_no' => [
                'int',
                'nullable',
            ],
            'rate_data.*.rm_no' => [
                'int',
                'nullable',
                'exists:rate_meta,rm_no'
            ],
            'rate_data.*.co_no' => [
                'int',
                'nullable',
                'exists:company,co_no'
            ],
            'rate_data.*.rd_no' => [
                'int',
                'nullable'
            ],
            'rate_data.*.rd_cate_meta1' => [
                'required',
                'string',
                'max:255',
            ],
            'rate_data.*.rd_cate1' => [
                'required',
                'string',
                'max:255',
            ],
            'rate_data.*.rd_cate2' => [
                'required',
                'string',
                'max:255',
            ],
            'rate_data.*.rd_data1' => [
                'required',
                'string',
                'max:255',
            ],
            'rate_data.*.rd_data2' => [
                'required',
                'string',
                'max:255',
            ],
            'rate_data.*.rd_data3' => [
                'required',
                'string',
                'max:255',
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

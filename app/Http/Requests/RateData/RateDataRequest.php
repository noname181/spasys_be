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
            '*.rd_no' => [
                'int',
                'nullable'
            ],
            '*.rm_no' => [
                'required',
                'integer',
            ],
            '*.rd_cate_meta1' => [
                'required',
                'string',
                'max:255',
            ],
            '*.rd_cate_meta2' => [
                'required',
                'string',
                'max:255',
            ],
            '*.rd_cate1' => [
                'required',
                'string',
                'max:255',
            ],
            '*.rd_cate2' => [
                'required',
                'string',
                'max:255',
            ],
            '*.rd_cate3' => [
                'required',
                'string',
                'max:255',
            ],
            '*.rd_data1' => [
                'required',
                'string',
                'max:255',
            ],
            '*.rd_data2' => [
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
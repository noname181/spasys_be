<?php

namespace App\Http\Requests\RateMeta;

use App\Http\Requests\BaseFormRequest;

class RateMetaRequest extends BaseFormRequest
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
            'rm_biz_name' => [
                'required',
                'string',
                'max:255',
            ],
            'rm_owner_name' => [
                'required',
                'string',
                'max:255',
            ],
            'rm_biz_number' => [
                'required',
                'string',
                'max:255',
            ],
            'rm_biz_address' => [
                'required',
                'string',
                'max:255',
            ],
            // 'rm_biz_address_detail' => [
            //     'required',
            //     'string',
            //     'max:255',
            // ],
            'rm_biz_email' => [
                'required',
                'string',
                'max:255',
                'email',
            ],
            'rm_name' => [
                'required',
                'string',
                'max:255',
            ],
            'rm_hp' => [
                'required',
                'string',
                'max:255',
                'regex:/[0-9]{3}-[0-9]{4}-[0-9]{4}$/',
            ]
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

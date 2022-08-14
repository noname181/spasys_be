<?php

namespace App\Http\Requests\RateDataSendMeta;

use App\Http\Requests\BaseFormRequest;

class RateDataSendMetaRequest extends BaseFormRequest
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
            'rdsm_biz_name' => [
                'required',
                'string',
                'max:255',
            ],
            'rdsm_owner_name' => [
                'required',
                'string',
                'max:255',
            ],
            'rdsm_biz_number' => [
                'required',
                'string',
                'max:255',
            ],
            'rdsm_biz_address' => [
                'required',
                'string',
                'max:255',
            ],
            'rdsm_biz_address_detail' => [
                'required',
                'string',
                'max:255',
            ],
            'rdsm_biz_email' => [
                'required',
                'string',
                'max:255',
                'email',
            ],
            'rdsm_name' => [
                'required',
                'string',
                'max:255',
            ],
            'rdsm_hp' => [
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

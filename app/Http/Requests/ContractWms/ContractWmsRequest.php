<?php

namespace App\Http\Requests\ContractWms;

use App\Http\Requests\BaseFormRequest;

class ContractWmsRequest extends BaseFormRequest
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
            'co_no' => [
                '',
            ],
            'mb_no' => [
                '',
            ],
            'cw_name' => [
                '',
            ],
            'cw_code' => [
                '',
            ],
            'cw_tab' => [
                '',
            ],
            'ss_no' => [
                'int',
                'nullable',
            ],
            'contract_wms_tab1' => [
                'array',
                'nullable'
            ],
            'contract_wms_tab2' => [
                'array',
                'nullable'
            ],
            'contract_wms_tab1.*.cw_name' => [
                'string',
                'nullable',
                'max:255',
            ],
            'contract_wms_tab1.*.cw_code' => [
                'string',
                'nullable',
                'max:255',
            ],

            'contract_wms_tab2.*.cw_code' => [
                'string',
                'nullable',
                'max:255',
            ],
            'contract_wms_tab2.*.cw_name' => [
                'string',
                'nullable',
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
        return [];
    }
}

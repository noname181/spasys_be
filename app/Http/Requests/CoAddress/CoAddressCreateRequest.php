<?php

namespace App\Http\Requests\CoAddress;

use App\Http\Requests\BaseFormRequest;

class CoAddressCreateRequest extends BaseFormRequest
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
            '*.co_no' => [
                'integer',
            ],
            '*.ca_name' => [
                'required',
                'string',
                'max:255',
            ],
            '*.ca_manager' => [
                'required',
                'string',
                'max:255',
            ],
            '*.ca_hp' => [
                'required',
                'string',
                'max:255',
            ],
            '*.ca_address' => [
                'required',
                'string',
                'max:255',
            ],
            '*.ca_address_detail' => [
                'required',
                'string',
                'max:255',
            ],
            '*.ca_no' => [
                'integer',
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

<?php

namespace App\Http\Requests\Company\CompanyRegisterController;

use App\Http\Requests\BaseFormRequest;

class InvokeRequest extends BaseFormRequest
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
            'co_name' => [
                'required',
                'string',
                'max:255',
            ],
            'co_address' => [
                'required',
                'string',
                'max:255',
            ],
            'co_zipcode' => [
                'nullable',
                'string',
                'max:255',
            ],
            'co_address_detail' => [
                'required',
                'string',
                'max:255',
            ],
            'co_country' => [
                'string',
                'max:255'
            ],
            'co_major' => [
                'required',
                'string',
                'max:255'
            ],
            'co_license' => [
                'required',
                'string',
                'max:255'
            ],
            'co_owner' => [
                'required',
                'string',
                'max:255'
            ],
            // FIXME no found
            'co_close_yn' => [
                'required',
                'string',
                'max:1'
            ],
            'co_homepage' => [
                'required',
                'string',
                'max:255'
            ],
            'co_email' => [
                'required',
                'string',
                'max:255',
                'email',
            ],
            'co_etc' => [
                'required',
                'string',
            ],
            'co_address' => [
                'required',
                'string',
                'max:255',
            ],
            'co_address_detail' => [
                'required',
                'string',
                'max:255',
            ],
            'co_tel' => [
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

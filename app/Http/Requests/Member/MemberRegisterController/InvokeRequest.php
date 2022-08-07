<?php

namespace App\Http\Requests\Member\MemberRegisterController;

use App\Http\Requests\BaseFormRequest;

class InvokeRequest extends BaseFormRequest
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
            'mb_id' => [
                'required',
                'string',
                'max:20',
                'min:4',
                'regex:/^[a-zA-Z]{1,}([0-9]*)?$/',
                'unique:member,mb_id'
            ],
            'co_no' => [
                'required',
                'integer',
            ],
            // FIXME hard set role_no = 1
            // 'role_no' => [
            //     'required',
            //     'integer',
            //     'exists:role,role_no'
            // ],
            'role_no' => [
                'required',
                'string',
            ],
            'mb_name' => [
                'required',
                'string',
                'max:20',
                'min:4',
            ],
            'mb_email' => [
                'required',
                'string',
                'max:255',
                'email',
                'unique:member,mb_email'
            ],
            'mb_tel' => [
                'string',
                'max:255',
                'regex:/^\(\+[0-9]{2}\) [0-9]{2}-[0-9]{4}-[0-9]{4}$/'
            ],
            'mb_pw' => [
                'required',
                'string',
                'min:4',
            ],
            // FIXME hard set mb_language = ko
            // 'mb_language' => [
            //     'required',
            //     'string',
            //     'max:255',
            // ],
            'mb_hp' => [
                'required',
                'string',
                'max:255',
                'regex:/[0-9]{3}-[0-9]{4}-[0-9]{4}$/',
                'unique:member,mb_hp'
            ],
            'mb_use_yn' => [
                'required',
                'string',
                'max:1'
            ],
            'mb_push_yn' => [
                'required',
                'string',
                'max:1'
            ],
            'mb_service_no_array' => [
                'required',
                'string'
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

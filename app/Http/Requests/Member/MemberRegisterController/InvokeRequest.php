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
                'max:255',
                'unique:member,mb_id'
            ],
            'role_no' => [
                'required',
                'integer',
                'exists:role,role_no'
            ],
            'mb_name' => [
                'required',
                'string',
                'max:255',
            ],
            'mb_email' => [
                'required',
                'string',
                'max:255',
                'email',
                'unique:member,mb_email'
            ],
            'mb_pw' => [
                'required',
                'string',
                'max:255',
            ],
            'mb_language' => [
                'required',
                'string',
                'max:255',
            ],
            'mb_hp' => [
                'required',
                'string',
                'max:255',
                'unique:member,mb_hp'
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

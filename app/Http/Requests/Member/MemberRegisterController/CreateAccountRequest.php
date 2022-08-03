<?php

namespace App\Http\Requests\Member\MemberRegisterController;

use App\Http\Requests\BaseFormRequest;

class CreateAccountRequest extends BaseFormRequest
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
            'mb_name' => [
                'required',
                'string',
                'max:20',
                'min:4',
            ],
            'mb_pw' => [
                'required',
                'string',
                'min:4',
            ],
            'mb_note' => [
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

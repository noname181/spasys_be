<?php

namespace App\Http\Requests\Member;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Auth;

class MemberSpasysUpdateRequest extends BaseFormRequest
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
            'mb_id' => [
                'required',
                'string',
                'max:20',
                'min:4',
                'regex:/^[a-zA-Z]{1,}([0-9]*)?$/'
            ],
            'mb_name' => [
                'required',
                'string',
                'max:20',
                'min:4',
            ],
            'mb_pw' => [
                'string',
                'min:4',
            ],
            'mb_note' => [
                'required',
                'string',
                'max:255',
            ],
            'mb_tel' => [
                'required',
                'string',
                'max:255',
            ],
            'co_operating_time' => [
                'required',
                'string',
                'max:255',
            ],
            'co_lunch_break' => [
                'required',
                'string',
                'max:255',
            ],
            'co_email' => [
                'required',
                'string',
                'max:255',
                'regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,8}$/ix',
                
            ],
            'co_about_us' => [
                'required',
                'string',
                'max:255',
                'regex:/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/',
            ],
            'co_help_center' => [
                'required',
                'string',
                'max:255',
                'regex:/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/',
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

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
                '',
                // 'string',
                // 'max:20',
                // 'min:4',
                // 'regex:/^[a-zA-Z]{1,}([0-9]*)?$/'
            ],
            'mb_name' => [
                'required',
                'string',
                'max:20',
                'min:2',
            ],
            'co_owner' => [
                'required',
                'string',
                'max:20',
                'min:2',
            ],
            'mb_pw' => [
                '',
            ],
            'cp_bank_number' => [
                '',
            ],
            'mb_note' => [
                'required',
                'string',
                'max:255',
            ],
            'mb_tel' => [
                '',
            ],
            'co_operating_time' => [
                '',
            ],
            'co_lunch_break' => [
                '',
            ],
            'co_email' => [
                ''
                //'required',
                //'string',
               // 'max:255',
                //'regex:/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/',
                
            ],
            'co_about_us' => [
                ''
                //'required',
                //'string',
                //'max:255',
                //'regex:/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/',
            ],
            'co_policy' => [
                'nullable',
                'string'
            ],
            'co_help_center' => [
                ''
                //'required',
                //'string',
                //'max:255',
                //'regex:/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/',
            ],
            'warehouse_code' => [
                ''
            ],
            'co_address' => [
                ''
            ],
            'co_address_detail' => [
                ''
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

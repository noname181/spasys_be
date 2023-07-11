<?php

namespace App\Http\Requests\SendEmail;

use App\Http\Requests\BaseFormRequest;

class SendEmailRegisterRequest extends BaseFormRequest
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
            'se_content' => [
                'required',
                'string',
            ],
            'se_title' => [
                'required',
                'string',
                'max:255'
            ],
            'se_name_receiver' => [
                'required',
                'string',
                'max:255'
            ],
            'se_email_receiver' => [
                'required',
                'string',
                'max:255'
            ],
            'se_email_cc' => [
                'string',
                'nullable'
            ],
            'mb_no' => [
                ''
            ],
            'rm_no' => [
                'int',
                'nullable'
            ],
            'rmd_number' => [
                'string',
                'nullable'
            ],
            'rmd_no' => [
                '',
                'nullable'
            ],
            'rmd_service' => [
                '',
                'nullable'
            ],
            'rmd_tab_child'=>[
                '',
                'nullable'
            ],
            'co_no_services'=>[
                '',
                'nullable'
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
        return [];
    }
}

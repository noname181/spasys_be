<?php

namespace App\Http\Requests\SendMail;

use App\Http\Requests\BaseFormRequest;

class ValidateOtpRequest extends BaseFormRequest
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
            'mb_no' => [
                'required',
                'integer',
            ],
            'mb_otp' => [
                'required',
                'string',
                'max:4',
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

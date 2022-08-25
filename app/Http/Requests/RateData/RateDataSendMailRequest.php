<?php

namespace App\Http\Requests\RateData;

use App\Http\Requests\BaseFormRequest;

class RateDataSendMailRequest extends BaseFormRequest
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
            'sender_name' => [
                'required',
                'string',
                'max:255',
            ],
            'recipient_mail' => [
                'required',
                'string',
                'max:500',
            ],
            'cc' => [
                'nullable',
                'array',
            ],
            'cc.*' => [
                'email'
            ],
            'subject' => [
                'required',
                'string',
                'max:78',
            ],
            'content' => [
                'required',
                'string',
            ],
            'files' => [
                'required',
                'array',
            ],
            'files.*' => [
                'file',
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

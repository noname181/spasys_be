<?php

namespace App\Http\Requests\Service;

use App\Http\Requests\BaseFormRequest;

class ServiceRequest extends BaseFormRequest
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
            'services' => [
                'array',
            ],
            'services.*.service_no' => [
                'int',
                'nullable'
            ],
            'services.*.service_name' => [
                'required',
                'max:255'
            ],
            'services.*.service_eng' => [
                'required',
                'max:255'
            ],
            'services.*.service_use_yn' => [
                'required',
                'string',
                'max:1'
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

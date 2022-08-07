<?php

namespace App\Http\Requests\ForwarderInfo;

use App\Http\Requests\BaseFormRequest;

class ForwarderInfoCreateRequest extends BaseFormRequest
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
            '*.co_no' => [
                'integer',
            ],
            '*.fi_name' => [
                'required',
                'string',
                'max:255',
            ],
            '*.fi_manager' => [
                'required',
                'string',
                'max:255',
            ],
            '*.fi_hp' => [
                'required',
                'string',
                'max:255',
            ],
            '*.fi_address' => [
                'required',
                'string',
                'max:255',
            ],
            '*.fi_address_detail' => [
                'required',
                'string',
                'max:255',
            ],
            '*.fi_no' => [
                'integer',
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

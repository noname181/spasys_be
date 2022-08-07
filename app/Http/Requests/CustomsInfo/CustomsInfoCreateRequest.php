<?php

namespace App\Http\Requests\CustomsInfo;

use App\Http\Requests\BaseFormRequest;

class CustomsInfoCreateRequest extends BaseFormRequest
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
            '*.ci_name' => [
                'required',
                'string',
                'max:255',
            ],
            '*.ci_manager' => [
                'required',
                'string',
                'max:255',
            ],
            '*.ci_hp' => [
                'required',
                'string',
                'max:255',
            ],
            '*.ci_address' => [
                'required',
                'string',
                'max:255',
            ],
            '*.ci_address_detail' => [
                'required',
                'string',
                'max:255',
            ],
            '*.ci_no' => [
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

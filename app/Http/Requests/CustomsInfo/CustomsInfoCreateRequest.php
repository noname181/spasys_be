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
                '',
            ],
            '*.ci_manager' => [
                '',
            ],
            '*.ci_hp' => [
                '',
            ],
            '*.ci_address' => [
                '',
            ],
            '*.ci_address_detail' => [
                '',
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

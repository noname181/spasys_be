<?php

namespace App\Http\Requests\WarehousingStatus;

use App\Http\Requests\BaseFormRequest;

class WarehousingStatusRequest extends BaseFormRequest
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
            'per_page' => [
                'nullable',
                'int',
            ],
            'page' => [
                'nullable',
                'int',
            ],
            'type_page' => [
                '',
            ],
            'page_type' => [
                '',
            ],
            'type' => [
                ''
            ],
            'w_no' => [
                '',
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

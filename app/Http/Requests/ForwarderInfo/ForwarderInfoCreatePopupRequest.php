<?php

namespace App\Http\Requests\ForwarderInfo;

use App\Http\Requests\BaseFormRequest;

class ForwarderInfoCreatePopupRequest extends BaseFormRequest
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
            'co_no' => [
                'integer',
            ],
            'fi_name' => [
                '',
            ],
            'fi_manager' => [
                '',
            ],
            'fi_hp' => [
                '',
            ],
            'fi_address' => [
                '',
            ],
            'fi_address_detail' => [
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

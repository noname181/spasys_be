<?php

namespace App\Http\Requests\Adjustment;

use App\Http\Requests\BaseFormRequest;

class AdjustmentGroupCreatePopupRequest extends BaseFormRequest
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
            'ag_name' => [
                'required',
                'string',
                'max:255',
            ],
            'ag_manager' => [
                '',
            ],
            'ag_hp' => [
                '',
            ],
            'ag_email' => [
                'required',
                'string',
                'max:255',
                'email',
            ],
            'ag_email2' => [
                'nullable',
                'string',
                'max:255',
                'email',
            ],
            'ag_auto_issue' => [
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

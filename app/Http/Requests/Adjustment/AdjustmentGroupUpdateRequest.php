<?php

namespace App\Http\Requests\Adjustment;

use App\Http\Requests\BaseFormRequest;

class AdjustmentGroupUpdateRequest extends BaseFormRequest
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
                'required',
                'integer',
            ],
            'mb_no' => [
                'required',
                'integer',
            ],
            'ag_name' => [
                'required',
                'string',
                'max:255',
            ],
            'ag_manager' => [
                'required',
                'string',
                'max:255',
            ],
            'ag_hp' => [
                'required',
                'string',
                'max:255',
            ],
            'ag_email' => [
                'required',
                'string',
                'max:255',
                'email',
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

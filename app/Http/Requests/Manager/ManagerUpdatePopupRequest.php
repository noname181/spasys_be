<?php

namespace App\Http\Requests\Manager;

use App\Http\Requests\BaseFormRequest;

class ManagerUpdatePopupRequest extends BaseFormRequest
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
            'm_no' => [
                'required',
                'int'
            ],
            'm_position' => [
                '',
            ],
            'm_name' => [
                'required',
                'string',
                'max:255',
            ],
            'm_duty1' => [
                '',
            ],
            'm_duty2' => [
                '',
            ],            
            'm_hp' => [
                '',
                //'regex:/[0-9]{3}-[0-9]{4}-[0-9]{4}$/',
            ],
            'm_etc' => [
                'string',
                'nullable',
            ],
            'm_email' => [
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

<?php

namespace App\Http\Requests\Manager;

use App\Http\Requests\BaseFormRequest;

class ManagerCreateRequest extends BaseFormRequest
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
            '*.m_no' => [
                'integer',
            ],
            '*.co_no' => [
                'integer',
            ],
            '*.m_position' => [
                '',
            ],
            '*.m_name' => [
                '',
            ],
            '*.m_duty1' => [
                '',
            ],
            '*.m_duty2' => [
                '',
            ],            
            '*.m_hp' => [
                'string',
                'nullable',
                'max:255',
                //'regex:/[0-9]{3}-[0-9]{4}-[0-9]{4}$/',
            ],
            '*.m_etc' => [
                'string',
                'nullable',
            ],
            '*.m_email' => [
                'string',
                'nullable',
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

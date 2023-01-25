<?php

namespace App\Http\Requests\ImportSchedule;

use App\Http\Requests\BaseFormRequest;

class ImportScheduleSearchRequest extends BaseFormRequest
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
            'from_date' => [
                'string',
                'date_format:Y-m-d',
            ],
            'to_date' => [
                'string',
                'date_format:Y-m-d'
            ],
            'co_parent_name' => [
                'nullable',
                'string',
            ],
            'co_name' => [
                'nullable',
                'string',
            ],
            'm_bl' => [
                'nullable',
                'string',
            ],
            'h_bl' => [
                'nullable',
                'string',
            ],
            'logistic_manage_number' => [
                'nullable',
                'string',
            ],
            'per_page' => [
                'nullable',
                'int',
            ],
            'page' => [
                'nullable',
                'int',
            ],
            'tie_status' => [
                '',
            ],
            'tie_status_2' => [
                '',
            ],
            'ti_status' => [
                'nullable',
            ],
            'status' => [
                '',
            ],
            'connection' => [
                ''
            ]
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

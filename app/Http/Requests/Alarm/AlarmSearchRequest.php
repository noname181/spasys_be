<?php

namespace App\Http\Requests\Alarm;

use App\Http\Requests\BaseFormRequest;

class AlarmSearchRequest extends BaseFormRequest
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
            'page_type' => [
                'nullable',
            ],
            'hbl' => [
                'nullable',
            ],
            'from_date' => [
                'string',
                'date_format:Y-m-d',
            ],
            'to_date' => [
                'string',
                'date_format:Y-m-d'
            ],
            'co_name' => [
                'nullable',
                'string',
            ],
            'co_parent_name' => [
                'nullable',
                'string',
            ],
            'w_no' => [
                'nullable',
            ],
            'h_bl' => [
                'nullable',
            ],
            'page_type' => [
                'nullable',
            ],
            'service' => [
                'nullable',
            ],
            'service_name' => [
                'nullable',
            ],
            'alarm_type' => [
                'nullable',
                'string',
            ],
            'sender' => [
                'nullable',
                'string',
            ],
            'w_schedule_number'=> [
                'nullable',
            ],
            'home'=> [
                'nullable',
            ],
            'mb_push_yn' => [
                'nullable',
                'string',
                'max:1'
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

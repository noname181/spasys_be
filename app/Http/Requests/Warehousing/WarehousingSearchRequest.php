<?php

namespace App\Http\Requests\Warehousing;

use App\Http\Requests\BaseFormRequest;

class WarehousingSearchRequest extends BaseFormRequest
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
            'mb_name' => [
                'nullable',
                'string',
            ],
            'co_name' => [
                'nullable',
                'string',
            ],
            'w_schedule_number' => [
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
            'rgd_status1' => [
                'nullable',
                'string',
            ],
            'rgd_status1_1' => [
                'nullable',
                'string',
            ],
            'rgd_status1_2' => [
                'nullable',
                'string',
            ],
            'rgd_status1_3' => [
                'nullable',
                'string',
            ],
            'rgd_status2' => [
                'nullable',
                'string',
            ],
            'rgd_status2_1' => [
                'nullable',
                'string',
            ],
            'rgd_status2_2' => [
                'nullable',
                'string',
            ],
            'rgd_status2_3' => [
                'nullable',
                'string',
            ],
            'rgd_status3' => [
                'nullable',
                'string',
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

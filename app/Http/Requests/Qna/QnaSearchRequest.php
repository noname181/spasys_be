<?php

namespace App\Http\Requests\Qna;

use App\Http\Requests\BaseFormRequest;

class QnaSearchRequest extends BaseFormRequest
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
                'date_format:Y-m-d'
            ],
            'to_date' => [
                'string',
                'date_format:Y-m-d'
            ],
            'search_string' => [
                '',
            ],
            'qna_title' => [
                '',
            ],
            'qna_content' => [
                '',
            ],
            'qna_status1' => [
                '',
            ],
            'qna_status2' => [
                '',
            ],
            'qna_status3' => [
                '',
            ],
            'per_page' => [
                'nullable',
                'int',
            ],
            'page' => [
                'nullable',
                'int',
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

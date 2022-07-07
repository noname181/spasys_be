<?php

namespace App\Http\Requests\Qna;

use App\Http\Requests\BaseFormRequest;

class QnaRegisterRequest extends BaseFormRequest
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
            'mb_no' => [
                'required',
                'integer',
                'max:255',
                'exists:member,mb_no'
            ],
            'qna_title' => [
                'required',
                'string',
                'max:255',
            ],
            'qna_content' => [
                'required',
                'string',
            ],
            'mb_no_target' => [
                'required',
                'string',
                'max:255',
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

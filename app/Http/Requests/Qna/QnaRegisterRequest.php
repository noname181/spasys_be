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
            // FIXME hard set mb_no = 1 and mb_no_target = 1
            // 'mb_no' => [
            //     'required',
            //     'integer',
            //     'max:255',
            //     'exists:member,mb_no'
            // ],
            'mb_no_target' => [
                '',
            ],
            'qna_title' => [
                'required',
                'string',
                'max:255',
                'min:7',
            ],
            'qna_content' => [
                'required',
                'string',
                'min:7',
            ],
            'qna_status' => [

            ],
            'files' => [
                'array',
                // 'required',
            ],
            'files.*' => [
                'file',
                'max:5119'
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

<?php

namespace App\Http\Requests\Notice;

use App\Http\Requests\BaseFormRequest;

class NoticeCreateRequest extends BaseFormRequest
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
            'notice_title' => [
                'required',
                'string',
                'max:255',
            ],
            'notice_content' => [
                'required',
                'string',
                'max:255',
            ],
            'notice_target' => [
                'required',
                'string',
                'max:255',
            ],
            'files' => [
                'array',
                'required',
            ],
            'files.*' => [
                'file',
                'max:5000',
                'mimes:jpg,jpeg,png,pdf',
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

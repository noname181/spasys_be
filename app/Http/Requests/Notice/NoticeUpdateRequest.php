<?php

namespace App\Http\Requests\Notice;

use App\Http\Requests\BaseFormRequest;

class NoticeUpdateRequest extends BaseFormRequest
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
            'notice_no' => [
                'required',
                'integer',
            ],
            'notice_content' => [
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

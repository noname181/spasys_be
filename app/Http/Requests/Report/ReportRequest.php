<?php

namespace App\Http\Requests\Report;

use App\Http\Requests\BaseFormRequest;

class ReportRequest extends BaseFormRequest
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
            'reports' => [
                'array',
            ],
            'reports.*.rp_content' => [
                'string',
                'required',
                'max:300'
            ],
            // 'reports.*.files' => [
            //     'array',
            //     'required',
            // ],
            // 'reports.*.files.*' => [
            //     'file',
            //     'max:5000',
            //     'mimes:jpg,jpeg,png,pdf',
            // ],
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

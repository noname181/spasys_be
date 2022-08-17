<?php

namespace App\Http\Requests\WarehousingRequest;

use App\Http\Requests\BaseFormRequest;

class WarehousingRequestRegisterRequest extends BaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'wr_type' => [
                'required',
                'string',
                'max:255',
            ],
            'wr_contents' => [
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

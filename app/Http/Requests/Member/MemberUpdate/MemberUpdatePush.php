<?php

namespace App\Http\Requests\Member\MemberUpdate;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MemberUpdatePush extends BaseFormRequest
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
            'mb_push_yn' => [
                'required',
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
        return [
        ];
    }

    public function messages()
    {
        return [
            'mb_email.unique' => '이미 존재하는 이메일주소입니다.',
        ];
    }
}

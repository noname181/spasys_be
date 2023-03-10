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
            'connection_number_type' => [
                ''
            ],
            'connection_number' => [
                ''
            ],
            'service' => [
                ''
            ],
            'status' => [
                ''
            ],
            'settlement_cycle' => [
                ''
            ],
            'order_id' => [
                ''
            ],
            'page_type' => [
                ''
            ],
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
            'co_parent_name' => [
                'nullable',
                'string',
            ],
            'co_no' => [
                'nullable',
                'int',
            ],
            'w_schedule_number' => [
                '',
            ],
            'w_schedule_number_iw' => [
                '',
            ],
            'w_schedule_number_ew' => [
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
            'rgd_status1' => [
                'nullable',
                'string',
            ],
            'rgd_status1_1' => [
                'nullable',
            ],
            'rgd_status1_2' => [
                'nullable',
            ],
            'rgd_status1_3' => [
                'nullable',
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
            'rgd_status3_1' => [
                'nullable',
                'string',
            ],
            'rgd_status3_2' => [
                'nullable',
                'string',
            ],
            'rgd_status3_3' => [
                'nullable',
                'string',
            ],
            'w_type' => [
                'nullable',
                'string',
            ],
            'm_bl' => [
                '',
            ],
            'h_bl' => [
                '',
            ],
            'logistic_manage_number' => [
                '',
            ],
            'item_bar_code' => [
                '',
            ],
            'item_brand' => [
                '',
            ],
            'item_cargo_bar_code' => [
                '',
            ],
            'item_upc_code' => [
                '',
            ],
            'service_korean_name' => [
                '',
            ],
            'settlement_cycle' => [
                '',
            ],
            'settlement_cycle1' => [
                '',
            ],
            'settlement_cycle2' => [
                '',
            ],
            'w_schedule_number2' => [
                '',
            ],
            'carrier' => [
                '',
            ],
            'rgd_status5' => [
                '',
            ],
            'rgd_receiver' => [
                '',
            ],
            'rgd_contents' => [
                '',
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

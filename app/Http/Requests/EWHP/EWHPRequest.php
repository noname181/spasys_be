<?php

namespace App\Http\Requests\EWHP;

use App\Http\Requests\BaseFormRequest;

class EWHPRequest extends BaseFormRequest
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
    public static function rules()
    {
        return [
            'import' => [
                'array',
                'nullable'
            ],
            "import.*.ti_status" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_logistic_manage_number" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_carry_in_number" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_register_id" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_i_date" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_i_time" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_i_report_type" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_i_division_type" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_i_confirm_number" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_i_order" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_i_type" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_m_bl" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_h_bl" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_i_report_number" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_i_packing_type" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_i_number" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_i_weight" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_i_weight_unit" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_co_license" => [
                'nullable',
                'max:255',
            ],
            "import.*.ti_logistic_type" => [
                'nullable',
                'max:255',
            ],

            "export" => [
                'array',
                'nullable'
            ],
            "export.*.te_status" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_logistic_manage_number" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_e_confirm_number" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_e_confirm_type" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_e_confirm_date" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_carry_in_number" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_carry_out_number" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_register_id" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_e_date" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_e_time" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_e_order" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_m_bl" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_h_bl" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_e_division_type" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_e_packing_type" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_e_number" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_e_weight" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_e_weight_unit" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_e_type" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_e_do_number" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_e_price" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_co_license" => [
                'nullable',
                'max:255',
            ],
            "export.*.te_logistic_type" => [
                'nullable',
                'max:255',
            ],

            "import_expected" => [
                'array',
                'nullable'
            ],

            'import_expected.*.tie_status' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.tie_logistic_manage_number' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.tie_register_id' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.tie_is_date' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.tie_is_ship' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.tie_co_license' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.tie_is_cargo_eng' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.tie_is_number' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.tie_is_weight' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.tie_is_weight_unit' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.tie_m_bl' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.tie_h_bl' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.tie_is_name_eng' => [
                'nullable',
                'max:255',
            ],
            
            "export_confirm" => [
                'array',
                'nullable'
            ],

            "export_confirm.*.tec_status" => [
                'nullable',
                'max:255',
            ],
            "export_confirm.*.tec_logistic_manage_number" => [
                'nullable',
                'max:255',
            ],
            "export_confirm.*.tec_ec_confirm_number" => [
                'nullable',
                'max:255',
            ],
            "export_confirm.*.tec_ec_type" => [
                'nullable',
                'max:255',
            ],
            "export_confirm.*.tec_ec_date" => [
                'nullable',
                'max:255',
            ],
            "export_confirm.*.tec_register_id" => [
                'nullable',
                'max:255',
            ],
            "export_confirm.*.tec_ec_number" => [
                'nullable',
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    use HasFactory;

    protected $table = "t_import";

    protected $primaryKey = 'ti_no';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
         'ti_no',
         "ti_status",
         "ti_logistic_manage_number",
         "ti_carry_in_number",
         "ti_register_id", 
         "ti_i_date",
         "ti_i_time",
         "ti_i_report_type", 
         "ti_i_division_type",
         "ti_i_confirm_number",
         "ti_i_order",
         "ti_i_type",
         "ti_m_bl",
         "ti_h_bl",
         "ti_i_report_number",
         "ti_i_packing_type",
         "ti_i_number",
         "ti_i_weight",
         "ti_i_weight_unit",
         "ti_co_license",
         "ti_logistic_type",
         'created_at',
         'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
        'ti_is_date' => "date:Y.m.d",
    ];

}
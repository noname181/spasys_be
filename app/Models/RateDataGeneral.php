<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateDataGeneral extends Model
{
    use HasFactory;

    protected $table = "rate_data_general";


    protected $primaryKey = 'rdg_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'rdg_no ',
        'w_no',
        'rgd_no',
        'rgd_no_expectation',
        'rgd_no_final',
        'rgd_no_additional',
        'mb_no',
        'rdg_set_type',
        'rdg_bill_type',
        'rdg_supply_price1',
        'rdg_supply_price2',
        'rdg_supply_price3',
        'rdg_vat1',
        'rdg_vat2',
        'rdg_vat3',
        'rdg_sum1',
        'rdg_sum2',
        'rdg_sum3',
        'rdg_etc1',
        'rdg_etc2',
        'rdg_etc3',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];

}

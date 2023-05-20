<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\Warehousing;
use App\Models\ReceivingGoodsDelivery;
class RateDataGeneral extends Model
{
    use HasFactory;

    protected $table = "rate_data_general";


    protected $primaryKey = 'rdg_no';

        public $timestamps = true;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->timezone('Asia/seoul')->format('Y-m-d H:i:s');
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'rdg_no ',
        'w_no',
        'rgd_no',
        'rmd_no',
        'rgd_no_expectation',
        'rgd_no_final',
        'rgd_no_additional',
        'mb_no',
        'ag_no',
        'rdg_set_type',
        'rdg_bill_type',
        'rdg_supply_price1',
        'rdg_supply_price2',
        'rdg_supply_price3',
        'rdg_supply_price4',
        'rdg_supply_price5',
        'rdg_supply_price6',
        'rdg_supply_price7',
        'rdg_supply_price8',
        'rdg_supply_price9',
        'rdg_supply_price10',
        'rdg_supply_price11',
        'rdg_supply_price12',
        'rdg_supply_price13',
        'rdg_supply_price14',
        'rdg_vat1',
        'rdg_vat2',
        'rdg_vat3',
        'rdg_vat4',
        'rdg_vat5',
        'rdg_vat6',
        'rdg_vat7',
        'rdg_vat8',
        'rdg_vat9',
        'rdg_vat10',
        'rdg_vat11',
        'rdg_vat12',
        'rdg_vat13',
        'rdg_vat14',
        'rdg_sum1',
        'rdg_sum2',
        'rdg_sum3',
        'rdg_sum4',
        'rdg_sum5',
        'rdg_sum6',
        'rdg_sum7',
        'rdg_sum8',
        'rdg_sum9',
        'rdg_sum10',
        'rdg_sum11',
        'rdg_sum12',
        'rdg_sum13',
        'rdg_sum14',
        'rdg_etc1',
        'rdg_etc2',
        'rdg_etc3',
        'rdg_etc4',
        'rdg_etc5',
        'rdg_etc6',
        'rdg_etc7',
        'rdg_count_work',
        'rdg_precalculate_total'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d",
    ];

    public function warehousing()
    {
        return $this->hasOne(Warehousing::class, 'w_no', 'w_no')->with(['co_no', 'warehousing_item','w_import_parent', 'warehousing_request']);
    }
    public function rgd_no_final()
    {

        return $this->belongsTo(RateDataGeneral::class, 'rgd_no_final', 'rgd_no');
    }

    public function adjustment_group()
    {
        return $this->hasOne(AdjustmentGroup::class, 'ag_no', 'ag_no')->select(['ag_no', 'ag_auto_issue']);
    }

}

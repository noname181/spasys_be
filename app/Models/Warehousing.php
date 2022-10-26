<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;
use App\Models\Company;
use App\Models\AdjustmentGroup;

class Warehousing extends Model
{
    use HasFactory;

    protected $table = "warehousing";

    protected $primaryKey = 'w_no';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'w_no',
        'w_schedule_number',
        'w_type',
        'w_category_name',
        'mb_no',
        'co_no',
        'w_schedule_day',
        'w_schedule_amount',
        'w_completed_day',
        'w_amount',
        'm_bl',
        'h_bl',
        'logistic_manage_number',
        'connection_number',
        'tie_no',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
        'w_completed_day'=> "date:Y.m.d"
    ];

    public function mb_no()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');
    }

    public function member()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no')->select(['mb_no', 'mb_name']);
    }

    public function co_no()
    {
        return $this->belongsTo(Company::class, 'co_no', 'co_no')->with(['contract', 'co_parent', 'company_settlement','adjustment_group', 'company_distribution_cycle']);

    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'co_no', 'co_no')->with(['contract', 'co_parent', 'company_settlement','adjustment_group']);

    }

    public function warehousing_item()
    {
        return $this->hasMany(WarehousingItem::class, 'w_no', 'w_no')->with('item_no');
    }

    public function warehousing_item_IW_spasys_confirm()
    {
        return $this->hasMany(WarehousingItem::class, 'w_no', 'w_no')->with('item_no')->where('wi_type', '입고_spasys');
    }
    public function warehousing_item_EW_spasys_confirm()
    {
        return $this->hasMany(WarehousingItem::class, 'w_no', 'w_no')->with('item_no')->where('wi_type', '출고_spasys');
    }

    public function receving_goods_delivery()
    {
        return $this->hasMany(ReceivingGoodsDelivery::class, 'w_no', 'w_no');
    }

    public function import_schedule()
    {
        return $this->belongsTo(ImportSchedule::class, 'connection_number', 'logistic_manage_number');
    }
    public function w_import_parent()
    {
        return $this->belongsTo(Warehousing::class, 'w_import_no', 'w_no');
    }

    public function w_ew()
    {
        return $this->hasOne(Warehousing::class, 'w_import_no', 'w_no')->with(['warehousing_item'])->orderBy('w_no','desc');
    }
    public function w_ew_many()
    {
        return $this->hasMany(Warehousing::class, 'w_import_no', 'w_no')->with(['warehousing_item']);
    }

    public function w_ew2()
    {
        return $this->hasOne(Warehousing::class, 'w_import_no', 'w_no')->with(['warehousing_item'])->orderBy('w_no','desc');
    }

    public function warehousing_child()
    {
        return $this->hasMany(Warehousing::class, 'w_import_no')->withSum('warehousing_item_EW_spasys_confirm', 'wi_number');
    }

    public function warehousing_request()
    {
        return $this->hasMany(WarehousingRequest::class, 'w_no', 'w_no')->orderBy('wr_no', 'desc');
    }

    public function rate_data_general()
    {
        return $this->hasOne(RateDataGeneral::class, 'w_no', 'w_no');
    }

    public function import_expect()
    {
        return $this->belongsTo(ImportExpected::class, 'tie_no', 'tie_no');
    }
}

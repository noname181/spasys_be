<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;
use App\Models\Company;

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
        'w_amount',
        'm_bl',
        'h_bl',
        'logistic_manage_number',
        'connection_number',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function mb_no()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');
    }

    public function co_no()
    {
        return $this->belongsTo(Company::class, 'co_no', 'co_no')->with(['contract', 'co_parent', 'company_settlement']);

    }

    public function warehousing_item()
    {
        return $this->hasMany(WarehousingItem::class, 'w_no', 'w_no')->with('item_no');
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

    public function warehousing_child()
    {
        return $this->hasMany(Warehousing::class, 'w_import_no');
    }

    public function warehousing_request()
    {
        return $this->hasOne(WarehousingRequest::class, 'w_no', 'w_no');
    }

    public function rate_data_general()
    {
        return $this->hasOne(RateDataGeneral::class, 'w_no', 'w_no');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;
use App\Models\Warehousing;

class ReceivingGoodsDelivery extends Model
{
    use HasFactory;

    protected $table = "receiving_goods_delivery";

    protected $primaryKey = 'rgd_no';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'rgd_no',
        'mb_no',
        'w_no',
        'rgd_contents',
        'rgd_address',
        'rgd_address_detail',
        'rgd_receiver',
        'rgd_hp',
        'rgd_memo',
        'rgd_status1',
        'rgd_status2',
        'rgd_status3',
        'rgd_delivery_company',
        'rgd_tracking_code',
        'rgd_delivery_man',
        'rgd_delivery_man_hp',
        'rgd_delivery_schedule_day',
        'rgd_arrive_day',
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

    public function w_no()
    {
        return $this->hasOne(Warehousing::class, 'w_no', 'w_no')->with(['co_no', 'warehousing_item']);
    }
}
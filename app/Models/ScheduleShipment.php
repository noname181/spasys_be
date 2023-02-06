<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ItemChannel;
use App\Models\WarehousingItem;
use App\Models\ItemInfo;
use App\Models\Item;
use App\Models\ScheduleShipmentInfo;
use App\Models\ContractWms;
use App\Models\ReceivingGoodsDelivery;

class ScheduleShipment extends Model
{
    use HasFactory;
    use \Awobaz\Compoships\Compoships;
    protected $table = "schedule_shipment";

    protected $primaryKey = 'ss_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'co_no',
        'seq',
        'pack',
        'shop_code',
        'shop_name',
        'order_id',
        'order_id_seq',
        'order_id_seq2',
        'shop_product_id',
        'shop_option_id',
        'product_name',
        'options',
        'qty',
        'order_name',
        'order_mobile',
        'order_tel',
        'recv_name',
        'recv_mobile',
        'recv_tel',
        'recv_address',
        'recv_zip',
        'memo',
        'status',
        'delivery_status',
        'order_cs',
        'collect_date',
        'order_date',
        'trans_date',
        'trans_date_pos',
        'shopstat_date',
        'supply_price',
        'amount',
        'extra_money',
        'trans_corp',
        'trans_no',
        'trans_who',
        'prepay_price',
        'gift',
        'hold',
        'org_seq',
        'deal_no',
        'sub_domain',
        'sub_domain_seq',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
        'trans_date' => "date:Y.m.d",
        'order_date' => "date:Y.m.d",
    ];
   
    public function item_channels()
    {
        return $this->hasMany(ItemChannel::class, 'item_no', 'item_no');
    }
    public function item()
    {
        return $this->hasOne(Item::class, 'item_no', 'item_no');
    }
    public function schedule_shipment_info()
    {
        return $this->hasMany(ScheduleShipmentInfo::class, 'ss_no', 'ss_no')->with('item');
    }

    public function ContractWms()
    {
        return $this->hasOne(ContractWms::class, 'cw_code', 'shop_code')->where('cw_tab','=','판매처')->with('company');

    }

    public function receving_goods_delivery()
    {
        return $this->hasMany(ReceivingGoodsDelivery::class, 'ss_no', 'ss_no');
    }
    
}

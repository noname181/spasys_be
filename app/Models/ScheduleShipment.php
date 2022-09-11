<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ItemChannel;
use App\Models\WarehousingItem;
use App\Models\ItemInfo;


class ScheduleShipment extends Model
{
    use HasFactory;

    protected $table = "schedule_shipment";

    protected $primaryKey = 'ss_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'ss_no',
        'item_no',
        'co_no',
        'shop_no',
        'seq',
        'pack',
        'shop_name',
        'order_id',
        'order_id_seq',
        'order_id_seq2',
        'shop_product_id',
        'product_name',
        'options',
        'qty',
        'order_name',
        'order_mobile',
        'recv_zip',
        'memo',
        'status',
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
        'order_products'
    ];

    protected $casts = [

    ];


    
}

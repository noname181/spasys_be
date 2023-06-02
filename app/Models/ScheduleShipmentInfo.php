<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\Item;

class ScheduleShipmentInfo extends Model
{
    use HasFactory;
    use \Awobaz\Compoships\Compoships;
    protected $table = "schedule_shipment_info";

    protected $primaryKey = 'ssi_no';

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
        'ss_no',
        'co_no',
        'barcode',
        'brand',
        'cancel_date',
        'change_date',
        'enable_sale',
        'extra_money',
        'is_gift',
        'link_id',
        'name',
        'new_link_id',
        'options',
        'order_cs',
        'prd_amount',
        'prd_seq',
        'prd_supply_price',
        'product_id',
        'qty',
        'shop_price',
        'supply_code',
        'supply_name',
        'supply_options'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];


    public function item()
    {
        return $this->hasMany(Item::class, 'option_id', 'product_id')->with('item_channels');
    }
    
}

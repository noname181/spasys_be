<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Item;

class ScheduleShipmentInfo extends Model
{
    use HasFactory;

    protected $table = "schedule_shipment_info";

    protected $primaryKey = 'ssi_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
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
        'supply_options',
        'co_no',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",

    ];


    public function item()
    {
        return $this->hasOne(Item::class, 'product_id', 'product_id');
    }
    
}

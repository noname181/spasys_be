<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ItemChannel;
use App\Models\WarehousingItem;

class ItemInfo extends Model
{
    use HasFactory;

    protected $table = "item_info";

    protected $primaryKey = 'item_info_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'supply_code',
        'trans_fee',
        'img_desc1',
        'img_desc2',
        'img_desc3',
        'img_desc4',
        'img_desc5',
        'product_desc',
        'product_desc2',
        'location',
        'memo',
        'category',
        'maker',
        'md',
        'manager1',
        'manager2',
        'supply_options',
        'enable_sale',
        'use_temp_soldout',
        'stock',
        'stock_alarm1',
        'stock_alarm2',
        'extra_price',
        'extra_shop_price',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    
}
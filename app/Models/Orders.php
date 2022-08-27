<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    use HasFactory;

    protected $table = "orders";

    protected $primaryKey = 'id';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'id',
        'unique_id',
        'status',
        'type',
        'delivery_time',
        'delivery_to',
        'request',
        'customer_name',
        'customer_phone',
        'user_address',
        'user_lat',
        'user_long',
        'payment_mode',
        'rating',
        'restaurant_charges',
        'tax',
        'gst_tax',
        'pst_tax',
        'hst_tax',
        'delivery_charge',
        'coupon_discount',
        'tip',
        'rewards',
        'total_charges',
        'created_at',
        'created_at_restaurant',
        'updated_at',
        'updated_at_restaurant',
        'restaurant_id',
        'coupon_id',
        'user_id',
        'trans_id',
        'client_id',
        'order_code2',
        'is_asap',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];
}

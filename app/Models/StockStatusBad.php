<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockStatusBad extends Model
{
    use HasFactory;
    
    protected $table = "stock_status_bad";

    protected $primaryKey = 'id';

    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'id',
        'product_id',
        'option_id',
        'stock',
        'status',
        'item_no'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];
    
    public function item_status_bad()
    {
        return $this->belongsTo(Item::class, 'product_id', 'product_id')->with(['file', 'company', 'item_channels', 'item_info', 'ContractWms']);
    }
}

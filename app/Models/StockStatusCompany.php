<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class StockStatusCompany extends Model
{
    use HasFactory;
    
    protected $table = "stock_status_company";

    protected $primaryKey = 'id';

    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'ssc_id',    
        'stock',
        'co_no',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];
    
    public function item_status_bad()
    {
        return $this->belongsTo(Item::class, 'product_id', 'product_id')->with(['file', 'company', 'item_channels', 'item_info', 'ContractWms']);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'co_no', 'co_no')->with(['co_parent']);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ItemChannel;

class Item extends Model
{
    use HasFactory;

    protected $table = "item";

    protected $primaryKey = 'item_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'item_no',
        'item_brand',
        'item_name',
        'item_option1',
        'item_option2',
        'item_channel',
        'item_cargo_bar_code',
        'item_upc_code',
        'item_bar_code',
        'item_weight',
        'item_url',
        'item_price1',
        'item_price2',
        'item_price3',
        'item_price4',
        'item_cate1',
        'item_cate2',
        'item_cate3',
        'mb_no',
        'item_regtime',
        'item_table',
        'item_key'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function file()
    {
        return $this->hasOne(File::class, 'file_table_key', 'item_no')->where('file_table', 'item');
    }

    public function item_channels()
    {
        return $this->hasMany(ItemChannel::class, 'item_no', 'item_no');
    }

    public function company()
    {
        return $this->HasOne(Company::class, 'co_no', 'co_no');
    }

}

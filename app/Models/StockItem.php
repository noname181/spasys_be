<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class StockItem extends Model
{
    use HasFactory;

    protected $table = "stock_item";

    protected $primaryKey = 's_no';

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
        's_no',
        'item_no',
        'co_no_shop',
        'co_no_cargo',
        's_schedule_number',
        's_schedule_day',
        'ss_number',
        's_number',
        'm_bl',
        'h_bl',
        'logistic_manage_number',
        's_connection_number',
        'mb_no',
    ];

    protected $casts = [
        's_regtime' => "date:Y.m.d H:i",
    ];
}

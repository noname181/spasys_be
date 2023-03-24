<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Item;
use App\Models\Warehousing;
use App\Models\WarehousingItem;
use App\Models\Member;
use App\Models\Export;
use App\Models\ImportExpected;
use App\Models\Import;

class Alarm extends Model
{
    use HasFactory;

    protected $table = "alarm";

    protected $primaryKey = 'alarm_no';

    public $timestamps = true;

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    // public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'alarm_no',
        'mb_no',
        'w_no',
        'alarm_content',
        'alarm_h_bl',
    ];
    // public function item()
    // {
    //     return $this->hasOne(Item::class, 'item_no', 'item_no')->with(['company']);
    // }
    public function warehousing()
    {
        return $this->belongsTo(Warehousing::class, 'w_no', 'w_no')->with(['warehousing_item','company']);;
    }
    public function member()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');;
    }
    // public function export()
    // {
    //     return $this->belongsTo(Export::class, 'w_no', 'te_carry_out_number')->with(['import_expected']);
    // }
    public function export()
    {
        //$this->belongsTo(Export::class, 'rp_h_bl', 'te_h_bl')->with(['import_expected']);
        return $this->belongsTo(Export::class, 'alarm_h_bl', 'te_h_bl')->with(['import_expected']);
    }
    public function import()
    {
        return $this->belongsTo(Import::class, 'alarm_h_bl', 'ti_h_bl')->with(['import_expected']);
    }
    public function import_expect()
    {
        return $this->belongsTo(ImportExpected::class, 'alarm_h_bl', 'tie_h_bl')->with(['company','company_spasys']);
    }
}

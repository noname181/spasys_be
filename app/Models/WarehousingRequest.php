<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\Member;
use App\Models\Warehousing;
use App\Models\ScheduleShipment;
class WarehousingRequest extends Model
{
    use HasFactory;

    protected $table = "warehousing_request";

    protected $primaryKey = 'wr_no';

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
        'wr_no',
        'w_no',
        'mb_no',
        'wr_type',
        'wr_contents',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function mb_no()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');
    }

    public function warehousing()
    {
        return $this->belongsTo(Warehousing::class, 'w_no', 'w_no')->with('w_import_parent');
    }

    public function shedule_shipment()
    {
        return $this->belongsTo(ScheduleShipment::class, 'w_no', 'ss_no');
    }
}
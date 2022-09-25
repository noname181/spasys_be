<?php

namespace App\Models;

use App\Http\Requests\ImportSchedule\ImportScheduleRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Contract;
use App\Models\Warehousing;
use App\Models\ScheduleShipmentInfo;

class Company extends Model
{
    use HasFactory;

    protected $table = "company";

    protected $primaryKey = 'co_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'co_no',
        'co_parent_no',
        'mb_no',
        'co_name',
        'co_address',
        'co_zipcode',
        'co_address_detail',
        'co_post_number',
        'co_country',
        'co_service',
        'co_license',
        'co_owner',
        'co_close_yn',
        'co_homepage',
        'co_major',
        'co_email',
        'co_etc',
        'co_operating_time',
        'co_lunch_break',
        'co_about_us',
        'co_policy',
        'co_help_center',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];

    public function contract()
    {
        return $this->hasOne(Contract::class, 'co_no', 'co_no');
    }

    public function co_parent()
    {
        return $this->belongsTo(Company::class, 'co_parent_no', 'co_no')->with('co_parent');
    }
    public function import_schedule()
    {
        return $this->hasMany(ImportSchedule::class, 'co_license', 'co_license');
    }

    public function company_settlement()
    {
        return $this->hasMany(CompanySettlement::class, 'co_no', 'co_no');
    }
    public function schedule_shipment_info()
    {
        return $this->hasMany(ScheduleShipmentInfo::class, 'co_no', 'co_no');
    }

    public function warehousing()
    {
        return $this->hasOne(Warehousing::class, 'co_no', 'co_no');

    }
}

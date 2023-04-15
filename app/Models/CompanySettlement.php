<?php

namespace App\Models;

use App\Http\Requests\ImportSchedule\ImportScheduleRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\Contract;

class CompanySettlement extends Model
{
    use HasFactory;

    protected $table = "company_settlement";

    protected $primaryKey = 'cs_no';

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
        'cs_no',
        'co_no',
        'service_no',
        'cs_payment_cycle',
        'cs_money_type',
        'cs_payment_group',
        'cs_system',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'co_no', 'co_no');
    }


}

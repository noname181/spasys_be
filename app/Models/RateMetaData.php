<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\RateData;
use App\Models\RateDataGeneral;
class RateMetaData extends Model
{
    use HasFactory;

    protected $table = "rate_meta_data";


    protected $primaryKey = 'rmd_no';

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
        'rmd_no',
        'mb_no',
        'rm_no',
        'set_type',
        'rmd_number',
        'w_no',
        'rgd_no',
        'co_no',
        'rmd_mail_detail1a',
        'rmd_mail_detail1b',
        'rmd_mail_detail1c',
        'rmd_mail_detail2',
        'rmd_mail_detail3',
        'rmd_service',
        'rmd_tab_child',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i:s",
        'updated_at' => "date:Y.m.d",
    ];

    public function rate_meta() {
        return $this->hasOne(RateMeta::class, 'rm_no', 'rm_no')->with('send_email');
    }
    public function rate_data(){
        return $this->hasMany(RateData::class, 'rmd_no', 'rmd_no');
    }
    public function rate_data_general(){
        return $this->hasOne(RateDataGeneral::class, 'rmd_no', 'rmd_no');
    }
    // public function total1(){
    //     return $this->hasOne(RateData::class, 'rmd_no', 'rmd_no')->where('set_type','=','work')->sum('rd_data7');
    // }
    public function company() {
        return $this->hasOne(Company::class, 'co_no', 'co_no')->with(['contract', 'co_parent','adjustment_group','manager']);
    }

    public function member() {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no')->with('company:co_no,co_name,co_service');
    }

    public function rate_data_one(){
        return $this->hasOne(RateData::class, 'rmd_no', 'rmd_no');
    }
    public function send_email_rmd(){
        return $this->hasOne(SendEmail::class, 'rmd_no', 'rmd_no')->orderBy('se_no','desc')->with('member');
    }

}

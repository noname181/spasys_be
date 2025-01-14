<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\SendEmailHistory;
class RateMeta extends Model
{
    use HasFactory;

    protected $table = "rate_meta";


    protected $primaryKey = 'rm_no';

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
        'rm_no',
        'mb_no',
        'rm_biz_name',
        'rm_owner_name',
        'rm_biz_number',
        'rm_biz_email',
        'rm_biz_address',
        'rm_biz_address_detail',
        'rm_name',
        'rm_hp',
        'rm_mail_detail',
        'rm_mail_detail2',
        'rm_mail_detail3'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];

    public function send_email() {
        return $this->hasOne(SendEmailHistory::class, 'rm_no', 'rm_no')->orderBy('se_no','desc')->with('member');
    }

}

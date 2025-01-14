<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\Member;
class SendEmailHistory extends Model
{
    use HasFactory;

    protected $table = "send_email";


    protected $primaryKey = 'se_no';

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
        'se_no',
        'mb_no',
        'rm_no',
        'rmd_no',
        'se_name_receiver',
        'se_email_receiver', 
        'se_email_cc',
        'se_title',
        'se_content',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function member() {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');
    }
}

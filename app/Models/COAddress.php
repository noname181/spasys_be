<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\Company;

class COAddress extends Model
{
    use HasFactory;

    protected $table = "co_address";

    protected $primaryKey = 'ca_no';

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
        'ca_no',
        'co_no',
        'mb_no',
        'ca_address',
        'ca_address_detail',
        'ca_post_number',
        'ca_name',
        'ca_tel'
    ];
    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];
    public function company()
    {
        return $this->belongsTo(Company::class, 'co_no', 'co_no')->with('co_parent','contract');
    }
}

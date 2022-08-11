<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Contract;

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
        'mb_no',
        'co_name',
        'co_address',
        'co_address_detail',
        'co_post_number',
        'co_country',
        'co_service',
        'co_license',
        'co_owner',
        'co_close_yn',
        'co_homepage',
        'co_email',
        'co_etc'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];

    public function contract()
    {
        return $this->hasOne(Contract::class, 'co_no', 'co_no');
    }

}

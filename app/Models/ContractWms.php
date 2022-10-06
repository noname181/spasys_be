<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;


class ContractWms extends Model
{
    use HasFactory;

    protected $table = "contract_wms";

    protected $primaryKey = 'cw_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'co_no',
        'mb_no',
        'cw_name',
        'cw_code',
        'cw_tab',

    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",

    ];
    public function mb_no()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'co_no', 'co_no')->with('co_parent');
    }
}

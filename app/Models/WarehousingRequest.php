<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;

class WarehousingRequest extends Model
{
    use HasFactory;

    protected $table = "warehousing_request";

    protected $primaryKey = 'wr_no';

    public $timestamps = true;

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
}
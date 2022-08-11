<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;

class Role extends Model
{
    use HasFactory;

    protected $table = "role";

    protected $primaryKey = 'role_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'role_no',
        'role_id',
        'role_name',
        'role_eng',
        'role_use_yn',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];

    public function member()
    {
        return $this->hasMany(Member::class, 'role_no', 'role_no');
    }

}

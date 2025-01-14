<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\Member;
use App\Models\MenuToken;

class Role extends Model
{
    use HasFactory;

    protected $table = "role";

    protected $primaryKey = 'role_no';

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
        'role_no',
        'role_id',
        'role_name',
        'role_eng',
        'role_type',
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

    public function permission()
    {
        return $this->hasMany(Permission::class, 'role_no', 'role_no');
    }
    public function menu_token(){
        return $this->hasOne(MenuToken::class, 'role_no','role_no');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Helper\Tokenable;
use App\Models\Company;
use App\Models\Role;

class Member extends Authenticatable
{
    use HasFactory, Notifiable, Tokenable;

    const ROLE_ADMIN = 1;
    const ROLE_SPASYS_ADMIN = 2;
    const ROLE_SPASYS_MANAGER = 3;
    const ROLE_SPASYS_OPERATOR = 4;
    const ROLE_SPASYS_WORKER = 5;
    const ROLE_AGENCY_MANAGER = 6;
    const ROLE_AGENCY_OPERATOR = 7;
    const ROLE_SHOP_MANAGER = 8;
    const ROLE_SHOP_OPERATOR = 9;

    const ADMIN = 'admin';
    const SPASYS = 'spasys';
    const AGENCY = 'agency';
    const SHOP = 'shop';

    protected $table = "member";


    protected $primaryKey = 'mb_no';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'mb_pw',
        'mb_token',
    ];


    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'mb_id',
        'role_no',
        'mb_name',
        'mb_email',
        'mb_pw',
        'mb_language',
        'mb_hp'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'co_no', 'co_no');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_no', 'role_no');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Helper\Tokenable;

class Member extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, Tokenable;

    const ROLE_ADMIN = 1;
    const ROLE_MANAGER = 2;
    const ROLE_OPERATOR = 3;
    const ROLE_WORKER = 4;
    const ROLE_SHOP_MANAGER = 5;
    const ROLE_COMPANY_MANAGER = 6;
    const ROLE_COMPANY_OPERATOR = 7;

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
}

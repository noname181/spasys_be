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

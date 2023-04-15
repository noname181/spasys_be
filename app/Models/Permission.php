<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Permission extends Model
{
    use HasFactory;

    protected $table = "permission";


    protected $primaryKey = 'permission_no';

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
        'permission_no',
        'permission_name',
        'menu_no',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_no', 'menu_no');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_no', 'role_no');
    }
}
